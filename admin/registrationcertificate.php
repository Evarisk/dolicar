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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/dolicar.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "dolicar@dolicar"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolicarsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$arrayofparameters = array(
	'DOLICAR_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1),
	'DOLICAR_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
	//'DOLICAR_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
	//'DOLICAR_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
	//'DOLICAR_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
	//'DOLICAR_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
	//'DOLICAR_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
	//'DOLICAR_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
);

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;
// Convert arrayofparameter into a formSetup object
if ($useFormSetup && (float) DOL_VERSION >= 15) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	$formSetup = new FormSetup($db);

	// you can use the param convertor
	$formSetup->addItemsFromParamsArray($arrayofparameters);

	// or use the new system see exemple as follow (or use both because you can ;-) )

	/*
	// Hôte
	$item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
	$item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
	$item->cssClass = 'minwidth500';

	// Setup conf DOLICAR_MYPARAM1 as a simple string input
	$item = $formSetup->newItem('DOLICAR_MYPARAM1');

	// Setup conf DOLICAR_MYPARAM1 as a simple textarea input but we replace the text of field title
	$item = $formSetup->newItem('DOLICAR_MYPARAM2');
	$item->nameText = $item->getNameText().' more html text ';

	// Setup conf DOLICAR_MYPARAM3
	$item = $formSetup->newItem('DOLICAR_MYPARAM3');
	$item->setAsThirdpartyType();

	// Setup conf DOLICAR_MYPARAM4 : exemple of quick define write style
	$formSetup->newItem('DOLICAR_MYPARAM4')->setAsYesNo();

	// Setup conf DOLICAR_MYPARAM5
	$formSetup->newItem('DOLICAR_MYPARAM5')->setAsEmailTemplate('thirdparty');

	// Setup conf DOLICAR_MYPARAM6
	$formSetup->newItem('DOLICAR_MYPARAM6')->setAsSecureKey()->enabled = 0; // disabled

	// Setup conf DOLICAR_MYPARAM7
	$formSetup->newItem('DOLICAR_MYPARAM7')->setAsProduct();
	*/

	$setupnotempty = count($formSetup->items);
}


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'alpha');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/dolicar/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'DOLICAR_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object');
		if (!empty($tmpobjectkey)) {
			$constforval = 'DOLICAR_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if ($conf->global->$constforval == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'DOLICAR_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'DOLICAR_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = $langs->transnoentities('DolicarSetup');

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = dolicarAdminPrepareHead();
print dol_get_fiche_head($head, 'registrationcertificate', $langs->trans($page_name), -1, "dolicar@dolicar");

print load_fiche_titre($langs->transnoentities("RegistrationCertificateFields"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("Parameters") . '</td>';
print '<td class="center">' . $langs->transnoentities("Visible") . '</td>';
print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
print '</tr>';

// Registration number visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowRegistrationNumber") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_A_REGISTRATION_NUMBER_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowRegistrationNumberHelp"));
print '</td>';
print '</tr>';

// First registration date visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowFirstRegistrationDate") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_B_FIRST_REGISTRATION_DATE_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowFirstRegistrationDateHelp"));
print '</td>';
print '</tr>';

// Owner fullname visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowOwnerFullname") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_C1_OWNER_FULLNAME_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowOwnerFullnameHelp"));
print '</td>';
print '</tr>';

// Registration address visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowRegistrationAddress") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_C3_REGISTRATION_ADDRESS_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowRegistrationAddressHelp"));
print '</td>';
print '</tr>';

// Owner number visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowOwnerNumber") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_C41_OWNER_NUMBER_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowOwnerNumberHelp"));
print '</td>';
print '</tr>';

// Owner second name visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowOwnerSecondName") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_C41_OWNER_SECOND_NAME_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowOwnerSecondNameHelp"));
print '</td>';
print '</tr>';

// Vehicle serial number visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleSerialNumber") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_E_VEHICLE_SERIAL_NUMBER_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleSerialNumberHelp"));
print '</td>';
print '</tr>';

// Technical PTAC visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowTechnicalPtac") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_F1_TECHNICAL_PTAC_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowTechnicalPtacHelp"));
print '</td>';
print '</tr>';

// PTAC visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowPtac") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_F2_PTAC_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowPtacHelp"));
print '</td>';
print '</tr>';

// Vehicle owner visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleOwner") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_C4A_VEHICLE_OWNER_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleOwnerHelp"));
print '</td>';
print '</tr>';

// Vehicle brand visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleBrand") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_D1_VEHICLE_BRAND_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleBrandHelp"));
print '</td>';
print '</tr>';

// Vehicle type visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleType") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_D2_VEHICLE_TYPE_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleTypeHelp"));
print '</td>';
print '</tr>';

// Vehicle cnit visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleCnit") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_D21_VEHICLE_CNIT_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleCnitHelp"));
print '</td>';
print '</tr>';

// Vehicle model visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleModel") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_D3_VEHICLE_MODEL_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleModelHelp"));
print '</td>';
print '</tr>';

// PTRA visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowPtra") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_F3_PTRA_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowPtraHelp"));
print '</td>';
print '</tr>';

// Vehicle weight visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleWeight") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_G_VEHICLE_WEIGHT_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleWeightHelp"));
print '</td>';
print '</tr>';

// Vehicle empty weight visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleEmptyWeight") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_G1_VEHICLE_EMPTY_WEIGHT_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleEmptyWeightHelp"));
print '</td>';
print '</tr>';

// Vehicle empty weight visible
print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowVehicleEmptyWeight") . '</td>';
print '<td class="center">';
print ajax_constantonoff('DOLICAR_H_VALIDITY_PERIOD_VISIBLE');
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->transnoentities("ShowVehicleEmptyWeightHelp"));
print '</td>';
print '</tr>';

$registrationCertificateFields = [
	'DOLICAR_H_VALIDITY_PERIOD' => 'ValidityPeriod',
	'DOLICAR_I_VEHICLE_REGISTRATION_DATE' => 'VehicleRegistrationDate',
	'DOLICAR_J_VEHICLE_CATEGORY' => 'VehicleCategory',
	'DOLICAR_J1_NATIONAL_TYPE' => 'NationalType',
	'DOLICAR_J2_EUROPEAN_BODYWORK' => 'EuropeanBodywork',
	'DOLICAR_J3_NATIONAL_BODYWORK' => 'NationalBodywork',
	'DOLICAR_K_TYPE_APPROVAL_NUMBER' => 'TypeApprovalNumber',
	'DOLICAR_P1_CYLINDER_CAPACITY' => 'CylinderCapcity',
	'DOLICAR_P2_MAXIMUM_NET_POWER' => 'MaximumNetPower',
	'DOLICAR_P3_FUEL_TYPE' => 'FuelType',
	'DOLICAR_P6_NATIONAL_ADMINISTRATIVE_POWER' => 'NationalAdministrativePower',
	'DOLICAR_H_VALIDITY_PERIOD' => 'ValidityPeriod',

];
		'p1_cylinder_capacity' => array('type'=>'integer', 'label'=>'CylinderCapacity', 'enabled'=>'1', 'position'=>320, 'notnull'=>0, 'visible'=>3,),
		'p2_maximum_net_power' => array('type'=>'integer', 'label'=>'MaximumNetPower', 'enabled'=>'1', 'position'=>330, 'notnull'=>0, 'visible'=>3,),
		'p3_fuel_type' => array('type'=>'varchar(128)', 'label'=>'FuelType', 'enabled'=>'1', 'position'=>340, 'notnull'=>0, 'visible'=>3,),
		'p6_national_administrative_power' => array('type'=>'integer', 'label'=>'NationalAdministrativePower', 'enabled'=>'1', 'position'=>350, 'notnull'=>0, 'visible'=>3,),
		'q_power_to_weight_ratio' => array('type'=>'integer', 'label'=>'PowerToWeightRatio', 'enabled'=>'1', 'position'=>360, 'notnull'=>0, 'visible'=>3,),
		's1_seatingCapacity' => array('type'=>'integer', 'label'=>'SeatingCapacity', 'enabled'=>'1', 'position'=>370, 'notnull'=>0, 'visible'=>3,),
		's2_standing_capacity' => array('type'=>'integer', 'label'=>'StationaryCapacity', 'enabled'=>'1', 'position'=>380, 'notnull'=>0, 'visible'=>3,),
		'u1_stationary_noise_level' => array('type'=>'integer', 'label'=>'StationaryNoiseLevel', 'enabled'=>'1', 'position'=>390, 'notnull'=>0, 'visible'=>3,),
		'u2_motor_speed' => array('type'=>'integer', 'label'=>'MotorSpeed', 'enabled'=>'1', 'position'=>400, 'notnull'=>0, 'visible'=>3,),
		'v7_co2_emission' => array('type'=>'integer', 'label'=>'COEmission', 'enabled'=>'1', 'position'=>410, 'notnull'=>0, 'visible'=>3,),
		'v9_environmental_category' => array('type'=>'varchar(128)', 'label'=>'EnvironmentalCategory', 'enabled'=>'1', 'position'=>420, 'notnull'=>0, 'visible'=>3,),
		'x1_first_technical_inspection_date' => array('type'=>'datetime', 'label'=>'FirstTechnicallnspectionDate', 'enabled'=>'1', 'position'=>430, 'notnull'=>0, 'visible'=>3,),
		'y1_regional_tax' => array('type'=>'double(24,8)', 'label'=>'RegionalTax', 'enabled'=>'1', 'position'=>440, 'notnull'=>0, 'visible'=>3,),
		'y2_professional_tax' => array('type'=>'double(24,8)', 'label'=>'ProfessionalTax', 'enabled'=>'1', 'position'=>450, 'notnull'=>0, 'visible'=>3,),
		'y3_ecological_tax' => array('type'=>'double(24,8)', 'label'=>'EcologicalTax', 'enabled'=>'1', 'position'=>460, 'notnull'=>0, 'visible'=>3,),
		'y4_management_tax' => array('type'=>'double(24,8)', 'label'=>'ManagementTax', 'enabled'=>'1', 'position'=>470, 'notnull'=>0, 'visible'=>3,),
		'y5_forwarding_expenses_tax' => array('type'=>'double(24,8)', 'label'=>'ForwardingExpensesTax', 'enabled'=>'1', 'position'=>480, 'notnull'=>0, 'visible'=>3,),
		'y6_total_price_vehicle_registration' => array('type'=>'double(24,8)', 'label'=>'TotalPriceVehicleRegistration', 'enabled'=>'1', 'position'=>490, 'notnull'=>0, 'visible'=>3,),
		'z1_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails1', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>3,),
		'z2_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails2', 'enabled'=>'1', 'position'=>510, 'notnull'=>0, 'visible'=>3,),
		'z3_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails3', 'enabled'=>'1', 'position'=>520, 'notnull'=>0, 'visible'=>3,),
		'z4_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails4', 'enabled'=>'1', 'position'=>530, 'notnull'=>0, 'visible'=>3,),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>'1', 'position'=>16, 'notnull'=>-1, 'visible'=>-1, 'index'=>1, 'css'=>'maxwidth500', 'validate'=>'1',),
		'fk_lot' => array('type'=>'intege
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
