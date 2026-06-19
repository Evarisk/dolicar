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

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $actionComm, $action); // Note that $action and $project may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
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
        $problemAction->note_private = (!empty($_SERVER['REMOTE_ADDR']) ? $langs->transnoentities('IPAddress') . ' : ' . $_SERVER['REMOTE_ADDR'] . '<br>' : '');
        $problemAction->note_private .= $langs->transnoentities('Comment') . ' : ' . $comment;
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

    if ($action == 'get_registration_number') {
        $registrationNumber = GETPOST('registration_number');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?registration_number=' . $registrationNumber . '&entity=' . $entity);
        exit;
    }

    if ($action == 'add') {
        // Resolve driver full name from user ID posted by select_dolusers
        $driverUserId = GETPOSTINT('driver_user_id');
        $driverName   = '';
        if ($driverUserId > 0) {
            $driverUserObj = new User($db);
            $driverUserObj->fetch($driverUserId);
            $driverName = trim($driverUserObj->firstname . ' ' . $driverUserObj->lastname);
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

if ($success) {
    $showScreen  = 'success';
    $successType = $isVehicleOut ? 'depart' : 'retour';
} elseif ($id > 0 && $registrationCertificateFR->id > 0) {
    if ($actionType === 'probleme') {
        $showScreen = 'problem';
    } else {
        $showScreen = in_array($actionType, ['depart', 'retour']) ? 'form' : 'vehicle';
    }
} else {
    $showScreen = 'search';
}

$title   = $langs->trans('PublicVehicleLogBook');
$moreJS  = ['/custom/saturne/js/includes/signature-pad.min.js'];
$moreCSS = ['/custom/saturne/css/pico.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(1, '', $title, '', '', 0, 0, $moreJS, $moreCSS, '', 'page-public-card page-signature');

$baseUrl     = $_SERVER['PHP_SELF'] . '?entity=' . urlencode($entity);
$vehicleUrl  = $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . urlencode($entity);
$logoUrl     = DOL_URL_ROOT . '/custom/dolicar/img/dolicar_color.svg'; ?>

<div class="plv2-app">

<?php if (!getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) : ?>
    <div class="plv2-forbidden">
        <i class="fas fa-lock"></i>
        <p><?php echo $langs->trans('PublicInterfaceForbidden', $langs->transnoentities('OfPublicVehicleLogBook')); ?></p>
    </div>

<?php elseif ($showScreen === 'search') : ?>
    <!-- ===== SCREEN 1 : saisie plaque ===== -->
    <div class="plv2-header plv2-header--dark">
        <div class="plv2-header__top">
            <div class="plv2-header__logo">
                <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
                <div class="plv2-header__logo-text">
                    DoliCar
                    <small><?php echo $langs->trans('PublicVehicleLogBook'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="plv2-content">
        <?php if (!empty($registrationNumber) && empty($registrationCertificateFR->id)) : ?>
            <div class="plv2-notice plv2-notice--error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong><?php echo $langs->trans('PlateNotFound'); ?></strong>
                    <p><?php echo $langs->trans('LicencePlateNotFoundInDB'); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $baseUrl; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="get_registration_number">
            <div class="plv2-card plv2-plate-card">
                <div class="plv2-plate-card__icon"><i class="fas fa-id-card"></i></div>
                <h2><?php echo $langs->trans('EnterPlate'); ?></h2>
                <p><?php echo $langs->trans('QRCodeMayHavePrefilled'); ?></p>
                <input type="text"
                       name="registration_number"
                       class="plv2-plate-input"
                       placeholder="AB-123-CD"
                       value="<?php echo dol_escape_htmltag($registrationNumber); ?>"
                       maxlength="11"
                       required
                       autocomplete="off">
                <button type="submit" class="plv2-btn plv2-btn--primary plv2-btn--full">
                    <i class="fas fa-arrow-right"></i>
                    <?php echo $langs->trans('Continue'); ?>
                </button>
            </div>
        </form>
    </div>

<?php elseif ($showScreen === 'vehicle') : ?>
    <!-- ===== SCREEN 2 : fiche véhicule + choix action ===== -->
    <div class="plv2-header plv2-header--dark">
        <div class="plv2-header__top">
            <a href="<?php echo $baseUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="plv2-header__logo">
                <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
                <div class="plv2-header__logo-text">
                    DoliCar
                    <small><?php echo $langs->trans('PublicVehicleLogBook'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="plv2-content">
        <div class="plv2-vehicle-card">
            <div class="plv2-vehicle-card__head">
                <div class="plv2-vehicle-card__icon"><i class="fas fa-car"></i></div>
                <div>
                    <h3><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></h3>
                    <span class="plv2-plate-badge"><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number); ?></span>
                </div>
            </div>

            <div class="plv2-vehicle-card__info">
                <div class="plv2-info-grid">
                    <?php if (!empty($registrationCertificateFR->fk_soc)) : ?>
                        <div class="plv2-info-cell">
                            <span class="plv2-info-cell__label"><?php echo $langs->trans('ThirdParty'); ?></span>
                            <span class="plv2-info-cell__value"><?php echo dol_escape_htmltag($registrationCertificateFR->thirdparty->name ?? ''); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($registrationCertificateFR->p3_fuel_type)) : ?>
                        <div class="plv2-info-cell">
                            <span class="plv2-info-cell__label"><?php echo $langs->trans('FuelType'); ?></span>
                            <span class="plv2-info-cell__value"><?php echo dol_escape_htmltag($registrationCertificateFR->p3_fuel_type); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($registrationCertificateFR->b_first_registration_date)) : ?>
                        <div class="plv2-info-cell">
                            <span class="plv2-info-cell__label"><?php echo $langs->trans('FirstRegistrationDate'); ?></span>
                            <span class="plv2-info-cell__value"><?php echo dol_print_date($registrationCertificateFR->b_first_registration_date, 'day'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($lastArrivalMileage)) : ?>
                        <div class="plv2-info-cell">
                            <span class="plv2-info-cell__label"><?php echo $langs->trans('KnownMileage'); ?></span>
                            <span class="plv2-info-cell__value"><?php echo number_format($lastArrivalMileage, 0, ',', ' ') . ' km'; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isVehicleOut) : ?>
                <div class="plv2-vehicle-card__state plv2-vehicle-card__state--out">
                    <i class="fas fa-sign-out-alt"></i>
                    <div>
                        <strong><?php echo $langs->trans('VehicleCurrentlyOut'); ?></strong>
                        <?php if (!empty($lastUnfinishedActionComm[0])) :
                            $driverName = json_decode($lastUnfinishedActionComm[0]->array_options['options_json'] ?? '{}', true)['driver'] ?? '';
                            echo '<span>' . ($driverName ? dol_escape_htmltag($driverName) . ' · ' : '') . dol_print_date($lastUnfinishedActionComm[0]->datep, 'dayhour') . '</span>';
                        endif; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="plv2-vehicle-card__state plv2-vehicle-card__state--in">
                    <i class="fas fa-sign-in-alt"></i>
                    <strong><?php echo $langs->trans('VehicleCurrentlyIn'); ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <p class="plv2-section-label"><?php echo $langs->trans('WhatDoYouWantToDo'); ?></p>

        <div class="plv2-action-buttons">
            <a href="<?php echo $vehicleUrl . '&action_type=depart'; ?>"
               class="plv2-action-btn plv2-action-btn--depart<?php echo $isVehicleOut ? ' plv2-action-btn--disabled' : ''; ?>">
                <div class="plv2-action-btn__icon"><i class="fas fa-sign-out-alt"></i></div>
                <div class="plv2-action-btn__label"><?php echo $langs->trans('TakeVehicle'); ?></div>
                <div class="plv2-action-btn__sub"><?php echo $langs->trans('DeclareDeparture'); ?></div>
            </a>
            <a href="<?php echo $vehicleUrl . '&action_type=retour'; ?>"
               class="plv2-action-btn plv2-action-btn--retour<?php echo !$isVehicleOut ? ' plv2-action-btn--disabled' : ''; ?>">
                <div class="plv2-action-btn__icon"><i class="fas fa-sign-in-alt"></i></div>
                <div class="plv2-action-btn__label"><?php echo $langs->trans('ReturnVehicle'); ?></div>
                <div class="plv2-action-btn__sub"><?php echo $langs->trans('DeclareReturn'); ?></div>
            </a>
            <a href="<?php echo $vehicleUrl . '&action_type=probleme'; ?>"
               class="plv2-action-btn plv2-action-btn--probleme">
                <div class="plv2-action-btn__icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="plv2-action-btn__label"><?php echo $langs->trans('ReportProblem'); ?></div>
                <div class="plv2-action-btn__sub"><?php echo $langs->trans('ReportProblemSub'); ?></div>
            </a>
        </div>

        <?php if (!empty($recentActionComms)) : ?>
            <p class="plv2-section-label"><?php echo $langs->trans('RecentTrips'); ?></p>
            <div class="plv2-history">
                <?php
                $fuelIcons = ['reserve' => 'fa-battery-empty', 'quarter' => 'fa-battery-quarter', 'half' => 'fa-battery-half', 'threequarters' => 'fa-battery-three-quarters', 'full' => 'fa-battery-full'];
                $fuelLabels = ['reserve' => $langs->trans('FuelReserve'), 'quarter' => '1/4', 'half' => '1/2', 'threequarters' => '3/4', 'full' => $langs->trans('FuelFull')];
                foreach ($recentActionComms as $ac) :
                    $acJson      = json_decode($ac->array_options['options_json'] ?? '{}', true);
                    $acDriver    = $acJson['driver'] ?? '—';
                    $acIsOpen    = empty($ac->datef);
                    $acKmStart   = $ac->array_options['options_starting_mileage'] ?? null;
                    $acKmEnd           = $ac->array_options['options_arrival_mileage'] ?? null;
                    $acFuelLevel       = $acJson['fuel_level'] ?? null;
                    $acReturnFuelLevel = $acJson['return_fuel_level'] ?? null;
                    $acStartComment    = $acJson['start_comment'] ?? null;
                    $acEndComment      = $acJson['end_comment'] ?? null;
                ?>
                    <div class="plv2-history-item">
                        <div class="plv2-history-item__head">
                            <div class="plv2-history-item__driver">
                                <i class="fas fa-user-circle"></i>
                                <?php echo dol_escape_htmltag($acDriver); ?>
                            </div>
                            <span class="plv2-badge <?php echo $acIsOpen ? 'plv2-badge--out' : 'plv2-badge--done'; ?>">
                                <?php echo $acIsOpen ? $langs->trans('TripInProgress') : $langs->trans('TripDone'); ?>
                            </span>
                        </div>
                        <div class="plv2-history-item__row">
                            <span class="plv2-history-item__type plv2-history-item__type--depart">
                                <i class="fas fa-sign-out-alt"></i> <?php echo $langs->trans('Departure'); ?>
                            </span>
                            <span><?php echo dol_print_date($ac->datep, 'dayhour'); ?></span>
                            <?php if (!empty($acKmStart)) : ?>
                                <span class="plv2-history-item__km"><?php echo number_format((int) $acKmStart, 0, ',', ' ') . ' km'; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($acFuelLevel) && isset($fuelIcons[$acFuelLevel])) : ?>
                                <span class="plv2-history-item__fuel">
                                    <i class="fas <?php echo $fuelIcons[$acFuelLevel]; ?>"></i>
                                    <?php echo $fuelLabels[$acFuelLevel]; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($acStartComment)) : ?>
                            <div class="plv2-history-item__comment">
                                <i class="fas fa-comment-dots"></i>
                                <?php echo dol_escape_htmltag($acStartComment); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!$acIsOpen) : ?>
                            <div class="plv2-history-item__row">
                                <span class="plv2-history-item__type plv2-history-item__type--retour">
                                    <i class="fas fa-sign-in-alt"></i> <?php echo $langs->trans('Return'); ?>
                                </span>
                                <span><?php echo dol_print_date($ac->datef, 'dayhour'); ?></span>
                                <?php if (!empty($acKmEnd)) : ?>
                                    <span class="plv2-history-item__km"><?php echo number_format((int) $acKmEnd, 0, ',', ' ') . ' km'; ?></span>
                                <?php endif; ?>
                                <?php if (!empty($acReturnFuelLevel) && isset($fuelIcons[$acReturnFuelLevel])) : ?>
                                    <span class="plv2-history-item__fuel">
                                        <i class="fas <?php echo $fuelIcons[$acReturnFuelLevel]; ?>"></i>
                                        <?php echo $fuelLabels[$acReturnFuelLevel]; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($acEndComment)) : ?>
                                <div class="plv2-history-item__comment">
                                    <i class="fas fa-comment-dots"></i>
                                    <?php echo dol_escape_htmltag($acEndComment); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($showScreen === 'form') : ?>
    <!-- ===== SCREEN 3 : formulaire départ / retour ===== -->
    <?php $isDepart = ($actionType === 'depart'); ?>
    <div class="plv2-header plv2-header--dark">
        <div class="plv2-header__top">
            <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="plv2-header__logo">
                <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
                <div class="plv2-header__logo-text">
                    <span class="plv2-action-badge <?php echo $isDepart ? 'plv2-action-badge--depart' : 'plv2-action-badge--retour'; ?>">
                        <?php echo $isDepart ? $langs->trans('Departure') : $langs->trans('Return'); ?>
                    </span>
                    <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
                </div>
            </div>
        </div>
    </div>

    <form id="public-vehicle-log-book-form" method="POST" action="<?php echo $vehicleUrl; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="action_type" value="<?php echo dol_escape_htmltag($actionType); ?>">

        <div class="plv2-form">
            <!-- Identité -->
            <?php if ($isDepart) : ?>
                <div class="plv2-card">
                    <h3><i class="fas fa-user"></i> <?php echo $langs->trans('WhoAreYou'); ?></h3>
                    <div class="plv2-form-group">
                        <label><?php echo $langs->trans('Driver'); ?> <span class="plv2-req">*</span></label>
                        <?php echo $form->select_dolusers($preselectedDriverId, 'driver_user_id', 1, null, 0, '', '', (string) $conf->entity); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Date / Heure -->
            <div class="plv2-card">
                <h3><i class="fas fa-calendar"></i> <?php echo $langs->trans('When'); ?></h3>
                <div class="plv2-form-group">
                    <label><?php echo $isDepart ? $langs->trans('StartDateAndHour') : $langs->trans('EndDateAndHour'); ?></label>
                    <input type="datetime-local"
                           name="<?php echo $isDepart ? 'start_date_and_hour' : 'end_date_and_hour'; ?>"
                           value="<?php echo dol_print_date(dol_now(), '%Y-%m-%dT%H:%M'); ?>"
                           required>
                </div>
            </div>

            <!-- Kilométrage -->
            <div class="plv2-card">
                <h3><i class="fas fa-gauge"></i> <?php echo $langs->trans('Mileage'); ?></h3>
                <div class="plv2-form-group">
                    <label>
                        <?php echo $isDepart ? $langs->trans('StartingMileage') : $langs->trans('ArrivalMileage'); ?>
                        <span class="plv2-req">*</span>
                    </label>
                    <?php if ($isDepart) :
                        $minKm = $lastArrivalMileage ?? 0; ?>
                        <input type="number"
                               name="options_starting_mileage"
                               class="plv2-km-input"
                               min="<?php echo $minKm; ?>"
                               placeholder="000000"
                               required>
                        <?php if ($minKm > 0) : ?>
                            <div class="plv2-km-hint">
                                <?php echo $langs->trans('LastKnownMileage'); ?> : <strong><?php echo number_format($minKm, 0, ',', ' ') . ' km'; ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php else :
                        $startKm    = (int) ($lastUnfinishedActionComm[0]->array_options['options_starting_mileage'] ?? 0);
                        $minKmRetour = $startKm > 0 ? $startKm + 1 : 0;
                        $maxKmRetour = $startKm + getDolGlobalInt('DOLICAR_PUBLIC_MAX_ARRIVAL_MILEAGE', 1000); ?>
                        <input type="number"
                               name="options_arrival_mileage"
                               class="plv2-km-input"
                               min="<?php echo $minKmRetour; ?>"
                               max="<?php echo $maxKmRetour; ?>"
                               placeholder="000000"
                               required>
                        <?php if ($startKm > 0) : ?>
                            <div class="plv2-km-hint">
                                <?php echo $langs->trans('DepartureMileage'); ?> : <strong><?php echo number_format($startKm, 0, ',', ' ') . ' km'; ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Niveau de carburant -->
            <div class="plv2-card">
                <h3><i class="fas fa-gas-pump"></i> <?php echo $langs->trans('FuelLevel'); ?></h3>
                <div class="plv2-fuel-btns">
                    <?php
                    $fuelLevels = [
                        'reserve'     => ['label' => $langs->trans('FuelReserve'), 'icon' => 'fa-battery-empty'],
                        'quarter'     => ['label' => '1/4',                         'icon' => 'fa-battery-quarter'],
                        'half'        => ['label' => '1/2',                         'icon' => 'fa-battery-half'],
                        'threequarters' => ['label' => '3/4',                       'icon' => 'fa-battery-three-quarters'],
                        'full'        => ['label' => $langs->trans('FuelFull'),      'icon' => 'fa-battery-full'],
                    ];
                    foreach ($fuelLevels as $val => $data) : ?>
                        <button type="button"
                                class="plv2-fuel-btn"
                                data-value="<?php echo $val; ?>"
                                onclick="plv2SelectFuel(this)">
                            <i class="fas <?php echo $data['icon']; ?>"></i>
                            <span><?php echo $data['label']; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="options_fuel_level" id="plv2-fuel-value">
            </div>

            <!-- Observation -->
            <div class="plv2-card">
                <h3><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Observation'); ?></h3>
                <div class="plv2-form-group">
                    <label><?php echo $langs->trans('Remarks'); ?></label>
                    <textarea name="<?php echo $isDepart ? 'start_comment' : 'end_comment'; ?>"
                              rows="3"
                              placeholder="<?php echo $langs->trans('RemarksPlaceholder'); ?>"></textarea>
                </div>
            </div>

            <!-- Signature -->
            <?php if ($publicInterfaceUseSignatory) : ?>
                <div class="plv2-card">
                    <h3><i class="fas fa-signature"></i> <?php echo $langs->trans('Signature'); ?></h3>
                    <div class="plv2-signature-pad" id="plv2-sig-pad">
                        <canvas class="canvas-container editable canvas-signature"></canvas>
                        <div class="plv2-signature-pad__hint"><?php echo $langs->trans('SignHere'); ?></div>
                        <button type="button"
                                class="signature-erase plv2-erase-btn wpeo-button button-square-40 button-rounded button-grey">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="plv2-submit-area">
            <button type="submit"
                    class="plv2-btn plv2-btn--full <?php echo $isDepart ? 'plv2-btn--primary' : 'plv2-btn--success'; ?> <?php echo $publicInterfaceUseSignatory ? 'wpeo-button no-load public-vehicle-log-book-validate button-grey button-disable' : ''; ?>">
                <i class="fas fa-check"></i>
                <?php echo $isDepart ? $langs->trans('ValidateDeparture') : $langs->trans('ValidateReturn'); ?>
            </button>
        </div>
    </form>

