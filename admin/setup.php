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
 * \brief   DoliCar setup page
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

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'dolicar_color@dolicar');

// Configuration header
print '<div style="text-indent: 1em"><i class="fas fa-2x fa-calendar-alt" style="padding: 10px"></i>' . $langs->trans('AgendaModuleRequired') . '</div>';
print '<div style="text-indent: 1em"><i class="fas fa-2x fa-tools" style="padding: 10px"></i>' . $langs->trans('HowToSetupOtherModules') . '<a href=' . '"../../../admin/modules.php">' . $langs->trans('ConfigMyModules') . '</a></div>';
print '<div style="text-indent: 1em"><i class="fas fa-2x fa-globe" style="padding: 10px"></i>' . $langs->trans('AvoidLogoProblems') . '<a href="' . $langs->trans('LogoHelpLink') . '">' . $langs->trans('LogoHelpLink') . '</a></div>';
print '<div style="text-indent: 1em"><i class="fab fa-2x fa-css3-alt" style="padding: 10px"></i>' . $langs->trans('HowToSetupIHM') . '<a href=' . '"../../../admin/ihm.php">' . $langs->trans('ConfigIHM') . '</a></div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
