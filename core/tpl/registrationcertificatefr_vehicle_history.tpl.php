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
 * \file    core/tpl/registrationcertificatefr_vehicle_history.tpl.php
 * \ingroup dolicar
 * \brief   Template for vehicle history events (CT, Révision, Accident, Autre)
 */

/**
 * The following vars must be defined:
 * Global   : $db, $langs, $form, $user
 * Variable : $object (RegistrationCertificateFr), $permissiontoadd
 */

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$vehicleEventTypes = [
    'AC_DOLICAR_CT'       => $langs->transnoentities('VehicleEventTypeCT'),
    'AC_DOLICAR_REVISION' => $langs->transnoentities('VehicleEventTypeRevision'),
    'AC_DOLICAR_ACCIDENT' => $langs->transnoentities('VehicleEventTypeAccident'),
    'AC_DOLICAR_AUTRE'    => $langs->transnoentities('VehicleEventTypeAutre'),
];

$vehicleEventIcons = [
    'AC_DOLICAR_CT'       => 'fa-check-circle',
    'AC_DOLICAR_REVISION' => 'fa-wrench',
    'AC_DOLICAR_ACCIDENT' => 'fa-exclamation-triangle',
    'AC_DOLICAR_AUTRE'    => 'fa-circle',
];

$vehicleEventColors = [
    'AC_DOLICAR_CT'       => '#5BA86E',
    'AC_DOLICAR_REVISION' => '#E8A317',
    'AC_DOLICAR_ACCIDENT' => '#E05353',
    'AC_DOLICAR_AUTRE'    => '#888888',
];

$out = '';

if (empty($object->fk_lot) || $object->fk_lot <= 0) {
    return;
}

// === Add event form ===
if (!empty($permissiontoadd)) {
    $out .= load_fiche_titre($langs->transnoentities('AddVehicleEvent'), '', 'dolicar_color@dolicar');

    $out .= '<form method="POST" action="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '">';
    $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
    $out .= '<input type="hidden" name="action" value="add_vehicle_event">';

    $out .= '<table class="border centpercent tableforfield">';

    $out .= '<tr>';
    $out .= '<td class="titlefield fieldrequired">' . $langs->transnoentities('VehicleEventType') . '</td>';
    $out .= '<td>' . Form::selectarray('event_type', $vehicleEventTypes, GETPOST('event_type', 'aZ09'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth200') . '</td>';
    $out .= '</tr>';

    $out .= '<tr>';
    $out .= '<td class="fieldrequired">' . $langs->transnoentities('Date') . '</td>';
    $out .= '<td>' . $form->selectDate(dol_now(), 'event_date', 0, 0, 0, '', 1, 1) . '</td>';
    $out .= '</tr>';

    $out .= '<tr>';
    $out .= '<td>' . $langs->transnoentities('Mileage') . '</td>';
    $out .= '<td><input type="number" name="event_mileage" class="flat minwidth100" value="' . (GETPOSTINT('event_mileage') ?: '') . '" min="0"> km</td>';
    $out .= '</tr>';

    $out .= '<tr>';
    $out .= '<td>' . $langs->transnoentities('Note') . '</td>';
    $out .= '<td><textarea name="event_note" class="flat minwidth400" rows="2">' . dol_escape_htmltag(GETPOST('event_note', 'restricthtml')) . '</textarea></td>';
    $out .= '</tr>';

    $out .= '</table>';

    $out .= '<div class="tabsAction">';
    $out .= '<input type="submit" class="butAction" value="' . $langs->transnoentities('Save') . '">';
    $out .= '</div>';

    $out .= '</form>';
}

// === Event history list ===
$allowedCodes = array_keys($vehicleEventTypes);
$codeFilter   = " AND a.code IN ('" . implode("','", $allowedCodes) . "')";

$actionComm = new ActionComm($db);
$eventsList = $actionComm->getActions(0, (int) $object->fk_lot, 'productlot', $codeFilter, 'a.datep', 'DESC');

$out .= load_fiche_titre($langs->transnoentities('VehicleHistory'), '', 'dolicar_color@dolicar');
$out .= '<table class="noborder centpercent">';
$out .= '<tr class="liste_titre">';
$out .= '<td>' . $langs->transnoentities('VehicleEventType') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('Date') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('Mileage') . '</td>';
$out .= '<td>' . $langs->transnoentities('Note') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('UserAuthor') . '</td>';
$out .= '</tr>';

if (is_array($eventsList) && !empty($eventsList)) {
    foreach ($eventsList as $evt) {
        $evt->fetch_optionals();

        $ownerUser = new User($db);
        $ownerUser->fetch($evt->userownerid);

        $code    = $evt->code;
        $icon    = $vehicleEventIcons[$code] ?? 'fa-circle';
        $color   = $vehicleEventColors[$code] ?? '#888888';
        $label   = $vehicleEventTypes[$code] ?? dol_escape_htmltag($evt->label);
        $km      = (int) ($evt->array_options['options_starting_mileage'] ?? 0);
        $userStr = $ownerUser->getNomUrl(1);
        $badge   = '<span style="color:' . $color . ';font-weight:bold;"><i class="fas ' . $icon . '"></i> ' . $label . '</span>';

        $out .= '<tr class="oddeven">';
        $out .= '<td class="nowrap">' . $badge . '</td>';
        $out .= '<td class="center nowraponall">' . dol_print_date($evt->datep, 'day') . '</td>';
        $out .= '<td class="center">' . ($km > 0 ? price($km, 0, '', 1, 0) . ' km' : '') . '</td>';
        $out .= '<td>' . dol_escape_htmltag((string) $evt->note_private) . '</td>';
        $out .= '<td class="center nowraponall">' . $userStr . '</td>';
        $out .= '</tr>';
    }
} else {
    $out .= '<tr><td colspan="5"><em>' . $langs->transnoentities('NoVehicleHistory') . '</em></td></tr>';
}

$out .= '</table>';

print $out;