<?php elseif ($showScreen === 'problem') : ?>
    <!-- ===== SCREEN : signaler un problème ===== -->
    <div class="plv2-header plv2-header--dark">
        <div class="plv2-header__top">
            <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="plv2-header__logo">
                <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
                <div class="plv2-header__logo-text">
                    <span class="plv2-action-badge plv2-action-badge--probleme"><?php echo $langs->trans('ReportProblem'); ?></span>
                    <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
                </div>
            </div>
        </div>
    </div>

    <form id="public-vehicle-problem-form" method="POST" action="<?php echo $vehicleUrl; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="report_problem">

        <div class="plv2-form">
            <div class="plv2-card">
                <div class="plv2-form-group">
                    <label for="problem_comment"><?php echo $langs->trans('Comment'); ?></label>
                    <textarea id="problem_comment" name="problem_comment" rows="4" placeholder="<?php echo dol_escape_htmltag($langs->trans('DescribeProblem')); ?>"></textarea>
                </div>
                <div class="plv2-form-group">
                    <label><?php echo $langs->trans('PhotoAndVoiceMemo'); ?></label>
                    <div class="dolicar-problem-media-row">
                        <?php print saturne_render_media_block('dolicar', $problemUploadSubDir, 'problem_', '', ['show_photo' => true, 'show_audio' => true, 'show_file' => false]); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="plv2-submit-area">
            <button type="submit" class="plv2-btn plv2-btn--full plv2-btn--primary">
                <i class="fas fa-paper-plane"></i>
                <?php echo $langs->trans('SendReport'); ?>
            </button>
        </div>
    </form>

