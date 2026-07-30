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

// Nothing to say about a vehicle without warranty: no empty block eating the card space
if (empty($vehicleWarranties)) {
    return;
}

print '<div class="underbanner clearboth"></div>';

// No tableforfield class here: the card script collapses the vehicle data rows by scanning that class
print '<table class="border centpercent dolicar-vehicle-warranties">';

print '<tr class="liste_titre">';
print '<td colspan="2">' . img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth"') . $langs->transnoentities('VehicleWarranties') . ' <span class="badge marginleftonlyshort">' . count($vehicleWarranties) . '</span></td>';
print '</tr>';

foreach ($vehicleWarranties as $vehicleWarranty) {
    $warranty        = $vehicleWarranty['warranty'];
    $invoice         = $vehicleWarranty['invoice'];
    $warrantyEndDate = !empty($warranty['date_end']) ? dol_stringtotime($warranty['date_end']) : 0;
    $isExpired       = $warrantyEndDate > 0 && $warrantyEndDate < dol_now();

    print '<tr class="oddeven' . ($isExpired ? ' opacitymedium' : '') . '">';

    // The invoice granting the warranty stays one click away, as a picto to keep the block narrow
    print '<td class="tdoverflowmax200">' . $invoice->getNomUrl(2) . ' ' . dol_escape_htmltag($warranty['label'] ?? '') . '</td>';

    print '<td class="right nowraponall">';
    if ($warrantyEndDate > 0) {
        print $isExpired
            ? img_picto($langs->transnoentities('WarrantyExpired'), 'warning')
            : img_picto($langs->transnoentities('WarrantyRunning'), 'tick');
        print ' ' . dol_print_date($warrantyEndDate, 'day');
    }
    foreach ($warranty['files'] as $warrantyFileName) {
        print ' ' . dolicar_get_invoice_warranty_preview_link($invoice, $warrantyFileName);
    }
    print '</td>';

    print '</tr>';
}

print '</table>';
