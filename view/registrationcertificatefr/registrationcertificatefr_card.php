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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

global $conf, $langs, $user, $db, $hookmanager;

// Load translation files required by the page
saturne_load_langs(['other', 'propal', 'interventions']);

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

$permissiontoread   = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd    = $user->rights->dolicar->registrationcertificatefr->write;
$permissiontodelete = $user->rights->dolicar->registrationcertificatefr->delete;

// Security check - Protection if external user
saturne_check_access($permissiontoread);

$upload_dir = $conf->dolicar->multidir_output[isset($object->entity) ? $object->entity : 1].'/registrationcertificatefr';

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create' && $action != 'getRegistrationCertificateData') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__' . '&a_registration_number=' . GETPOST('a_registration_number'));
			}
		}
	}

	if ($subaction == 'getProductBrand') {
		$data = json_decode(file_get_contents('php://input'), true);

		$productId = $data['productId'];

		$brand_name = get_vehicle_brand($productId);
	}

	if ($action == 'add') {
		$registrationNumber = strtoupper(GETPOST('a_registration_number'));
	}

	if ($action == 'getRegistrationCertificateData') {
		require_once __DIR__ . '/../../core/tpl/dolicar_registrationcertificatefr_immatriculation_api_fetch_action.tpl.php';
	}


	$triggermodname = 'DOLICAR_REGISTRATIONCERTIFICATEFR_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("RegistrationCertificateFr");
