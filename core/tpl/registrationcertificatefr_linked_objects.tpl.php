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
 * Global   : $db, $langs, $conf
 * Variable : $fromProductLot
 */

global $conf;

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DigiQuali libraries if enabled
$digiqualiEnabled = isModEnabled('digiquali');
if ($digiqualiEnabled) {
    dol_include_once('/digiquali/class/control.class.php');
    dol_include_once('/digiquali/class/survey.class.php');
    dol_include_once('/digiquali/class/sheet.class.php');
}

// Load commercial document classes to list linked invoices/proposals and sum their amounts
$factureEnabled = isModEnabled('facture');
$propalEnabled  = isModEnabled('propal');
if ($factureEnabled) {
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
}
if ($propalEnabled) {
    require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
}

$totalAmount = 0;

/**
 * Helper : does the linked object carry an amount ? Only commercial documents do
 *
 * @param  CommonObject $linkedObject        The linked object
 * @param  string       $linkedObjectElement The element type key (e.g. 'facture')
 * @return bool                              True if the object has an amount (incl. tax)
 */
$hasAmount = static function ($linkedObject, string $linkedObjectElement): bool {
    return in_array($linkedObjectElement, ['facture', 'propal', 'commande'], true) && isset($linkedObject->total_ttc);
};

/**
 * Helper : render a single linked object row
 *
 * @param  CommonObject $linkedObject        The linked object to display
 * @param  string       $linkedObjectElement The element type key (e.g. 'digiquali_control')
 * @param  bool         $digiqualiEnabled    Whether DigiQuali module is active
 * @param  bool         $showAmountColumn    Whether the amount column is displayed
 * @param  Translate    $langs               Translation object
 * @param  float        $totalAmount         Running total of amounts (passed by reference)
 * @return string                            HTML row string
 */
