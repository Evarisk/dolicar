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
 * \file    registrationcertificatefr_vehiclehistory.php
 * \ingroup dolicar
 * \brief   Page to view and add vehicle history events for a carte grise
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
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/lib/medias.lib.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../lib/dolicar_functions.lib.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Global variables definitions
global $conf, $db, $form, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['trips']);

// Get parameters
$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

// Initialize technical objects
$object      = new RegistrationCertificateFr($db);
$extrafields = new ExtraFields($db);
$form        = new Form($db);

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd  = $user->rights->dolicar->registrationcertificatefr->write;
saturne_check_access($permissionToRead);

// Photos and files of the event being created are uploaded before it exists: they go to a per-session
// temp dir keyed by an upload token, then move next to the event once it is saved (issue #475)
$eventUploadContext = 'dolicar_vehicle_event_' . $object->id;
$eventUploadSubDir  = 'vehicle_event/' . saturne_get_upload_token($eventUploadContext);

// AJAX: return the lines of an expense report for the vehicle-event form (issue #452)
if ($action == 'get_expensereport_lines') {
    require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';

    $fkExpenseReport = GETPOSTINT('fk_expensereport');
    $linesData       = [];
    if ($fkExpenseReport > 0) {
        $expenseReport = new ExpenseReport($db);
        if ($expenseReport->fetch($fkExpenseReport) > 0) {
            $expenseReport->fetch_lines();
            if (is_array($expenseReport->lines)) {
                foreach ($expenseReport->lines as $line) {
                    $typeLabel    = ($langs->transnoentities($line->type_fees_code) != $line->type_fees_code) ? $langs->transnoentities($line->type_fees_code) : ($line->type_fees_libelle ?: '');
                    $projectLabel = $line->projet_title ?: $line->projet_ref;
                    $parts        = [];
                    if ($typeLabel !== '') {
                        $parts[] = $typeLabel;
                    }
                    if (!empty($projectLabel)) {
                        $parts[] = $projectLabel;
                    }
                    if (!empty($line->comments)) {
                        $parts[] = dol_trunc($line->comments, 60);
                    }
                    $label = implode(' · ', $parts);
                    $linesData[] = [
                        'id'     => (int) $line->rowid,
                        'label'  => $label,
                        'amount' => price($line->total_ttc, 0, $langs, 1, -1, -1, $conf->currency),
                    ];
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($linesData);
    exit;
}

// Ensure parent event category exists and children are initialized
$parentCategoryId = getDolGlobalInt('DOLICAR_VEHICLE_EVENT_CATEGORY_ID');
if ($parentCategoryId > 0) {
    $checkCat = new Categorie($db);
    if ($checkCat->fetch($parentCategoryId) <= 0) {
        $parentCategoryId = 0;
    }
}
if ($parentCategoryId <= 0) {
    $findParent = new Categorie($db);
    if ($findParent->fetch(0, 'DoliCar Véhicule Événement', Categorie::TYPE_ACTIONCOMM) > 0) {
        $parentCategoryId = $findParent->id;
    } else {
        $newParent            = new Categorie($db);
        $newParent->label     = 'DoliCar Véhicule Événement';
        $newParent->type      = Categorie::TYPE_ACTIONCOMM;
        $newParent->fk_parent = 0;
        $newParent->visible   = 1;
        $parentCategoryId     = $newParent->create($user);
    }

    if ($parentCategoryId > 0) {
        dolibarr_set_const($db, 'DOLICAR_VEHICLE_EVENT_CATEGORY_ID', $parentCategoryId, 'chaine', 0, '', $conf->entity);

        $defaultChildren = [
            ['label' => 'Contrôle technique', 'color' => '5BA86E'],
            ['label' => 'Révision',           'color' => 'E8A317'],
            ['label' => 'Réparation',         'color' => '3B82F6'],
            ['label' => 'Accident',           'color' => 'E05353'],
            ['label' => 'Autre',              'color' => '888888'],
        ];
        foreach ($defaultChildren as $childDef) {
            $child            = new Categorie($db);
            $child->label     = $childDef['label'];
            $child->type      = Categorie::TYPE_ACTIONCOMM;
            $child->fk_parent = $parentCategoryId;
            $child->color     = $childDef['color'];
            $child->visible   = 1;
            if (!$child->already_exists()) {
                $child->create($user);
            }
        }
    }
}

// Icon mapping for default event types (label → Font Awesome class)
$iconMap = [
    'Contrôle technique' => 'fa-check-circle',
    'Révision'           => 'fa-wrench',
    'Accident'           => 'fa-exclamation-triangle',
    'Autre'              => 'fa-circle',
];
$iconMap[$langs->transnoentities('ReportedProblem')] = 'fa-exclamation-triangle';
$iconMap['Réparation']                               = 'fa-wrench';
$iconMap[$langs->transnoentities('Repair')]          = 'fa-wrench';

// Load child categories and build display structures for the TPL
$catById   = [];
$catLabels = [];
if ($parentCategoryId > 0) {
    $parentCat = new Categorie($db);
    if ($parentCat->fetch($parentCategoryId) > 0) {
        $filles = $parentCat->get_filles();
        if (is_array($filles)) {
            foreach ($filles as $cat) {
                $catIcon  = $iconMap[$cat->label] ?? 'fa-circle';
                $catColor = '#' . ltrim($cat->color, '#');

                $catById[$cat->id]   = $cat;
                $catLabels[$cat->id] = [
                    'label'     => $cat->label,
                    'data-html' => '<i class="fas ' . $catIcon . '" style="color:' . $catColor . '"></i> ' . dol_escape_htmltag($cat->label),
                ];
            }
        }
    }
}

// Load all vehicle history events and resolve their category in one pass
$eventsList = [];
$evtCatById = [];
if (!empty($object->fk_lot) && $object->fk_lot > 0 && !empty($catById)) {
    $actionCommHelper = new ActionComm($db);
    $catHelper        = new Categorie($db);
    $allEvents        = $actionCommHelper->getActions(0, (int) $object->fk_lot, 'productlot', '', 'a.datep', 'DESC');

    if (is_array($allEvents)) {
        foreach ($allEvents as $evt) {
            $matched   = false;
            $evtCatIds = $catHelper->containing($evt->id, Categorie::TYPE_ACTIONCOMM, 'id');
            if (is_array($evtCatIds)) {
                foreach ($evtCatIds as $evtCatId) {
                    if (isset($catById[(int) $evtCatId])) {
                        $eventsList[]               = $evt;
                        $evtCatById[(int) $evt->id] = $catById[(int) $evtCatId];
                        $matched                    = true;
                        break;
                    }
                }
            }

            // Problem reports from the public logbook carry no DoliCar category — surface them with a dedicated badge
            if (!$matched && !empty($evt->code) && strpos($evt->code, '_REPORT_PROBLEM') !== false) {
                $eventsList[]               = $evt;
                $evtCatById[(int) $evt->id] = (object) ['label' => $langs->transnoentities('ReportedProblem'), 'color' => 'F59E0B'];
                $matched                    = true;
            }

            // Repairs from the public logbook carry no DoliCar category — surface them with a dedicated badge
            if (!$matched && !empty($evt->code) && strpos($evt->code, '_ADD_REPAIR') !== false) {
                $eventsList[]               = $evt;
                $evtCatById[(int) $evt->id] = (object) ['label' => $langs->transnoentities('Repair'), 'color' => '3B82F6'];
            }
        }
    }
}

/*
 * Actions
 */

// Photo/file upload and deletion posted by the Saturne media block of the add-event form (issue #475).
// The block reloads itself from the HTML of this page, so these actions only touch the filesystem.
if (in_array($action, ['uploadPhoto', 'uploadFile', 'deletePhoto', 'deleteFile'], true) && !empty($permissiontoadd)) {
    $uploadSubDir = GETPOST('sub_dir', 'alpha');

    // Only the temp dir of the event being created may be written to
    if ($uploadSubDir === $eventUploadSubDir) {
        $uploadDir = $conf->dolicar->dir_output . '/' . $uploadSubDir;

        if (($action == 'uploadPhoto' || $action == 'uploadFile') && !empty($conf->global->MAIN_UPLOAD_DOC)) {
            if (!dol_is_dir($uploadDir)) {
                dol_mkdir($uploadDir);
            }

            $invalidFile   = false;
            $uploadedFiles = isset($_FILES['userfile']) ? $_FILES['userfile'] : [];

            // The photo button must not become a way to store any file type
            if ($action == 'uploadPhoto' && !empty($uploadedFiles['tmp_name'])) {
                $tmpNames = is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'] : [$uploadedFiles['tmp_name']];
                foreach ($tmpNames as $tmpName) {
                    if (empty($tmpName)) {
                        continue;
                    }
                    $finfo    = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);
                    if (strpos($mimeType, 'image/') !== 0) {
                        $invalidFile = true;
                        break;
                    }
                }
            }

            if ($invalidFile) {
                setEventMessages($langs->trans('ErrorFileNotAnImage'), null, 'errors');
            } else {
                dol_add_file_process($uploadDir, GETPOSTINT('overwrite') ? 1 : 0, 1, 'userfile', '', null, '', 1);
            }
        }

        if ($action == 'deletePhoto' || $action == 'deleteFile') {
            $fileName = dol_sanitizeFileName(GETPOST('filename', 'alpha'));
            $filePath = $uploadDir . '/' . $fileName;
            if (!empty($fileName) && dol_is_file($filePath)) {
                dol_delete_file($filePath);
            }
        }
    }

    $action = '';
}

if ($action == 'add_vehicle_event' && !empty($permissiontoadd) && !empty($object->fk_lot) && $object->fk_lot > 0) {
    $error           = 0;
    $eventCategoryId = GETPOSTINT('event_category_id');
    $eventDate       = dol_mktime(12, 0, 0, GETPOSTINT('event_datemonth'), GETPOSTINT('event_dateday'), GETPOSTINT('event_dateyear'));
    $eventMileage    = GETPOSTINT('event_mileage');
    $eventNote       = GETPOST('event_note', 'restricthtml');
    $fkFacture       = GETPOSTINT('event_fk_facture');
    $fkPropal        = GETPOSTINT('event_fk_propal');
    $fkExpenseReport = GETPOSTINT('event_fk_expensereport');
    $fkSupplierOrder = GETPOSTINT('event_fk_supplierorder');
    $fkSupplierInvoice = GETPOSTINT('event_fk_supplierinvoice');
    $fkControl       = GETPOSTINT('event_fk_control');
    $expenseReportLineIds = array_values(array_filter(array_map('intval', (array) GETPOST('event_expensereport_lines', 'array'))));

    // Event type is optional: when none is picked, fall back to the "Autre" category so the event still saves and shows in the history
    if ($eventCategoryId <= 0 && $parentCategoryId > 0) {
        $fallbackCat = new Categorie($db);
        if ($fallbackCat->fetch(0, 'Autre', Categorie::TYPE_ACTIONCOMM) > 0 && (int) $fallbackCat->fk_parent === $parentCategoryId) {
            $eventCategoryId = (int) $fallbackCat->id;
        }
    }

    $selectedCat = new Categorie($db);
    if ($eventCategoryId <= 0 || $selectedCat->fetch($eventCategoryId) <= 0 || (int) $selectedCat->fk_parent !== $parentCategoryId) {
        setEventMessages($langs->transnoentities('VehicleEventType') . ' ' . $langs->transnoentities('NotValid'), null, 'errors');
        $error++;
    }

    if (!$error) {
        $actionComm               = new ActionComm($db);
        $actionComm->code         = 'AC_OTH';
        $actionComm->type_code    = 'AC_OTH';
        $actionComm->fk_element   = (int) $object->fk_lot;
        $actionComm->elementtype  = 'productlot';
        $actionComm->label        = $selectedCat->label;
        $actionComm->datep        = $eventDate ?: dol_now();
        $actionComm->userownerid  = $user->id;
        $actionComm->percentage   = -1;
        $actionComm->note_private = $eventNote;

        $result = $actionComm->create($user);
        if ($result > 0 && $eventMileage > 0) {
            $actionComm->array_options['options_starting_mileage'] = $eventMileage;
            $actionComm->insertExtraFields();
        }

        if ($result > 0) {
            $actionComm->setCategories([$eventCategoryId]);

            if ($fkFacture > 0) {
                $actionComm->add_object_linked('facture', $fkFacture);
            }
            if ($fkPropal > 0) {
                $actionComm->add_object_linked('propal', $fkPropal);
            }
            // Link the whole expense report only when no specific lines were selected (issue #452)
            if (empty($expenseReportLineIds) && $fkExpenseReport > 0) {
                $actionComm->add_object_linked('expensereport', $fkExpenseReport);
            }
            // Otherwise link the selected expense report lines (core element_element table, no schema change)
            foreach ($expenseReportLineIds as $expenseReportLineId) {
                $actionComm->add_object_linked('expensereportline', $expenseReportLineId);
            }
            if ($fkSupplierOrder > 0) {
                $actionComm->add_object_linked('order_supplier', $fkSupplierOrder);
            }
            if ($fkSupplierInvoice > 0) {
                $actionComm->add_object_linked('invoice_supplier', $fkSupplierInvoice);
            }
            if ($fkControl > 0) {
                $actionComm->add_object_linked('control', $fkControl);
            }

            // Move the uploaded photos/files from the temp dir to their permanent location keyed by the event
            $eventMediaFiles = dolicar_get_upload_temp_files($eventUploadSubDir);
            if (!empty($eventMediaFiles)) {
                $eventFinalDir = $conf->dolicar->dir_output . '/vehicle_event/' . (int) $actionComm->id;
                if (!dol_is_dir($eventFinalDir)) {
                    dol_mkdir($eventFinalDir);
                }
                foreach ($eventMediaFiles as $eventMediaFile) {
                    dol_move($eventMediaFile['fullname'], $eventFinalDir . '/' . $eventMediaFile['name'], 0, 1, 0, 0);
                }
            }
            saturne_invalidate_upload_token($eventUploadContext, 'dolicar', 'vehicle_event');

            setEventMessages($langs->transnoentities('VehicleEventAdded'), null, 'mesgs');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
            exit;
        } else {
            setEventMessages($actionComm->error, $actionComm->errors, 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('VehicleHistory') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'vehiclehistory', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';
    require_once __DIR__ . '/../../core/tpl/registrationcertificatefr_vehicle_history.tpl.php';
    print '</div>';

    print dol_get_fiche_end();

    // Photo editor modal — its DOM must exist at page load, otherwise selecting a photo in the
    // media block of the add-event form does nothing
    require_once __DIR__ . '/../../../saturne/core/tpl/medias/photo_editor_modal.tpl.php';
}

// End of page
llxFooter();
$db->close();
