<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dolicar/admin/setup.php
 * \ingroup dolicar
 * \brief   DoliCar registration certificate config page
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Load DoliCar libraries
require_once __DIR__ . '/../lib/dolicar.lib.php';
require_once __DIR__ . '/../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Initialize technical objects
$object = new RegistrationCertificateFr($db);

// Initialize view objects
$form = new Form($db);

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', 'DoliCar');
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'registrationcertificate', $title, -1, 'dolicar_color@dolicar');

print load_fiche_titre($langs->transnoentities('ImmatriculationAPIConfig'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('RemainingRequests') . '</td>';
print '<td class="center">';
print '<b>' . (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') ?? 0) . '</b>';
print '</td>';
print '</tr>';

print '</table>';

print load_fiche_titre($langs->transnoentities('RegistrationCertificateFieldsConfig'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Status') . '</td>';
print '<td class="center">' . $langs->transnoentities('ShortInfo') . '</td>';
print '</tr>';

foreach ($object->fields as $registrationCertificateCode => $registrationCertificateField) {
    if ($registrationCertificateField['config'] == 1) {
        print '<tr class="oddeven"><td>' . $langs->transnoentities('Display') . ' ' . $langs->transnoentities($registrationCertificateField['label']) . '</td>';
        print '<td class="center">';
        print ajax_constantonoff('DOLICAR_' . dol_strtoupper($registrationCertificateCode) . '_VISIBLE');
        print '</td>';
        print '<td class="center">';
        print $form->textwithpicto('', $langs->transnoentities('ShowRegistrationCertificateFieldHelp'));
        print '</td>';
        print '</tr>';
    }
}

print '</table>';

print load_fiche_titre($langs->trans('Config'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans('HideObjectDetsDolicarDetails') . '</td>';
print '<td>';
print  $langs->trans('HideObjectDetsDolicarDetailsDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS');
print '</td>';
print '</tr>';

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
