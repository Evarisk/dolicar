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
 * \file    registrationcertificatefr_linkedobjects.php
 * \ingroup dolicar
 * \brief   Page to view registrationcertificatefr linked objects
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
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Global variables definitions
global $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id  = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Initialize technical objects
$object = new RegistrationCertificateFr($db);

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->registrationcertificatefr->read;
saturne_check_access($permissionToRead);

/*
 * View
 */

$title   = $langs->trans('LinkedObjects') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_DoliCar';

saturne_header( 0, '', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'linkedobjects', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';
    require_once __DIR__ . '/../../core/tpl/registrationcertificatefr_linked_objects.tpl.php';
    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
