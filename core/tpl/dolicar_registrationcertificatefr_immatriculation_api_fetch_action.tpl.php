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
}

$registrationNumber = dol_strtoupper(GETPOST('registrationNumber'));
$vinNumber          = dol_strtoupper(GETPOST('vinNumber'));
$confirmRetry       = GETPOST('confirm_retry', 'int') == 1;

// Use VIN if provided and not empty, otherwise use registration number
if (!empty($vinNumber)) {
    $searchValue = $vinNumber;
    $searchType  = 'vin';
} else {
    $searchValue = $registrationNumber;
    $searchType  = 'plaque';
}

$apiData                       = $object->getRegistrationCertificateData($searchValue, $searchType, $confirmRetry);
$registrationCertificateObject = isset($apiData['data'])        ? $apiData['data']        : null;
$api                           = isset($apiData['api'])         ? $apiData['api']         : '';
$error                         = isset($apiData['error'])       ? $apiData['error']       : '';
$draftId                       = isset($apiData['draft_id'])    ? (int)$apiData['draft_id'] : 0;
$errorDraft                    = isset($apiData['error_draft']) ? $apiData['error_draft'] : false;

// Previous API attempt had an error: ask the user to confirm before consuming a new token
if ($errorDraft) {
    setEventMessages($langs->trans('RegistrationNumberPreviousApiError', $registrationNumber, $error), [], 'warnings');
    if (!empty($createRegistrationCertificate)) {
        // Quick creation context: redirect back to the same form (no action = display form)
        header('Location: ' . $_SERVER['PHP_SELF'] . '?confirm_retry=1&registrationNumber=' . urlencode($registrationNumber));
    } else {
        // Card creation context
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . urlencode($registrationNumber) . '&confirm_retry=1');
    }
    exit;
}

if (getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER') <= 10) {
    setEventMessages($langs->trans('LessThanHundredApiRequestsRemaining'), [], 'warnings');
}

