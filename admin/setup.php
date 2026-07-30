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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->adminpage->read;
saturne_check_access($permissionToRead);

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'update_warehouse') {
    $error = 0;

    $warehouseId = GETPOSTINT('DOLICAR_DEFAULT_WAREHOUSE_ID');
    if ($warehouseId > 0) {
        if (!dolibarr_set_const($db, 'DOLICAR_DEFAULT_WAREHOUSE_ID', $warehouseId, 'integer', 0, '', $conf->entity)) {
            $error++;
        }
    } else {
        $error++;
    }

    if (!$error) {
        setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    }
    $action = 'edit';
}

if ($action == 'update_repair_service') {
    $refMask         = GETPOST('DOLICAR_VEHICLE_REPAIR_SERVICE_REF_MASK', 'alphanohtml');
    $descriptionMask = GETPOST('DOLICAR_VEHICLE_REPAIR_SERVICE_DESCRIPTION_MASK', 'alphanohtml');
    $vatRate         = price2num(GETPOST('DOLICAR_VEHICLE_REPAIR_SERVICE_VAT_RATE', 'alpha'));

    // The accounting selects post -1 when nothing is picked
    $accountancyCodeBuy  = GETPOST('DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_BUY', 'alpha');
    $accountancyCodeSell = GETPOST('DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_SELL', 'alpha');
    if ($accountancyCodeBuy == '-1') {
        $accountancyCodeBuy = '';
    }
    if ($accountancyCodeSell == '-1') {
        $accountancyCodeSell = '';
    }

    dolibarr_set_const($db, 'DOLICAR_VEHICLE_REPAIR_SERVICE_REF_MASK', $refMask, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLICAR_VEHICLE_REPAIR_SERVICE_DESCRIPTION_MASK', $descriptionMask, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLICAR_VEHICLE_REPAIR_SERVICE_VAT_RATE', $vatRate, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_BUY', $accountancyCodeBuy, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_SELL', $accountancyCodeSell, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    $action = 'edit';
}

if ($action == 'update_problem_report') {
    $email = GETPOST('DOLICAR_PROBLEM_REPORT_EMAIL', 'alphanohtml');
    dolibarr_set_const($db, 'DOLICAR_PROBLEM_REPORT_EMAIL', $email, 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    $action = 'edit';
}

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

// Warehouse configuration section
print '<br>';
print load_fiche_titre($langs->transnoentities('WarehouseConfig'), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_warehouse">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">';
print $langs->transnoentities('DefaultWarehouseForVehicle') . '<br><span class="opacitymedium">' . $langs->transnoentities('DefaultWarehouseForVehicleDesc') . '</span>';
print '</td>';
print '<td class="center">';
$formproduct = new FormProduct($db);
print $formproduct->selectWarehouses(getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 'DOLICAR_DEFAULT_WAREHOUSE_ID', '', 1, 0, 0, '', 0, 0, [], 'minwidth300');
print '</td>';
print '</tr>';
print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans('Save') . '">';
print '</div>';
print '</form>';

// Vehicle repair service section (issue #475)
print '<br>';
print load_fiche_titre($langs->transnoentities('VehicleRepairServiceConfig'), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_repair_service">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">';
print $langs->transnoentities('VehicleRepairServiceEnabled') . '<br><span class="opacitymedium">' . $langs->transnoentities('VehicleRepairServiceEnabledDesc') . '</span>';
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_VEHICLE_REPAIR_SERVICE_ENABLED');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">';
print $langs->transnoentities('VehicleRepairServiceRefMask') . '<br><span class="opacitymedium">' . $langs->transnoentities('VehicleRepairServiceMaskDesc') . '</span>';
print '</td>';
print '<td class="center">';
print '<input class="flat minwidth300" type="text" name="DOLICAR_VEHICLE_REPAIR_SERVICE_REF_MASK" value="' . dol_escape_htmltag(getDolGlobalString('DOLICAR_VEHICLE_REPAIR_SERVICE_REF_MASK', 'DIVREP-{PLAQUE}-{VIN}')) . '">';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">';
print $langs->transnoentities('VehicleRepairServiceDescriptionMask') . '<br><span class="opacitymedium">' . $langs->transnoentities('VehicleRepairServiceMaskDesc') . '</span>';
print '</td>';
print '<td class="center">';
print '<input class="flat minwidth300" type="text" name="DOLICAR_VEHICLE_REPAIR_SERVICE_DESCRIPTION_MASK" value="' . dol_escape_htmltag(getDolGlobalString('DOLICAR_VEHICLE_REPAIR_SERVICE_DESCRIPTION_MASK', 'Divers réparation sur le véhicule : {PLAQUE} {VIN}')) . '">';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">' . $langs->transnoentities('VehicleRepairServiceVATRate') . '</td>';
print '<td class="center">';
print '<input class="flat maxwidth75" type="text" name="DOLICAR_VEHICLE_REPAIR_SERVICE_VAT_RATE" value="' . dol_escape_htmltag(getDolGlobalString('DOLICAR_VEHICLE_REPAIR_SERVICE_VAT_RATE', '20')) . '"> %';
print '</td>';
print '</tr>';

$accountancyCodes = [
    'DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_BUY'  => 'VehicleRepairServiceAccountancyCodeBuy',
    'DOLICAR_VEHICLE_REPAIR_SERVICE_ACCOUNTANCY_CODE_SELL' => 'VehicleRepairServiceAccountancyCodeSell'
];

if (isModEnabled('accounting')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
    $formAccounting = new FormAccounting($db);
}

foreach ($accountancyCodes as $accountancyCodeConst => $accountancyCodeLabel) {
    print '<tr class="oddeven">';
    print '<td class="nowraponall">' . $langs->transnoentities($accountancyCodeLabel) . '</td>';
    print '<td class="center">';
    if (isModEnabled('accounting')) {
        print $formAccounting->select_account(getDolGlobalString($accountancyCodeConst), $accountancyCodeConst, 1, [], 1, 1, 'minwidth200 maxwidth300', '1');
    } else {
        print '<input class="flat minwidth200" type="text" name="' . $accountancyCodeConst . '" value="' . dol_escape_htmltag(getDolGlobalString($accountancyCodeConst)) . '">';
    }
    print '</td>';
    print '</tr>';
}

print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans('Save') . '">';
print '</div>';
print '</form>';

// Problem report email section (issue #443)
print '<br>';
print load_fiche_titre($langs->transnoentities('ProblemReportConfig'), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_problem_report">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td class="center">' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowraponall">';
print $langs->transnoentities('ProblemReportEmail') . '<br><span class="opacitymedium">' . $langs->transnoentities('ProblemReportEmailDesc') . '</span>';
print '</td>';
print '<td class="center">';
print '<input class="flat minwidth300" type="email" name="DOLICAR_PROBLEM_REPORT_EMAIL" value="' . dol_escape_htmltag(getDolGlobalString('DOLICAR_PROBLEM_REPORT_EMAIL')) . '" placeholder="responsable@example.com">';
print '</td>';
print '</tr>';
print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans('Save') . '">';
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