$renderLinkedObjectRow = static function ($linkedObject, string $linkedObjectElement, bool $digiqualiEnabled, bool $showAmountColumn, $langs, &$totalAmount) use ($hasAmount): string {
    global $conf, $db;
    $isControl       = ($linkedObjectElement === 'digiquali_control');
    $isSurvey        = ($linkedObjectElement === 'digiquali_survey');
    $isCommercialDoc = in_array($linkedObjectElement, ['facture', 'propal', 'commande'], true);

    $verdictValue = $isControl ? (int) $linkedObject->verdict : '';

    $row  = '<tr class="dolicar-linked-object-row" data-verdict="' . $verdictValue . '">';
    $row .= '<td class="nowrap">' . $langs->transnoentities(ucfirst($linkedObjectElement)) . '</td>';
    $row .= '<td>' . $linkedObject->getNomUrl(1) . '</td>';
    if ($digiqualiEnabled) {
        if ($isControl || $isSurvey) {
            $sheet     = new Sheet($db);
            $sheetLink = '';
            if (!empty($linkedObject->fk_sheet) && $sheet->fetch($linkedObject->fk_sheet) > 0) {
                $sheetLink = $sheet->getNomUrl(1) . (!empty($sheet->label) ? ' - ' . $sheet->label : '');
            }
            $row .= '<td>' . $sheetLink . '</td>';
        } else {
            $row .= '<td></td>';
        }
    }
    $row .= '<td>' . (!$isControl && !$isSurvey && !$isCommercialDoc ? ($linkedObject->array_options['options_mileage'] ?? '') : '') . '</td>';
    $row .= '<td>' . dol_print_date($linkedObject->date_creation, 'dayhour') . '</td>';
    // Status of every object. The public interface only shows locked controls: say so on the others, or the user wonders where they went
    $row .= '<td class="nowrap">' . (method_exists($linkedObject, 'getLibStatut') ? $linkedObject->getLibStatut(5) : '');
    if ($isControl && $linkedObject->status != Control::STATUS_LOCKED) {
        $row .= ' <span class="classfortooltip opacitymedium" title="' . dol_escape_htmltag($langs->transnoentities('ControlNotOnPublicInterface')) . '"><i class="fas fa-eye-slash"></i></span>';
    }
    $row .= '</td>';
    if ($digiqualiEnabled) {
        if ($isControl) {
            $verdictColor = $linkedObject->verdict == 1 ? 'green' : ($linkedObject->verdict == 2 ? 'red' : 'grey');
            $verdictLabel = $linkedObject->fields['verdict']['arrayofkeyval'][!empty($linkedObject->verdict) ? $linkedObject->verdict : 0] ?? 'N/A';
            $row .= '<td><div class="wpeo-button button-' . $verdictColor . '">' . $langs->trans($verdictLabel) . '</div></td>';
            $row .= '<td>' . dol_print_date($linkedObject->control_date, 'day') . '</td>';
            $row .= '<td>' . dol_print_date($linkedObject->next_control_date, 'day') . '</td>';
        } else {
            $row .= '<td></td><td></td><td></td>';
        }
    }
    // Amount (incl. tax) column: only commercial documents carry an amount; it feeds the grand total
    if ($showAmountColumn) {
        if ($hasAmount($linkedObject, $linkedObjectElement)) {
            $totalAmount += (float) $linkedObject->total_ttc;
            $row .= '<td class="right nowrap">' . price($linkedObject->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
        } else {
            $row .= '<td class="right"></td>';
        }
    }
    $row .= '</tr>';
    return $row;
};

$registrationCertificate = new RegistrationCertificateFr($db);
$registrationCertificate->fetch(!isset($fromProductLot) ? GETPOST('id') : 0, !isset($fromProductLot) ? GETPOST('ref') : '', isset($fromProductLot) ? ' AND t.fk_lot = ' . GETPOST('id') : '');

// Collect all linked objects, keyed by element+id to avoid duplicates. The rows are rendered once all are known:
// the amount column is only displayed when one of them carries an amount
$linkedItems = [];

// Linked objects directly on the registration certificate
// No params = OR query: finds links where regcert is source (digiquali controls) AND where it is target (propals, invoices, etc.)
// The linked objects are keyed by link id, not by object id: key them by object id, else an invoice also found below is listed twice
$registrationCertificate->fetchObjectLinked();
if (!empty($registrationCertificate->linkedObjects)) {
    foreach ($registrationCertificate->linkedObjects as $linkedObjectElement => $linkedObjects) {
        foreach ($linkedObjects as $linkedObject) {
            if (isset($linkedObject->status) && $linkedObject->status == -1) {
                continue;
            }
            $linkedItems[$linkedObjectElement . '_' . $linkedObject->id] = [$linkedObject, $linkedObjectElement];
        }
    }
}

// Controls and surveys linked to the associated product lot
if ($digiqualiEnabled && $registrationCertificate->fk_lot > 0) {
    $productLotLinked = new Productlot($db);
    $productLotLinked->fetch($registrationCertificate->fk_lot);
    // No params = OR query: finds links where lot is source (digiquali controls) AND where it is target
    $productLotLinked->fetchObjectLinked();
    foreach (['digiquali_control', 'digiquali_survey'] as $elementType) {
        if (!empty($productLotLinked->linkedObjects[$elementType])) {
            foreach ($productLotLinked->linkedObjects[$elementType] as $linkedObject) {
                if (isset($linkedObject->status) && $linkedObject->status == -1) {
                    continue;
                }
                $key = $elementType . '_' . $linkedObject->id;
                if (!isset($linkedItems[$key])) {
                    $linkedItems[$key] = [$linkedObject, $elementType];
                }
            }
        }
    }
}

// Invoices and proposals linked through their "registrationcertificatefr" extrafield (set by the New invoice/proposal buttons)
$commercialDocSources = [];
if ($factureEnabled) {
    $commercialDocSources['facture'] = ['table' => 'facture_extrafields', 'class' => 'Facture'];
}
if ($propalEnabled) {
    $commercialDocSources['propal'] = ['table' => 'propal_extrafields', 'class' => 'Propal'];
}
foreach ($commercialDocSources as $elementType => $source) {
    $sql   = 'SELECT fk_object FROM ' . MAIN_DB_PREFIX . $source['table'] . ' WHERE registrationcertificatefr = ' . (int) $registrationCertificate->id;
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $key = $elementType . '_' . $obj->fk_object;
            if (isset($linkedItems[$key])) {
                continue;
            }
            $commercialDoc = new $source['class']($db);
            if ($commercialDoc->fetch($obj->fk_object) > 0) {
                $linkedItems[$key] = [$commercialDoc, $elementType];
            }
        }
        $db->free($resql);
    }
}

