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
 * \file    core/tpl/registrationcertificatefr_warranties.tpl.php
 * \ingroup dolicar
 * \brief   Template for the warranties block of a registrationcertificatefr card
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $object (RegistrationCertificateFr)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../lib/dolicar_functions.lib.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

$vehicleWarranties = dolicar_get_vehicle_warranties($object);

print load_fiche_titre($langs->transnoentities('VehicleWarranties'), '', 'dolicar_color@dolicar');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('WarrantyLabel') . '</td>';
print '<td class="center">' . $langs->transnoentities('WarrantyEndDate') . '</td>';
print '<td>' . $langs->transnoentities('Invoice') . '</td>';
print '<td>' . $langs->transnoentities('WarrantyAttachment') . '</td>';
print '</tr>';

if (!empty($vehicleWarranties)) {
    foreach ($vehicleWarranties as $vehicleWarranty) {
        $warranty        = $vehicleWarranty['warranty'];
        $invoice         = $vehicleWarranty['invoice'];
        $warrantyEndDate = !empty($warranty['date_end']) ? dol_stringtotime($warranty['date_end']) : 0;
        $isExpired       = $warrantyEndDate > 0 && $warrantyEndDate < dol_now();

        print '<tr class="oddeven">';
        print '<td' . ($isExpired ? ' class="opacitymedium"' : '') . '>' . dol_escape_htmltag($warranty['label'] ?? '') . '</td>';
        print '<td class="center nowraponall">';
        if ($warrantyEndDate > 0) {
            print dol_print_date($warrantyEndDate, 'day');
            print $isExpired
                ? ' ' . img_picto($langs->transnoentities('WarrantyExpired'), 'warning')
                : ' ' . img_picto($langs->transnoentities('WarrantyRunning'), 'tick');
        }
        print '</td>';
        print '<td class="nowraponall">' . $invoice->getNomUrl(1) . '</td>';
        print '<td>';
        if (!empty($warranty['file'])) {
            print '<a href="' . dol_escape_htmltag(dolicar_get_invoice_warranty_file_url($invoice, $warranty['file'])) . '" target="_blank"><i class="fas fa-paperclip paddingright"></i>' . dol_escape_htmltag($warranty['file']) . '</a>';
        }
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">' . $langs->transnoentities('NoVehicleWarranty') . '</span></td></tr>';
}

print '</table>';
print '</div>';
