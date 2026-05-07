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
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Global variables definitions
global $db, $form, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

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

/*
 * Actions
 */

if ($action == 'add_vehicle_event' && !empty($permissiontoadd) && !empty($object->fk_lot) && $object->fk_lot > 0) {
    $error        = 0;
    $allowedCodes = ['AC_DOLICAR_CT', 'AC_DOLICAR_REVISION', 'AC_DOLICAR_ACCIDENT', 'AC_DOLICAR_AUTRE'];
    $eventType    = GETPOST('event_type', 'aZ09');
    $eventDate    = dol_mktime(12, 0, 0, GETPOSTINT('event_datemonth'), GETPOSTINT('event_dateday'), GETPOSTINT('event_dateyear'));
    $eventMileage = GETPOSTINT('event_mileage');
    $eventNote    = GETPOST('event_note', 'restricthtml');

    if (!in_array($eventType, $allowedCodes, true)) {
        setEventMessages($langs->transnoentities('VehicleEventType') . ' ' . $langs->transnoentities('NotValid'), null, 'errors');
        $error++;
    }

    if (!$error) {
        $eventLabelKeys = [
            'AC_DOLICAR_CT'       => 'VehicleEventTypeCT',
            'AC_DOLICAR_REVISION' => 'VehicleEventTypeRevision',
            'AC_DOLICAR_ACCIDENT' => 'VehicleEventTypeAccident',
            'AC_DOLICAR_AUTRE'    => 'VehicleEventTypeAutre',
        ];

        $actionComm               = new ActionComm($db);
        $actionComm->code         = $eventType;
        $actionComm->type_code    = 'AC_OTH';
        $actionComm->fk_element   = (int) $object->fk_lot;
        $actionComm->elementtype  = 'productlot';
        $actionComm->label        = $langs->transnoentities($eventLabelKeys[$eventType]);
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
}

// End of page
llxFooter();
$db->close();
