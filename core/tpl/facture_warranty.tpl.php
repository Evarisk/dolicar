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
 * \file    core/tpl/facture_warranty.tpl.php
 * \ingroup dolicar
 * \brief   Template for the warranties block of an invoice card
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $form, $langs, $user
 * Variable : $object (Facture)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../lib/dolicar_functions.lib.php';

$warranties      = dolicar_get_invoice_warranties($object);
$permissionToAdd = $user->hasRight('facture', 'creer');
$backToPage      = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;

print '<div class="fichecenter dolicar-invoice-warranties">';

print load_fiche_titre(img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth"') . $langs->transnoentities('Warranties'), '', '');

print '<form method="POST" action="' . dol_escape_htmltag($backToPage) . '" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add_warranty">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('WarrantyLabel') . '</td>';
print '<td class="center">' . $langs->transnoentities('WarrantyEndDate') . '</td>';
print '<td>' . $langs->transnoentities('WarrantyAttachment') . '</td>';
print '<td class="right"></td>';
print '</tr>';

if (!empty($warranties)) {
    foreach ($warranties as $warranty) {
        $warrantyEndDate = !empty($warranty['date_end']) ? dol_stringtotime($warranty['date_end']) : 0;

        print '<tr class="oddeven">';
        print '<td>' . dol_escape_htmltag($warranty['label'] ?? '') . '</td>';
        print '<td class="center nowraponall">';
        if ($warrantyEndDate > 0) {
            print dol_print_date($warrantyEndDate, 'day');
            // An expired warranty must not read like a valid one
            if ($warrantyEndDate < dol_now()) {
                print ' ' . img_picto($langs->transnoentities('WarrantyExpired'), 'warning');
            }
        }
        print '</td>';
        print '<td>';
        if (!empty($warranty['file'])) {
            print '<a href="' . dol_escape_htmltag(dolicar_get_invoice_warranty_file_url($object, $warranty['file'])) . '" target="_blank"><i class="fas fa-paperclip paddingright"></i>' . dol_escape_htmltag($warranty['file']) . '</a>';
        }
        print '</td>';
        print '<td class="right">';
        if ($permissionToAdd) {
            print '<a class="reposition" href="' . dol_escape_htmltag($backToPage . '&action=delete_warranty&warranty_id=' . (int) ($warranty['id'] ?? 0) . '&token=' . newToken()) . '">' . img_delete() . '</a>';
        }
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">' . $langs->transnoentities('NoWarranty') . '</span></td></tr>';
}

if ($permissionToAdd) {
    print '<tr class="oddeven">';
    print '<td><input type="text" class="flat minwidth200" name="warranty_label" value="' . dol_escape_htmltag(GETPOST('warranty_label', 'alphanohtml')) . '"></td>';
    print '<td class="center">' . $form->selectDate(-1, 'warranty_end', 0, 0, 1, '', 1, 0) . '</td>';
    print '<td><input type="file" class="flat" name="userfile"></td>';
    print '<td class="right"><input type="submit" class="button button-add small" value="' . $langs->trans('Add') . '"></td>';
    print '</tr>';
}

print '</table>';
print '</form>';

print '</div>';
