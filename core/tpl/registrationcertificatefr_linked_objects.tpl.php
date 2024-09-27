<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/registrationcertificatefr_linked_objects.tpl.php
 * \ingroup dolicar
 * \brief   Template page for registrationcertificatefr linked objects
 */

/**
 * The following vars must be defined :
 * Global   : $db, $langs
 * Variable : $fromProductLot
 */

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

$out  = load_fiche_titre($langs->transnoentities('LinkedObjects'), '', 'dolicar_color@dolicar');
$out .= '<table class="noborder centpercent">';
$out .= '<tr class="liste_titre">';
$out .= '<td>' . $langs->trans('ObjectType') . '</td>';
$out .= '<td>' . $langs->trans('Object') . '</td>';
$out .= '<td>' . $langs->trans('Mileage') . '</td>';
$out .= '<td>' . $langs->trans('Date') . '</td>';
$out .= '</tr>';

$registrationCertificate = new RegistrationCertificateFr($db);
$registrationCertificate->fetch(!isset($fromProductLot) ? GETPOST('id') : '', !isset($fromProductLot) ? GETPOST('ref') : '', isset($fromProductLot) ? ' AND t.fk_lot = ' . GETPOST('id') : '');
$registrationCertificate->fetchObjectLinked(null, '', $registrationCertificate->id, $registrationCertificate->module . '_' . $registrationCertificate->element);
if (!empty($registrationCertificate->linkedObjects)) {
    foreach ($registrationCertificate->linkedObjects as $linkedObjectElement => $linkedObjects) {
        foreach ($linkedObjects as $linkedObject) {
            $out .= '<tr>';
            $out .= '<td class="nowrap">' . $langs->transnoentities(ucfirst($linkedObjectElement)) . '</td>';
            $out .= '<td>' . $linkedObject->getNomUrl(1) . '</td>';
            $out .= '<td>' . $linkedObject->array_options['options_mileage'] . '</td>';
            $out .= '<td>' . dol_print_date($linkedObject->date_creation, 'dayhour') . '</td>';
            $out .= '</tr>';
        }
    }
} else {
    $out .= '<tr><td colspan="4">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
}

$out .= '</table>'; ?>

<script>
    jQuery('.fichecenter').first().append(<?php echo json_encode($out); ?>);
</script>
