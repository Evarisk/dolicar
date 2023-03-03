<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       dolicar/dolicarindex.php
 *	\ingroup    dolicar
 *	\brief      Home page of dolicar top menu
 */

// Load DoliCar environment
if (file_exists('dolicar.main.inc.php')) {
	require_once __DIR__ . '/dolicar.main.inc.php';
} else {
	die('Include of dolicar main fails');
}

global $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $user;

// Libraries
require_once __DIR__ . '/core/modules/mod' . $moduleName . '.class.php';

// Load translation files required by the page
saturne_load_langs();

// Initialize technical objects
$classname = 'mod' . $moduleName;
$modModule = new $classname($db);

// Security check
$permissiontoread = $user->rights->$moduleNameLowerCase->read;
saturne_check_access($permissiontoread, null, true);

/*
 * View
 */

$title   = $langs->trans('ModuleArea', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title . ' ' . $modModule->version, $helpUrl);

print load_fiche_titre($title . ' ' . $modModule->version, '', $moduleNameLowerCase . '_color.png@' . $moduleNameLowerCase);

// End of page
llxFooter();
$db->close();
