<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/dolicar_registrationcertificatefr_immatriculation_api_fetch_action.tpl.php
 * \ingroup dolicar
 * \brief   Template page for registration certificate immatriculation api action
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $action, $createRegistrationCertificate, $parameters
 * Objects    : $category, $product, $object
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

if (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') <= 0) {
    setEventMessages($langs->trans('ZeroApiRequestsRemaining'), [], 'errors');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
    exit;
} elseif (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') <= 10) {
    setEventMessages($langs->trans('LessThanHundredApiRequestsRemaining'), [], 'warnings');
}

$registrationNumber = GETPOST('registrationNumber');
$registrationNumber = dol_strtoupper($registrationNumber);

$apiData = $object->getRegistrationCertificateData($registrationNumber);
$registrationCertificateObject = isset($apiData['data']) ? $apiData['data'] : null;
$api = isset($apiData['api']) ? $apiData['api'] : '';
$error = isset($apiData['error']) ? $apiData['error'] : '';

if ($api == 'apiplaqueimmatriculation.com') {
    if ($error) {
        setEventMessages($error, [], 'errors');
    }
    if (is_object($registrationCertificateObject)) {

        $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

        $productRef            = $registrationCertificateObject->marque . ' ' . $registrationCertificateObject->modele . ' ' . $registrationCertificateObject->version;
        $product->ref          = $productRef;
        $product->label        = $productRef;
        $product->status_batch = 1;

        $product->fetch(0, dol_sanitizeFileName(dol_string_nospecial(trim($productRef))));
        $productId = $product->id;
        if ($productId <= 0) {
            $productId = $product->create($user);
            if ($productId > 0) {
                $resultCategory = $category->fetch(0, $registrationCertificateObject->marque);
                if ($category <= 0) {
                    $category->label       = $registrationCertificateObject->marque;
                    $category->description = $registrationCertificateObject->marque;
                    $category->visible     = 1;
                    $category->type        = 'product';
                    $category->fk_parent   = getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG');
                    $categoryID            = $category->create($user);
                } else {
                    $categoryID = $category->id;
                }
                $product->setCategories([$categoryID, getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')]);
            }
        }

        $productLot->batch      = $registrationCertificateObject->vin;
        $productLot->fk_product = $productId;

        $productLotID = $productLot->create($user);
        $product->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1,0, $langs->transnoentities('ClientVehicle'), 0, '', '', $productLot->batch, '', 'dolicar_registrationcertificate', 0);

        if ($productId > 0 && $productLotID > 0) {

            if (isset($createRegistrationCertificate) && $createRegistrationCertificate > 0) {

                $object->fk_product            = $productId;
                $object->fk_lot                = $productLotID;
                $object->fk_soc                = $parameters['thirdpartyID'];
                $object->fk_project            = $parameters['projectID'];
                $object->a_registration_number = $registrationNumber;

                $registrationDateArray = explode('-', $registrationCertificateObject->date1erCir_fr);
                $sqlDate               = dol_mktime(12, 0, 0, $registrationDateArray[1], $registrationDateArray[0], $registrationDateArray[2]); // for date without hour, we use gmt

                $object->b_first_registration_date        = $sqlDate;
                $object->d1_vehicle_brand                 = $registrationCertificateObject->marque;
                $object->d2_vehicle_type                  = $registrationCertificateObject->type_moteur;
                $object->d21_vehicle_cnit                 = $registrationCertificateObject->cnit;
                $object->d3_vehicle_model                 = $registrationCertificateObject->modele;
                $object->e_vehicle_serial_number          = $registrationCertificateObject->vin;
                $object->j1_national_type                 = $registrationCertificateObject->genreVCG;
                $object->p1_cylinder_capacity             = $registrationCertificateObject->ccm;
                $object->p3_fuel_type                     = $registrationCertificateObject->energieNGC;
                $object->p6_national_administrative_power = $registrationCertificateObject->puisFisc;
                $object->s1_seating_capacity              = $registrationCertificateObject->nr_passagers;
                $object->v7_co2_emission                  = $registrationCertificateObject->co2;

                $object->json = json_encode($registrationCertificateObject);

                $registrationCertificateId = $object->create($user);

                $backtopage = dol_buildpath('custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $registrationCertificateId;
            } else {

                $_POST['fk_product'] = $productId;
                $_POST['fk_lot']     = $productLotID;

                $registrationDateArray = explode('-', $registrationCertificateObject->date1erCir_fr);

                $_POST['a_registration_number']            = $registrationNumber;
                $_POST['b_first_registration_date']        = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2];
                $_POST['b_first_registration_dateday']     = $registrationDateArray[0];
                $_POST['b_first_registration_datemonth']   = $registrationDateArray[1];
                $_POST['b_first_registration_dateyear']    = $registrationDateArray[2];
                $_POST['d1_vehicle_brand']                 = $registrationCertificateObject->marque;
                $_POST['d2_vehicle_type']                  = $registrationCertificateObject->type_moteur;
                $_POST['d21_vehicle_cnit']                 = $registrationCertificateObject->cnit;
                $_POST['d3_vehicle_model']                 = $registrationCertificateObject->modele;
                $_POST['e_vehicle_serial_number']          = $registrationCertificateObject->vin;
                $_POST['i_vehicle_registration_date']      = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2];
                $_POST['j1_national_type']                 = $registrationCertificateObject->genreVCG;
                $_POST['p1_cylinder_capacity']             = $registrationCertificateObject->ccm;
                $_POST['p3_fuel_type']                     = $registrationCertificateObject->energieNGC;
                $_POST['p6_national_administrative_power'] = $registrationCertificateObject->puisFisc;
                $_POST['s1_seating_capacity']              = $registrationCertificateObject->nr_passagers;
                $_POST['v7_co2_emission']                  = $registrationCertificateObject->co2;

                $_POST['json'] = json_encode($registrationCertificateObject);
            }
        }
    }
}

