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
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($_SERVER['PHP_SELF'], 'dolicar') !== false) {
            ?>
            <script>
                $('link[rel="manifest"]').remove();
            </script>
            <?php

            $this->resprints = '<link rel="manifest" href="' . DOL_URL_ROOT . '/custom/dolicar/manifest.json.php' . '" />';
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
        global $extrafields, $user;

        if (preg_match('/propalcard|ordercard|invoicecard/', $parameters['context'])) {
            $registrationCertificateFr = new RegistrationCertificateFr($this->db);

            if ($action == 'add') {
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

                    $product = new Product($this->db);

                    $extraFieldsNames = ['vehicle_model', 'registration_number', 'linked_product', 'linked_lot', 'first_registration_date', 'VIN_number'];
                    foreach ($extraFieldsNames as $extraFieldsName) {
                        $extrafields->attributes[$object->element]['list'][$extraFieldsName] = 1;
                    }

                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));
                    $product->fetch($registrationCertificateFr->fk_product);

                    $_POST['options_vehicle_model']           = $product->label;
                    $_POST['options_registration_number']     = $registrationCertificateFr->a_registration_number;
                    $_POST['options_linked_product']          = $registrationCertificateFr->fk_product;
                    $_POST['options_linked_lot']              = $registrationCertificateFr->fk_lot;
                    $_POST['options_first_registration_date'] = $registrationCertificateFr->b_first_registration_date;
                    $_POST['options_VIN_number']              = $registrationCertificateFr->e_vehicle_serial_number;
                }
            }

            if ($action == 'update_extras') {
                if (GETPOST('attribute') == 'registrationcertificatefr' && !empty(GETPOST('options_registrationcertificatefr'))) {
                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));
                    $object->array_options['options_vehicle_model']           = $registrationCertificateFr->d3_vehicle_model;
                    $object->array_options['options_registration_number']     = $registrationCertificateFr->a_registration_number;
                    $object->array_options['options_linked_product']          = $registrationCertificateFr->fk_product;
                    $object->array_options['options_linked_lot']              = $registrationCertificateFr->fk_lot;
                    $object->array_options['options_first_registration_date'] = $registrationCertificateFr->b_first_registration_date;
                    $object->array_options['options_VIN_number']              = $registrationCertificateFr->e_vehicle_serial_number;
                    $object->update($user);
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
            $picto            = img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth paddingright"');
            $extraFieldsNames = ['registrationcertificatefr', 'vehicle_model', 'mileage', 'registration_number', 'linked_product', 'linked_lot', 'first_registration_date', 'VIN_number'];
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
            $picto            = img_picto('', 'dolicar_color@dolicar', 'class="pictofixedwidth paddingright"');
            $extraFieldsNames = ['registrationcertificatefr', 'vehicle_model', 'mileage', 'registration_number', 'linked_product', 'linked_lot', 'first_registration_date', 'VIN_number'];
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
     * Overloading the beforePDFCreation function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function beforePDFCreation(array $parameters, $object): int
    {
        global $langs;

        if (preg_match('/propalcard|ordercard|invoicecard/', $parameters['context'])) {
            if ($object->array_options['options_registrationcertificatefr'] > 0) {
                $registrationCertificateFr = new RegistrationCertificateFr($this->db);

                $registrationCertificateFr->fetch($object->array_options['options_registrationcertificatefr']);

                $object->note_public  = dol_strlen($object->array_options['options_registration_number']) > 0 ? $langs->transnoentities('RegistrationNumber') . ' : ' . $object->array_options['options_registration_number'] . '<br>' : '';
                $object->note_public .= dol_strlen($object->array_options['options_vehicle_model']) > 0 ? $langs->transnoentities('VehicleModel') . ' : ' . $object->array_options['options_vehicle_model'] . '<br>' : '';
                $object->note_public .= dol_strlen($object->array_options['options_VIN_number']) > 0 ? $langs->transnoentities('VINNumber') . ' : ' .  $object->array_options['options_VIN_number'] . '<br>' : '';
                $object->note_public .= $object->array_options['options_first_registration_date'] > 0 ? $langs->transnoentities('FirstRegistrationDate') . ' : ' . dol_print_date($object->array_options['options_first_registration_date'], 'day') . '<br>' : '';
                $object->note_public .= $object->array_options['options_mileage'] > 0 ? $langs->transnoentities('Mileage') . ' : ' . price($object->array_options['options_mileage'], 0,'',1, 0) . ' ' . $langs->trans('km') . '<br>' : '';
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the extendSheetLinkableObjectsList function : replacing the parent's function with the one below
     *
     * @param  array $linkableObjectTypes  Array of linkable objects
     * @return int                         0 < on error, 0 on success, 1 to replace standard code
     */
    public function extendSheetLinkableObjectsList(array $linkableObjectTypes): int {
        require_once __DIR__ . '/../lib/dolicar_registrationcertificatefr.lib.php';

        $registrationCertificate = new RegistrationCertificateFr($this->db);
        $linkableObjectTypes['dolicar_regcertfr'] = [
            'langs'          => 'RegistrationCertificateFr',
            'langfile'       => 'dolicar@dolicar',
            'picto'          => $registrationCertificate->picto,
            'className'      => 'registrationCertificateFr',
            'name_field'     => 'ref',
            'post_name'      => 'fk_registrationcertificatefr',
            'link_name'      => 'dolicar_regcertfr',
            'tab_type'       => 'registrationcertificatefr',
            'hook_name_list' => 'registrationcertificatefrcard',
            'hook_name_card' => 'registrationcertificatefrlist',
            'create_url'     => 'custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php?action=create',
            'class_path'     => 'custom/dolicar/class/registrationcertificatefr.class.php',
        ];
        $this->results = $linkableObjectTypes;

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
}
