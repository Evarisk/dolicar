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
if (file_exists("../dolicar.main.inc.php")) $res = @include "../dolicar.main.inc.php";

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

require_once __DIR__ . '/../lib/dolicar.lib.php';
require_once __DIR__ . '/../lib/dolicar_functions.lib.php';
require_once __DIR__ . '/../lib/dolicar_registrationcertificatefr.lib.php';
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

$registrationCertificateFields = get_registration_certificate_fields();

if (is_array($registrationCertificateFields) && !empty($registrationCertificateFields)) {
	foreach ($registrationCertificateFields as $registrationCertificateCode => $registrationCertificateField) {
		print '<tr class="oddeven"><td>' . $langs->transnoentities('Display') . ' ' . $langs->transnoentities($registrationCertificateField) . '</td>';
		print '<td class="center">';
		print ajax_constantonoff('DOLICAR_' . $registrationCertificateCode . '_VISIBLE');
		print '</td>';
		print '<td class="center">';
		print $form->textwithpicto('', $langs->transnoentities('Show' . $registrationCertificateField . 'Help'));
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
