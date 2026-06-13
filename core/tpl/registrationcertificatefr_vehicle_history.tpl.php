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
 * \brief   Template for vehicle history events driven by ActionComm categories
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $db, $form, $langs
 * Variable : $object (RegistrationCertificateFr), $permissiontoadd
 *            $iconMap   (array label => FA class)
 *            $catById   (array id => Categorie)
 *            $catLabels (array id => ['label' => string, 'data-html' => string])
 *            $eventsList (array of ActionComm)
 *            $evtCatById (array evtId => Categorie)
 *            $alreadyLinkedFactureIds (array of facture ids already linked to an event)
 *            $alreadyLinkedPropalIds  (array of propal ids already linked to an event)
 *            $alreadyLinkedControlIds (array of control ids already linked to an event)
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';

if (empty($object->fk_lot) || $object->fk_lot <= 0) {
    return;
}

$out = '';

// === Add event form ===
if (!empty($permissiontoadd)) {
    // Exclude elements already linked to an existing event so the same one cannot be assigned twice
    $factureFilter = !empty($alreadyLinkedFactureIds) ? ':0:(t.rowid:notin:' . implode(',', $alreadyLinkedFactureIds) . ')' : '';
    $propalFilter  = !empty($alreadyLinkedPropalIds) ? ':0:(t.rowid:notin:' . implode(',', $alreadyLinkedPropalIds) . ')' : '';
    $controlFilter = !empty($alreadyLinkedControlIds) ? ':0:(t.rowid:notin:' . implode(',', $alreadyLinkedControlIds) . ')' : '';

    $out .= load_fiche_titre($langs->transnoentities('AddVehicleEvent'), '', 'dolicar_color@dolicar');

    $out .= '<form method="POST" action="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '">';
    $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
    $out .= '<input type="hidden" name="action" value="add_vehicle_event">';

    $out .= '<table class="border centpercent tableforfield">';

    $out .= '<tr>';
    $out .= '<td class="titlefield fieldrequired">' . $langs->transnoentities('VehicleEventType') . '</td>';
    $out .= '<td>' . Form::selectarray('event_category_id', $catLabels, GETPOSTINT('event_category_id'), 1, 0, 0, '', 0, 0, 0, '', 'minwidth200') . '</td>';
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

    $out .= '<tr>';
    $out .= '<td>' . img_picto('', 'bill', 'class="pictofixedwidth"') . $langs->transnoentities('Invoices') . '</td>';
    $out .= '<td>' . $form->selectForForms('Facture:compta/facture/class/facture.class.php' . $factureFilter, 'event_fk_facture', GETPOSTINT('event_fk_facture'), 1, '', '', 'minwidth300') . '</td>';
    $out .= '</tr>';

    $out .= '<tr>';
    $out .= '<td>' . img_picto('', 'propal', 'class="pictofixedwidth"') . $langs->transnoentities('Proposals') . '</td>';
    $out .= '<td>' . $form->selectForForms('Propal:comm/propal/class/propal.class.php' . $propalFilter, 'event_fk_propal', GETPOSTINT('event_fk_propal'), 1, '', '', 'minwidth300') . '</td>';
    $out .= '</tr>';

    $out .= '<tr>';
    $out .= '<td><i class="fas fa-tasks pictofixedwidth"></i>' . $langs->transnoentities('Controls') . '</td>';
    $out .= '<td>' . $form->selectForForms('Control:custom/digiquali/class/control.class.php' . $controlFilter, 'event_fk_control', GETPOSTINT('event_fk_control'), 1, '', '', 'minwidth300') . '</td>';
    $out .= '</tr>';

    $out .= '</table>';

    $out .= '<div class="tabsAction">';
    $out .= '<input type="submit" class="butAction" value="' . $langs->transnoentities('Save') . '">';
    $out .= '</div>';

    $out .= '</form>';
}

// === Event history list ===
$out .= load_fiche_titre($langs->transnoentities('VehicleHistory'), '', 'dolicar_color@dolicar');
$out .= '<table class="noborder centpercent">';
$out .= '<tr class="liste_titre">';
$out .= '<td>' . $langs->transnoentities('VehicleEventType') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('Date') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('Mileage') . '</td>';
$out .= '<td>' . $langs->transnoentities('Note') . '</td>';
$out .= '<td class="center">' . $langs->transnoentities('UserAuthor') . '</td>';
$out .= '<td>' . $langs->transnoentities('LinkedDocuments') . '</td>';
$out .= '</tr>';

if (!empty($eventsList)) {
    foreach ($eventsList as $evt) {
        $evt->fetch_optionals();

        $ownerUser = new User($db);
        $ownerUser->fetch($evt->userownerid);

        $evtCat  = $evtCatById[(int) $evt->id] ?? null;
        $label   = $evtCat ? dol_escape_htmltag($evtCat->label) : dol_escape_htmltag($evt->label);
        $color   = $evtCat ? '#' . ltrim($evtCat->color, '#') : '#888888';
        $icon    = $evtCat ? ($iconMap[$evtCat->label] ?? 'fa-circle') : 'fa-circle';
        $km      = (int) ($evt->array_options['options_starting_mileage'] ?? 0);
        $userStr = $ownerUser->getNomUrl(1);
        $badge   = '<span style="color:' . $color . ';font-weight:bold;"><i class="fas ' . $icon . '"></i> ' . $label . '</span>';

        $evt->fetchObjectLinked(null, null, null, null, 'OR', 1, 'sourcetype', 0);
        $linkedHtml = '';
        foreach (($evt->linkedObjectsIds['facture'] ?? []) as $facId) {
            $tmpFac = new Facture($db);
            if ($tmpFac->fetch($facId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpFac->getNomUrl(1);
                $linkedHtml .= ' &mdash; <strong>' . price($tmpFac->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($tmpFac->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpFac->note_public, 80)) . '</span>';
                }
                if (!empty($tmpFac->note_private)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpFac->note_private, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }
        foreach (($evt->linkedObjectsIds['propal'] ?? []) as $propalId) {
            $tmpPropal = new Propal($db);
            if ($tmpPropal->fetch($propalId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpPropal->getNomUrl(1);
                $linkedHtml .= ' &mdash; <strong>' . price($tmpPropal->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($tmpPropal->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpPropal->note_public, 80)) . '</span>';
                }
                if (!empty($tmpPropal->note_private)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpPropal->note_private, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }
        foreach (($evt->linkedObjectsIds['control'] ?? []) as $controlId) {
            $tmpControl = new Control($db);
            if ($tmpControl->fetch($controlId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpControl->getNomUrl(1);
                $linkedHtml .= ' &mdash; ' . $tmpControl->getLibVerdict(4);
                if (!empty($tmpControl->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpControl->note_public, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }

        $out .= '<tr class="oddeven">';
        $out .= '<td class="nowrap">' . $badge . '</td>';
        $out .= '<td class="center nowraponall">' . dol_print_date($evt->datep, 'day') . '</td>';
        $out .= '<td class="center">' . ($km > 0 ? price($km, 0, '', 1, 0) . ' km' : '') . '</td>';
        $out .= '<td>' . dol_escape_htmltag((string) $evt->note_private) . '</td>';
        $out .= '<td class="center nowraponall">' . $userStr . '</td>';
        $out .= '<td>' . $linkedHtml . '</td>';
        $out .= '</tr>';
    }
} else {
    $out .= '<tr><td colspan="6"><em>' . $langs->transnoentities('NoVehicleHistory') . '</em></td></tr>';
}

$out .= '</table>';

print $out;
