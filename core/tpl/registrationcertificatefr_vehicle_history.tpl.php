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
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

if (empty($object->fk_lot) || $object->fk_lot <= 0) {
    return;
}

$out = '';

// === Add event form ===
if (!empty($permissiontoadd)) {
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

    foreach (dolicar_get_vehicle_event_linkable_types() as $typeKey => $linkableType) {
        if (!dolicar_vehicle_event_type_enabled($linkableType['const'])) {
            continue;
        }

        $typePicto = !empty($linkableType['picto'])
            ? img_picto('', $linkableType['picto'], 'class="pictofixedwidth"')
            : '<i class="fas ' . $linkableType['pictofa'] . ' pictofixedwidth"></i>';

        $out .= '<tr>';
        $out .= '<td>' . $typePicto . $langs->transnoentities($linkableType['label']) . '</td>';
        $out .= '<td>' . $form->selectForForms($linkableType['selectarg'], $linkableType['field'], GETPOSTINT($linkableType['field']), 1, '', '', 'minwidth300') . '</td>';
        $out .= '</tr>';

        // Expense report: pick specific lines instead of the whole report (issue #452)
        if ($typeKey === 'expensereport') {
            $out .= '<tr id="dolicar-er-lines-row" style="display:none;">';
            $out .= '<td>' . $langs->transnoentities('ExpenseReportLines') . '</td>';
            $out .= '<td><div id="dolicar-er-lines" class="dolicar-er-lines" data-url="' . dol_escape_htmltag($_SERVER['PHP_SELF'] . '?id=' . $object->id) . '"></div></td>';
            $out .= '</tr>';
        }
    }

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
        foreach (($evt->linkedObjectsIds['expensereport'] ?? []) as $expenseReportId) {
            $tmpExpenseReport = new ExpenseReport($db);
            if ($tmpExpenseReport->fetch($expenseReportId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpExpenseReport->getNomUrl(1);
                $linkedHtml .= ' &mdash; <strong>' . price($tmpExpenseReport->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($tmpExpenseReport->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpExpenseReport->note_public, 80)) . '</span>';
                }
                if (!empty($tmpExpenseReport->note_private)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpExpenseReport->note_private, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }
        foreach (($evt->linkedObjectsIds['order_supplier'] ?? []) as $supplierOrderId) {
            $tmpSupplierOrder = new CommandeFournisseur($db);
            if ($tmpSupplierOrder->fetch($supplierOrderId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpSupplierOrder->getNomUrl(1);
                $linkedHtml .= ' &mdash; <strong>' . price($tmpSupplierOrder->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($tmpSupplierOrder->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpSupplierOrder->note_public, 80)) . '</span>';
                }
                if (!empty($tmpSupplierOrder->note_private)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpSupplierOrder->note_private, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }
        foreach (($evt->linkedObjectsIds['invoice_supplier'] ?? []) as $supplierInvoiceId) {
            $tmpSupplierInvoice = new FactureFournisseur($db);
            if ($tmpSupplierInvoice->fetch($supplierInvoiceId) > 0) {
                $linkedHtml .= '<div class="inline-block">';
                $linkedHtml .= $tmpSupplierInvoice->getNomUrl(1);
                $linkedHtml .= ' &mdash; <strong>' . price($tmpSupplierInvoice->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($tmpSupplierInvoice->note_public)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpSupplierInvoice->note_public, 80)) . '</span>';
                }
                if (!empty($tmpSupplierInvoice->note_private)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($tmpSupplierInvoice->note_private, 80)) . '</span>';
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

        // Expense report lines selected for this event (issue #452)
        $erLineIds = array_values($evt->linkedObjectsIds['expensereportline'] ?? []);
        if (!empty($erLineIds)) {
            require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereportline.class.php';
            foreach ($erLineIds as $erLineId) {
                $erLine = new ExpenseReportLine($db);
                if ($erLine->fetch($erLineId) <= 0) {
                    continue;
                }
                $parentEr = new ExpenseReport($db);
                $parentEr->fetch($erLine->fk_expensereport);
                $rawTypeLabel = $erLine->type_fees_libelle ?: ($erLine->type_fees_code ?: '');
                $typeLabel    = $rawTypeLabel !== '' ? $langs->trans($rawTypeLabel) : '';
                $projectLabel = $erLine->projet_title ?: $erLine->projet_ref;

                $linkedHtml .= '<div class="inline-block">';
                if ($parentEr->id > 0) {
                    $linkedHtml .= $parentEr->getNomUrl(1) . ' &mdash; ';
                }
                $linkedHtml .= dol_print_date($erLine->date, 'day');
                if (!empty($typeLabel)) {
                    $linkedHtml .= ' &middot; ' . dol_escape_htmltag($typeLabel);
                }
                if (!empty($projectLabel)) {
                    $linkedHtml .= ' &middot; ' . dol_escape_htmltag($projectLabel);
                }
                $linkedHtml .= ' &mdash; <strong>' . price($erLine->total_ttc) . ' ' . $conf->currency . '</strong>';
                if (!empty($erLine->comments)) {
                    $linkedHtml .= ' &mdash; <span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($erLine->comments, 80)) . '</span>';
                }
                $linkedHtml .= '</div><br>';
            }
        }

        $out .= '<tr class="oddeven">';
        $out .= '<td class="nowrap">' . $badge . '</td>';
        $out .= '<td class="center nowraponall">' . dol_print_date($evt->datep, 'day') . '</td>';
        $out .= '<td class="center">' . ($km > 0 ? price($km, 0, '', 1, 0) . ' km' : '') . '</td>';
        $out .= '<td>' . nl2br(dol_escape_htmltag((string) $evt->note_private)) . '</td>';
        $out .= '<td class="center nowraponall">' . $userStr . '</td>';
        $out .= '<td>' . $linkedHtml . '</td>';
        $out .= '</tr>';
    }
} else {
    $out .= '<tr><td colspan="6"><em>' . $langs->transnoentities('NoVehicleHistory') . '</em></td></tr>';
}

$out .= '</table>';

print $out;
