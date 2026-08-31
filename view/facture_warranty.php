<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    view/facture_warranty.php
 * \ingroup dolicar
 * \brief   Warranties tab of a customer or supplier invoice
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

// Load DoliCar libraries
require_once __DIR__ . '/../lib/dolicar_functions.lib.php';

// Global variables definitions
global $conf, $db, $form, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['bills']);

// Get parameters
$id      = GETPOSTINT('id');
$action  = GETPOST('action', 'aZ09');
$element = GETPOST('element', 'aZ09');

// Initialize technical objects
$isSupplierInvoice = $element == 'invoice_supplier';
$object            = $isSupplierInvoice ? new FactureFournisseur($db) : new Facture($db);
$form              = new Form($db);

if ($id <= 0 || $object->fetch($id) <= 0) {
    accessforbidden($langs->trans('ErrorRecordNotFound'));
}
$object->fetch_thirdparty();
$object->fetch_optionals();

// Security check - the tab lives on an invoice, its own permissions rule
$permissionToRead = $isSupplierInvoice
    ? ($user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'lire'))
    : $user->hasRight('facture', 'lire');
if (empty($permissionToRead)) {
    accessforbidden();
}
$permissionToAdd = dolicar_can_edit_invoice_warranties($object);

$backToPage = dol_buildpath('/custom/dolicar/view/facture_warranty.php', 1) . '?id=' . $object->id . ($isSupplierInvoice ? '&element=invoice_supplier' : '');

// Certificates are attached before the warranty exists: they wait in a temp dir keyed by an upload
// token, and move next to the invoice once the warranty is saved
$warrantyUploadContext = 'dolicar_invoice_warranty_' . $object->element . '_' . $object->id;
$warrantyUploadSubDir  = 'invoice_warranty/' . saturne_get_upload_token($warrantyUploadContext);

/*
 * Actions
 */

// Upload and deletion posted by the Saturne media block of the warranty form. The block reloads
// itself from the HTML of this page, so these actions only touch the filesystem
if (in_array($action, ['uploadFile', 'deleteFile'], true) && $permissionToAdd) {
    // Only the temp dir of the warranty being created may be written to
    if (GETPOST('sub_dir', 'alpha') === $warrantyUploadSubDir) {
        $warrantyUploadDir = $conf->dolicar->dir_output . '/' . $warrantyUploadSubDir;

        if ($action == 'uploadFile' && !empty($conf->global->MAIN_UPLOAD_DOC)) {
            if (!dol_is_dir($warrantyUploadDir)) {
                dol_mkdir($warrantyUploadDir);
            }
            dol_add_file_process($warrantyUploadDir, 0, 1, 'userfile', '', null, '', 1);
        }

        if ($action == 'deleteFile') {
            $warrantyFileName = dol_sanitizeFileName(GETPOST('filename', 'alpha'));
            if (!empty($warrantyFileName) && dol_is_file($warrantyUploadDir . '/' . $warrantyFileName)) {
                dol_delete_file($warrantyUploadDir . '/' . $warrantyFileName);
            }
        }
    }

    $action = '';
}