if ($api == 'apiplaqueimmatriculation.com') {
    if ($error) {
        $_POST['a_registration_number']  = $registrationNumber;
        $_POST['e_vehicle_serial_number'] = $vinNumber;
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
                if ($category->id <= 0) {
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

        $vin = isset($registrationCertificateObject->vin) ? trim((string)$registrationCertificateObject->vin) : '';

        if (!empty($vin)) {
            $productLot->batch      = $vin;
            $productLot->fk_product = $productId;
            $productLotID    = $productLot->create($user);
            $isNewProductLot = $productLotID > 0;
            if ($productLotID == -1) {
                $productLotID = $productLot->fetch(0, $productId, $vin);
            }
        } else {
            // VIN absent from API response: fall back to a default lot so data mapping can proceed
            $productLotID    = create_default_product_lot($productId);
            $isNewProductLot = $productLotID > 0;
        }

        if ($isNewProductLot) {
            $product->correct_stock_batch($user, GETPOSTINT('warehouse_id') > 0 ? GETPOSTINT('warehouse_id') : getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'), 0, '', '', $productLot->batch, '', 'dolicar_registrationcertificate', 0);
        }

        if ($productId > 0 && $productLotID > 0) {

            if (isset($createRegistrationCertificate) && $createRegistrationCertificate > 0) {

                $finalRegistrationNumber = isset($registrationCertificateObject->plaque) ? $registrationCertificateObject->plaque : $registrationNumber;

                // Save a draft on first attempt so API data is preserved even if this process fails
                if ($draftId == 0) {
                    $draftObject                          = new RegistrationCertificateFr($db);
                    $draftObject->a_registration_number   = $finalRegistrationNumber;
                    $draftObject->e_vehicle_serial_number = isset($registrationCertificateObject->vin) ? $registrationCertificateObject->vin : '';
                    $draftObject->json                    = json_encode($registrationCertificateObject);
                    $draftObject->status                  = RegistrationCertificateFr::STATUS_DRAFT;
                    $draftId = $draftObject->create($user);
                }

                // Load the draft and promote it to validated with full data
                $object->fetch($draftId);
                $object->fk_product            = $productId;
                $object->fk_lot                = $productLotID;
                $object->fk_soc                = $parameters['thirdpartyID'];
                $object->fk_project            = $parameters['projectID'];
                $object->a_registration_number = $finalRegistrationNumber;

                $registrationDateArray = explode('-', $registrationCertificateObject->date1erCir_fr);
                $sqlDate               = dol_mktime(12, 0, 0, (int)$registrationDateArray[1], (int)$registrationDateArray[0], (int)$registrationDateArray[2]);

                $object->b_first_registration_date        = $sqlDate;
                $object->d1_vehicle_brand                 = isset($registrationCertificateObject->marque) ? $registrationCertificateObject->marque : '';
                $object->d2_vehicle_type                  = isset($registrationCertificateObject->type_moteur) ? $registrationCertificateObject->type_moteur : '';
                $object->d21_vehicle_cnit                 = isset($registrationCertificateObject->cnit) ? $registrationCertificateObject->cnit : '';
                $object->d3_vehicle_model                 = isset($registrationCertificateObject->modele) ? $registrationCertificateObject->modele : '';
                $object->e_vehicle_serial_number          = isset($registrationCertificateObject->vin) ? $registrationCertificateObject->vin : '';
                $object->j1_national_type                 = isset($registrationCertificateObject->genreVCG) ? $registrationCertificateObject->genreVCG : '';
                $object->p1_cylinder_capacity             = isset($registrationCertificateObject->ccm) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->ccm) : '';
                $object->p3_fuel_type                     = isset($registrationCertificateObject->energieNGC) ? $registrationCertificateObject->energieNGC : '';
                $object->p6_national_administrative_power = isset($registrationCertificateObject->puisFisc) ? (int)$registrationCertificateObject->puisFisc : '';
                $object->s1_seating_capacity              = isset($registrationCertificateObject->nr_passagers) ? (int)$registrationCertificateObject->nr_passagers : '';
                $object->v7_co2_emission                  = isset($registrationCertificateObject->co2) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->co2) : '';
                $object->f2_ptac                          = isset($registrationCertificateObject->ptac) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->ptac) : '';
                $object->g_vehicle_weight                 = isset($registrationCertificateObject->poids) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->poids) : '';
                $object->j2_european_bodywork             = isset($registrationCertificateObject->carrosserieCG) ? $registrationCertificateObject->carrosserieCG : '';
                $object->j3_national_bodywork             = isset($registrationCertificateObject->carrosserie) ? $registrationCertificateObject->carrosserie : '';
                $object->j_vehicle_category               = isset($registrationCertificateObject->genreVCGNGC) ? $registrationCertificateObject->genreVCGNGC : '';
                $object->k_type_approval_number           = isset($registrationCertificateObject->type_mine) ? $registrationCertificateObject->type_mine : '';
                $object->p2_maximum_net_power             = isset($registrationCertificateObject->puisFiscReelKW) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->puisFiscReelKW) : '';
                $object->v9_environmental_category        = isset($registrationCertificateObject->energie) ? $registrationCertificateObject->energie : '';
                $object->h_validity_period                = isset($registrationCertificateObject->date30) ? $registrationCertificateObject->date30 : '';

                if (isset($registrationCertificateObject->date1erCir_us) && !empty($registrationCertificateObject->date1erCir_us)) {
                    $dateUs = explode('-', $registrationCertificateObject->date1erCir_us);
                    if (count($dateUs) == 3) {
                        $object->i_vehicle_registration_date = dol_mktime(12, 0, 0, (int)$dateUs[1], (int)$dateUs[2], (int)$dateUs[0]);
                    }
                }

                $object->json   = json_encode($registrationCertificateObject);
                $object->status = RegistrationCertificateFr::STATUS_VALIDATED;

                $registrationCertificateId = $object->update($user);

                $backtopage = dol_buildpath('custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $registrationCertificateId;
            } else {

                $_POST['fk_product'] = $productId;
                $_POST['fk_lot']     = $productLotID;

                $finalRegistrationNumber = isset($registrationCertificateObject->plaque) ? $registrationCertificateObject->plaque : $registrationNumber;

                $registrationDateArray = explode('-', $registrationCertificateObject->date1erCir_fr);

                $_POST['a_registration_number']            = $finalRegistrationNumber;
                $_POST['b_first_registration_date']        = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2];
                $_POST['b_first_registration_dateday']     = $registrationDateArray[0];
                $_POST['b_first_registration_datemonth']   = $registrationDateArray[1];
                $_POST['b_first_registration_dateyear']    = $registrationDateArray[2];
                $_POST['d1_vehicle_brand']                 = isset($registrationCertificateObject->marque) ? $registrationCertificateObject->marque : '';
                $_POST['d2_vehicle_type']                  = isset($registrationCertificateObject->type_moteur) ? $registrationCertificateObject->type_moteur : '';
                $_POST['d21_vehicle_cnit']                 = isset($registrationCertificateObject->cnit) ? $registrationCertificateObject->cnit : '';
                $_POST['d3_vehicle_model']                 = isset($registrationCertificateObject->modele) ? $registrationCertificateObject->modele : '';
                $_POST['e_vehicle_serial_number']          = isset($registrationCertificateObject->vin) ? $registrationCertificateObject->vin : '';
                $_POST['i_vehicle_registration_date']      = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2];
                $_POST['j1_national_type']                 = isset($registrationCertificateObject->genreVCG) ? $registrationCertificateObject->genreVCG : '';
                $_POST['p1_cylinder_capacity']             = isset($registrationCertificateObject->ccm) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->ccm) : '';
                $_POST['p3_fuel_type']                     = isset($registrationCertificateObject->energieNGC) ? $registrationCertificateObject->energieNGC : '';
                $_POST['p6_national_administrative_power'] = isset($registrationCertificateObject->puisFisc) ? (int)$registrationCertificateObject->puisFisc : '';
                $_POST['s1_seating_capacity']              = isset($registrationCertificateObject->nr_passagers) ? (int)$registrationCertificateObject->nr_passagers : '';
                $_POST['v7_co2_emission']                  = isset($registrationCertificateObject->co2) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->co2) : '';
                $_POST['f2_ptac']                          = isset($registrationCertificateObject->ptac) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->ptac) : '';
                $_POST['g_vehicle_weight']                 = isset($registrationCertificateObject->poids) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->poids) : '';
                $_POST['j2_european_bodywork']             = isset($registrationCertificateObject->carrosserieCG) ? $registrationCertificateObject->carrosserieCG : '';
                $_POST['j3_national_bodywork']             = isset($registrationCertificateObject->carrosserie) ? $registrationCertificateObject->carrosserie : '';
                $_POST['j_vehicle_category']               = isset($registrationCertificateObject->genreVCGNGC) ? $registrationCertificateObject->genreVCGNGC : '';
                $_POST['k_type_approval_number']           = isset($registrationCertificateObject->type_mine) ? $registrationCertificateObject->type_mine : '';
                $_POST['p2_maximum_net_power']             = isset($registrationCertificateObject->puisFiscReelKW) ? preg_replace('/[^0-9]/', '', $registrationCertificateObject->puisFiscReelKW) : '';
                $_POST['v9_environmental_category']        = isset($registrationCertificateObject->energie) ? $registrationCertificateObject->energie : '';
                $_POST['h_validity_period']                = isset($registrationCertificateObject->date30) ? $registrationCertificateObject->date30 : '';

                if (isset($registrationCertificateObject->date1erCir_us) && !empty($registrationCertificateObject->date1erCir_us)) {
                    $dateUs = explode('-', $registrationCertificateObject->date1erCir_us);
                    if (count($dateUs) == 3) {
                        $_POST['i_vehicle_registration_date'] = $dateUs[2] . '/' . $dateUs[1] . '/' . $dateUs[0];
                    }
                }

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
                if ($category->id <= 0) {
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

        $productLotID    = $productLot->create($user);
        $isNewProductLot = $productLotID > 0;
        if ($productLotID == -1) {
            $productLotID = $productLot->fetch(0, $productId, $registrationCertificateObject->ExtendedData->numSerieMoteur);
        }
        if ($isNewProductLot) {
            $product->correct_stock_batch($user, GETPOSTINT('warehouse_id') > 0 ? GETPOSTINT('warehouse_id') : getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'), 0, '', '', $productLot->batch, '', 'dolicar_registrationcertificate', 0);
        }

        if ($productId > 0 && $productLotID > 0) {
            if (isset($createRegistrationCertificate) && $createRegistrationCertificate > 0) {

                // Save a draft on first attempt so API data is preserved even if this process fails
                if ($draftId == 0) {
                    $draftObject                          = new RegistrationCertificateFr($db);
                    $draftObject->a_registration_number   = $registrationNumber;
                    $draftObject->e_vehicle_serial_number = $registrationCertificateObject->ExtendedData->numSerieMoteur;
                    $draftObject->json                    = json_encode($registrationCertificateObject);
                    $draftObject->status                  = RegistrationCertificateFr::STATUS_DRAFT;
                    $draftId = $draftObject->create($user);
                }

                // Load the draft and promote it to validated with full data
                $object->fetch($draftId);
                $object->fk_product            = $productId;
                $object->fk_lot                = $productLotID;
                $object->fk_soc                = $parameters['thirdpartyID'];
                $object->fk_project            = $parameters['projectID'];
                $object->a_registration_number = $registrationNumber;

                $registrationDateArray = str_split($registrationCertificateObject->ExtendedData->datePremiereMiseCirculation, 2);
                $sqlDate               = dol_mktime(12, 0, 0, $registrationDateArray[1], $registrationDateArray[0], $registrationDateArray[2] . $registrationDateArray[3]);

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

                $object->json   = json_encode($registrationCertificateObject);
                $object->status = RegistrationCertificateFr::STATUS_VALIDATED;

                $registrationCertificateId = $object->update($user);

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
            }
        }
    }
}

$action = 'create';
