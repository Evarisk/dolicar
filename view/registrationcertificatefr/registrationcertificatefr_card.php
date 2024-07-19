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
 * \file    registrationcertificatefr_card.php
 * \ingroup dolicar
 * \brief   Page to create/edit/view registrationcertificatefr
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['propal', 'interventions']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'registrationcertificatefrcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object      = new RegistrationCertificateFr($db);
$product     = new Product($db);
$productLot  = new Productlot($db);
$category    = new Categorie($db);
$extrafields = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['registrationcertificatefrcard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST('search_all', 'alpha');
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead   = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd    = $user->rights->dolicar->registrationcertificatefr->write;
$permissiontodelete = $user->rights->dolicar->registrationcertificatefr->delete;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create' && $action != 'getRegistrationCertificateData') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    if ($action == 'getRegistrationCertificateData') {
        require_once __DIR__ . '/../../core/tpl/dolicar_registrationcertificatefr_immatriculation_api_fetch_action.tpl.php';
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/banner_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_DoliCar';

saturne_header( 0, '', $title, $helpUrl);

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('New' . ucfirst($object->element)), '', 'object_' . $object->picto);

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
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    $_POST['fk_product']                           = getDolGlobalInt('DOLICAR_DEFAULT_VEHICLE');
    $object->fields['d1_vehicle_brand']['default'] = get_vehicle_brand(GETPOST('fk_product'));

    // Fk_lot
    print'<tr class="field_fk_lot"><td>';
    print $langs->trans('DolicarBatch');
    print '</td><td>';
    $productLotsData = [];
    $productLots     = saturne_fetch_all_object_type('ProductLot', '', '', 0, 0, ['customsql' => 't.fk_product = ' . GETPOST('fk_product')]);
    if (is_array($productLots) && !empty($productLots)) {
        foreach ($productLots as $productLotSingle) {
            $productLotsData[$productLotSingle->id] = $productLotSingle->batch;
        }
    }
    print img_picto('', 'lot', 'class="pictofixedwidth"') . $form::selectarray('fk_lot', $productLotsData, GETPOST('fk_lot'), $langs->transnoentities('SelectProductLots'), '', '', '', '', '', '','', 'maxwidth500 widthcentpercentminusx');
    print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/stock/productlot_card.php?action=create' . ((GETPOST('fk_product') > 0) ? '&fk_product=' . GETPOST('fk_product') : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProductLot') . '"></span></a>';
    print '</td></tr>';

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>'; ?>

    <script>
        const $table     = $('.tableforfieldcreate');
        const $rowToMove = $table.find('.field_fk_lot');
        const $targetRow = $table.find('.field_fk_product');

        $targetRow.after($rowToMove);
    </script>
    <?php
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans('Modify' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" id="registrationcertificatefr_edit">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    print '<input hidden class="car-brand" value="' . $brandName . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">';

    // Fk_lot
    print'<tr class="field_fk_lot"><td>';
    print $langs->trans('DolicarBatch');
    print '</td><td>';
    $productLotsData = [];
    $productLots     = saturne_fetch_all_object_type('ProductLot', '', '', 0, 0, ['customsql' => 't.fk_product = ' . GETPOST('fk_product') > 0 ? GETPOST('fk_product') : $object->fk_product]);
    if (is_array($productLots) && !empty($productLots)) {
        foreach ($productLots as $productLotSingle) {
            $productLotsData[$productLotSingle->id] = $productLotSingle->batch;
        }
    }
    print img_picto('', 'lot', 'class="pictofixedwidth"') . $form::selectarray('fk_lot', $productLotsData, GETPOST('fk_lot') > 0 ? GETPOST('fk_lot') : $object->fk_lot, $langs->transnoentities('SelectProductLots'), '', '', '', '', '', '','', 'maxwidth500 widthcentpercentminusx');
    print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/product/stock/productlot_card.php?action=create' . ((GETPOST('fk_product') > 0) ? '&fk_product=' . GETPOST('fk_product') : $object->fk_product) . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddProductLot') . '"></span></a>';
    print '</td></tr>';

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>'; ?>

    <script>
        const $table     = $('.tableforfieldedit');
        const $rowToMove = $table.find('.field_fk_lot');
        const $targetRow = $table.find('.field_fk_product');

        $targetRow.after($rowToMove);
    </script>
    <?php
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object);

    $formConfirm = '';

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formConfirm];
    $resHook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($resHook)) {
        $formConfirm .= $hookmanager->resPrint;
    } elseif ($resHook > 0) {
        $formConfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formConfirm;

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    unset($object->fields['fk_soc']);     // Hide field already shown in banner
    unset($object->fields['fk_project']); // Hide field already shown in banner

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    // Buttons for actions
    if ($action != 'presend') {
        print '<div class="tabsAction">';
        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($resHook)) {
            $displayButton = $conf->browser->layout == 'classic' ? '<i class="fas fa-edit pictofixedwidth"></i>' . $langs->trans('Modify') : '<i class="fas fa-edit fa-2x"></i>';
            print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);

            $displayButton = $conf->browser->layout == 'classic' ? '<i class="fas fa-file-signature pictofixedwidth"></i>' . $langs->trans('NewPropal') : '<i class="fas fa-file-signature fa-2x"></i>';
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('comm/propal/card.php?action=create&socid=' . $object->fk_soc . '&options_registrationcertificatefr=' . $object->id, 3), '', $permissiontoadd);

            $displayButton = $conf->browser->layout == 'classic' ? '<i class="fas fa-file-invoice-dollar"></i>' . ' ' . $langs->trans('NewInvoice') : '<i class="fas fa-file-invoice-dollar fa-2x"></i>';
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('compta/facture/card.php?action=create&socid=' . $object->fk_soc . '&options_registrationcertificatefr=' . $object->id, 3), '', $permissiontoadd);

            $displayButton = $conf->browser->layout == 'classic' ? '<i class="fas fa-ambulance"></i>' . ' ' . $langs->trans('NewIntervention') : '<i class="fas fa-ambulance fa-2x"></i>';
            print dolGetButtonAction($displayButton, '', 'default', dol_buildpath('fichinter/card.php?action=create&socid=' . $object->fk_soc, 3), '', $permissiontoadd);
        }
        print '</div>';
    }
}

// End of page
llxFooter();
$db->close();
