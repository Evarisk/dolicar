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
 * \file    dolicar/class/actions_dolicar.class.php
 * \ingroup dolicar
 * \brief   DoliCar hook overload
 */

// Load DoliCar libraries
require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

/**
 * Class ActionsDoliCar
 */
class ActionsDoliCar
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string|null String displayed by executeHook() immediately after return
     */
    public ?string $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the hookSetManifest function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function hookSetManifest(array $parameters): int
    {
        if (strpos($_SERVER['PHP_SELF'], 'dolicar') !== false) {
            $this->resprints = dol_buildpath('custom/dolicar/manifest.json.php', 1);
            return 1; // or return 1 to replace standard code
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $conf, $db, $extrafields, $user, $langs; // $conf/$db mandatory for actions_setnotes.inc.php

        if (preg_match('/propalcard|ordercard|invoicecard/', $parameters['context'])) {
            $registrationCertificateFr = new RegistrationCertificateFr($this->db);

            if ($action == 'add') {
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

                    $product = new Product($this->db);

                    $extraFieldsNames = ['registration_number', 'vehicle_model', 'first_registration_date', 'VIN_number', 'linked_product', 'linked_lot'];
                    foreach ($extraFieldsNames as $extraFieldsName) {
                        $extrafields->attributes[$object->element]['list'][$extraFieldsName] = 1;
                    }

                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));
                    $product->fetch($registrationCertificateFr->fk_product);

                    $_POST['options_registration_number'] = $registrationCertificateFr->a_registration_number;
                    $_POST['options_vehicle_model']       = $product->label;
                    $_POST['options_VIN_number']          = $registrationCertificateFr->e_vehicle_serial_number;

                    $bFirstRegistrationDate                        = dol_getdate($registrationCertificateFr->b_first_registration_date);
                    $_POST['options_first_registration_date']      = $bFirstRegistrationDate['mday'] . '/' . $bFirstRegistrationDate['mon'] . '/' . $bFirstRegistrationDate['year'];
                    $_POST['options_first_registration_dateday']   = $bFirstRegistrationDate['mday'];
                    $_POST['options_first_registration_datemonth'] = $bFirstRegistrationDate['mon'];
                    $_POST['options_first_registration_dateyear']  = $bFirstRegistrationDate['year'];

                    $_POST['options_linked_product'] = $registrationCertificateFr->fk_product;
                    $_POST['options_linked_lot']     = $registrationCertificateFr->fk_lot;
                }
            }

            if ($action == 'update_extras') {
                if (GETPOST('attribute') == 'registrationcertificatefr' && !empty(GETPOSTINT('options_registrationcertificatefr'))) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

                    $product = new Product($this->db);

                    $registrationCertificateFrId = GETPOSTINT('options_registrationcertificatefr');
                    $registrationCertificateFr->fetch($registrationCertificateFrId);
                    $product->fetch($registrationCertificateFr->fk_product);

                    $object->array_options['options_registration_number']     = $registrationCertificateFr->a_registration_number;
                    $object->array_options['options_vehicle_model']           = $product->label;
                    $object->array_options['options_VIN_number']              = $registrationCertificateFr->e_vehicle_serial_number;
                    $object->array_options['options_first_registration_date'] = dol_print_date($registrationCertificateFr->b_first_registration_date, 'day');
                    $object->array_options['options_linked_product']          = $registrationCertificateFr->fk_product;
                    $object->array_options['options_linked_lot']              = $registrationCertificateFr->fk_lot;
                    $object->update($user);

                    if ($registrationCertificateFrId > 0) {
                        $object->updateObjectLinked(null, '', $registrationCertificateFr->id, $registrationCertificateFr->table_element);
                        $registrationCertificateFr->add_object_linked($object->element, $object->id);

                        $_POST['note_public']  = $langs->transnoentities('RegistrationNumber') . ' : ' . (dol_strlen($object->array_options['options_registration_number']) > 0 ? $object->array_options['options_registration_number'] : $langs->transnoentities('NoData')) . '<br>';
                        $_POST['note_public'] .= $langs->transnoentities('VehicleModel') . ' : ' . (dol_strlen($object->array_options['options_vehicle_model']) > 0 ? $object->array_options['options_vehicle_model'] : $langs->transnoentities('NoData')) . '<br>';
                        $_POST['note_public'] .= $langs->transnoentities('VINNumber') . ' : ' .  (dol_strlen($object->array_options['options_VIN_number']) > 0 ? $object->array_options['options_VIN_number'] : $langs->transnoentities('NoData')) . '<br>';
                        $_POST['note_public'] .= $langs->transnoentities('FirstRegistrationDate') . ' : ' . ($object->array_options['options_first_registration_date'] > 0 ? dol_print_date($object->array_options['options_first_registration_date'], 'day') : $langs->transnoentities('NoData')) . '<br>';
                        $_POST['note_public'] .= $langs->transnoentities('Mileage') . ' : ' . ($object->array_options['options_mileage'] > 0 ? price($object->array_options['options_mileage'], 0,'',1, 0) : 0) . ' ' . $langs->trans('km') . '<br>';

                        $action         = 'setnote_public';
                        $permissionnote = $user->hasRight($object->element, 'creer');
                        $id             = $object->id;
                        require_once DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php';
                    } else {
                        $object->deleteObjectLinked(null, '', $registrationCertificateFr->id, $registrationCertificateFr->table_element);
                    }
                }

                if (GETPOST('attribute') == 'mileage' && !empty(GETPOSTINT('options_mileage'))) {
                    $mileage = GETPOSTINT('options_mileage');

                    $_POST['note_public']  = $langs->transnoentities('RegistrationNumber') . ' : ' . (dol_strlen($object->array_options['options_registration_number']) > 0 ? $object->array_options['options_registration_number'] : $langs->transnoentities('NoData')) . '<br>';
                    $_POST['note_public'] .= $langs->transnoentities('VehicleModel') . ' : ' . (dol_strlen($object->array_options['options_vehicle_model']) > 0 ? $object->array_options['options_vehicle_model'] : $langs->transnoentities('NoData')) . '<br>';
                    $_POST['note_public'] .= $langs->transnoentities('VINNumber') . ' : ' .  (dol_strlen($object->array_options['options_VIN_number']) > 0 ? $object->array_options['options_VIN_number'] : $langs->transnoentities('NoData')) . '<br>';
                    $_POST['note_public'] .= $langs->transnoentities('FirstRegistrationDate') . ' : ' . ($object->array_options['options_first_registration_date'] > 0 ? dol_print_date($object->array_options['options_first_registration_date'], 'day') : $langs->transnoentities('NoData')) . '<br>';
                    $_POST['note_public'] .= $langs->transnoentities('Mileage') . ' : ' . ($mileage > 0 ? price($mileage, 0,'',1, 0) : 0) . ' ' . $langs->trans('km') . '<br>';

                    $action         = 'setnote_public';
                    $permissionnote = $user->hasRight($object->element, 'creer');
                    $id             = $object->id;
                    require_once DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php';
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadatas (context, etc...)
     * @param CommonObject $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formObjectOptions(array $parameters, $object): int
    {
        global $extrafields, $langs;

        if (preg_match('/propalcard|ordercard|invoicecard/', $parameters['context'])) {
            $picto            = img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth"');
            $extraFieldsNames = ['registration_number', 'vehicle_model', 'VIN_number', 'first_registration_date', 'mileage', 'registrationcertificatefr', 'linked_product', 'linked_lot'];
            foreach ($extraFieldsNames as $extraFieldsName) {
                $extrafields->attributes[$object->element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->element]['label'][$extraFieldsName]);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListOption(array $parameters, $object): int
    {
        global $extrafields, $langs;

        if (preg_match('/propallist|orderlist|invoicelist/', $parameters['context'])) {
            $picto            = img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth"');
            $extraFieldsNames = ['registration_number', 'vehicle_model', 'VIN_number', 'first_registration_date', 'mileage', 'registrationcertificatefr', 'linked_product', 'linked_lot'];
            foreach ($extraFieldsNames as $extraFieldsName) {
                $extrafields->attributes[$object->element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->element]['label'][$extraFieldsName]);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printCommonFooter(array $parameters): int
    {
        global $db, $langs; // // $db/$langs mandatory for TPL

        if ((strpos($parameters['context'], 'productlotcard') !== false) && GETPOST('action', 'aZ09') != 'create') {
            $fromProductLot = 1;
            require_once __DIR__ . '/../core/tpl/registrationcertificatefr_linked_objects.tpl.php';
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneExtendGetObjectsMetadata function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneExtendGetObjectsMetadata(array $parameters): int
    {
        $objectsMetadata['dolicar_registrationcertificatefr'] = [
            'mainmenu'       => 'dolicar',
            'leftmenu'       => '',
            'langs'          => 'RegistrationCertificateFr',
            'langfile'       => 'dolicar@dolicar',
            'picto'          => 'fontawesome_fa-car_fas_#d35968',
            'color'          => '#d35968',
            'class_name'     => 'registrationCertificateFr',
            'post_name'      => 'fk_registrationcertificatefr',
            'link_name'      => 'dolicar_registrationcertificatefr',
            'tab_type'       => 'registrationcertificatefr',
            'table_element'  => 'dolicar_registrationcertificatefr',
            'name_field'     => 'ref',
            'hook_name_card' => 'registrationcertificatefrlist',
            'hook_name_list' => 'registrationcertificatefrcard',
            'create_url'     => 'custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php?action=create',
            'class_path'     => 'custom/dolicar/class/registrationcertificatefr.class.php',
            'lib_path'       => 'custom/dolicar/lib/dolicar_registrationcertificatefr.lib.php'
        ];
        $this->results = $objectsMetadata;

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the quickCreationAction function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function quickCreationAction(array $parameters, CommonObject $object, string $action)
    {
        global $conf, $db, $langs, $user; // $conf/$db/$langs mandatory for TPL

        if (strpos($parameters['context'], 'dolicar_quickcreation') !== false) {
            if (isModEnabled('product')) {
                $product = new Product($this->db);
            }
            if (isModEnabled('productbatch')) {
                require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
                $productLot = new Productlot($this->db);
            }
            if (isModEnabled('categorie')) {
                require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                $category = new Categorie($this->db);
            }

            require_once __DIR__ . '/../lib/dolicar_registrationcertificatefr.lib.php';

            $object = new RegistrationCertificateFr($this->db);

            $backtopage                    = '';
            $createRegistrationCertificate = 1;
            require_once __DIR__ . '/../core/tpl/dolicar_registrationcertificatefr_immatriculation_api_fetch_action.tpl.php';

            if (getDolGlobalInt('DOLICAR_AUTOMATIC_CONTACT_CREATION') > 0 && !empty($parameters['thirdpartyID']) && empty($parameters['$contactID'])) {
                if (isModEnabled('societe')) {
                    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

                    $thirdparty = new Societe($this->db);
                    $contact    = new Contact($this->db);

                    $thirdpartyID = $parameters['thirdpartyID'];

                    $thirdparty->fetch($thirdpartyID);

                    $contact->socid     = $thirdpartyID;
                    $contact->lastname  = $thirdparty->name;
                    $contact->email     = $thirdparty->email;
                    $contact->phone_pro = $thirdparty->phone;

                    $contact->create($user);
                }
            }

            if (dol_strlen($backtopage) > 0){
                $this->resprints = $backtopage;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the digiqualiPublicControlTab function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function digiqualiPublicControlTab(array $parameters): int
    {
        global $langs;

        if (isModEnabled('digiquali') && $parameters['objectType'] == 'productlot') {
            $linkableElement = $parameters['linkableElements'][$parameters['linkedObject']->element];
            if (isset($linkableElement['fk_parent'])) {
                $linkedObjectParentData = [];
                foreach ($parameters['linkableElements'] as $value) {
                    if (isset($value['post_name']) && $value['post_name'] === $linkableElement['fk_parent']) {
                        $linkedObjectParentData = $value;
                        break;
                    }
                }

                if (!empty($linkedObjectParentData['class_path'])) {
                    require_once DOL_DOCUMENT_ROOT . '/' . $linkedObjectParentData['class_path'];

                    $parentLinkedObject = new $linkedObjectParentData['class_name']($this->db);

                    $parentLinkedObject->fetch($parameters['linkedObject']->{$linkableElement['fk_parent']});

                    $categories = $parentLinkedObject->getCategoriesCommon($parentLinkedObject->element);
                    if (in_array(getDolGlobalInt('DOLICAR_VEHICLE_TAG'), $categories)) {
                        $langs->load('dolicar@dolicar');

                        print '<a class="tab" href="' . dol_buildpath('custom/dolicar/public/agenda/public_vehicle_logbook.php?id=' . $parameters['objectId'] . '&entity=' . $parameters['entity'], 1) . '">';
                        print $langs->transnoentities('PublicVehicleLogBook');
                        print '</a>';
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneSetVarsFromFetchObj function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneSetVarsFromFetchObj(array $parameters, object $object): int
    {
        global $conf;

        if (strpos($parameters['context'], 'registrationcertificatefrlist') !== false && isModEnabled('digiquali')) {
            $conf->cache['control']  = null;
            $conf->cache['controls'] = [];
            $object->fetchObjectLinked(null, '', '', 'digiquali_control');
            if (!is_array($object->linkedObjects['digiquali_control']) || empty($object->linkedObjects['digiquali_control'])) {
                return 0;
            }

            $countControls = 0;
            arsort($object->linkedObjects['digiquali_control']);
            foreach ($object->linkedObjects['digiquali_control'] as $controlID => $control) {
                if ($control->status != Control::STATUS_LOCKED) {
                    continue;
                }

                if ($countControls < getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT')) {
                    $conf->cache['controls'][$controlID] = $control;
                }
                $countControls++;
            }
            $conf->cache['control'] = reset($conf->cache['controls']);
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListLoopObject function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturnePrintFieldListLoopObject(array $parameters): int
    {
        global $conf;

        if (strpos($parameters['context'], 'registrationcertificatefrlist') !== false && isModEnabled('digiquali')) {
            $out = [];

            if ($parameters['key'] == 'controls') {
                $firstOccurrence = true;
                $controls        = $conf->cache['controls'];
                if (!is_array($controls) || empty($controls)) {
                    return 0;
                }

                foreach ($controls as $control) {
                    $out[$parameters['key']] .= $control->getNomUrl(1, '', 0, $firstOccurrence ? 'bold' : '') . '<br>';
                    $firstOccurrence = false;
                }
            }

            if ($parameters['key'] == 'control_date') {
                $control = $conf->cache['control'];
                if ($control == null) {
                    return 0;
                }

                $out[$parameters['key']] = dol_print_date($control->{$parameters['key']}, 'day');
            }

            if ($parameters['key'] == 'days_remaining_before_next_control') {
                $control = $conf->cache['control'];
                if ($control == null) {
                    return 0;
                }

                if (dol_strlen($control->next_control_date) > 0) {
                    $nextControl          = (int) round(($control->next_control_date - dol_now('tzuser'))/(3600 * 24));
                    $nextControlDateColor = $control->getNextControlDateColor();
                    $out[$parameters['key']] = '<div class="wpeo-button" style="background-color: ' . $nextControlDateColor .'; border-color: ' . $nextControlDateColor . ' ">' . $nextControl . '</div>';
                }
            }

            if ($parameters['key'] == 'verdict') {
                $control = $conf->cache['control'];
                if ($control == null) {
                    return 0;
                }

                $verdictColor            = $control->{$parameters['key']} == 1 ? 'green' : ($control->{$parameters['key']} == 2 ? 'red' : 'grey');
                $out[$parameters['key']] = '<div class="wpeo-button button-' . $verdictColor . '">' . $control->fields[$parameters['key']]['arrayofkeyval'][(!empty($control->{$parameters['key']})) ? $control->{$parameters['key']} : 0] . '</div>';
            }

            $this->results = $out;
        }

        return 0; // or return 1 to replace standard code
    }
}
