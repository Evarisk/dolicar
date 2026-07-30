<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dolicar/lib/dolicar_functions.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for DoliCar
 */

/**
 * Create default product lot
 *
 * @param  int $productID    Product ID
 * @return int $productLotID 0 < if KO, Product lot ID created
 */
function create_default_product_lot(int $productID): int
{
    // Global variables definitions
    global $db, $langs, $user;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

    // Initialize technical objects
    $productLot = new Productlot($db);

    $productLot->fk_product = $productID;
    $productLot->batch      = generate_random_id();

    $productLotID = $productLot->create($user);
    if ($productLotID > 0) {
        $product = new Product($db);
        $product->fetch($productID);
        $product->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'),0,'','', $productLot->batch,'','dolicar_registrationcertificate',0);
        return $productLotID;
    } else {
        return -1;
    }
}

/**
 * Get or create the vehicle product and its lot from registration certificate data.
 *
 * Reproduces the product/lot business logic of the quick creation wizard so it can be reused
 * (e.g. by the CSV import): the product reference is built from brand + model + version, the
 * brand category is created if missing, and the lot is created from the VIN (or a default lot).
 * Falls back to the default vehicle product when no brand/model is available.
 *
 * @param  string $brand   Vehicle brand (D.1)
 * @param  string $model   Vehicle model (D.3)
 * @param  string $version Vehicle version/type (D.2), may be empty
 * @param  string $vin     Vehicle serial number / VIN (E), may be empty
 * @return array{fk_product: int, fk_lot: int, is_new_lot: bool}
 */
