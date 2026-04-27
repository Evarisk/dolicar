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
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DigiQuali libraries if enabled
$digiqualiEnabled = isModEnabled('digiquali');
if ($digiqualiEnabled) {
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/survey.class.php';
    require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/sheet.class.php';
}

$colspanEmpty = $digiqualiEnabled ? 8 : 4;

$out  = load_fiche_titre($langs->transnoentities('LinkedObjects'), '', 'dolicar_color@dolicar');
$out .= '<table class="noborder centpercent">';
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
$out .= '</tr>';

/**
 * Helper : render a single linked object row
 *
 * @param CommonObject $linkedObject        The linked object to display
 * @param string       $linkedObjectElement The element type key (e.g. 'digiquali_control')
 * @param bool         $digiqualiEnabled    Whether DigiQuali module is active
 * @param Translate    $langs               Translation object
 * @return string                           HTML row string
 */
$renderLinkedObjectRow = static function ($linkedObject, string $linkedObjectElement, bool $digiqualiEnabled, $langs): string {
    global $db;
    $isControl = ($linkedObjectElement === 'digiquali_control');
    $isSurvey  = ($linkedObjectElement === 'digiquali_survey');

    $row  = '<tr>';
    $row .= '<td class="nowrap">' . $langs->transnoentities(ucfirst($linkedObjectElement)) . '</td>';
    $row .= '<td>' . $linkedObject->getNomUrl(1) . '</td>';
    if ($digiqualiEnabled) {
        if ($isControl || $isSurvey) {
            $sheet = new Sheet($db);
            $sheetLink = '';
            if (!empty($linkedObject->fk_sheet) && $sheet->fetch($linkedObject->fk_sheet) > 0) {
                $sheetLink = $sheet->getNomUrl(1) . (!empty($sheet->label) ? ' - ' . $sheet->label : '');
            }
            $row .= '<td>' . $sheetLink . '</td>';
        } else {
            $row .= '<td></td>';
        }
    }
    $row .= '<td>' . (!$isControl && !$isSurvey ? $linkedObject->array_options['options_mileage'] : '') . '</td>';
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
            $rows[$linkedObjectElement . '_' . $linkedObjectId] = $renderLinkedObjectRow($linkedObject, $linkedObjectElement, $digiqualiEnabled, $langs);
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
                    $rows[$key] = $renderLinkedObjectRow($linkedObject, $elementType, $digiqualiEnabled, $langs);
                }
            }
        }
    }
}

if (!empty($rows)) {
    $out .= implode('', $rows);
} else {
    $out .= '<tr><td colspan="' . $colspanEmpty . '">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
}

$out .= '</table>'; ?>

<script>
    jQuery('.fichecenter').first().append(<?php echo json_encode($out); ?>);
</script>
