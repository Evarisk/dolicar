<?php
/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/registrationcertificatefr/quickcreation.php
 * \ingroup dolicar
 * \brief   Quick creation wizard for a registration certificate (carte grise)
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $mysoc, $user;

// Load translation files required by the page
saturne_load_langs(['companies']);

// Get parameters
$action       = GETPOST('action', 'aZ09');
$cancel       = GETPOST('cancel', 'aZ09');
$backtopage   = GETPOST('backtopage', 'alpha');
$step         = GETPOST('step', 'aZ09') ?: 'search';
$confirmRetry = GETPOST('confirm_retry', 'int') == 1;

// API source of the plate search: value picked in the wizard dropdown, defaulting to the API set in the module settings
$apiSourceMap  = [
    'immatriculationapi'       => 'immatriculationapi.com',
    'apiplaqueimmatriculation' => 'apiplaqueimmatriculation.com',
];
$defaultSource = array_search(getDolGlobalString('DOLICAR_REGISTRATION_CERTIFICATE_API', 'immatriculationapi.com'), $apiSourceMap) ?: 'immatriculationapi';
$apiSource     = GETPOST('api_source', 'alphanohtml') ?: $defaultSource;

// Initialize technical objects
$object      = new RegistrationCertificateFr($db);
$form        = new Form($db);
$formproduct = new FormProduct($db);
$formcompany = new FormCompany($db);

// Third party quick-creation of a new linked company is only offered when the config is enabled
$thirdpartyQuickCreation = isModEnabled('societe') && getDolGlobalInt('DOLICAR_THIRDPARTY_QUICK_CREATION') && !empty($user->rights->societe->creer);

$hookmanager->initHooks(['dolicar_quickcreation']);