if ($api == 'immatriculationapi.com') {
    if (is_object($registrationCertificateObject)) {
        // In order to avoid product creation error
        $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

        $productRef            = $registrationCertificateObject->CarMake->CurrentTextValue . ' ' . $registrationCertificateObject->CarModel->CurrentTextValue . ' ' . $registrationCertificateObject->ExtendedData->version;
        $product->ref          = $productRef;
        $product->label        = $productRef;
        $product->status_batch = 1;

        $product->fetch(0, dol_sanitizeFileName(dol_string_nospecial(trim($productRef))));
        $productId = $product->id;
        if ($productId <= 0) {
            $productId = $product->create($user);
            if ($productId > 0) {
                $resultCategory = $category->fetch(0, $registrationCertificateObject->CarMake->CurrentTextValue);
                if ($category <= 0) {
                    $category->label       = $registrationCertificateObject->CarMake->CurrentTextValue;
                    $category->description = $registrationCertificateObject->CarMake->CurrentTextValue;
                    $category->visible     = 1;
                    $category->type        = 'product';
                    $category->fk_parent   = getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG');
                    $categoryID            = $category->create($user);
                } else {
                    $categoryID = $category->id;
                }
                $product->setCategories([$categoryID, getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')]);
            }
        }

        $productLot->batch      = $registrationCertificateObject->ExtendedData->numSerieMoteur;
        $productLot->fk_product = $productId;

        $productLotID = $productLot->create($user);
        $product->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1,0, $langs->transnoentities('ClientVehicle'), 0, '', '', $productLot->batch, '', 'dolicar_registrationcertificate', 0);

        if ($productId > 0 && $productLotID > 0) {
            if (isset($createRegistrationCertificate) && $createRegistrationCertificate > 0) {
                $object->fk_product            = $productId;
                $object->fk_lot                = $productLotID;
                $object->fk_soc                = $parameters['thirdpartyID'];
                $object->fk_project            = $parameters['projectID'];
                $object->a_registration_number = $registrationNumber;

                $registrationDateArray = str_split($registrationCertificateObject->ExtendedData->datePremiereMiseCirculation, 2);
                $sqlDate               = dol_mktime(12, 0, 0, $registrationDateArray[1], $registrationDateArray[0], $registrationDateArray[2] . $registrationDateArray[3]); // for date without hour, we use gmt

                $object->b_first_registration_date        = $sqlDate;
                $object->d1_vehicle_brand                 = $registrationCertificateObject->CarMake->CurrentTextValue;
                $object->d2_vehicle_type                  = $registrationCertificateObject->ExtendedData->typeVehicule;
                $object->d21_vehicle_cnit                 = $registrationCertificateObject->ExtendedData->CNIT;
                $object->d3_vehicle_model                 = $registrationCertificateObject->ExtendedData->libelleModele;
                $object->e_vehicle_serial_number          = $registrationCertificateObject->ExtendedData->numSerieMoteur;
                $object->i_vehicle_registration_date      = $registrationCertificateObject->RegistrationDate;
                $object->j1_national_type                 = $registrationCertificateObject->ExtendedData->genre;
                $object->p1_cylinder_capacity             = $registrationCertificateObject->ExtendedData->EngineCC;
                $object->p3_fuel_type                     = $registrationCertificateObject->FuelType->CurrentTextValue;
                $object->p6_national_administrative_power = $registrationCertificateObject->ExtendedData->puissance;
                $object->s1_seating_capacity              = $registrationCertificateObject->ExtendedData->nbPlace;
                $object->v7_co2_emission                  = $registrationCertificateObject->ExtendedData->Co2;

                $object->json = json_encode($registrationCertificateObject);

                $registrationCertificateId = $object->create($user);

                $backtopage = dol_buildpath('custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $registrationCertificateId;
                header('Location: ' . $backtopage);
                exit;
            } else {
                $_POST['fk_product'] = $productId;
                $_POST['fk_lot']     = $productLotID;

                $registrationDateArray = str_split($registrationCertificateObject->ExtendedData->datePremiereMiseCirculation, 2);

                $_POST['a_registration_number']            = $registrationNumber;
                $_POST['b_first_registration_date']        = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2] . $registrationDateArray[3];
                $_POST['b_first_registration_dateday']     = $registrationDateArray[0];
                $_POST['b_first_registration_datemonth']   = $registrationDateArray[1];
                $_POST['b_first_registration_dateyear']    = $registrationDateArray[2] . $registrationDateArray[3];
                $_POST['d1_vehicle_brand']                 = $registrationCertificateObject->CarMake->CurrentTextValue;
                $_POST['d2_vehicle_type']                  = $registrationCertificateObject->ExtendedData->typeVehicule;
                $_POST['d21_vehicle_cnit']                 = $registrationCertificateObject->ExtendedData->CNIT;
                $_POST['d3_vehicle_model']                 = $registrationCertificateObject->ExtendedData->libelleModele;
                $_POST['e_vehicle_serial_number']          = $registrationCertificateObject->ExtendedData->numSerieMoteur;
                $_POST['i_vehicle_registration_date']      = $registrationCertificateObject->RegistrationDate;
                $_POST['j1_national_type']                 = $registrationCertificateObject->ExtendedData->genre;
                $_POST['p1_cylinder_capacity']             = $registrationCertificateObject->ExtendedData->EngineCC;
                $_POST['p3_fuel_type']                     = $registrationCertificateObject->FuelType->CurrentTextValue;
                $_POST['p6_national_administrative_power'] = $registrationCertificateObject->ExtendedData->puissance;
                $_POST['s1_seating_capacity']              = $registrationCertificateObject->ExtendedData->nbPlace;
                $_POST['v7_co2_emission']                  = $registrationCertificateObject->ExtendedData->Co2;

                $_POST['json'] = json_encode($registrationCertificateObject);

            }
        }
    }

}

$action = 'create';
