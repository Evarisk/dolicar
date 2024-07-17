<?php
/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
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
 * \file    dolicar/admin/quickcreation.php
 * \ingroup dolicar
 * \brief   DoliCar quickcreation config page
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

// Load DoliCar libraries
require_once __DIR__ . '/../lib/dolicar.lib.php';

// Load translation files required by the page
saturne_load_langs();

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
$linkBack = '<a href="'. DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'quickcreation', $title, -1, 'dolicar_color@dolicar');

print load_fiche_titre($langs->transnoentities('Config'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Status') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('AutomaticContactCreation') . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_AUTOMATIC_CONTACT_CREATION');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('ThirdpartyQuickCreation') . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_THIRDPARTY_QUICK_CREATION');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('ContactQuickCreation') . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_CONTACT_QUICK_CREATION');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->transnoentities('ProjectQuickCreation') . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_PROJECT_QUICK_CREATION');
print '</td>';
print '</tr>';

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