$help_url = '';
saturne_header( 0, '', $help_url);

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden($langs->trans('NotEnoughPermissions'), 0, 1);
		exit;
	}

	print load_fiche_titre($langs->trans("NewRegistrationCertificateFr"), '', 'object_'.$object->picto);

	print '<hr>';
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="getRegistrationCertificateData">';
    print '<input type="hidden" name="action" value="getRegistrationCertificateData">';
    print '<input type="hidden" name="token" value="'. newToken() .'">';
	print '<table class="border centpercent tableforfieldcreate">';
	print '<tr>';
	print '<td class="titlefieldcreate">';
	print $langs->trans('FindLicencePlateInRepertory');
	print '</td>';
	print '<td class="valuefieldcreate">';
	print '<input class="flat minwidth400 --success" id="registrationNumber" name="registrationNumber" value="'. GETPOST('a_registration_number') .'">';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '</tr>';
	print '</table>';
	print '<div class="center">';
	print '<input type="submit" class="button butAction" value="'. $langs->trans('Search') .'">';
	print '</div>';
	print '</form>';
	print '<hr>';
	print '<br>';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="registrationcertificatefr_create">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print '<input hidden class="car-brand" value="'.$brand_name.'">';

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";
	// Common attributes
	unset($object->fields['fk_lot']);
	unset($object->fields['fk_product']);
	unset($object->fields['a_registration_number']);

	//Registration Number
	print '<tr><td class="fieldrequired">' . $langs->trans('RegistrationNumber') . '</td><td>';
	print '<input class="maxwidth500 widthcentpercentminusxx" id="a_registration_number" name="a_registration_number" value="'. GETPOST("a_registration_number") .'">';
	print '</td></tr>';

	//Fk_product
	$productPost = GETPOST('fk_product') ?: $conf->global->DOLICAR_DEFAULT_VEHICLE;
	print '<tr><td class="">' . $langs->trans('LinkedProduct') . '</td><td>';
	$form->select_produits($productPost, 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProductsOrServices', 0, 'maxwidth500 widthcentpercentminusxx', 1);
	print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/card.php?action=create&statut=0&statut_buy=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('NewProduct') . '"></span></a>';
	print '</td></tr>';

	//Fk_lot
	$productLotPost = GETPOST('fk_lot') ?: 0;
	print'<tr id=""><td >';
	print $langs->trans('DolicarBatch');
	print '</td><td class="lot-container">';
	print '<span class="lot-content">';

    $productLots = saturne_fetch_all_object_type('ProductLot', '', '', 0, 0, $productPost > 0 ? ['customsql' => ' fk_product = ' . $productPost] : []);
    $productLotsData  = [];
    if (is_array($productLots) && !empty($productLots)) {
        foreach ($productLots as $productLotSingle) {
            $productLotsData[$productLotSingle->id] = $productLotSingle->batch;
        }
    }

    print $form::selectarray('fk_lot', $productLotsData, $productLotPost, $langs->transnoentities('SelectProductLots'), '', '', '', '', '', '','', 'maxwidth500 widthcentpercentminusx');

    print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/stock/productlot_card.php?action=create' . ((GETPOST('fk_product') > 0) ? '&fk_product=' . GETPOST('fk_product') : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProductLot') . '"></span></a>';
	print '</span>';
	print '</td></tr>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	$registrationCertificateFields = get_registration_certificate_fields();

	if (is_array($registrationCertificateFields) && !empty($registrationCertificateFields)) {
		foreach ($registrationCertificateFields as $registrationCertificateCode => $registrationCertificateField) {
			$fieldName = 'field_' . strtolower($registrationCertificateCode);
			$confName = 'DOLICAR_' . $registrationCertificateCode . '_VISIBLE';
			if ($conf->global->$confName < 1) {
				?>
				<script>
					$('.' + <?php echo json_encode($fieldName); ?>).hide()
				</script>
				<?php
			} else {
				$counter++;
			}
		}
	}
	?>
	<script>
		$('.' + <?php echo json_encode('field_json'); ?>).hide()
	</script>
	<?php

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("RegistrationCertificateFr"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="registrationcertificatefr_edit">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print '<input hidden class="car-brand" value="'.$brand_name.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	unset($object->fields['ref']);
	unset($object->fields['fk_lot']);
	unset($object->fields['fk_product']);
	unset($object->fields['a_registration_number']);

	//Registration Number
	print '<tr><td class="fieldrequired">' . $langs->trans('RegistrationNumber') . '</td><td>';
	print '<input class="maxwidth500 widthcentpercentminusxx" id="a_registration_number" name="a_registration_number" value="'. ($object->a_registration_number ?: GETPOST("a_registration_number")) .'">';
	print '</td></tr>';

	//Fk_product
	$productPost = GETPOST('fk_product') ?: $object->fk_product ;
	print '<tr><td class="">' . $langs->trans('LinkedProduct') . '</td><td>';
	$form->select_produits($productPost, 'fk_product', '', 0, 1, -1, 2, '', '', '', '', 'SelectProductsOrServices', 0, 'maxwidth500 widthcentpercentminusxx', 1);
	print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/card.php?action=create&statut=0&statut_buy=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProduct') . '"></span></a>';
	print '</td></tr>';

	//Fk_lot
	$productLotPost = GETPOST('fk_lot') ?: $object->fk_lot;
	print'<tr id=""><td>';
	print $langs->trans('DolicarBatch');
	print '</td><td class="lot-container">';
	print '<span class="lot-content">';

    $productLots = saturne_fetch_all_object_type('ProductLot', '', '', 0, 0, $productPost > 0 ? ['customsql' => ' fk_product = ' . $productPost] : []);
    $productLotsData  = [];
    if (is_array($productLots) && !empty($productLots)) {
        foreach ($productLots as $productLotSingle) {
            $productLotsData[$productLotSingle->id] = $productLotSingle->batch;
        }
    }

    print $form::selectarray('fk_lot', $productLotsData, $productLotPost, $langs->transnoentities('SelectProductLots'), '', '', '', '', '', '','', 'maxwidth500 widthcentpercentminusx');
	print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/stock/productlot_card.php?action=create' . ((GETPOST('fk_product') > 0) ? '&fk_product=' . GETPOST('fk_product') : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProductLot') . '"></span></a>';
	print '</span>';
	print '</td></tr>';

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	?>
	<script>
		$('.' + <?php echo json_encode('field_json'); ?>).hide()
	</script>
	<?php

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	print saturne_get_fiche_head($object, 'card', $langs->trans("RegistrationCertificateFr"));

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRegistrationCertificateFr'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------

	saturne_banner_tab($object);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	?>
	<script>
		$('.' + <?php echo json_encode('field_json'); ?>).hide()
	</script>
	<?php

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
            $displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
            print dolGetButtonAction($displayButton, '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

            $displayButton = $onPhone ? '<i class="fas fa-file-signature fa-2x"></i>' : '<i class="fas fa-file-signature"></i>' . ' ' . $langs->trans('NewPropal');
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('/comm/propal/card.php?action=create&socid=' . $object->fk_soc . '&options_registrationcertificatefr=' . $object->id, 3), '', $permissiontoadd);

            $displayButton = $onPhone ? '<i class="fas fa-file-invoice-dollar fa-2x"></i>' : '<i class="fas fa-file-invoice-dollar"></i>' . ' ' . $langs->trans('NewInvoice');
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('/compta/facture/card.php?action=create&socid=' . $object->fk_soc . '&options_registrationcertificatefr=' . $object->id, 3), '', $permissiontoadd);

            $displayButton = $onPhone ? '<i class="fas fa-ambulance fa-2x"></i>' : '<i class="fas fa-ambulance"></i>' . ' ' . $langs->trans('NewIntervention');
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('/fichinter/card.php?action=create&socid=' . $object->fk_soc, 3), '', $permissiontoadd);
		}
		print '</div>'."\n";
	}
}

// End of page
llxFooter();
$db->close();
