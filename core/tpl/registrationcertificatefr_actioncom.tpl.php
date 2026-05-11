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
 * \file    core/tpl/registrationcertificatefr_actioncom.tpl.php
 * \ingroup dolicar
 * \brief   Template for the actioncom section on the registrationcertificatefr card
 */

/**
 * The following vars must be defined:
 * Global   : $db, $langs, $user
 * Variable : $object (RegistrationCertificateFr)
 */

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';

$MAXEVENT   = 10;
$actionComm = new ActionComm($db);

// Fetch actions from both the card and the vehicle history (productlot) in one sorted list
$filter  = " AND ((a.fk_element = " . (int) $object->id . " AND a.elementtype = '" . $db->escape($object->element) . "')";
if (!empty($object->fk_lot) && $object->fk_lot > 0) {
    $filter .= " OR (a.fk_element = " . (int) $object->fk_lot . " AND a.elementtype = 'productlot')";
}
$filter .= ")";

$listofactions = $actionComm->getActions(0, 0, '', $filter, 'a.datep,a.id', 'DESC', $MAXEVENT + 1);
$num           = is_array($listofactions) ? count($listofactions) : 0;

$usercanaddaction = isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create'));
$morehtmlright    = '';
if ($usercanaddaction) {
    $addUrl        = DOL_URL_ROOT . '/comm/action/card.php?action=create&token=' . newToken()
        . '&origin=' . urlencode($object->element) . '&originid=' . (int) $object->id
        . ($object->fk_soc > 0 ? '&socid=' . (int) $object->fk_soc : '')
        . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id);
    $morehtmlright = dolGetButtonTitle($langs->trans('AddEvent'), '', 'fa fa-plus-circle', $addUrl);
}

print load_fiche_titre($langs->trans('LatestLinkedEvents', $MAXEVENT), $morehtmlright, '', 0, '', '', '');
print '<div class="div-table-responsive-no-min">';
print '<table class="centpercent noborder listactions">';
print '<tr class="liste_titre">';
print getTitleFieldOfList('Ref', 0, $_SERVER['PHP_SELF'], '', '', '', '', '', '', '', 1);
print getTitleFieldOfList('Date', 0, $_SERVER['PHP_SELF'], 'a.datep', '', '', '', '', '', 'center ', 1);
print getTitleFieldOfList('By', 0, $_SERVER['PHP_SELF'], '', '', '', '', '', '', '', 1);
print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', '', '', '', '', 'center ', 1);
print getTitleFieldOfList('Title', 0, $_SERVER['PHP_SELF'], '', '', '', '', '', '', '', 1);
print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', '', '', '', '', 'right ', 1);
print '</tr>';

if (is_array($listofactions) && !empty($listofactions)) {
    $cacheusers  = [];
    $cursorevent = 0;
    foreach ($listofactions as $evt) {
        if ($cursorevent >= $MAXEVENT) {
            break;
        }
        print '<tr class="oddeven">';
        print '<td class="nowraponall nopaddingrightimp">' . $evt->getNomUrl(1, -1) . '</td>';
        print '<td class="center nowraponall celldateheight">';
        print dolOutputDates($evt->datep, $evt->datef, $evt->fulldayevent, 0, '', 'tzuserrel', 1);
        print '</td>';
        print '<td class="nowraponall tdoverflowmax100">';
        if (!empty($evt->userownerid)) {
            if (!isset($cacheusers[$evt->userownerid])) {
                $tmpuser = new User($db);
                $tmpuser->fetch($evt->userownerid);
                $cacheusers[$evt->userownerid] = $tmpuser;
            }
            if ($cacheusers[$evt->userownerid]->id > 0) {
                print $cacheusers[$evt->userownerid]->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
            }
        }
        print '</td>';
        print '<td class="tdoverflowmax100 center valignmiddle" title="' . dolPrintHTML($evt->getTypeLabel(2)) . '">';
        print $evt->getTypePicto('valignmiddle');
        if (preg_match('/PRIVATE/', $evt->code)) {
            print ' ' . img_picto($langs->trans('Private'), 'lock', 'class="valignmiddle"');
        }
        print '</td>';
        print '<td class="tdoverflowmax250">' . $evt->getNomUrl(0) . '</td>';
        print '<td class="right">' . $evt->getLibStatut(3) . '</td>';
        print '</tr>';
        $cursorevent++;
    }
} else {
    print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">' . $langs->trans('None') . '</span></td></tr>';
}

if ($num > $MAXEVENT) {
    print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">' . $langs->trans('More') . '...</span></td></tr>';
}

print '</table>';
print '</div>';
