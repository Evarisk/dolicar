<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 SuperAdmin <test@test.fr>
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
 * \brief   DoliCar setup page.
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
	die('Include of dolicar main fails');
}

// Global variables definitions
global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

require_once __DIR__ . '/../lib/dolicar.lib.php';
require_once __DIR__ . '/../lib/dolicar_functions.lib.php';
require_once __DIR__ . '/../lib/dolicar_registrationcertificatefr.lib.php';
//require_once "../class/myclass.class.php";

// Translations
saturne_load_langs(['admin', 'categories']);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolicarsetup', 'globalsetup'));

// Access control
$permissiontoread = $user->rights->dolicar->adminpage->read;
saturne_check_access($permissiontoread);

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = $langs->transnoentities('DolicarSetup');

saturne_header(0, '', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ?: DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'registrationcertificate', $langs->trans($page_name), -1, "dolicar_color@dolicar");

print load_fiche_titre($langs->transnoentities("ImmatriculationAPIConfig"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Parameters") . '</td>';
print '<td class="center">' . $langs->transnoentities("Value") . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('RemainingRequests') . '</td>';
print '<td class="center">';
print '<b>' . ($conf->global->DOLICAR_API_REMAINING_REQUESTS_COUNTER ?? 0) . '</b>';
print '</td>';
print '</tr>';

print '</table>';

print load_fiche_titre($langs->transnoentities("RegistrationCertificateFieldsConfig"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Parameters") . '</td>';
print '<td class="center">' . $langs->transnoentities("Visible") . '</td>';
print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
print '</tr>';

$registrationCertificateFields = get_registration_certificate_fields();

if (is_array($registrationCertificateFields) && !empty($registrationCertificateFields)) {
	foreach ($registrationCertificateFields as $registrationCertificateCode => $registrationCertificateField) {
		print '<tr class="oddeven"><td>' . $langs->transnoentities('Display') . ' ' . $langs->transnoentities($registrationCertificateField) . '</td>';
		print '<td class="center">';
		print ajax_constantonoff('DOLICAR_' . $registrationCertificateCode . '_VISIBLE');
		print '</td>';
		print '<td class="center">';
		print $form->textwithpicto('', $langs->transnoentities('ShowRegistrationCertificateFieldHelp'));
		print '</td>';
		print '</tr>';
	}
}

print '</table>';
print '</div>';

print load_fiche_titre($langs->trans("ProductBatch"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>';
print '<td class="center">' . $langs->trans("ShortInfo") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

// Show logo for company
print '<tr class="oddeven"><td>' . $langs->trans("HideObjectDetsDolicarDetails") . '</td>';
print '<td class="center">';
print  $langs->trans("HideObjectDetsDolicarDetailsDescription");
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS');
print '</td>';
print '</tr>';
print '</form>';

print '</table>';
print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
