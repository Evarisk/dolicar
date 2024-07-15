<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/publicinterface.php
 * \ingroup dolicar
 * \brief   DoliCar publicinterface config page
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

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['publicinterfaceadmin', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'set_public_interface_config') {
    dolibarr_set_const($db, 'DOLICAR_PUBLIC_MAX_ARRIVAL_MILEAGE', GETPOST('max_arrival_mileage'), 'integer', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'publicinterface', $title, -1, 'dolicar_color@dolicar');

print load_fiche_titre($langs->trans('Config'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="public_interface_config">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_public_interface_config">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

// Public Interface UseSignatoryUse signatory
print '<tr class="oddeven"><td>';
print $langs->transnoentities('PublicInterfaceUseSignatory', dol_strtolower($langs->transnoentities('PublicVehicleLogBook')));
print '</td><td>';
print $langs->transnoentities('PublicInterfaceUseSignatoryDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_PUBLIC_INTERFACE_USE_SIGNATORY');
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->transnoentities('PublicInterfaceUser');
print '</td><td>';
print $langs->transnoentities('PublicInterfaceUserDescription');
print '</td>';
print '<td class="center minwidth400 maxwidth500">';
print img_picto($langs->trans('User'), 'user', 'class="pictofixedwidth"') . $form->select_dolusers(getDolGlobalInt('DOLICAR_PUBLIC_INTERFACE_USER'), 'public_interface_user_id', 1, null, 0, '', '', '0', 0, 0, '', 0, '','minwidth400 maxwidth500');
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->transnoentities('MaxArrivalMileage');
print '</td><td>';
print $langs->transnoentities('MaxArrivalMileageDescription');
print '</td>';
print '<td class="center">';
print '<input type="number" name="max_arrival_mileage" min="0" value="' . getDolGlobalInt('DOLICAR_PUBLIC_MAX_ARRIVAL_MILEAGE', 1000) . '"></td>';
print '</td></tr>';

print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
