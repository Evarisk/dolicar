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

// Load Saturne libraries
if ($publicInterfaceUseSignatory) {
    require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';
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
    $registrationCertificateFR->fetch('', '', ' AND fk_lot = ' . $id);
} else {
    $registrationCertificateFR->fetch('', '', ' AND a_registration_number = ' . "'" . $registrationNumber . "'");
    $id = $registrationCertificateFR->fk_lot;
}
$productLot->fetch($id);
$lastActionComm = $actionComm->getActions(0, $id,'productlot', ' AND code = "AC_' . strtoupper($productLot->element) . '_ADD_Public_Vehicle_Log_Book"', 'id','DESC', 1);
if (is_array($lastActionComm) && !empty($lastActionComm)) {
    $lastArrivalMileage = $lastActionComm[0]->array_options['options_arrival_mileage'];
}
$user->fetch(getDolGlobalInt('DOLICAR_PUBLIC_INTERFACE_USER'));
if ($isModEnabledDigiquali) {
    $controls = saturne_fetch_all_object_type('Control', 'DESC', 't.control_date', 1, 0, ['customsql' => 't.rowid = ee.fk_target AND t.status = ' . Control::STATUS_LOCKED], 'AND', false, true, false, ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as ee on ee.sourcetype = "productbatch" AND ee.fk_source = ' . $id . ' AND ee.targettype = "digiquali_control" AND ee.fk_target = t.rowid');
    if (is_array($controls) && !empty($controls)) {
        foreach ($controls as $control) {
            $lastControl = $control;
        }
    }
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
    if ($action == 'get_registration_number') {
        $registrationNumber = GETPOST('registration_number');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?registration_number=' . $registrationNumber . '&entity=' . $entity);
        exit;
    }

    if ($action == 'add') {
        $actionComm->elementtype = $productLot->element;
        $actionComm->type_code   = 'AC_PUBLIC';
        $actionComm->code        = 'AC_' . strtoupper($productLot->element) . '_ADD_Public_Vehicle_Log_Book';
        $actionComm->datep       = dol_stringtotime(GETPOST('start_date_and_hour'));
        $actionComm->datef       = dol_stringtotime(GETPOST('end_date_and_hour'));
        $actionComm->fk_element  = $productLot->id;
        $actionComm->userownerid = $user->id;
        $actionComm->percentage  = -1;

        // The client can set HTTP header information (like $_SERVER['HTTP_CLIENT_IP'] ...) to any arbitrary value it wants. As such it's far more reliable to use $_SERVER['REMOTE_ADDR'], as this cannot be set by the user
        $actionComm->note_private  = (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ? $langs->transnoentities('IPAddress') . ' : ' . $_SERVER['REMOTE_ADDR'] . '<br>' : $langs->transnoentities('NoData'));
        $actionComm->note_private .= $langs->transnoentities('Driver') . ' : ' . GETPOST('driver') . '<br>';
        $actionComm->note_private .= $langs->transnoentities('Comment') . ' : ' . GETPOST('comment', 'restricthtml');
        $actionComm->label         = $langs->transnoentities('ObjectAddPublicVehicleLogBook', $productLot->batch, $registrationCertificateFR->a_registration_number);

        $extraFields->setOptionalsFromPost([], $actionComm);

        $actionCommID = $actionComm->create($user);
        if ($publicInterfaceUseSignatory) {
            $signatory->status    = SaturneSignature::STATUS_SIGNED;
            $signatory->role      = $langs->transnoentities('Driver');
            $signatory->firstname = GETPOST('driver');

            $signatory->signature_date = dol_now();
            $signatory->signature      = GETPOST('signature');

            $signatory->element_type = 'user';
            $signatory->element_id   = 0;
            $signatory->object_type  = 'actiocomm';
            $signatory->fk_object    = $actionCommID;
            $signatory->module_name  = 'dolicar';

            $signatory->create($user);
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('PublicVehicleLogBook');
$moreJS  = ['/custom/saturne/js/includes/signature-pad.min.js'];
$moreCSS = ['/dolicar/css/pico.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title,  '', '', 0, 0, $moreJS, $moreCSS, '', 'page-public-card page-signature');

print '<form id="public-vehicle-log-book-form' . ($id > 0 ? '' : '-pwa') . '" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&entity=' . $entity . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="' . ($id > 0 ? 'add' : 'get_registration_number') . '">';
if ($backToPage) {
    print '<input type="hidden" name="backtopage" value="' . $backToPage . '">';
} ?>

<div class="public-card__container" data-public-interface="true">
    <?php if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) : ?>
        <?php if ($id > 0) : ?>
            <div class="public-card__header wpeo-gridlayout grid-2">
                <div class="header-information">
                    <div><a href="<?php echo $backToPage; ?>" class="information-back">
                        <i class="fas fa-sm fa-chevron-left"></i>
                        <?php echo $langs->trans('Back'); ?>
                    </a></div>
                    <div class="information-title wpeo-gridlayout grid-2">
                        <?php echo $langs->trans('PublicVehicleLogBook'); ?>
                        <?php if ($isModEnabledDigiquali && !empty($lastControl)) :
                            echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $lastControl->ref . '/photos/', 'small', 1, 0, 0, 0, 70, 70, 0, 0, 1, 'control/' . $lastControl->ref . '/photos/', $lastControl);
                        endif; ?>
                    </div>
                </div>

                <div class="header-objet">
                    <div class="objet-container">
                        <div class="objet-info">
                            <div class="objet-type"><?php echo $langs->trans('Batch'); ?></div>
                            <div class="objet-label">
                                <?php echo $productLot->getNomUrl(1, 'nolink'); ?>
                                <?php echo '<br>' . $registrationCertificateFR->getNomUrl(1, 'nolink'); ?>
                            </div>
                        </div>
                        <div class="objet-actions file-generation">
                            <?php $path = DOL_MAIN_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'; ?>
                            <input type="hidden" class="specimen-name" data-specimen-name="<?php echo $objectType . '_specimen_' . $trackID . '.odt'; ?>">
                            <input type="hidden" class="specimen-path" data-specimen-path="<?php echo $path; ?>">
                            <?php if (GETPOSTISSET('document_type') && $fileExists) : ?>
                                <div class="wpeo-button button-square-40 button-rounded button-blue auto-download"><i class="fas fa-download"></i></div>
                            <?php else : ?>
                                <div class="wpeo-button button-square-40 button-rounded button-grey"><i class="fas fa-download"></i></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Start/end date and hour -->
            <div class="wpeo-gridlayout grid-2">
                <label for=start_date_and_hour">
                    <?php echo $langs->trans('StartDateAndHour'); ?>
                    <input type="datetime-local" name="start_date_and_hour" id="start_date_and_hour" required>
                </label>
                <label for=end_date_and_hour">
                    <?php echo $langs->trans('EndDateAndHour'); ?>
                    <input type="datetime-local" name="end_date_and_hour" id="end_date_and_hour" required>
                </label>
            </div>

            <!-- Driver -->
            <label for="driver">
                <input type="text" id="driver" name="driver" placeholder="<?php echo $langs->trans('Driver'); ?>" required>
            </label>

            <!-- Starting/Arrival mileage -->
            <div class="wpeo-gridlayout grid-2">
                <label for="starting_mileage">
                    <input type="number" id="starting_mileage" name="options_starting_mileage" min="<?php echo $lastArrivalMileage ?? 0; ?>" placeholder="<?php echo $langs->trans('StartingMileage'); ?>" value="<?php echo dol_escape_htmltag($lastArrivalMileage ?? ''); ?>" required>
                </label>
                <label for="arrival_mileage">
                    <input type="number" id="arrival_mileage" name="options_arrival_mileage" min="<?php echo $lastArrivalMileage ?? 0; ?>" placeholder="<?php echo $langs->trans('ArrivalMileage'); ?>" required>
                </label>
            </div>

            <!-- Comment -->
            <label for="comment">
                <textarea name="comment" id="comment" rows="3" placeholder="<?php echo $langs->trans('Comment'); ?>"></textarea>
            </label>

            <?php if ($publicInterfaceUseSignatory) : ?>
                <div class="public-card__content signature">
                    <div class="signature-element">
                        <canvas class="canvas-container editable canvas-signature"></canvas>
                        <div class="signature-erase wpeo-button button-square-40 button-rounded button-grey"><span><i class="fas fa-eraser"></i></span></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="public-card__footer">
                <button type="submit" class="wpeo-button no-load public-vehicle-log-book-validate <?php echo $publicInterfaceUseSignatory ? 'button-grey button-disable' : 'button-blue'; ?>"><i class="fas fa-save pictofixedwidth"></i><?php echo $langs->trans('Save'); ?></button>
            </div>

            <?php
            $confirmationParams = [
                'picto'             => 'fontawesome_fa-check-circle_fas_#47e58e',
                'color'             => '#47e58e',
                'confirmationTitle' => 'SavedPublicVehicleLogBook',
                'buttonParams'      => ['CloseModal' => 'button-blue public-vehicle-log-book-confirmation-close']
            ];
            require_once __DIR__ . '/../../../saturne/core/tpl/utils/confirmation_view.tpl.php'; ?>
        <?php else : ?>
            <div class="public-card__header">
                <div class="header-information">
                    <div class="information-title"><?php echo $langs->trans('PublicVehicleLogBook'); ?></div>
                </div>
            </div>

            <!-- Driver -->
            <label for="registration_number">
                <input type="text" id="registration_number" name="registration_number" placeholder="<?php echo $langs->trans('RegistrationNumber'); ?>" required>
            </label>

            <div class="public-card__footer">
                <button type="submit" class="wpeo-button no-load button-blue"><i class="fas fa-save pictofixedwidth"></i><?php echo $langs->trans('Save'); ?></button>
            </div>
        <?php endif;
    else :
        print '<div class="center">' . $langs->trans('PublicInterfaceForbidden', dol_strtolower($langs->transnoentities('PublicVehicleLogBook'))) . '</div>';
    endif; ?>
</div>
<?php print '</form>';

llxFooter('', 'public');
$db->close();