// Security check
$permissionToRead   = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd    = $user->rights->dolicar->registrationcertificatefr->write;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($cancel) {
        header('Location: ' . dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php', 1));
        exit;
    }

    if ($action === 'search_plate' && $permissiontoadd) {
        $searchPlate = strtoupper(trim(GETPOST('a_registration_number', 'alphanohtml')));

        if (empty($searchPlate)) {
            setEventMessages($langs->trans('PleaseEnterRegistrationNumber'), [], 'warnings');
        } else {
            // Override API for this request only, based on user's dropdown selection
            if (isset($apiSourceMap[$apiSource])) {
                $conf->global->DOLICAR_REGISTRATION_CERTIFICATE_API = $apiSourceMap[$apiSource];
            }

            // getRegistrationCertificateData handles validated redirect internally (exits)
            $apiData    = $object->getRegistrationCertificateData($searchPlate, 'plaque', $confirmRetry);
            $api        = isset($apiData['api'])         ? $apiData['api']              : '';
            $data       = isset($apiData['data'])        ? $apiData['data']             : null;
            $draftId    = isset($apiData['draft_id'])    ? (int) $apiData['draft_id']   : 0;
            $errorDraft = isset($apiData['error_draft']) ? $apiData['error_draft']      : false;
            $apiError   = isset($apiData['error'])       ? $apiData['error']            : '';
            $fromCache  = !empty($apiData['from_cache']);

            if ($errorDraft) {
                // Previous search failed: ask the user to confirm before consuming a new token
                setEventMessages($langs->trans('RegistrationNumberPreviousApiError', $searchPlate, $apiError), [], 'warnings');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?confirm_retry=1&a_registration_number=' . urlencode($searchPlate) . '&api_source=' . urlencode($apiSource));
                exit;
            }

            $plate     = $searchPlate;
            $apiFields = [];

            if ($data !== null && is_object($data)) {
                // Data found — go to step 2 with pre-filled fields
                $step       = 'edit';
                $plateFound = true;

                if ($api === 'apiplaqueimmatriculation.com') {
                    if (isset($data->plaque) && !empty($data->plaque)) {
                        $plate = $data->plaque;
                    }
                    $apiFields = [
                        'b_first_registration_date'        => isset($data->date1erCir_fr) ? str_replace('-', '/', $data->date1erCir_fr) : '',
                        'd1_vehicle_brand'                 => isset($data->marque)        ? (string) $data->marque        : '',
                        'd2_vehicle_type'                  => isset($data->type_moteur)   ? (string) $data->type_moteur   : '',
                        'd21_vehicle_cnit'                 => isset($data->cnit)          ? (string) $data->cnit          : '',
                        'd3_vehicle_model'                 => isset($data->modele)        ? (string) $data->modele        : '',
                        'e_vehicle_serial_number'          => isset($data->vin)           ? (string) $data->vin           : '',
                        'j1_national_type'                 => isset($data->genreVCG)      ? (string) $data->genreVCG      : '',
                        'p1_cylinder_capacity'             => isset($data->ccm)           ? preg_replace('/[^0-9]/', '', $data->ccm) : '',
                        'p3_fuel_type'                     => isset($data->energieNGC)    ? (string) $data->energieNGC    : '',
                        'p6_national_administrative_power' => isset($data->puisFisc)      ? (int)    $data->puisFisc      : '',
                        's1_seating_capacity'              => isset($data->nr_passagers)  ? (int)    $data->nr_passagers  : '',
                        'v7_co2_emission'                  => isset($data->co2)           ? preg_replace('/[^0-9]/', '', $data->co2) : '',
                    ];
                } elseif ($api === 'immatriculationapi.com') {
                    $regDateChunks                         = str_split($data->ExtendedData->datePremiereMiseCirculation, 2);
                    $apiFields = [
                        'b_first_registration_date'        => $regDateChunks[0] . '/' . $regDateChunks[1] . '/' . $regDateChunks[2] . $regDateChunks[3],
                        'd1_vehicle_brand'                 => (string) $data->CarMake->CurrentTextValue,
                        'd2_vehicle_type'                  => (string) $data->ExtendedData->typeVehicule,
                        'd21_vehicle_cnit'                 => (string) $data->ExtendedData->CNIT,
                        'd3_vehicle_model'                 => (string) $data->ExtendedData->libelleModele,
                        'e_vehicle_serial_number'          => (string) $data->ExtendedData->numSerieMoteur,
                        'j1_national_type'                 => (string) $data->ExtendedData->genre,
                        'p1_cylinder_capacity'             => (string) $data->ExtendedData->EngineCC,
                        'p3_fuel_type'                     => (string) $data->FuelType->CurrentTextValue,
                        'p6_national_administrative_power' => (string) $data->ExtendedData->puissance,
                        's1_seating_capacity'              => (string) $data->ExtendedData->nbPlace,
                        'v7_co2_emission'                  => (string) $data->ExtendedData->Co2,
                    ];
                }

                // Create or fetch product and lot from API data
                $fkProduct = 0;
                $fkLot     = 0;
                $isNewLot  = false;

                // If coming from cache, try to reuse already-created references
                if ($fromCache && $draftId > 0) {
                    $cachedDraft = new RegistrationCertificateFr($db);
                    $cachedDraft->fetch($draftId);
                    $fkProduct = (int) $cachedDraft->fk_product;
                    $fkLot     = (int) $cachedDraft->fk_lot;
                }

                // Create product/lot if either reference is missing (fresh call or cache with incomplete draft)
                if ($fkProduct <= 0 || $fkLot <= 0) {
                    $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

                    $product    = new Product($db);
                    $productLot = new Productlot($db);
                    $category   = new Categorie($db);

                    if ($api === 'apiplaqueimmatriculation.com') {
                        $brand   = isset($data->marque)  ? (string) $data->marque  : '';
                        $model   = isset($data->modele)  ? (string) $data->modele  : '';
                        $version = isset($data->version) ? (string) $data->version : '';
                        $vin     = isset($data->vin)     ? trim((string) $data->vin) : '';
                    } else {
                        $brand   = isset($data->CarMake, $data->CarMake->CurrentTextValue)         ? (string) $data->CarMake->CurrentTextValue         : '';
                        $model   = isset($data->CarModel, $data->CarModel->CurrentTextValue)       ? (string) $data->CarModel->CurrentTextValue        : '';
                        $version = isset($data->ExtendedData, $data->ExtendedData->version)        ? (string) $data->ExtendedData->version             : '';
                        $vin     = isset($data->ExtendedData, $data->ExtendedData->numSerieMoteur) ? trim((string) $data->ExtendedData->numSerieMoteur) : '';
                    }


                    // Fetch or create product
                    if ($fkProduct <= 0) {
                        $productRef            = trim($brand . ' ' . $model . ' ' . $version);
                        $product->ref          = $productRef;
                        $product->label        = $productRef;
                        $product->status_batch = 1;

                        // Try sanitized ref first (matches how it was originally created)
                        $product->fetch(0, dol_sanitizeFileName(dol_string_nospecial($productRef)));
                        $fkProduct = $product->id;

                        if ($fkProduct <= 0 && !empty($productRef)) {
                            // Try exact (non-sanitized) ref in case the product exists with special chars
                            $product->fetch(0, $productRef);
                            $fkProduct = $product->id;
                        }

                        if ($fkProduct <= 0 && !empty($productRef)) {
                            $fkProduct = $product->create($user);
                            if ($fkProduct <= 0) {
                                // Creation failed — product may already exist with a variant of the ref
                                $product->fetch(0, $productRef);
                                $fkProduct = $product->id > 0 ? $product->id : 0;
                            }
                            if ($fkProduct > 0 && !empty($brand)) {
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
                                $product->setCategories([$categoryID, getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')]);
                            }
                        } else {
                            $product->fetch($fkProduct);
                        }
                    } else {
                        $product->fetch($fkProduct);
                    }

                    // Fetch or create lot
                    if ($fkLot <= 0) {
                        $isNewLot = false;
                        if (!empty($vin)) {
                            $productLot->batch      = $vin;
                            $productLot->fk_product = $fkProduct;
                            $fkLot                  = $productLot->create($user);
                            $isNewLot               = $fkLot > 0;
                            if ($fkLot == -1) {
                                $fkLot = $productLot->fetch(0, $fkProduct, $vin);
                            }
                        } elseif ($fkProduct > 0) {
                            $fkLot    = create_default_product_lot($fkProduct);
                            $isNewLot = $fkLot > 0;
                        }

                        // correct_stock_batch is deferred to action=add so the user-selected warehouse is used
                    }

                    // Persist missing references on the draft
                    if ($draftId > 0 && ($fkProduct > 0 || $fkLot > 0)) {
                        $draftForUpdate = new RegistrationCertificateFr($db);
                        $draftForUpdate->fetch($draftId);
                        if ($fkProduct > 0) {
                            $draftForUpdate->fk_product = $fkProduct;
                        }
                        if ($fkLot > 0) {
                            $draftForUpdate->fk_lot = $fkLot;
                        }
                        $draftForUpdate->update($user);
                    }
                }
            } else {
                // API returned an error — draft saved with error marker, stay on step 1
                if (!empty($apiError)) {
                    setEventMessages($apiError, [], 'errors');
                }
                $step = 'search';
            }
        }
    }

    if ($action === 'add' && $permissiontoadd) {
        $existingDraftId = GETPOSTINT('draft_id');

        if ($existingDraftId > 0) {
            $object->fetch($existingDraftId);
        }

        // Third party — an existing selection takes priority; otherwise create a new one from the typed name
        $fkSoc = GETPOSTINT('fk_soc');
        if ($fkSoc <= 0 && $thirdpartyQuickCreation) {
            $thirdpartyName = trim(GETPOST('name', 'alphanohtml'));
            if (!empty($thirdpartyName)) {
                $thirdparty              = new Societe($db);
                $thirdparty->name        = $thirdpartyName;
                $thirdparty->code_client = -1;
                $thirdparty->client      = GETPOSTINT('client');
                $thirdparty->phone       = GETPOST('phone', 'alpha');
                $thirdparty->email       = trim(GETPOST('email_thirdparty', 'custom', 0, FILTER_SANITIZE_EMAIL));
                $thirdparty->country_id  = $mysoc->country_id;

                $newSocId = $thirdparty->create($user);
                if ($newSocId > 0) {
                    $fkSoc = $newSocId;
                } else {
                    setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                }
            }
        }

        $object->a_registration_number           = strtoupper(GETPOST('a_registration_number', 'alphanohtml'));
        $object->fk_soc                          = $fkSoc;
        $object->fk_product                      = GETPOSTINT('fk_product');
        $object->fk_lot                          = GETPOSTINT('fk_lot');
        $object->b_first_registration_date       = dol_mktime(0, 0, 0, GETPOST('b_first_registration_datemonth', 'int'), GETPOST('b_first_registration_dateday', 'int'), GETPOST('b_first_registration_dateyear', 'int'));
        $object->d1_vehicle_brand                = GETPOST('d1_vehicle_brand', 'alphanohtml');
        $object->d2_vehicle_type                 = GETPOST('d2_vehicle_type', 'alphanohtml');
        $object->d21_vehicle_cnit                = GETPOST('d21_vehicle_cnit', 'alphanohtml');
        $object->d3_vehicle_model                = GETPOST('d3_vehicle_model', 'alphanohtml');
        $object->e_vehicle_serial_number         = GETPOST('e_vehicle_serial_number', 'alphanohtml');
        $object->j1_national_type                = GETPOST('j1_national_type', 'alphanohtml');
        $object->p1_cylinder_capacity            = GETPOSTINT('p1_cylinder_capacity');
        $object->p3_fuel_type                    = GETPOST('p3_fuel_type', 'alphanohtml');
        $object->p6_national_administrative_power = GETPOSTINT('p6_national_administrative_power');
        $object->s1_seating_capacity             = GETPOSTINT('s1_seating_capacity');
        $object->v7_co2_emission                 = GETPOSTINT('v7_co2_emission');

        if ($existingDraftId > 0) {
            $result = $object->update($user);
            $resultId = $object->id;
        } else {
            $result = $object->create($user);
            $resultId = $result;
        }

        if ($result > 0) {
            // Add stock to the user-selected warehouse if the lot was newly created
            if (GETPOSTINT('is_new_lot') == 1 && $object->fk_product > 0 && $object->fk_lot > 0) {
                $stockProduct = new Product($db);
                $stockProduct->fetch($object->fk_product);
                $stockLot    = new Productlot($db);
                $stockLot->fetch($object->fk_lot);
                $warehouseId = GETPOSTINT('warehouse_id') > 0 ? GETPOSTINT('warehouse_id') : getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID');
                $stockProduct->correct_stock_batch($user, $warehouseId, 1, 0, $langs->transnoentities('ClientVehicle'), 0, '', '', $stockLot->batch, '', 'dolicar_registrationcertificate', 0);
            }
            header('Location: ' . dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $resultId);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
            $step   = 'edit';
            $action = '';
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('QuickRegistrationCertificateCreation');
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

if (!$permissiontoadd) {
    accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    exit;
}

// Retrieve plate and API data — set by action=search_plate above, or from POST on failed action=add
if (!isset($plate)) {
    $plate = strtoupper(GETPOST('a_registration_number', 'alphanohtml'));
}
if (!isset($plateFound)) {
    $plateFound = (bool) GETPOSTINT('plate_found');
}
if (!isset($draftId)) {
    $draftId = GETPOSTINT('draft_id');
}

// API-prefilled fields — set by action=search_plate above, or from POST on failed action=add
if (!isset($apiFields)) {
    $apiFields = [
        'b_first_registration_date'        => GETPOST('b_first_registration_date', 'alphanohtml'),
        'd1_vehicle_brand'                 => GETPOST('d1_vehicle_brand', 'alphanohtml'),
        'd2_vehicle_type'                  => GETPOST('d2_vehicle_type', 'alphanohtml'),
        'd21_vehicle_cnit'                 => GETPOST('d21_vehicle_cnit', 'alphanohtml'),
        'd3_vehicle_model'                 => GETPOST('d3_vehicle_model', 'alphanohtml'),
        'e_vehicle_serial_number'          => GETPOST('e_vehicle_serial_number', 'alphanohtml'),
        'j1_national_type'                 => GETPOST('j1_national_type', 'alphanohtml'),
        'p1_cylinder_capacity'             => GETPOST('p1_cylinder_capacity', 'alphanohtml'),
        'p3_fuel_type'                     => GETPOST('p3_fuel_type', 'alphanohtml'),
        'p6_national_administrative_power' => GETPOST('p6_national_administrative_power', 'alphanohtml'),
        's1_seating_capacity'              => GETPOST('s1_seating_capacity', 'alphanohtml'),
        'v7_co2_emission'                  => GETPOST('v7_co2_emission', 'alphanohtml'),
    ];
}

// Count prefilled fields for section badge
$prefilledCount = count(array_filter($apiFields, static function ($v) { return $v !== ''; }));

if (!isset($fkProduct)) {
    $fkProduct = GETPOSTINT('fk_product');
}
if (!isset($fkLot)) {
    $fkLot = GETPOSTINT('fk_lot');
}
if (!isset($isNewLot)) {
    $isNewLot = (bool) GETPOSTINT('is_new_lot');
}

print '<div class="quickcreation-page">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

// ============================================================
// STEPPER
// Steps: 1=Search, 2=Verify result, 3=Create
// In step 'search': step 1 active, 2+3 pending.
// In step 'edit':   steps 1+2 done, step 3 active.
// ============================================================
print '<div class="qc-stepper">';

// Step 1
$step1Class = ($step === 'search') ? 'qc-step--active' : 'qc-step--done';
print '<div class="qc-step ' . $step1Class . '">';
print '<div class="qc-step-num">' . ($step === 'search' ? '1' : '<i class="fas fa-check"></i>') . '</div>';
print '<div class="qc-step-text">';
print '<div class="qc-step-label">' . $langs->trans('Step') . ' 1</div>';
print '<div class="qc-step-title">' . $langs->trans('Search') . '</div>';
print '</div>';
print '</div>';

print '<i class="fas fa-chevron-right qc-step-arrow"></i>';

// Step 2
$step2Class = ($step === 'edit') ? 'qc-step--done' : '';
print '<div class="qc-step ' . $step2Class . '">';
print '<div class="qc-step-num">' . ($step === 'edit' ? '<i class="fas fa-check"></i>' : '2') . '</div>';
print '<div class="qc-step-text">';
print '<div class="qc-step-label">' . $langs->trans('Step') . ' 2</div>';
print '<div class="qc-step-title">' . $langs->trans('VerifyResult') . '</div>';
print '</div>';
print '</div>';

print '<i class="fas fa-chevron-right qc-step-arrow"></i>';

// Step 3
$step3Class = ($step === 'edit') ? 'qc-step--active' : '';
print '<div class="qc-step ' . $step3Class . '">';
print '<div class="qc-step-num">3</div>';
print '<div class="qc-step-text">';
print '<div class="qc-step-label">' . $langs->trans('Step') . ' 3</div>';
print '<div class="qc-step-title">' . $langs->trans('CreateRegistrationCertificate') . '</div>';
print '</div>';
print '</div>';

print '</div>'; // .qc-stepper

// ============================================================
// STEP 1 — SEARCH
// ============================================================
if ($step === 'search') {
    print '<div class="qc-inner">';

    // Section header: plate + source selector
    print '<div class="qc-section-header">';
    print '<div class="qc-section-title">';
    print '<i class="fas fa-id-card"></i>';
    print $langs->trans('RegistrationNumber');
    print ' <span class="qc-code">(A)</span>';
    print '</div>';

    // API source dropdown
    $sources = [
        'immatriculationapi'       => 'API immatriculationapi.com',
        'apiplaqueimmatriculation'  => 'API apiplaqueimmatriculation.com',
    ];
    $selectedSourceLabel = $sources[$apiSource] ?? reset($sources);

    print '<div class="qc-source-wrap" id="qcSrcDropdown">';
    print '<button type="button" class="qc-source-btn">';
    print '<i class="fas fa-globe globe"></i>';
    print '<span class="qc-source-text">' . dol_escape_htmltag($selectedSourceLabel) . '</span>';
    print '<i class="fas fa-chevron-down chevron"></i>';
    print '</button>';
    print '<input type="hidden" name="api_source" value="' . dol_escape_htmltag($apiSource) . '">';
    print '<div class="qc-source-menu">';
    foreach ($sources as $sourceKey => $sourceLabel) {
        $selectedClass = ($sourceKey === $apiSource) ? 'selected' : '';
        print '<div class="qc-source-option ' . $selectedClass . '" data-source="' . dol_escape_htmltag($sourceKey) . '">';
        print '<div class="qc-opt-radio"></div>';
        print '<div class="qc-opt-content">';
        print '<div class="qc-opt-title">' . dol_escape_htmltag($sourceLabel) . '</div>';
        print '<div class="qc-opt-desc">' . $langs->trans('SIVSource') . '</div>';
        print '</div>';
        print '</div>';
    }
    print '</div>'; // .qc-source-menu
    print '</div>'; // .qc-source-wrap
    print '</div>'; // .qc-section-header

    print '<div class="qc-section-body">';

    print '<input type="hidden" name="action" value="search_plate">';
    print '<input type="hidden" name="step" value="edit">';
    if ($confirmRetry) {
        print '<input type="hidden" name="confirm_retry" value="1">';
    }

    print '<div class="qc-form-row" style="grid-template-columns:120px 1fr;align-items:center">';
    print '<label>' . $langs->trans('LicencePlate') . '</label>';
    print '<div class="qc-field-wrapper" style="max-width:320px">';
    print '<i class="fas fa-id-card qc-field-icon" style="color:#b89a00"></i>';
    print '<input type="text" class="qc-plate-input" name="a_registration_number" placeholder="AB-123-CD" maxlength="11" value="' . dol_escape_htmltag($plate) . '" autocomplete="off">';
    print '</div>';
    print '</div>'; // .qc-form-row

    print '</div>'; // .qc-section-body

    // ---- Section: Client / Tiers ----
    if (isModEnabled('societe')) {
        $fkSoc = GETPOSTINT('fk_soc') ?: (int) $object->fk_soc;

        print '<div class="qc-section-header">';
        print '<div class="qc-section-title">';
        print '<i class="fas fa-user-tie"></i>';
        print $langs->trans('LinkedThirdParty');
        print '</div>';
        print '</div>';

        print '<div class="qc-section-body">';

        // Existing third party selector (always shown)
        print '<div class="qc-form-row">';
        print '<label>' . $langs->trans('ExistingThirdParty') . '</label>';
        print '<div class="qc-field-wrapper qc-select-wrapper">';
        print $form->select_company($fkSoc, 'fk_soc', '', 'SelectThirdParty', 0, 0, [], 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
        print '</div>';
        print '</div>';

        // New third party fields — greyed out (via JS) as soon as an existing third party is selected
        if ($thirdpartyQuickCreation) {
            print '<div class="qc-tier-divider"><span>' . $langs->trans('OrCreateNewThirdParty') . '</span></div>';

            print '<div class="qc-tier-new" data-tier-new>';

            // Name (required to trigger creation)
            print '<div class="qc-form-row">';
            print '<label>' . $langs->trans('ThirdPartyName') . '</label>';
            print '<div class="qc-field-wrapper">';
            print '<i class="fas fa-building qc-field-icon"></i>';
            print '<input type="text" name="name" maxlength="128" value="' . dol_escape_htmltag(GETPOST('name', 'alphanohtml')) . '" autocomplete="off">';
            print '</div>';
            print '</div>';

            // Prospect / customer type
            print '<div class="qc-form-row">';
            print '<label>' . $langs->trans('ProspectCustomer') . '</label>';
            print '<div class="qc-field-wrapper qc-select-wrapper">';
            print $formcompany->selectProspectCustomerType(GETPOSTISSET('client') ? GETPOSTINT('client') : 2, 'client', 'customerprospect', 'form', 'minwidth300 maxwidth500 widthcentpercentminusx');
            print '</div>';
            print '</div>';

            // Phone
            print '<div class="qc-form-row">';
            print '<label>' . $langs->trans('Phone') . '</label>';
            print '<div class="qc-field-wrapper">';
            print '<i class="fas fa-phone qc-field-icon"></i>';
            print '<input type="text" name="phone" value="' . dol_escape_htmltag(GETPOST('phone', 'alpha')) . '" autocomplete="off">';
            print '</div>';
            print '</div>';

            // Email
            print '<div class="qc-form-row">';
            print '<label>' . $langs->trans('Email') . '</label>';
            print '<div class="qc-field-wrapper">';
            print '<i class="fas fa-envelope qc-field-icon"></i>';
            print '<input type="text" name="email_thirdparty" value="' . dol_escape_htmltag(GETPOST('email_thirdparty', 'alpha')) . '" autocomplete="off">';
            print '</div>';
            print '</div>';

            print '</div>'; // .qc-tier-new
        }

        print '</div>'; // .qc-section-body (tiers)
    }

    // Primary action at the very bottom so the user completes the whole form (plate + third party) before searching
    print '<button type="submit" class="qc-search-btn">';
    print '<i class="fas fa-magnifying-glass"></i>';
    print ' ' . $langs->trans('LaunchSearch');
    print '</button>';

    print '<div class="qc-quick-action">';
    print $langs->trans('OrDirectlyCreate') . ' ';
    print '<a href="' . dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?action=create">';
    print $langs->trans('CreateEmptyRegistrationCertificate') . '</a>';
    print '</div>';

    print '</div>'; // .qc-inner
}

// ============================================================
// STEP 2 — EDIT / REVIEW
// ============================================================
if ($step === 'edit') {
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="step" value="edit">';
    print '<input type="hidden" name="api_source" value="' . dol_escape_htmltag($apiSource) . '">';
    print '<input type="hidden" name="draft_id" value="' . (int) $draftId . '">';
    print '<input type="hidden" name="plate_found" value="' . ($plateFound ? '1' : '0') . '">';
    print '<input type="hidden" name="is_new_lot" value="' . ($isNewLot ? '1' : '0') . '">';

    // Hidden split-date fields so action=add can compute the timestamp from dol_mktime
    if (!empty($apiFields['b_first_registration_date'])) {
        $dateParts = preg_split('/[\/\-]/', $apiFields['b_first_registration_date']);
        if (count($dateParts) === 3) {
            print '<input type="hidden" name="b_first_registration_dateday"   value="' . (int) $dateParts[0] . '">';
            print '<input type="hidden" name="b_first_registration_datemonth" value="' . (int) $dateParts[1] . '">';
            print '<input type="hidden" name="b_first_registration_dateyear"  value="' . (int) $dateParts[2] . '">';
        }
    }

    // Result banner (only shown when API returned no data)
    if (!$plateFound) {
        print '<div class="qc-notfound-banner">';
        print '<i class="fas fa-magnifying-glass-minus"></i>';
        print '<div><strong>' . $langs->trans('NoPlateFound') . '</strong>';
        print ' — ' . $langs->trans('CreateManuallyFromPaper') . '</div>';
        print '</div>';
    }

    // ---- Section: Numéro d'immatriculation ----
    print '<div class="qc-section-header">';
    print '<div class="qc-section-title">';
    print '<i class="fas fa-id-card"></i>';
    print $langs->trans('RegistrationNumber');
    print ' <span class="qc-code">(A)</span>';
    print '</div>';
    print '</div>';

    print '<div class="qc-section-body">';

    print '<div class="qc-form-row">';
    print '<label>' . $langs->trans('LicencePlate') . '</label>';
    print '<div class="qc-field-wrapper">';
    print '<i class="fas fa-id-card qc-field-icon"></i>';
    $plateClass = $plate ? 'qc-plate-input prefilled' : 'qc-plate-input';
    print '<input type="text" class="' . $plateClass . '" name="a_registration_number" value="' . dol_escape_htmltag($plate) . '" maxlength="11" autocomplete="off">';
    print '</div>';
    print '</div>';

    // Product selector
    print '<input type="hidden" name="fk_lot" value="' . (int) $fkLot . '">';
    print '<div class="qc-form-row">';
    print '<label>' . $langs->trans('Product') . '</label>';
    print '<div class="qc-field-wrapper">';
    print '<i class="fas fa-box qc-field-icon"></i>';
    print $form->select_produits((int) $fkProduct, 'fk_product', '', 0, 0, -1, 2, '', 0, [], 0, '1', 0, 'minwidth300 maxwidth500');
    print '</div>';
    print '</div>';

    print '</div>'; // .qc-section-body

    // ---- Third party — values are captured on step 1, carried forward here so action=add receives them ----
    if (isModEnabled('societe')) {
        print '<input type="hidden" name="fk_soc" value="' . (GETPOSTINT('fk_soc') ?: (int) $object->fk_soc) . '">';
        if ($thirdpartyQuickCreation) {
            print '<input type="hidden" name="name" value="' . dol_escape_htmltag(GETPOST('name', 'alphanohtml')) . '">';
            print '<input type="hidden" name="client" value="' . GETPOSTINT('client') . '">';
            print '<input type="hidden" name="phone" value="' . dol_escape_htmltag(GETPOST('phone', 'alphanohtml')) . '">';
            print '<input type="hidden" name="email_thirdparty" value="' . dol_escape_htmltag(GETPOST('email_thirdparty', 'alphanohtml')) . '">';
        }
    }

    // ---- Section: Caractéristiques (collapsible) ----
    $sectionCollapsed = 'collapsed';
    $warehouseId      = GETPOSTINT('warehouse_id') > 0 ? GETPOSTINT('warehouse_id') : getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID');

    print '<div class="qc-section-header collapsible ' . $sectionCollapsed . '">';
    print '<div class="qc-section-title">';
    print '<i class="fas fa-car"></i>';
    print $langs->trans('VehicleCharacteristics');
    if ($prefilledCount > 0) {
        print ' <span class="qc-badge-count">' . $prefilledCount . '</span>';
    }
    print '</div>';
    print '<div class="qc-warehouse-pill" onclick="event.stopPropagation()">';
    print '<i class="fas fa-warehouse"></i>';
    print $formproduct->selectWarehouses($warehouseId, 'warehouse_id', 'warehouseopen,warehouseinternal', 0, 0, 0, $langs->trans('DestinationWarehouse'), 0, 0, [], 'qc-warehouse-select');
    print '</div>';
    print '<i class="fas fa-chevron-down qc-chevron"></i>';
    print '</div>';

    print '<div class="qc-section-body ' . $sectionCollapsed . '">';

    $qcFields = [
        'b_first_registration_date' => [
            'label' => $langs->trans('FirstRegistrationDate'),
            'code'  => '(B)',
            'icon'  => 'fas fa-calendar',
            'type'  => 'text',
        ],
        'd1_vehicle_brand' => [
            'label' => $langs->trans('VehicleBrand'),
            'code'  => '(D.1)',
            'icon'  => 'fas fa-tag',
            'type'  => 'text',
        ],
        'd2_vehicle_type' => [
            'label' => $langs->trans('VehicleType'),
            'code'  => '(D.2)',
            'icon'  => 'fas fa-tags',
            'type'  => 'text',
        ],
        'd21_vehicle_cnit' => [
            'label' => $langs->trans('VehicleCNIT'),
            'code'  => '(D.2.1)',
            'icon'  => 'fas fa-fingerprint',
            'type'  => 'text',
        ],
        'd3_vehicle_model' => [
            'label' => $langs->trans('VehicleModel'),
            'code'  => '(D.3)',
            'icon'  => 'fas fa-car-side',
            'type'  => 'text',
        ],
        'e_vehicle_serial_number' => [
            'label' => $langs->trans('VehicleSerialNumber'),
            'code'  => '(E)',
            'icon'  => 'fas fa-barcode',
            'type'  => 'text',
        ],
        'j1_national_type' => [
            'label' => $langs->trans('NationalType'),
            'code'  => '(J.1)',
            'icon'  => 'fas fa-list',
            'type'  => 'text',
        ],
        'p1_cylinder_capacity' => [
            'label' => $langs->trans('CylinderCapacity'),
            'code'  => '(P.1)',
            'icon'  => 'fas fa-gauge',
            'type'  => 'number',
        ],
        'p3_fuel_type' => [
            'label' => $langs->trans('FuelType'),
            'code'  => '(P.3)',
            'icon'  => 'fas fa-gas-pump',
            'type'  => 'text',
        ],
        'p6_national_administrative_power' => [
            'label' => $langs->trans('NationalAdministrativePower'),
            'code'  => '(P.6)',
            'icon'  => 'fas fa-bolt',
            'type'  => 'number',
        ],
        's1_seating_capacity' => [
            'label' => $langs->trans('SeatingCapacity'),
            'code'  => '(S.1)',
            'icon'  => 'fas fa-chair',
            'type'  => 'number',
        ],
        'v7_co2_emission' => [
            'label' => $langs->trans('CO2Emission'),
            'code'  => '(V.7)',
            'icon'  => 'fas fa-leaf',
            'type'  => 'number',
        ],
    ];

    foreach ($qcFields as $fieldName => $fieldDef) {
        $fieldValue  = dol_escape_htmltag($apiFields[$fieldName]);
        $prefilled   = ($plateFound && $fieldValue !== '') ? 'prefilled' : '';
        $inputType   = $fieldDef['type'];
        $placeholder = ($inputType === 'text') ? '' : '';

        print '<div class="qc-form-row">';
        print '<label>' . dol_escape_htmltag($fieldDef['label']);
        if (!empty($fieldDef['code'])) {
            print ' <span class="qc-code">' . dol_escape_htmltag($fieldDef['code']) . '</span>';
        }
        print '</label>';
        print '<div class="qc-field-wrapper">';
        print '<i class="' . dol_escape_htmltag($fieldDef['icon']) . ' qc-field-icon"></i>';
        print '<input type="' . $inputType . '" class="' . $prefilled . '" name="' . dol_escape_htmltag($fieldName) . '" value="' . $fieldValue . '">';
        print '</div>';
        print '</div>';
    }

    print '</div>'; // .qc-section-body (caractéristiques)

    // Form actions
    print '<div class="qc-form-actions">';

    $backUrl = $_SERVER['PHP_SELF'];
    if (!empty($plate)) {
        $backUrl .= '?a_registration_number=' . urlencode($plate);
    }
    print '<a class="qc-btn" href="' . $backUrl . '">';
    print $langs->trans('Cancel');
    print '</a>';

    print '<button type="submit" class="qc-btn qc-btn-primary qc-btn-large">';
    print '<i class="fas fa-check"></i> ' . $langs->trans('CreateRegistrationCertificate');
    print '</button>';

    print '</div>'; // .qc-form-actions
}

print '</form>';
print '</div>'; // .quickcreation-page

// End of page
llxFooter();
$db->close();
