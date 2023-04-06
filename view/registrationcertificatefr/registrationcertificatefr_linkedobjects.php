<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       registrationcertificatefr_card.php
 *		\ingroup    dolicar
 *		\brief      Page to create/edit/view registrationcertificatefr
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
	die('Include of dolicar main fails');
}

require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

global $conf, $langs, $user, $db, $hookmanager;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'registrationcertificatefrcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid              = GETPOST('lineid', 'int');

// Initialize technical objects
$object      = new RegistrationCertificateFr($db);
$product     = new Product($db);
$productLot  = new Productlot($db);
$category    = new Categorie($db);
$extrafields = new ExtraFields($db);

$diroutputmassaction = $conf->dolicar->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('registrationcertificatefrcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd = $user->rights->dolicar->registrationcertificatefr->write;
$permissiontodelete = $user->rights->dolicar->registrationcertificatefr->delete;
$permissionnote = $user->rights->dolicar->registrationcertificatefr->write;
$permissiondellink = $user->rights->dolicar->registrationcertificatefr->write;

// Security check - Protection if external user
saturne_check_access($permissiontoread);

$upload_dir = $conf->dolicar->multidir_output[isset($object->entity) ? $object->entity : 1].'/registrationcertificatefr';

/*
 * Actions
 */

/*
 * View
 *
 * Put here all code to build page
 */

$title = $langs->trans("RegistrationCertificateFrLinkedObjects");
$help_url = '';
saturne_header( 0, '', $help_url);

$res = $object->fetch_optionals();

print saturne_get_fiche_head($object, 'linkedobjects', $langs->trans("RegistrationCertificateFr"));

saturne_banner_tab($object);

require_once __DIR__ . '/../../core/tpl/accountancy_linked_objects.tpl.php';

print $outputline;
