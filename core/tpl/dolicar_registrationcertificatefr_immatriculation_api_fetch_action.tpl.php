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

if (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') <= 0) {
    setEventMessages($langs->trans('ZeroApiRequestsRemaining'), [], 'errors');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
    exit;
} elseif (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') <= 10) {
    setEventMessages($langs->trans('LessThanHundredApiRequestsRemaining'), [], 'warnings');
}

$apiUrl             = 'https://www.immatriculationapi.com/api/reg.asmx/CheckFrance';
$username           = getDolGlobalString('DOLICAR_IMMATRICULATION_API_USERNAME');
$registrationNumber = GETPOST('registrationNumber');
$registrationNumber = dol_strtoupper($registrationNumber);

if (dol_strlen($registrationNumber) > 0) {
    $registrationNumber = normalize_registration_number($registrationNumber);

    $result = $object->fetch('', $registrationNumber);
    if ($result > 0) {
        setEventMessages($langs->trans('LicencePlateWasAlreadyExisting'), []);
        header('Location: ' . dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $object->id);
        exit;
    }
}

if (dol_strlen($username) > 0) {
    // Setup request to send json via POST
    $xmlData = @file_get_contents( $apiUrl . '?RegistrationNumber=' . $registrationNumber . '&username=' . $username);
    if (empty($xmlData)) {
        setEventMessages($langs->trans('BadAPIUsernameOrBadLicencePlateFormat'), [], 'errors');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
        exit;
    } else {
        $xml     = simplexml_load_string($xmlData);
        $strJson = $xml->vehicleJson;
        $registrationCertificateObject = json_decode($strJson);
        dolibarr_set_const($db, 'DOLICAR_API_REMAINING_REQUESTS_COUNTER', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER') - 1, 'integer', 0, '', $conf->entity);
        dolibarr_set_const($db, 'DOLICAR_API_REQUESTS_COUNTER', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER') + 1, 'integer', 0, '', $conf->entity);
        setEventMessages($langs->trans('LicencePlateInformationsCharged'), []);
        setEventMessages($langs->trans('RemainingRequests', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER')), []);
    }
} else {
    setEventMessages($langs->trans('BadAPIUsername'), [], 'errors');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
    exit;
}

if (is_object($registrationCertificateObject)) {
    // In order to avoid product creation error
    $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

    $productRef            = $registrationCertificateObject->CarMake->CurrentTextValue . ' ' . $registrationCertificateObject->CarModel->CurrentTextValue . ' ' . $registrationCertificateObject->ExtendedData->version;
    $product->ref          = $productRef;
    $product->label        = $productRef;
    $product->status_batch = 1;

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

        $productLot->batch      = $registrationCertificateObject->ExtendedData->numSerieMoteur;
        $productLot->fk_product = $productId;

        $productLotID = $productLot->create($user);

        $product->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1,0, $langs->transnoentities('ClientVehicle'), 0, '', '', $productLot->batch, '', 'dolicar_registrationcertificate', 0);
    } else {
        $productId    = -1;
        $productLotID = -1;
    }

    if ($productId > 0 && $productLotID > 0) {
        if ($createRegistrationCertificate > 0) {
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

            $action = 'create';
        }
    }
}
