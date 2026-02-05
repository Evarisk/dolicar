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

// Load DoliCar libraries
require_once __DIR__ . '/../lib/dolicar.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->adminpage->read;
saturne_check_access($permissionToRead);

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	$apiSelected = GETPOST('DOLICAR_REGISTRATION_CERTIFICATE_API', 'alpha');
    $apiKey      = GETPOST('DOLICAR_APIIMMATRICULATION_API_KEY', 'alphanohtml');

	$allowed_apis = array('immatriculationapi.com', 'apiplaqueimmatriculation.com');

	if (in_array($apiSelected, $allowed_apis)) {
		if (!dolibarr_set_const($db, 'DOLICAR_REGISTRATION_CERTIFICATE_API', $apiSelected, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

    if (!dolibarr_set_const($db, 'DOLICAR_APIIMMATRICULATION_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity)) {
        $error++;
    }

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	if ($action == 'update') {
		$action = 'edit';
	}
}
elseif ($action == 'update_car_brands') {
    require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

    // CSRF token check (Dolibarr standard: compare with session newtoken)
    $token = GETPOST('token', 'alphanohtml');
    if (empty($_SESSION['newtoken']) || $token !== $_SESSION['newtoken']) {
        accessforbidden('Bad token');
    }

    $result = RegistrationCertificateFr::updateCarBrandsFromApi();
    // Always stay on setup page; messages are already set in the method
    $action = 'edit';
}

$current_api = getDolGlobalString('DOLICAR_REGISTRATION_CERTIFICATE_API', 'immatriculationapi.com');
$current_api_key = getDolGlobalString('DOLICAR_APIIMMATRICULATION_API_KEY', '');

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', 'DoliCar');
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0,'', $title, $helpUrl);

$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

$head = dolicar_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'dolicar_color@dolicar');

print load_fiche_titre($langs->transnoentities('ImmatriculationAPIConfig'), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->transnoentities('RegistrationCertificateAPI') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td class="nowraponall">';
print '<input type="radio" id="api_immatriculationapi" name="DOLICAR_REGISTRATION_CERTIFICATE_API" value="immatriculationapi.com"' . ($current_api == 'immatriculationapi.com' ? ' checked' : '') . '>';
print '<label for="api_immatriculationapi"> immatriculationapi.com</label>';
print '</td><td class="opacitymedium">';
print $langs->transnoentities('ImmatriculationAPIDefault');
print '</td></tr>';

print '<tr class="oddeven"><td class="nowraponall">';
print '<input type="radio" id="api_apiplaqueimmatriculation" name="DOLICAR_REGISTRATION_CERTIFICATE_API" value="apiplaqueimmatriculation.com"' . ($current_api == 'apiplaqueimmatriculation.com' ? ' checked' : '') . '>';
print '<label for="api_apiplaqueimmatriculation"> apiplaqueimmatriculation.com</label>';
print '</td><td class="opacitymedium">';
print $langs->transnoentities('APIPlaqueImmatriculationDesc');
print '</td></tr>';

$styleApiKeyRow = ($current_api == 'apiplaqueimmatriculation.com') ? '' : ' style="display:none;"';
print '<tr class="oddeven"' . $styleApiKeyRow . '>';
print '<td class="nowraponall">';
print $langs->trans('ApiKey') . ' (apiplaqueimmatriculation.com)';
print '</td>';
print '<td class="center">';
print '<input class="flat minwidth300" type="text" name="DOLICAR_APIIMMATRICULATION_API_KEY" value="' . dol_escape_htmltag($current_api_key) . '">';
print '</td></tr>';


if ($current_api == 'immatriculationapi.com') {
    print '<tr class="oddeven"><td>';
    print $langs->transnoentities('RemainingRequests');
    print '</td><td class="center">';
    print '<b>' . (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') ?? 0) . '</b>';
    print '</td></tr>';
}

print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
print '</div>';
print '</form>';

$carBrandsFile = __DIR__ . '/../core/car_brands.txt';
$carBrands     = [];

if (is_readable($carBrandsFile)) {
    $lines = file($carBrandsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $brand = trim($line);
            if ($brand !== '') {
                $carBrands[] = $brand;
            }
        }
    }
}

print '<br>';
print load_fiche_titre($langs->transnoentities('CarBrands'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('CarBrands') . '</td>';
print '</tr>';

if (!empty($carBrands)) {
    $var = true;
    foreach ($carBrands as $brand) {
        $var = !$var;
        print '<tr class="' . ($var ? 'oddeven' : 'even') . '">';
        print '<td class="nowraponall">' . dol_escape_htmltag($brand) . '</td>';
        print '</tr>';
    }
} else {
    print '<tr class="oddeven"><td class="opacitymedium">' . $langs->transnoentities('NoData') . '</td></tr>';
}

print '</table>';

print '<br>';
print '<div class="center">';
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_car_brands">';
print '<input class="button" type="submit" value="' . $langs->trans('UpdateCarBrandsList') . '">';
print '</form>';
print '</div>';

print dol_get_fiche_end();
llxFooter();
$db->close();
