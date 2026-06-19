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
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/survey.class.php';
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/sheet.class.php';
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

// Column count for the "no data" message (+1 for the amount column, always displayed)
$colspanEmpty = ($digiqualiEnabled ? 8 : 4) + 1;

$totalAmount = 0;

/**
 * Helper : render a single linked object row
 *
 * @param  CommonObject $linkedObject        The linked object to display
 * @param  string       $linkedObjectElement The element type key (e.g. 'digiquali_control')
 * @param  bool         $digiqualiEnabled    Whether DigiQuali module is active
 * @param  Translate    $langs               Translation object
 * @param  float        $totalAmount         Running total of amounts (passed by reference)
 * @return string                            HTML row string
 */
$renderLinkedObjectRow = static function ($linkedObject, string $linkedObjectElement, bool $digiqualiEnabled, $langs, &$totalAmount): string {
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
    if ($isCommercialDoc && isset($linkedObject->total_ttc)) {
        $totalAmount += (float) $linkedObject->total_ttc;
        $row .= '<td class="right nowrap">' . price($linkedObject->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
    } else {
        $row .= '<td class="right"></td>';
    }
    $row .= '</tr>';
    return $row;
};

$registrationCertificate = new RegistrationCertificateFr($db);
$registrationCertificate->fetch(!isset($fromProductLot) ? GETPOST('id') : 0, !isset($fromProductLot) ? GETPOST('ref') : '', isset($fromProductLot) ? ' AND t.fk_lot = ' . GETPOST('id') : '');

// Collect all rows, keyed by element+id to avoid duplicates
$rows = [];

// Linked objects directly on the registration certificate
// No params = OR query: finds links where regcert is source (digiquali controls) AND where it is target (propals, invoices, etc.)
$registrationCertificate->fetchObjectLinked();
if (!empty($registrationCertificate->linkedObjects)) {
    foreach ($registrationCertificate->linkedObjects as $linkedObjectElement => $linkedObjects) {
        foreach ($linkedObjects as $linkedObjectId => $linkedObject) {
            if (isset($linkedObject->status) && $linkedObject->status == -1) {
                continue;
            }
            $rows[$linkedObjectElement . '_' . $linkedObjectId] = $renderLinkedObjectRow($linkedObject, $linkedObjectElement, $digiqualiEnabled, $langs, $totalAmount);
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
            foreach ($productLotLinked->linkedObjects[$elementType] as $linkedObjectId => $linkedObject) {
                if (isset($linkedObject->status) && $linkedObject->status == -1) {
                    continue;
                }
                $key = $elementType . '_' . $linkedObjectId;
                if (!isset($rows[$key])) {
                    $rows[$key] = $renderLinkedObjectRow($linkedObject, $elementType, $digiqualiEnabled, $langs, $totalAmount);
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
            if (isset($rows[$key])) {
                continue;
            }
            $commercialDoc = new $source['class']($db);
            if ($commercialDoc->fetch($obj->fk_object) > 0) {
                $rows[$key] = $renderLinkedObjectRow($commercialDoc, $elementType, $digiqualiEnabled, $langs, $totalAmount);
            }
        }
        $db->free($resql);
    }
}

$out = load_fiche_titre($langs->transnoentities('LinkedObjects'), '', 'dolicar_color@dolicar');

// Search / filter toolbar (client-side filtering handled by js/modules/linkedObjects.js)
if (!empty($rows)) {
    $out .= '<div class="dolicar-linked-objects-toolbar">';
    $out .= '<input type="text" class="dolicar-linked-objects-search" placeholder="' . dol_escape_htmltag($langs->trans('Search')) . '">';
    if ($digiqualiEnabled) {
        $control       = new Control($db);
        $verdictValues = $control->fields['verdict']['arrayofkeyval'] ?? [];
        $out .= '<select class="dolicar-linked-objects-verdict-filter flat">';
        $out .= '<option value="">' . $langs->trans('Verdict') . '</option>';
        foreach ($verdictValues as $verdictKey => $verdictLabel) {
            $out .= '<option value="' . (int) $verdictKey . '">' . $langs->trans($verdictLabel) . '</option>';
        }
        if (!isset($verdictValues[0])) {
            $out .= '<option value="0">N/A</option>';
        }
        $out .= '</select>';
    }
    $out .= '</div>';
}

$out .= '<table class="noborder centpercent dolicar-linked-objects-table">';
$out .= '<tr class="liste_titre">';
$out .= '<td>' . $langs->trans('ObjectType') . '</td>';
$out .= '<td>' . $langs->trans('Object') . '</td>';
if ($digiqualiEnabled) {
    $out .= '<td>' . $langs->trans('Sheet') . '</td>';
}
$out .= '<td>' . $langs->trans('Mileage') . '</td>';
$out .= '<td>' . $langs->trans('Date') . '</td>';
if ($digiqualiEnabled) {
    $out .= '<td>' . $langs->trans('Verdict') . '</td>';
    $out .= '<td>' . $langs->trans('ControlDate') . '</td>';
    $out .= '<td>' . $langs->trans('NextControlDate') . '</td>';
}
$out .= '<td class="right">' . $langs->trans('AmountTTC') . '</td>';
$out .= '</tr>';

if (!empty($rows)) {
    $out .= implode('', $rows);
    // Grand total of the linked commercial documents (invoices + proposals)
    $out .= '<tr class="liste_total">';
    $out .= '<td class="right" colspan="' . ($colspanEmpty - 1) . '">' . $langs->trans('Total') . '</td>';
    $out .= '<td class="right nowrap">' . price($totalAmount, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
    $out .= '</tr>';
} else {
    $out .= '<tr><td colspan="' . $colspanEmpty . '">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
}

$out .= '</table>'; ?>

<script>
    jQuery('.fichecenter').first().append(<?php echo json_encode($out); ?>);
</script>