<?php elseif ($showScreen === 'success') : ?>
    <!-- ===== SCREEN 4 : confirmation ===== -->
    <?php
    $problemSuccess  = GETPOSTINT('problem');
    $successIsDepart = $isVehicleOut;
    $successAction   = $successIsDepart ? ($lastUnfinishedActionComm[0] ?? null) : ($lastActionComm[0] ?? null);
    ?>
    <div class="plv2-success">
        <?php if ($problemSuccess) : ?>
            <div class="plv2-success__icon plv2-success__icon--green">
                <i class="fas fa-check"></i>
            </div>
            <h2><?php echo $langs->trans('ProblemReportSent'); ?></h2>
            <p><?php echo $langs->trans('ProblemReportSentDesc'); ?></p>

            <div class="plv2-success__recap">
                <div class="plv2-success__row">
                    <span><?php echo $langs->trans('Vehicle'); ?></span>
                    <span><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
                </div>
            </div>
        <?php else : ?>
            <div class="plv2-success__icon <?php echo $successIsDepart ? 'plv2-success__icon--blue' : 'plv2-success__icon--green'; ?>">
                <i class="fas fa-check"></i>
            </div>
            <h2><?php echo $successIsDepart ? $langs->trans('DepartureRecorded') : $langs->trans('ReturnRecorded'); ?></h2>
            <p><?php echo $langs->trans('ActionCommCreatedInAgenda'); ?></p>

            <div class="plv2-success__recap">
                <div class="plv2-success__row">
                    <span><?php echo $langs->trans('Vehicle'); ?></span>
                    <span><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
                </div>
                <div class="plv2-success__row">
                    <span><?php echo $langs->trans('Type'); ?></span>
                    <span class="<?php echo $successIsDepart ? 'plv2-text--blue' : 'plv2-text--green'; ?>">
                        <?php echo $successIsDepart ? $langs->trans('Departure') : $langs->trans('Return'); ?>
                    </span>
                </div>
                <?php if (!empty($successAction)) : ?>
                    <div class="plv2-success__row">
                        <span><?php echo $langs->trans('Date'); ?></span>
                        <span><?php echo dol_print_date($successIsDepart ? $successAction->datep : $successAction->datef, 'dayhour'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="plv2-success__actions">
            <a href="<?php echo $vehicleUrl; ?>" class="plv2-btn plv2-btn--primary plv2-btn--full">
                <?php echo $langs->trans('NewEntry'); ?>
            </a>
            <a href="<?php echo $baseUrl; ?>" class="plv2-btn plv2-btn--ghost plv2-btn--full">
                <?php echo $langs->trans('ClosePublicLogBook'); ?>
            </a>
        </div>
    </div>

<?php endif; ?>

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
</script>

<?php
llxFooter('', 'public');
$db->close();