function dolicar_get_or_create_vehicle_product_lot(string $brand, string $model, string $version, string $vin): array
{
    global $db, $conf, $langs, $user;

    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    // Avoid product creation error when an automatic barcode numbering is enabled
    $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

    $product  = new Product($db);
    $category = new Categorie($db);

    $brand   = trim($brand);
    $model   = trim($model);
    $version = trim($version);
    $vin     = trim($vin);

    $fkProduct = 0;
    $fkLot     = 0;
    $isNewLot  = false;

    // Fetch or create the product from "brand model version"
    $productRef = trim($brand . ' ' . $model . ' ' . $version);
    if ($productRef !== '') {
        $product->ref          = $productRef;
        $product->label        = $productRef;
        $product->status_batch = 1;

        // Try sanitized ref first (matches how it was originally created), then exact ref
        $product->fetch(0, dol_sanitizeFileName(dol_string_nospecial($productRef)));
        $fkProduct = $product->id;

        if ($fkProduct <= 0) {
            $product->fetch(0, $productRef);
            $fkProduct = $product->id;
        }

        if ($fkProduct <= 0) {
            $fkProduct = $product->create($user);
            if ($fkProduct <= 0) {
                // Creation failed — product may already exist with a variant of the ref
                $product->fetch(0, $productRef);
                $fkProduct = $product->id > 0 ? $product->id : 0;
            }
            if ($fkProduct > 0 && $brand !== '') {
                $category->fetch(0, $brand);
                if ($category->id <= 0) {
                    $category->label       = $brand;
                    $category->description = $brand;
                    $category->visible     = 1;
                    $category->type        = 'product';
                    $category->fk_parent   = getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG');
                    $categoryID            = $category->create($user);
                } else {
                    $categoryID = $category->id;
                }
                if ($categoryID > 0) {
                    $product->setCategories([$categoryID, getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')]);
                }
            }
        }
    }

    // Fallback to the shared default vehicle when no usable brand/model was provided
    if ($fkProduct <= 0) {
        $fkProduct = getDolGlobalInt('DOLICAR_DEFAULT_VEHICLE');
    }

    // Fetch or create the lot
    if ($fkProduct > 0) {
        if ($vin !== '') {
            $productLot             = new Productlot($db);
            $productLot->batch      = $vin;
            $productLot->fk_product = $fkProduct;
            $fkLot                  = $productLot->create($user);

            if ($fkLot > 0) {
                $isNewLot = true;
                // Put 1 unit in stock for the newly created lot (default warehouse)
                $stockProduct = new Product($db);
                $stockProduct->fetch($fkProduct);
                $stockProduct->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'), 0, '', '', $vin, '', 'dolicar_registrationcertificate', 0);
            } else {
                // Lot already exists for this product + VIN: fetch its id
                $existingLot = new Productlot($db);
                if ($existingLot->fetch(0, $fkProduct, $vin) > 0) {
                    $fkLot = $existingLot->id;
                } else {
                    $fkLot = 0;
                }
            }
        } else {
            $fkLot    = create_default_product_lot($fkProduct);
            $isNewLot = $fkLot > 0;
        }
    }

    return ['fk_product' => (int) $fkProduct, 'fk_lot' => (int) $fkLot, 'is_new_lot' => $isNewLot];
}

/**
 * Replace the tokens of a vehicle repair service mask.
 *
 * Supported tokens: {PLAQUE} (registration number) and {VIN} (vehicle serial number).
 * A token resolving to an empty string leaves its separator behind, so the separators
 * are collapsed afterwards: a vehicle without VIN gives "DIVREP-AB-123-CD", not
 * "DIVREP-AB-123-CD-".
 *
 * @param  string $mask               Mask holding the {PLAQUE} / {VIN} tokens
 * @param  string $registrationNumber Vehicle registration number (A)
 * @param  string $vin                Vehicle serial number (E)
 * @return string                     Mask with its tokens replaced
 */
function dolicar_apply_vehicle_repair_service_mask(string $mask, string $registrationNumber, string $vin): string
{
    $value = str_replace(['{PLAQUE}', '{VIN}'], [$registrationNumber, $vin], $mask);

    $value = preg_replace('/-{2,}/', '-', $value);
    $value = preg_replace('/[ \t]{2,}/', ' ', $value);

    return trim($value, " -\t\n\r");
}

/**
 * Create, once, the repair service dedicated to a vehicle.
 *
 * A garage buys its repairs through supplier orders: giving each vehicle its own service keeps the
 * cost of a repair attached to the vehicle it was made for. The service is remembered on the
 * certificate, so completing the VIN of a vehicle registered on its plate alone renames the
 * existing service instead of creating a second one next to the orders already placed on the first.
 *
 * @param  RegistrationCertificateFr $registrationCertificate Registration certificate of the vehicle
 * @return int                                                0 if nothing to do, > 0 service product ID, < 0 if KO
 */
function dolicar_create_vehicle_repair_service(RegistrationCertificateFr $registrationCertificate): int
{
    // Global variables definitions
    global $conf, $db, $mysoc, $user;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

    if (!isModEnabled('service') || getDolGlobalInt('DOLICAR_VEHICLE_REPAIR_SERVICE_ENABLED') <= 0) {
        return 0;
    }

    // A draft only caches an API answer waiting to be promoted, it describes no vehicle yet
    if (empty($registrationCertificate->id) || $registrationCertificate->status == RegistrationCertificateFr::STATUS_DRAFT) {
        return 0;
    }

    $registrationNumber = trim((string) $registrationCertificate->a_registration_number);

    // normalize_registration_number() answers -1 for a vehicle saved without a plate: that sentinel
    // must not end up in the reference, where it would read as a plate of its own
    if ($registrationNumber === '-1') {
        $registrationNumber = '';
    }

    // Only the certificate carries the VIN: the lot batch falls back to a random identifier when
    // the vehicle was created without one, which would end up in the reference of the service
    $vin = trim((string) $registrationCertificate->e_vehicle_serial_number);

    if ($registrationNumber === '' && $vin === '') {
        return 0;
    }

    $ref = dolicar_apply_vehicle_repair_service_mask(getDolGlobalString('DOLICAR_VEHICLE_REPAIR_SERVICE_REF_MASK', 'DIVREP-{PLAQUE}-{VIN}'), $registrationNumber, $vin);
    if ($ref === '') {
        return 0;
    }

    $description  = dolicar_apply_vehicle_repair_service_mask(getDolGlobalString('DOLICAR_VEHICLE_REPAIR_SERVICE_DESCRIPTION_MASK', 'Divers réparation sur le véhicule : {PLAQUE} {VIN}'), $registrationNumber, $vin);
    $sanitizedRef = dol_sanitizeFileName(dol_string_nospecial($ref));

    // The service already known for this vehicle wins over any reference lookup: its reference may
    // be the one built before the VIN was filled in
    $registrationCertificate->fetch_optionals();
    $knownServiceID = (int) ($registrationCertificate->array_options['options_repair_service'] ?? 0);

    $service = new Product($db);
    if ($knownServiceID > 0 && $service->fetch($knownServiceID) > 0) {
        if ($service->ref != $sanitizedRef) {
            $service->ref         = $ref;
            $service->label       = $description !== '' ? $description : $ref;
            $service->description = $description;

            if ($service->update($service->id, $user) <= 0) {
                dol_syslog('dolicar_create_vehicle_repair_service: cannot rename service ' . $service->ref . ' to ' . $ref . ' - ' . $service->error, LOG_ERR);
            }
        }

        return $knownServiceID;
    }

    // Product::create() sanitizes the reference, so look the sanitized form up first: it is the one
    // stored by a previous call, from before the service was remembered on the certificate
    $existingService = new Product($db);
    if ($existingService->fetch(0, $sanitizedRef) <= 0) {
        $existingService = new Product($db);
        $existingService->fetch(0, $ref);
    }

    if ($existingService->id > 0) {
        dolicar_remember_vehicle_repair_service($registrationCertificate, (int) $existingService->id);

        return (int) $existingService->id;
    }

    // In order to avoid product creation error when an automatic barcode numbering is enabled
    $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

    $service              = new Product($db);
    $service->ref         = $ref;
    $service->label       = $description !== '' ? $description : $ref;
    $service->description = $description;
    $service->type        = Product::TYPE_SERVICE;
    $service->status      = 1;
    $service->status_buy  = 1;

    // Same rate as a service created by hand: Product::create() would store 0 % if left empty.
    // The accounting codes are left to the Comptabilité module defaults for services.
    $service->tva_tx = (float) get_default_tva($mysoc, $mysoc);

    $serviceID = $service->create($user);
    if ($serviceID <= 0) {
        dol_syslog('dolicar_create_vehicle_repair_service: cannot create service ' . $ref . ' - ' . $service->error, LOG_ERR);
        return -1;
    }

    dolicar_remember_vehicle_repair_service($registrationCertificate, $serviceID);

    return $serviceID;
}

/**
 * Remember on a vehicle which service carries its repairs.
 *
 * @param  RegistrationCertificateFr $registrationCertificate Registration certificate of the vehicle
 * @param  int                       $serviceID               Repair service product ID
 * @return void
 */
function dolicar_remember_vehicle_repair_service(RegistrationCertificateFr $registrationCertificate, int $serviceID): void
{
    // The other extrafields must be reloaded first, insertExtraFields() writes them all
    $registrationCertificate->fetch_optionals();
    $registrationCertificate->array_options['options_repair_service'] = $serviceID;
    $registrationCertificate->insertExtraFields();
}

/**
 * List every file waiting in an upload temp directory.
 *
 * saturne_get_media_files() drops everything that is neither an image nor an audio record, so a
 * document attached through the media block would never leave the temp dir. This one keeps them all.
 *
 * @param  string $subDir Temp sub directory, relative to the DoliCar output dir
 * @return array          Files, as returned by dol_dir_list()
 */
function dolicar_get_upload_temp_files(string $subDir): array
{
    // Global variables definitions
    global $conf;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    if (empty($subDir)) {
        return [];
    }

    $uploadDir = $conf->dolicar->dir_output . '/' . $subDir;
    if (!dol_is_dir($uploadDir)) {
        return [];
    }

    return dol_dir_list($uploadDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC);
}

/**
 * Read the warranties recorded on an invoice.
 *
 * They live as a JSON array inside a single extrafield: a repair invoice often carries several
 * warranties (engine, bodywork, part...), each with its own end date and certificate.
 *
 * @param  CommonObject $invoice Invoice carrying the warranties
 * @return array                 Warranties, as [['id' => int, 'label' => string, 'date_end' => 'YYYY-MM-DD', 'file' => string]]
 */
function dolicar_get_invoice_warranties(CommonObject $invoice): array
{
    $rawWarranties = $invoice->array_options['options_warranty_end'] ?? '';
    if (empty($rawWarranties)) {
        return [];
    }

    $warranties = json_decode($rawWarranties, true);
    if (!is_array($warranties)) {
        return [];
    }

    // A warranty may carry several certificates, normalize so callers only ever read 'files'.
    // The JSON is writable through the API, so anything that is not shaped like a warranty is
    // dropped rather than trusted — it would otherwise take the invoice card down.
    $normalizedWarranties = [];
    foreach ($warranties as $warranty) {
        if (!is_array($warranty)) {
            continue;
        }

        if (!isset($warranty['files']) || !is_array($warranty['files'])) {
            $warranty['files'] = !empty($warranty['file']) && is_string($warranty['file']) ? [$warranty['file']] : [];
        }
        $warranty['files'] = array_values(array_filter($warranty['files'], 'is_string'));

        unset($warranty['file']);

        $normalizedWarranties[] = $warranty;
    }

    return $normalizedWarranties;
}

/**
 * Save the warranties of an invoice.
 *
 * @param  CommonObject $invoice    Invoice carrying the warranties
 * @param  array        $warranties Warranties to store
 * @return int                      0 < if KO, > 0 if OK
 */
function dolicar_set_invoice_warranties(CommonObject $invoice, array $warranties): int
{
    $invoice->array_options['options_warranty_end'] = !empty($warranties) ? json_encode(array_values($warranties), JSON_UNESCAPED_UNICODE) : '';

    return $invoice->insertExtraFields();
}

/**
 * Tell whether an object is an invoice able to carry warranties.
 *
 * Both the customer invoice and the supplier one do: a garage grants warranties to its client and
 * receives some from its parts supplier.
 *
 * @param  mixed $invoice Object to test
 * @return bool           True for a customer or supplier invoice
 */
function dolicar_is_warranty_invoice($invoice): bool
{
    return is_object($invoice) && in_array($invoice->element, ['facture', 'invoice_supplier'], true);
}

/**
 * Get the document.php modulepart of an invoice.
 *
 * @param  CommonObject $invoice Invoice carrying the warranties
 * @return string                Modulepart to hand to document.php / getAdvancedPreviewUrl()
 */
function dolicar_get_invoice_warranty_modulepart(CommonObject $invoice): string
{
    return $invoice->element == 'invoice_supplier' ? 'facture_fournisseur' : 'facture';
}

/**
 * Get the warranty directory of an invoice, relative to its modulepart output dir.
 *
 * The certificates sit in the invoice document directory itself, not in a sub directory of it:
 * dol_check_secure_access_document() reads the owning reference from the parent directory name of
 * the requested file, so a sub directory would leave external users unfiltered on those files.
 *
 * Supplier invoices spread their documents over a two level hierarchy, customer invoices do not —
 * get_exdir() answers for both.
 *
 * @param  CommonObject $invoice Invoice carrying the warranties
 * @return string                Relative path of the warranty directory
 */
function dolicar_get_invoice_warranty_relative_dir(CommonObject $invoice): string
{
    $subDir = $invoice->element == 'invoice_supplier'
        ? get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier')
        : '';

    return $subDir . dol_sanitizeFileName($invoice->ref);
}

/**
 * Get the directory holding the warranty certificates of an invoice.
 *
 * The files live in the invoice's own document directory, so Dolibarr moves them along when the
 * invoice reference changes on validation.
 *
 * @param  CommonObject $invoice Invoice carrying the warranties
 * @return string                Absolute path of the warranty directory
 */
function dolicar_get_invoice_warranty_dir(CommonObject $invoice): string
{
    // Global variables definitions
    global $conf;

    if ($invoice->element == 'invoice_supplier') {
        $dirOutput = !empty($conf->fournisseur->facture->multidir_output[$invoice->entity]) ? $conf->fournisseur->facture->multidir_output[$invoice->entity] : $conf->fournisseur->facture->dir_output;
    } else {
        $dirOutput = !empty($conf->facture->multidir_output[$invoice->entity]) ? $conf->facture->multidir_output[$invoice->entity] : $conf->facture->dir_output;
    }

    return $dirOutput . '/' . dolicar_get_invoice_warranty_relative_dir($invoice);
}

/**
 * Tell whether the current user may add or remove the warranties of an invoice.
 *
 * @param  CommonObject $invoice Invoice carrying the warranties
 * @return bool                  True when the user can write on that invoice
 */
function dolicar_can_edit_invoice_warranties(CommonObject $invoice): bool
{
    // Global variables definitions
    global $user;

    if ($invoice->element == 'invoice_supplier') {
        return $user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer');
    }

    return (bool) $user->hasRight('facture', 'creer');
}

/**
 * Build the download URL of a warranty certificate.
 *
 * @param  CommonObject $invoice  Invoice carrying the warranty
 * @param  string       $fileName Name of the certificate file
 * @return string                 Download URL, empty when the warranty has no certificate
 */
function dolicar_get_invoice_warranty_file_url(CommonObject $invoice, string $fileName): string
{
    if (empty($fileName)) {
        return '';
    }

    return DOL_URL_ROOT . '/document.php?modulepart=' . dolicar_get_invoice_warranty_modulepart($invoice) . '&entity=' . (int) $invoice->entity . '&file=' . urlencode(dolicar_get_invoice_warranty_relative_dir($invoice) . '/' . $fileName);
}

/**
 * Build a "magnifier" link opening a warranty certificate in the Dolibarr document preview modal.
 *
 * Falls back to a plain download link for the file types the preview cannot handle, and returns
 * nothing when the file is gone, so a stale entry never yields a broken preview.
 *
 * @param  CommonObject $invoice  Invoice carrying the warranty
 * @param  string       $fileName Name of the certificate file
 * @return string                 HTML <a>, empty when the file no longer exists
 */
function dolicar_get_invoice_warranty_preview_link(CommonObject $invoice, string $fileName): string
{
    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    if (empty($fileName) || !dol_is_file(dolicar_get_invoice_warranty_dir($invoice) . '/' . $fileName)) {
        return '';
    }

    $relativePath = dolicar_get_invoice_warranty_relative_dir($invoice) . '/' . $fileName;
    $preview      = getAdvancedPreviewUrl(dolicar_get_invoice_warranty_modulepart($invoice), $relativePath, 1, '&entity=' . (int) $invoice->entity);

    if (empty($preview) || empty($preview['url'])) {
        return '<a href="' . dol_escape_htmltag(dolicar_get_invoice_warranty_file_url($invoice, $fileName)) . '" target="_blank" title="' . dol_escape_htmltag($fileName) . '">' . img_picto($fileName, 'file') . '</a>';
    }

    return '<a href="' . $preview['url'] . '"'
        . (!empty($preview['css']) ? ' class="' . $preview['css'] . '"' : '')
        . (!empty($preview['mime']) ? ' mime="' . $preview['mime'] . '"' : '')
        . (!empty($preview['target']) ? ' target="' . $preview['target'] . '"' : '')
        . ' title="' . dol_escape_htmltag($fileName) . '">'
        . img_picto($fileName, 'search-plus')
        . '</a>';
}

/**
 * Get vehicle brand name with product ID
 *
 * @param  int    $productID Product ID
 * @return string $brandName Brand name
 */
function get_vehicle_brand(int $productID): string
{
    // Global variables definitions
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
    global $db;

    // Initialize technical objects
    $product  = new Product($db);
    $category = new Categorie($db);

    $brandName = '';

    if (!empty($productID) && $productID > 0) {
        $product->fetch($productID);
        $categories = $product->getCategoriesCommon('product');
        if (is_array($categories) && !empty($categories)) {
            foreach($categories as $categoryID) {
                $category->fetch($categoryID);
                if ($category->fk_parent == getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')) {
                    $brandName = $category->label;
                }
            }
        }
    }

    return $brandName;
}