// The amount column, and its total, only when one of the linked objects carries an amount
$showAmountColumn = false;
foreach ($linkedItems as [$linkedObject, $linkedObjectElement]) {
    if ($hasAmount($linkedObject, $linkedObjectElement)) {
        $showAmountColumn = true;
        break;
    }
}

$rows = [];
foreach ($linkedItems as $key => [$linkedObject, $linkedObjectElement]) {
    $rows[$key] = $renderLinkedObjectRow($linkedObject, $linkedObjectElement, $digiqualiEnabled, $showAmountColumn, $langs, $totalAmount);
}

$out = load_fiche_titre($langs->transnoentities('LinkedObjects'), '', 'dolicar_color@dolicar');

// Column definitions: keep the header, the per-column search row and the data cells aligned
$columns = [];
$columns[] = ['label' => $langs->trans('ObjectType')];
$columns[] = ['label' => $langs->trans('Object')];
if ($digiqualiEnabled) {
    $columns[] = ['label' => $langs->trans('Sheet')];
}
$columns[] = ['label' => $langs->trans('Mileage')];
$columns[] = ['label' => $langs->trans('Date')];
$columns[] = ['label' => $langs->trans('Status')];
if ($digiqualiEnabled) {
    $columns[] = ['label' => $langs->trans('Verdict'), 'filter' => 'verdict'];
    $columns[] = ['label' => $langs->trans('ControlDate')];
    $columns[] = ['label' => $langs->trans('NextControlDate')];
}
if ($showAmountColumn) {
    $columns[] = ['label' => $langs->trans('AmountTTC'), 'align' => 'right'];
}

$out .= '<table class="noborder centpercent dolicar-linked-objects-table">';

// Header row
$out .= '<tr class="liste_titre">';
foreach ($columns as $column) {
    $out .= '<td' . (($column['align'] ?? '') === 'right' ? ' class="right"' : '') . '>' . $column['label'] . '</td>';
}
$out .= '</tr>';

// Per-column search row (native Dolibarr style, client-side filtering handled by js/modules/linkedObjects.js)
if (!empty($rows)) {
    $verdictValues = [];
    if ($digiqualiEnabled) {
        $control       = new Control($db);
        $verdictValues = $control->fields['verdict']['arrayofkeyval'] ?? [];
    }
    $out .= '<tr class="liste_titre">';
    foreach ($columns as $colIndex => $column) {
        $out .= '<td' . (($column['align'] ?? '') === 'right' ? ' class="right"' : '') . '>';
        if (($column['filter'] ?? '') === 'verdict') {
            $out .= '<select class="dolicar-linked-objects-verdict-filter flat maxwidth100">';
            $out .= '<option value="">&nbsp;</option>';
            foreach ($verdictValues as $verdictKey => $verdictLabel) {
                $out .= '<option value="' . (int) $verdictKey . '">' . $langs->trans($verdictLabel) . '</option>';
            }
            if (!isset($verdictValues[0])) {
                $out .= '<option value="0">N/A</option>';
            }
            $out .= '</select>';
        } else {
            $out .= '<input type="text" class="dolicar-linked-objects-filter flat maxwidth100" data-col="' . $colIndex . '">';
        }
        $out .= '</td>';
    }
    $out .= '</tr>';
}

if (!empty($rows)) {
    $out .= implode('', $rows);
    // Grand total of the linked commercial documents (invoices + proposals)
    if ($showAmountColumn) {
        $out .= '<tr class="liste_total">';
        $out .= '<td class="right" colspan="' . (count($columns) - 1) . '">' . $langs->trans('Total') . '</td>';
        $out .= '<td class="right nowrap">' . price($totalAmount, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
        $out .= '</tr>';
    }
} else {
    $out .= '<tr><td colspan="' . count($columns) . '">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
}

$out .= '</table>'; ?>

<script>
    jQuery('.fichecenter').first().append(<?php echo json_encode($out); ?>);
</script>
