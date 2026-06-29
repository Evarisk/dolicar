<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    public/agenda/public_vehicle_logbook.php
 * \ingroup dolicar
 * \brief   Public page to create entry in vehicle logbook
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load DoliCar environment
if (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} elseif (file_exists('../../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Initialize technical variable
$publicInterfaceUseSignatory = getDolGlobalInt('DOLICAR_PUBLIC_INTERFACE_USE_SIGNATORY');
$isModEnabledDigiquali       = isModEnabled('digiquali');

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load Saturne libraries
if ($publicInterfaceUseSignatory) {
    require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php'; // For generate_random_id()
}

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Load DigiQuali libraries
if ($isModEnabledDigiquali) {
    require_once __DIR__ . '/../../../digiquali/class/control.class.php';
    require_once __DIR__ . '/../../../digiquali/class/sheet.class.php';
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs(['productbatch']);

// Get parameters
$id                 = GETPOST('id', 'int');
$registrationNumber = GETPOST('registration_number');
$entity             = GETPOST('entity');
$action             = GETPOST('action', 'aZ09');
$backToPage         = GETPOST('backtopage', 'alpha');
$actionType         = GETPOST('action_type', 'alpha');
$success            = GETPOSTINT('success');

// Initialize technical objects
$actionComm                = new ActionComm($db);
$productLot                = new ProductLot($db);
$registrationCertificateFR = new RegistrationCertificateFr($db);
$extraFields               = new ExtraFields($db);
$user                      = new User($db);
if ($publicInterfaceUseSignatory) {
    $signatory = new SaturneSignature($db, 'dolicar', 'actioncomm');
}
if ($isModEnabledDigiquali) {
    $control = new Control($db);
}

$hookmanager->initHooks(['publicvehiclelogbook', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Fetch optionals attributes and labels
$extraFields->fetch_name_optionals_label($actionComm->table_element);

// Load object
if ($id > 0) {
    $registrationCertificateFR->fetch(0, '', ' AND fk_lot = ' . $id);
} else {
    $registrationCertificateFR->fetch(0, '', ' AND a_registration_number = ' . "'" . $registrationNumber . "'");
    $id = $registrationCertificateFR->fk_lot;
}
$productLot->fetch($id);

$lastActionComm = $actionComm->getActions(0, $id,'productlot', ' AND fk_element = ' . $id . ' AND datep2 IS NOT NULL AND code = "AC_' . strtoupper($productLot->element) . '_ADD_PUBLIC_VEHICLE_LOG_BOOK"', 'id','DESC', 1);
if (is_array($lastActionComm) && !empty($lastActionComm)) {
    $lastArrivalMileage = $lastActionComm[0]->array_options['options_arrival_mileage'];
}
$lastUnfinishedActionComm = $actionComm->getActions(0, $id,'productlot', ' AND fk_element = ' . $id . ' AND datep2 IS NULL AND code = "AC_' . strtoupper($productLot->element) . '_ADD_PUBLIC_VEHICLE_LOG_BOOK"', 'id','DESC', 1);
if (!empty($lastUnfinishedActionComm) && is_array($lastUnfinishedActionComm)) {
    $lastUnfinishedActionCommJSON = json_decode($lastUnfinishedActionComm[0]->array_options['options_json'], true);
    $lastArrivalMileage           = $lastUnfinishedActionComm[0]->array_options['options_starting_mileage'];
    if ($publicInterfaceUseSignatory) {
        $signatory->fetch(0, '', ' AND object_type = "actiocomm" AND fk_object = ' . $lastUnfinishedActionComm[0]->id);
    }
}

$recentActionComms = $actionComm->getActions(0, $id, 'productlot', ' AND fk_element = ' . $id . ' AND code = "AC_' . strtoupper($productLot->element) . '_ADD_PUBLIC_VEHICLE_LOG_BOOK"', 'id', 'DESC', 5);
if (!is_array($recentActionComms)) {
    $recentActionComms = [];
}

if ($isModEnabledDigiquali) {
    $controls = saturne_fetch_all_object_type('Control', 'DESC', 't.control_date', 1, 0, ['customsql' => 't.rowid = ee.fk_target AND t.status = ' . Control::STATUS_LOCKED], 'AND', false, true, false, ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as ee on ee.sourcetype = "productbatch" AND ee.fk_source = ' . $id . ' AND ee.targettype = "digiquali_control" AND ee.fk_target = t.rowid');
    if (is_array($controls) && !empty($controls)) {
        foreach ($controls as $control) {
            $lastControl = $control;
        }
    }
}

// Problem report (issue #443): photo/voice are uploaded to a per-session temp dir keyed by an upload token
require_once __DIR__ . '/../../../saturne/lib/medias.lib.php';
$problemUploadContext = 'dolicar_problem_report_' . $id;
$problemUploadSubDir  = 'problem_report/' . saturne_get_upload_token($problemUploadContext);

// Trip photo/voice comments (issue #446): per-session temp dir keyed by an upload token
$tripUploadContext = 'dolicar_vehicle_trip_' . $id;
$tripUploadSubDir  = 'vehicle_trip/' . saturne_get_upload_token($tripUploadContext);

// Repair photo/voice comments: per-session temp dir keyed by an upload token (same pattern as the trip media)
$repairUploadContext = 'dolicar_vehicle_repair_' . $id;
$repairUploadSubDir  = 'vehicle_repair/' . saturne_get_upload_token($repairUploadContext);

/**
 * Grant the "view all third parties" right to the (anonymous) public user.
 *
 * select_thirdparty_list() and selectcontacts() restrict their lists to the third parties
 * assigned to the current user. On this public page $user is anonymous (id 0), so without this
 * the third-party and contact pickers would come back empty.
 *
 * @param  User $user Public anonymous user
 * @return void
 */
function dolicarGrantThirdpartyView(User $user): void
{
    if (empty($user->rights)) {
        $user->rights = new stdClass();
    }
    if (empty($user->rights->societe)) {
        $user->rights->societe = new stdClass();
    }
    if (empty($user->rights->societe->client)) {
        $user->rights->societe->client = new stdClass();
    }
    $user->rights->societe->client->voir = 1;
}

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $actionComm, $action); // Note that $action and $project may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Download the "before/after" state sheet PDF of a single trip (issue #457 — public history list)
    if ($action == 'download_trip_pdf' && getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
        $tripId    = GETPOSTINT('trip_id');
        $tripCheck = new ActionComm($db);
        // The trip must belong to this vehicle's lot and be a public logbook trip (no cross-vehicle access)
        if ($tripId > 0 && $registrationCertificateFR->id > 0 && $tripCheck->fetch($tripId) > 0
            && $tripCheck->code === 'AC_PRODUCTLOT_ADD_PUBLIC_VEHICLE_LOG_BOOK'
            && (int) $tripCheck->fk_element === (int) $productLot->id) {
            require_once __DIR__ . '/../../core/modules/dolicar/dolicardocuments/vehiclelogbookdocument/pdf_vehiclestatesheet.modules.php';

            $stateSheet = new pdf_vehiclestatesheet($db);
            $genResult  = $stateSheet->write_file($registrationCertificateFR, $langs, '', 0, 0, 0, ['object' => $registrationCertificateFR, 'user' => $user, 'trip_id' => $tripId]);

            if ($genResult > 0 && !empty($stateSheet->result['fullpath']) && is_file($stateSheet->result['fullpath'])) {
                $pdfFile = $stateSheet->result['fullpath'];
                top_httphead('application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($pdfFile) . '"');
                header('Content-Length: ' . filesize($pdfFile));
                readfile($pdfFile);
                exit;
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&view=history');
        exit;
    }

    // Problem report — photo upload posted by the Saturne media block (issue #443)
    if ($action == 'uploadPhoto' && !empty($conf->global->MAIN_UPLOAD_DOC)) {
        $uploadSubDir = GETPOST('sub_dir', 'alpha');
        if (!empty($uploadSubDir) && strpos($uploadSubDir, '..') === false) {
            $uploadDir = $conf->dolicar->dir_output . '/' . $uploadSubDir;
            if (!dol_is_dir($uploadDir)) {
                dol_mkdir($uploadDir);
            }

            $invalidFile   = false;
            $uploadedFiles = isset($_FILES['userfile']) ? $_FILES['userfile'] : [];
            if (!empty($uploadedFiles['tmp_name'])) {
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

            if (!$invalidFile) {
                dol_add_file_process($uploadDir, 0, 1, 'userfile', '', null, '', 1);
            }
        }
    }

    // Problem report — voice memo upload posted by the Saturne media block (issue #443)
    if ($action == 'add_audio') {
        $uploadSubDir = GETPOST('sub_dir', 'alpha');
        if (!empty($_FILES['audio']['tmp_name']) && !empty($uploadSubDir) && strpos($uploadSubDir, '..') === false) {
            $uploadDir = $conf->dolicar->dir_output . '/' . $uploadSubDir;
            if (!dol_is_dir($uploadDir)) {
                dol_mkdir($uploadDir);
            }
            $destFile = $uploadDir . '/' . dol_print_date(dol_now(), 'dayhourlog') . '_audio.wav';
            move_uploaded_file($_FILES['audio']['tmp_name'], $destFile);
        }
    }

    // Problem report — final submission: create the event, attach the media and email the manager (issue #443)
    if ($action == 'report_problem') {
        $comment = GETPOST('problem_comment', 'restricthtml');

        $photoFiles = saturne_get_media_files('dolicar', $problemUploadSubDir, '', '', ['type' => 'image']);
        $audioFiles = saturne_get_media_files('dolicar', $problemUploadSubDir, '', '', ['type' => 'audio']);

        // Trace the problem as an event on the vehicle lot (visible in the vehicle history)
        $problemAction               = new ActionComm($db);
        $problemAction->elementtype  = $productLot->element;
        $problemAction->type_code    = 'AC_OTH';
        $problemAction->code         = 'AC_' . strtoupper($productLot->element) . '_REPORT_PROBLEM';
        $problemAction->fk_element   = $productLot->id;
        $problemAction->datep        = dol_now();
        $problemAction->percentage   = -1;
        $problemAction->userownerid  = 0;
        $problemAction->label        = $langs->transnoentities('ProblemReportedOnVehicle', $registrationCertificateFR->a_registration_number);
        $problemAction->note_private = $comment;
        $problemActionID              = $problemAction->create($user);

        // Move the uploaded media from the temp dir to a permanent location keyed by the event
        $finalDir = $conf->dolicar->dir_output . '/problem_report/' . ($problemActionID > 0 ? (int) $problemActionID : dol_print_date(dol_now(), 'dayhourlog'));
        if (!dol_is_dir($finalDir)) {
            dol_mkdir($finalDir);
        }
        $attachmentPaths = [];
        $attachmentNames = [];
        $attachmentMimes = [];
        foreach (array_merge($photoFiles, $audioFiles) as $mediaFile) {
            $destPath = $finalDir . '/' . $mediaFile['name'];
            if (dol_move($mediaFile['fullname'], $destPath, 0, 1, 0, 0)) {
                $attachmentPaths[] = $destPath;
                $attachmentNames[] = $mediaFile['name'];
                $attachmentMimes[] = dol_mimetype($mediaFile['name']);
            }
        }

        // Email the manager
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $sendTo = getDolGlobalString('DOLICAR_PROBLEM_REPORT_EMAIL');
        if (empty($sendTo)) {
            $sendTo = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
        }
        if (!empty($sendTo)) {
            $from        = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
            $vehicleLink = dol_buildpath('/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 2) . '?id=' . (int) $registrationCertificateFR->id;
            $subject     = $langs->transnoentities('ProblemReportEmailSubject', $registrationCertificateFR->a_registration_number);

            $body  = '<p>' . $langs->transnoentities('ProblemReportEmailIntro', $registrationCertificateFR->a_registration_number) . '</p>';
            $body .= '<ul>';
            $body .= '<li>' . $langs->transnoentities('RegistrationPlate') . ' : <strong>' . dol_escape_htmltag($registrationCertificateFR->a_registration_number) . '</strong></li>';
            $body .= '<li>' . $langs->transnoentities('Vehicle') . ' : ' . dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model) . '</li>';
            $body .= '<li>' . $langs->transnoentities('Date') . ' : ' . dol_print_date(dol_now(), 'dayhour') . '</li>';
            $body .= '<li>' . $langs->transnoentities('Comment') . ' : ' . dol_escape_htmltag($comment) . '</li>';
            $body .= '</ul>';
            $body .= '<p><a href="' . $vehicleLink . '">' . $langs->transnoentities('AccessVehicleSheet') . '</a></p>';

            $mailFile = new CMailFile($subject, $sendTo, $from, $body, $attachmentPaths, $attachmentMimes, $attachmentNames, '', '', 0, 1);
            $mailFile->sendfile();
        }

        // Clean the temp token + dir
        saturne_invalidate_upload_token($problemUploadContext, 'dolicar', 'problem_report');

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&success=1&problem=1');
        exit;
    }

    // Repair — create an event in the vehicle history (logbook) with optional photo/voice comments
    if ($action == 'add_repair') {
        $repairDate    = dol_stringtotime(GETPOST('repair_date'));
        $repairMileage = GETPOSTINT('repair_mileage');
        $repairComment = GETPOST('repair_comment', 'restricthtml');

        $repairAction               = new ActionComm($db);
        $repairAction->elementtype  = $productLot->element;
        $repairAction->type_code    = 'AC_OTH';
        $repairAction->code         = 'AC_' . strtoupper($productLot->element) . '_ADD_REPAIR';
        $repairAction->fk_element   = $productLot->id;
        $repairAction->datep        = $repairDate ?: dol_now();
        $repairAction->percentage   = -1;
        $repairAction->userownerid  = 0;
        $repairAction->label        = $langs->transnoentities('RepairOnVehicle', $registrationCertificateFR->a_registration_number);
        $repairAction->note_private = $repairComment;

        $repairActionID = $repairAction->create($user);

        if ($repairActionID > 0) {
            if ($repairMileage > 0) {
                $repairAction->array_options['options_starting_mileage'] = $repairMileage;
                $repairAction->insertExtraFields();
            }

            // Move the uploaded photo/voice comments from the temp dir to a permanent location keyed by the event
            $repairMediaFiles = saturne_get_media_files('dolicar', $repairUploadSubDir);
            if (!empty($repairMediaFiles)) {
                $repairFinalDir = $conf->dolicar->dir_output . '/vehicle_repair/' . (int) $repairActionID;
                if (!dol_is_dir($repairFinalDir)) {
                    dol_mkdir($repairFinalDir);
                }
                foreach ($repairMediaFiles as $repairMediaFile) {
                    dol_move($repairMediaFile['fullname'], $repairFinalDir . '/' . $repairMediaFile['name'], 0, 1, 0, 0);
                }
            }
            saturne_invalidate_upload_token($repairUploadContext, 'dolicar', 'vehicle_repair');
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&success=1&repair=1');
        exit;
    }

    // Control — create a DigiQuali control linked to the vehicle, then redirect to the public answer page to fill it in
    if ($action == 'create_control' && $isModEnabledDigiquali) {
        $fkSheet          = GETPOSTINT('fk_sheet');
        $fkUserController  = GETPOSTINT('fk_user_controller');

        if ($fkSheet <= 0 || $fkUserController <= 0) {
            setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities($fkSheet <= 0 ? 'ControlSheet' : 'Controller')), null, 'errors');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&action_type=control');
            exit;
        }

        // Use the chosen controller as the acting user so fk_user_creat / fk_user_controller stay valid on this NOLOGIN page
        $controllerUser = new User($db);
        $controllerUser->fetch($fkUserController);

        $newControl                     = new Control($db);
        $newControl->fk_sheet           = $fkSheet;
        $newControl->fk_user_controller = $fkUserController;
        $newControl->control_date       = dol_now();
        $newControl->status             = Control::STATUS_DRAFT;
        $newControl->entity             = $conf->entity;
        $newControl->label              = $langs->transnoentities('ControlOnVehicle', $registrationCertificateFR->a_registration_number);

        $newControlID = $newControl->create($controllerUser);
        if ($newControlID > 0) {
            // Link the control to the vehicle lot (stored as sourcetype = productbatch / targettype = digiquali_control)
            $newControl->add_object_linked('productbatch', $id);

            $answerUrl = dol_buildpath('custom/digiquali/public/public_answer.php?track_id=' . $newControl->track_id . '&object_type=control&document_type=ControlDocument&entity=' . $entity, 3);
            header('Location: ' . $answerUrl);
            exit;
        }

        setEventMessages($newControl->error, $newControl->errors, 'errors');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&action_type=control');
        exit;
    }

    if ($action == 'get_registration_number') {
        $registrationNumber = GETPOST('registration_number');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?registration_number=' . $registrationNumber . '&entity=' . $entity);
        exit;
    }

    // External driver — return the native <option> list of a third party's contacts so the
    // contact select2 can be refreshed when the third party changes (uses Form::selectcontacts).
    if ($action == 'get_contacts') {
        $socid = GETPOSTINT('socid');
        if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE') && $socid > 0) {
            dolicarGrantThirdpartyView($user);
            $contactForm = new Form($db);
            echo $contactForm->selectcontacts($socid, '', 'driver_contact_id', 1, '', '', 0, '', 1);
        }
        exit;
    }

    if ($action == 'add') {
        // Resolve driver full name: internal user (select_dolusers) or external third-party contact
        $driverType = GETPOST('driver_type', 'aZ09');
        $driverName = '';
        if ($driverType === 'external') {
            $driverContactId = GETPOSTINT('driver_contact_id');
            if ($driverContactId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $driverContact = new Contact($db);
                $driverContact->fetch($driverContactId);
                $driverName = trim($driverContact->firstname . ' ' . $driverContact->lastname);
            }
            if ($driverName === '') {
                $driverSocid = GETPOSTINT('driver_socid');
                if ($driverSocid > 0) {
                    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                    $driverSoc = new Societe($db);
                    $driverSoc->fetch($driverSocid);
                    $driverName = $driverSoc->name;
                }
            }
        } else {
            $driverUserId = GETPOSTINT('driver_user_id');
            if ($driverUserId > 0) {
                $driverUserObj = new User($db);
                $driverUserObj->fetch($driverUserId);
                $driverName = trim($driverUserObj->firstname . ' ' . $driverUserObj->lastname);
            }
        }

        if (empty($lastUnfinishedActionComm)) {
            $actionComm->elementtype = $productLot->element;
            $actionComm->type_code   = 'AC_PUBLIC';
            $actionComm->code        = 'AC_' . strtoupper($productLot->element) . '_ADD_PUBLIC_VEHICLE_LOG_BOOK';
            $actionComm->datep       = dol_stringtotime(GETPOST('start_date_and_hour'));
            $actionComm->datef       = dol_stringtotime(GETPOST('end_date_and_hour'));
            $actionComm->fk_element  = $productLot->id;
            $actionComm->userownerid = 0;
            $actionComm->percentage  = -1;

            // The client can set HTTP header information (like $_SERVER['HTTP_CLIENT_IP'] ...) to any arbitrary value it wants. As such it's far more reliable to use $_SERVER['REMOTE_ADDR'], as this cannot be set by the user
            $actionComm->note_private  = (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ? $langs->transnoentities('IPAddress') . ' : ' . $_SERVER['REMOTE_ADDR'] . '<br>' : $langs->transnoentities('NoData'));
            $actionComm->note_private .= $langs->transnoentities('Driver') . ' : ' . $driverName . '<br>';
            $actionComm->note_private .= GETPOSTISSET('start_comment') && !empty(GETPOST('start_comment', 'restricthtml')) ? $langs->transnoentities('StartComment') . ' : ' . GETPOST('start_comment', 'restricthtml') . '<br>' : '';
            $actionComm->note_private .= GETPOSTISSET('end_comment') && !empty(GETPOST('end_comment', 'restricthtml')) ? $langs->transnoentities('EndComment') . ' : ' . GETPOST('end_comment', 'restricthtml') : '';
            $actionComm->label         = $langs->transnoentities('ObjectAddPublicVehicleLogBook', $registrationCertificateFR->a_registration_number, $productLot->batch);

            $actionComm->array_options['json'] = json_encode(['driver' => $driverName, 'fuel_level' => GETPOST('options_fuel_level', 'alpha'), 'start_comment' => GETPOST('start_comment', 'restricthtml'), 'end_comment' => GETPOST('end_comment', 'restricthtml')]);

            $extraFields->setOptionalsFromPost([], $actionComm);

            $actionCommID = $actionComm->create($user);
        } else {
            $lastUnfinishedActionComm[0]->datef         = dol_stringtotime(GETPOST('end_date_and_hour'));
            $lastUnfinishedActionComm[0]->note_private .= GETPOSTISSET('end_comment') && !empty(GETPOST('end_comment', 'restricthtml')) ? '<br>' . $langs->transnoentities('EndComment') . ' : ' . GETPOST('end_comment', 'restricthtml') : '';

            $lastUnfinishedActionComm[0]->array_options['options_arrival_mileage'] = GETPOST('options_arrival_mileage');
            $lastUnfinishedActionComm[0]->updateExtraField('arrival_mileage');

            $existingJson = json_decode($lastUnfinishedActionComm[0]->array_options['options_json'] ?? '{}', true);
            $existingJson['return_fuel_level'] = GETPOST('options_fuel_level', 'alpha');
            $existingJson['end_comment']       = GETPOST('end_comment', 'restricthtml');
            $lastUnfinishedActionComm[0]->array_options['options_json'] = json_encode($existingJson);
            $lastUnfinishedActionComm[0]->updateExtraField('json');

            $lastUnfinishedActionComm[0]->update($user);
        }

        if ($publicInterfaceUseSignatory && (isset($actionCommID) || isset($lastUnfinishedActionComm[0]))) {
            $signatory->status    = SaturneSignature::STATUS_SIGNED;
            $signatory->role      = $langs->transnoentities('Driver');
            $signatory->firstname = $lastUnfinishedActionCommJSON['driver'] ?? $driverName;

            $signatory->signature_date = dol_now();
            // The JS sends the data URI wrapped with JSON.stringify. GETPOST default ('alphanohtml') strips the quotes and never json_decodes,
            // so we read it raw and decode it ourselves (same intent as the standard Saturne signature flow).
            $postedSignature      = json_decode(GETPOST('signature', 'none'));
            $signatory->signature = is_string($postedSignature) ? $postedSignature : '';

            $signatory->element_type  = 'user';
            $signatory->element_id    = 0;
            $signatory->object_type   = 'actiocomm';
            $signatory->fk_object     = $actionCommID ?? (isset($lastUnfinishedActionComm[0]) ? $lastUnfinishedActionComm[0]->id : null);
            $signatory->module_name   = 'dolicar';
            $signatory->signature_url = generate_random_id(); // Mandatory: column has a UNIQUE index, must not stay empty

            $signatory->create($user);
        }

        // Attach the uploaded photo/voice comments to the trip event (issue #446)
        $tripActionId = $actionCommID ?? (isset($lastUnfinishedActionComm[0]) ? $lastUnfinishedActionComm[0]->id : 0);
        if ($tripActionId > 0) {
            $tripMediaFiles = saturne_get_media_files('dolicar', $tripUploadSubDir);
            if (!empty($tripMediaFiles)) {
                // Issue #457: keep départ and retour media in separate subfolders so the vehicle
                // state/photos PDF can render them apart. Creating the trip = départ, updating the
                // unfinished trip on return = retour.
                $tripPhase    = isset($actionCommID) ? 'depart' : 'retour';
                $tripFinalDir = $conf->dolicar->dir_output . '/vehicle_trip/' . (int) $tripActionId . '/' . $tripPhase;
                if (!dol_is_dir($tripFinalDir)) {
                    dol_mkdir($tripFinalDir);
                }
                foreach ($tripMediaFiles as $tripMediaFile) {
                    dol_move($tripMediaFile['fullname'], $tripFinalDir . '/' . $tripMediaFile['name'], 0, 1, 0, 0);
                }
            }
            saturne_invalidate_upload_token($tripUploadContext, 'dolicar', 'vehicle_trip');
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . $entity . '&success=1');
        exit;
    }
}

/*
 * View
 */

// Determine vehicle state and which screen to show
$isVehicleOut = !empty($lastUnfinishedActionComm);

// Form instance for native Dolibarr selectors
$form = new Form($db);

// Pre-select driver from cookie
$preselectedDriverId = isset($_COOKIE['plv2_driver_id']) ? (int) $_COOKIE['plv2_driver_id'] : 0;

// This NOLOGIN page never loads $user, but a Dolibarr session may still be active (e.g. a manager
// opening the page while logged in). Default the control "controller" picker to that session user.
$loggedUserId = 0;
if (!empty($_SESSION['dol_login'])) {
    $sessionUser = new User($db);
    if ($sessionUser->fetch(0, $_SESSION['dol_login']) > 0) {
        $loggedUserId = (int) $sessionUser->id;
    }
}

$view = GETPOST('view', 'aZ09');

if ($success) {
    $showScreen  = 'success';
    $successType = $isVehicleOut ? 'depart' : 'retour';
} elseif ($view === 'list') {
    $showScreen = 'list';
} elseif ($view === 'history' && $id > 0 && $registrationCertificateFR->id > 0) {
    $showScreen = 'history';
} elseif ($id > 0 && $registrationCertificateFR->id > 0) {
    if ($actionType === 'probleme') {
        $showScreen = 'problem';
    } elseif ($actionType === 'reparation') {
        $showScreen = 'repair';
    } elseif ($actionType === 'control' && $isModEnabledDigiquali) {
        $showScreen = 'control';
    } else {
        $showScreen = in_array($actionType, ['depart', 'retour']) ? 'form' : 'vehicle';
    }
} else {
    $showScreen = 'search';
}

// Control screen — list the DigiQuali sheets applicable to a vehicle (control type, locked, linked to productbatch)
$controlSheets = [];
if ($showScreen === 'control' && $isModEnabledDigiquali) {
    // Sheets store their linkable object types as a JSON key — for vehicles it's "productlot" (same filter as registrationcertificatefr_card.php)
    $fetchedSheets = saturne_fetch_all_object_type('Sheet', 'ASC', 't.ref', 0, 0, ['customsql' => "t.type = 'control' AND t.status = " . Sheet::STATUS_LOCKED . " AND t.element_linked LIKE '%\"productlot\"%'"]);
    if (is_array($fetchedSheets)) {
        $controlSheets = $fetchedSheets;
    }
}

// Remember the last opened vehicle so the bottom bar's "current vehicle" tab stays reachable
if ($id > 0) {
    setcookie('plv2_current_id', (string) $id, time() + 31536000, '/');
}
$currentVehicleId = $id > 0 ? $id : (isset($_COOKIE['plv2_current_id']) ? (int) $_COOKIE['plv2_current_id'] : 0);

// Resolve the registration plate of the "current vehicle" so the bottom bar tab shows it instead of a generic label
$currentVehiclePlate = '';
if ($currentVehicleId > 0) {
    if ($registrationCertificateFR->id > 0 && (int) $registrationCertificateFR->fk_lot === $currentVehicleId) {
        $currentVehiclePlate = $registrationCertificateFR->a_registration_number;
    } else {
        $currentVehicleCert = new RegistrationCertificateFr($db);
        if ($currentVehicleCert->fetch(0, '', ' AND fk_lot = ' . $currentVehicleId) > 0) {
            $currentVehiclePlate = $currentVehicleCert->a_registration_number;
        }
    }
}

// Bottom navigation bar — on the browsing screens plus the repair/control tabs (not on the depart/retour/problem/success flows)
$showBottomBar = in_array($showScreen, ['search', 'list', 'vehicle', 'history', 'repair', 'control'], true);

// History screen — full list of this vehicle's trips (departures/returns), newest first
$historyTrips = [];
if ($showScreen === 'history') {
    $historyTrips = $actionComm->getActions(0, $id, 'productlot', ' AND fk_element = ' . $id . ' AND code = "AC_' . strtoupper($productLot->element) . '_ADD_PUBLIC_VEHICLE_LOG_BOOK"', 'datep', 'DESC');
    if (!is_array($historyTrips)) {
        $historyTrips = [];
    }
    foreach ($historyTrips as $historyTrip) {
        $historyTrip->fetch_optionals();
    }
}

// Public list of registration certificates (cards linking to ?id=fk_lot)
$allCertificates = [];
if ($showScreen === 'list' && getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
    $listCertificate = new RegistrationCertificateFr($db);
    $fetchedCertificates = $listCertificate->fetchAll('ASC', 'a_registration_number', 0, 0, []);
    if (is_array($fetchedCertificates)) {
        $allCertificates = $fetchedCertificates;
    }
}

$title   = $langs->trans('PublicVehicleLogBook');
$moreJS  = ['/custom/saturne/js/includes/signature-pad.min.js'];
// select2 styling lives in the Dolibarr theme CSS, which this lightweight public page does not load.
// Load select2's own stylesheet so the native pickers (driver / third party / contact) render correctly.
$moreCSS = ['/custom/saturne/css/pico.min.css', '/includes/jquery/plugins/select2/dist/css/select2.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

// Focused form screens get an extra body class so their submit button is pinned to the bottom of the
// viewport even when the form is short. page-with-bottombar additionally keeps the submit area above
// the fixed bottom navigation bar on the repair/control screens — see _vehicle-logbook.scss.
$bodyCssClass = 'page-public-card page-signature';
if (in_array($showScreen, ['form', 'problem', 'repair', 'control'], true)) {
    $bodyCssClass .= ' page-focused-form';
}
if ($showBottomBar) {
    $bodyCssClass .= ' page-with-bottombar';
}

saturne_header(1, '', $title, '', '', 0, 0, $moreJS, $moreCSS, '', $bodyCssClass);

$baseUrl     = $_SERVER['PHP_SELF'] . '?entity=' . urlencode($entity);
$vehicleUrl  = $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . urlencode($entity);
$logoUrl     = DOL_URL_ROOT . '/custom/dolicar/img/dolicar_color.svg'; ?>

<div class="plv2-app">

<?php
// Render the screen matching $showScreen. Each screen lives in its own template under
// core/tpl/public_vehicle_logbook_<screen>.tpl.php and inherits this page's scope.
$publicLogbookTplDir = __DIR__ . '/../../core/tpl/';
if (!getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
    include $publicLogbookTplDir . 'public_vehicle_logbook_forbidden.tpl.php';
} else {
    $publicLogbookScreenTpl = $publicLogbookTplDir . 'public_vehicle_logbook_' . $showScreen . '.tpl.php';
    if (is_readable($publicLogbookScreenTpl)) {
        include $publicLogbookScreenTpl;
    }
}

if ($showBottomBar) {
    include $publicLogbookTplDir . 'public_vehicle_logbook_bottombar.tpl.php';
}
?>

</div>

<script>
function plv2SelectFuel(btn) {
    $(btn).closest('.plv2-fuel-btns').find('.plv2-fuel-btn').removeClass('active');
    $(btn).addClass('active');
    $('#plv2-fuel-value').val($(btn).data('value'));
}

$(document).on('change', '#driver_user_id', function () {
    var driverId = $(this).val();
    if (driverId) {
        document.cookie = 'plv2_driver_id=' + encodeURIComponent(driverId) + '; path=/; max-age=31536000; SameSite=Lax';
    }
});

// Live client-side filter of the registration certificates list
$(document).on('input', '#plv2-cg-search', function () {
    var query   = $(this).val().toLowerCase().trim();
    var visible = 0;
    $('.plv2-cg-item').each(function () {
        var match = ($(this).attr('data-search') || '').indexOf(query) !== -1;
        $(this).toggle(match);
        if (match) {
            visible++;
        }
    });
    $('.plv2-cg-noresult').toggle(visible === 0);
});

// Driver type toggle: internal user picker vs external third-party + contact pickers
$(document).on('click', '#plv2-driver-type .plv2-seg__btn', function () {
    var type = $(this).data('type');
    $('#plv2-driver-type .plv2-seg__btn').removeClass('active');
    $(this).addClass('active');
    $('#plv2-driver-type-value').val(type);
    $('#plv2-driver-internal').toggle(type === 'internal');
    $('#plv2-driver-external').toggle(type === 'external');
});

// When the third party changes, refresh the native contact select2 with that company's contacts
$(document).on('change', '#driver_socid', function () {
    var socid    = $(this).val();
    var $contact = $('#driver_contact_id');
    if (!socid || socid === '-1') {
        return;
    }
    $.get('<?php echo dol_escape_js($_SERVER['PHP_SELF']); ?>', { action: 'get_contacts', socid: socid, entity: '<?php echo dol_escape_js((string) $entity); ?>' }, function (html) {
        // Replace the <option>s (returned by Form::selectcontacts) and tell select2 to re-read them
        $contact.html(html).trigger('change');
    });
});
</script>

<?php
llxFooter('', 'public');
$db->close();