if ($action == 'add_warranty' && $permissionToAdd) {
    $warranties        = dolicar_get_invoice_warranties($object);
    $warrantyLabel     = GETPOST('warranty_label', 'alphanohtml');
    $warrantyEndDate   = dol_mktime(12, 0, 0, GETPOSTINT('warranty_endmonth'), GETPOSTINT('warranty_endday'), GETPOSTINT('warranty_endyear'));
    $warrantyTempFiles = dolicar_get_upload_temp_files($warrantyUploadSubDir);

    if (empty($warrantyLabel) && empty($warrantyEndDate) && empty($warrantyTempFiles)) {
        setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('WarrantyEndDate')), null, 'errors');
    } else {
        $warrantyDir       = dolicar_get_invoice_warranty_dir($object);
        $warrantyFileNames = [];

        if (!empty($warrantyTempFiles)) {
            if (!dol_is_dir($warrantyDir)) {
                dol_mkdir($warrantyDir);
            }

            foreach ($warrantyTempFiles as $warrantyTempFile) {
                $warrantyFileName = dol_sanitizeFileName($warrantyTempFile['name']);
                // Two warranties may come with a certificate sharing the same name
                if (dol_is_file($warrantyDir . '/' . $warrantyFileName)) {
                    $warrantyFileName = dol_sanitizeFileName(pathinfo($warrantyFileName, PATHINFO_FILENAME) . '_' . dol_print_date(dol_now(), 'dayhourlog') . '.' . pathinfo($warrantyFileName, PATHINFO_EXTENSION));
                }

                // Index the move, dol_add_file_process() has recorded the temp path in llx_ecm_files
                if (dol_move($warrantyTempFile['fullname'], $warrantyDir . '/' . $warrantyFileName, 0, 1, 0, 1)) {
                    $warrantyFileNames[] = $warrantyFileName;
                }
            }

            saturne_invalidate_upload_token($warrantyUploadContext, 'dolicar', 'invoice_warranty');
        }

        // Ids are only there to target a warranty on deletion, so a simple increment is enough
        $nextWarrantyId = 1;
        foreach ($warranties as $warranty) {
            $nextWarrantyId = max($nextWarrantyId, (int) ($warranty['id'] ?? 0) + 1);
        }

        $warranties[] = [
            'id'       => $nextWarrantyId,
            'label'    => $warrantyLabel,
            'date_end' => $warrantyEndDate > 0 ? dol_print_date($warrantyEndDate, '%Y-%m-%d') : '',
            'files'    => $warrantyFileNames
        ];

        if (dolicar_set_invoice_warranties($object, $warranties) > 0) {
            setEventMessages($langs->transnoentities('WarrantyAdded'), null);
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    header('Location: ' . $backToPage);
    exit;
}

if ($action == 'delete_warranty' && $permissionToAdd) {
    $warranties         = dolicar_get_invoice_warranties($object);
    $warrantyIdToDelete = GETPOSTINT('warranty_id');

    foreach ($warranties as $key => $warranty) {
        if ((int) ($warranty['id'] ?? 0) !== $warrantyIdToDelete) {
            continue;
        }

        foreach ($warranty['files'] as $warrantyFileName) {
            $warrantyFilePath = dolicar_get_invoice_warranty_dir($object) . '/' . dol_sanitizeFileName($warrantyFileName);
            if (dol_is_file($warrantyFilePath)) {
                dol_delete_file($warrantyFilePath);
            }
        }

        unset($warranties[$key]);
    }

    if (dolicar_set_invoice_warranties($object, $warranties) > 0) {
        setEventMessages($langs->transnoentities('WarrantyDeleted'), null);
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    header('Location: ' . $backToPage);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('Warranties') . ' - ' . ($isSupplierInvoice ? $langs->trans('SupplierInvoice') : $langs->trans('Bill'));
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

// Same tabs and banner as the invoice card, the tab must not look like it left the invoice
$head = $isSupplierInvoice ? facturefourn_prepare_head($object) : facture_prepare_head($object);
print dol_get_fiche_head($head, 'dolicarwarranty', $isSupplierInvoice ? $langs->trans('SupplierInvoice') : $langs->trans('Bill'), -1, $isSupplierInvoice ? 'supplier_invoice' : 'bill');

$linkback = '<a href="' . dol_buildpath('/' . ($isSupplierInvoice ? 'fourn/facture' : 'compta/facture') . '/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

$morehtmlref = '<div class="refidno">';
if ($isSupplierInvoice) {
    $morehtmlref .= $langs->trans('RefSupplier') . ' : ' . dol_escape_htmltag($object->ref_supplier);
}
$morehtmlref .= '<br>' . $object->thirdparty->getNomUrl(1, $isSupplierInvoice ? 'supplier' : 'customer');
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
require_once __DIR__ . '/../core/tpl/facture_warranty.tpl.php';
print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
