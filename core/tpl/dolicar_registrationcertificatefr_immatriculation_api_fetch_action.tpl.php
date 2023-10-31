<?php
require_once __DIR__ . '/../../../../core/lib/admin.lib.php';
// In order to avoid product creation error
$conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

if ($conf->global->DOLICAR_API_REMAINING_REQUESTS_COUNTER <= 0) {
    setEventMessage($langs->trans('ZeroApiRequestsRemaining'), 'errors');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
    exit;
} else if ($conf->global->DOLICAR_API_REMAINING_REQUESTS_COUNTER <= 100) {
    setEventMessage($langs->trans('LessThanHundredApiRequestsRemaining'), 'warning');
}

$apiUrl = 'http://www.immatriculationapi.com/api/reg.asmx/CheckFrance';

$username = $conf->global->DOLICAR_IMMATRICULATION_API_USERNAME;
$registrationNumber = GETPOST('registrationNumber');
$registrationNumber = strtoupper($registrationNumber);

if (dol_strlen($registrationNumber) > 0) {
	$registrationNumber = normalize_registration_number($registrationNumber);
	$existingRegistrationCertificate = $object->fetchAll('', '', 0, 0, ['customsql' => ' ref = "' . $registrationNumber . '"']);

	if (is_array($existingRegistrationCertificate) && !empty($existingRegistrationCertificate)) {
		$existingRegistrationCertificateObject = array_shift($existingRegistrationCertificate);
		$existingRegistrationCertificateId = $existingRegistrationCertificateObject->id;

		setEventMessages($langs->trans("LicencePlateWasAlreadyExisting"), null, 'mesgs');
		header('Location: ' . dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $existingRegistrationCertificateId);
		exit;
	}
}

if (dol_strlen($username) > 0) {
	// Setup request to send json via POST

	$xmlData = @file_get_contents( $apiUrl . '?RegistrationNumber=' . $registrationNumber ."&username=" . $username);

	if (empty($xmlData)) {
		$usernameConfigUrl = DOL_URL_ROOT . '/custom/dolicar/admin/registrationcertificate.php';

		setEventMessage($langs->trans('BadAPIUsernameOrBadLicencePlateFormat', $usernameConfigUrl), 'errors');
		$error++;
		$action = $createRegistrationCertificate ? '' : 'create';
	} else {
		$xml = simplexml_load_string($xmlData);
		$strJson = $xml->vehicleJson;
		$registrationCertificateObject = json_decode($strJson);
        dolibarr_set_const($db, 'DOLICAR_API_REMAINING_REQUESTS_COUNTER', $conf->global->DOLICAR_API_REMAINING_REQUESTS_COUNTER - 1, 'integer', 0, '', $conf->entity);
        dolibarr_set_const($db, 'DOLICAR_API_REQUESTS_COUNTER', $conf->global->DOLICAR_API_REQUESTS_COUNTER + 1, 'integer', 0, '', $conf->entity);
        setEventMessages($langs->trans("LicencePlateInformationsCharged"), null, 'mesgs');
        setEventMessages($langs->trans("RemainingRequests", $conf->global->DOLICAR_API_REMAINING_REQUESTS_COUNTER), null, 'mesgs');
	}
} else {
	$usernameConfigUrl = DOL_URL_ROOT . '/custom/dolicar/admin/registrationcertificate.php';

	setEventMessage($langs->trans('BadAPIUsername', $usernameConfigUrl), 'errors');
	$error++;
	$action = $createRegistrationCertificate ? '' : 'create';
}

if (is_object($registrationCertificateObject)) {
	//Product Creation
	$productRef = $registrationCertificateObject->CarMake->CurrentTextValue . ' ' . $registrationCertificateObject->CarModel->CurrentTextValue . ' ' . $registrationCertificateObject->ExtendedData->version;
	$sanitizedProductRef = dol_sanitizeFileName(dol_string_nospecial(trim($productRef)));
	$result = $product->fetch('', $sanitizedProductRef);

	if ($result <= 0) {
		$product->ref = $productRef;
		$product->label = $productRef;
        $product->status_batch = 1;
		$productId = $product->create($user);

		if ($productId > 0) {
			$resultCategory = $category->fetch(0, $registrationCertificateObject->CarMake->CurrentTextValue);

			if ($category <= 0) {
				$category->label       = $registrationCertificateObject->CarMake->CurrentTextValue;
				$category->description = $registrationCertificateObject->CarMake->CurrentTextValue;
				$category->visible     = 1;
				$category->type        = 'product';
				$category->fk_parent   = $conf->global->DOLICAR_CAR_BRANDS_TAG;
				$categoryID            = $category->create($user);
			} else {
				$categoryID = $category->id;
			}

			$product->setCategories(array($categoryID, $conf->global->DOLICAR_CAR_BRANDS_TAG));
		} else {
			$error++;
		}
	}

	$productLotLabel = $registrationCertificateObject->ExtendedData->numSerieMoteur;
	$resultProductlot = $productLot->fetch(0,$product->id ,$productLotLabel);

	if ($resultProductlot <= 0) {
		$productLot->batch = $productLotLabel;
		$productLot->fk_product = $product->id;
		$resultProductLotCreation = $productLot->create($user);
		$productLot->fetch($resultProductLotCreation);
		$product->fetch($product->id);
		$product->correct_stock_batch(
			$user,
			$conf->global->DOLICAR_DEFAULT_WAREHOUSE_ID,
			1,
			0,
			$langs->trans('ClientVehicle'), // label movement
			0,
			'',
			'',
			$productLot->batch,
			'',
			'dolicar_registrationcertificate',
			0
		);

		if ($resultProductLotCreation <= 0) {
			$error++;
		}
	}

	if ($createRegistrationCertificate > 0) {

		$projectID = $parameters['projectID'];
		$thirdpartyID = $parameters['thirdpartyID'];

		$object->fk_product = $product->id;
		$object->fk_lot = $productLot->id;
		$object->fk_soc = $thirdpartyID;
		$object->fk_project = $projectID;
		$object->a_registration_number = $registrationNumber;

		$registrationDateArray = str_split($registrationCertificateObject->ExtendedData->datePremiereMiseCirculation, 2);
		$formattedRegistrationDate = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2] . $registrationDateArray[3];

		$sqlDate = dol_mktime(12, 0, 0, $registrationDateArray[1], $registrationDateArray[0], $registrationDateArray[2] . $registrationDateArray[3]); // for date without hour, we use gmt

		$object->b_first_registration_date = $sqlDate;
		$object->b_first_registration_dateday = $registrationDateArray[0];
		$object->b_first_registration_datemonth = $registrationDateArray[1];
		$object->b_first_registration_dateyear = $registrationDateArray[2] . $registrationDateArray[3];
		$object->c1_owner_name = '';
		$object->c3_registration_address = '';
		$object->c4a_vehicle_owner = '';
		$object->c41_second_owner_number = '';
		$object->c41_second_owner_name = '';
		$object->d1_vehicle_brand = $registrationCertificateObject->CarMake->CurrentTextValue;
		$object->d2_vehicle_type  = $registrationCertificateObject->ExtendedData->typeVehicule;
		$object->d21_vehicle_cnit = $registrationCertificateObject->ExtendedData->CNIT;
		$object->d3_vehicle_model = $registrationCertificateObject->ExtendedData->libelleModele;
		$object->e_vehicle_serial_number = $registrationCertificateObject->ExtendedData->numSerieMoteur;
		$object->f1_technical_ptac = '';
		$object->f2_ptac = '';
		$object->f3_ptra = '';
		$object->g_vehicle_weight = '';
		$object->g1_vehicle_empty_weight = '';
		$object->h_validity_period = '';
		$object->i_vehicle_registration_date = $registrationCertificateObject->RegistrationDate;
		$object->j_vehicle_category = '';
		$object->j1_national_type = $registrationCertificateObject->ExtendedData->genre;
		$object->j2_european_bodywork = '';
		$object->j3_national_bodywork = '';
		$object->k_type_approval_number = '';
		$object->p1_cylinder_capacity = $registrationCertificateObject->ExtendedData->EngineCC;
		$object->p2_maximum_net_power = '';
		$object->p3_fuel_type = $registrationCertificateObject->FuelType->CurrentTextValue;
		$object->p6_national_administrative_power = $registrationCertificateObject->ExtendedData->puissance;
		$object->q_power_to_weight_ratio = '';
		$object->s1_seating_capacity = $registrationCertificateObject->ExtendedData->nbPlace;
		$object->s2_standing_capacity = '';
		$object->u1_stationary_noise_level = '';
		$object->u2_motor_speed = '';
		$object->v7_co2_emission = $registrationCertificateObject->ExtendedData->Co2;
		$object->v9_environmental_category = '';
		$object->x1_first_technical_inspection_date = '';
		$object->y1_regional_tax = '';
		$object->y2_professional_tax = '';
		$object->y3_ecological_tax = '';
		$object->y4_management_tax = '';
		$object->y5_forwarding_expenses_tax = '';
		$object->y6_total_price_vehicle_registration = '';
		$object->z1_specific_details = '';
		$object->z2_specific_details = '';
		$object->z3_specific_details = '';
		$object->z4_specific_details = '';
		$object->json = json_encode($registrationCertificateObject);

		$registrationCertificateId = $object->create($user);

		$backtopage = dol_buildpath('/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $registrationCertificateId;

	} else {
		$_POST['fk_product'] = $product->id;
		$_POST['fk_lot'] = $productLot->id;
		$_POST['fk_soc'] = $thirdpartyID;
		$_POST['fk_project'] = $projectID;
		$_POST['a_registration_number'] = $registrationNumber;

		$registrationDateArray = str_split($registrationCertificateObject->ExtendedData->datePremiereMiseCirculation, 2);
		$formattedRegistrationDate = $registrationDateArray[0] . '/' . $registrationDateArray[1] . '/' . $registrationDateArray[2] . $registrationDateArray[3];

		$_POST['b_first_registration_date'] = $formattedRegistrationDate;
		$_POST['b_first_registration_dateday'] = $registrationDateArray[0];
		$_POST['b_first_registration_datemonth'] = $registrationDateArray[1];
		$_POST['b_first_registration_dateyear'] = $registrationDateArray[2] . $registrationDateArray[3];
		$_POST['c1_owner_name'] = '';
		$_POST['c3_registration_address'] = '';
		$_POST['c4a_vehicle_owner'] = '';
		$_POST['c41_second_owner_number'] = '';
		$_POST['c41_second_owner_name'] = '';
		$_POST['d1_vehicle_brand'] = $registrationCertificateObject->CarMake->CurrentTextValue;
		$_POST['d2_vehicle_type']  = $registrationCertificateObject->ExtendedData->typeVehicule;
		$_POST['d21_vehicle_cnit'] = $registrationCertificateObject->ExtendedData->CNIT;
		$_POST['d3_vehicle_model'] = $registrationCertificateObject->ExtendedData->libelleModele;
		$_POST['e_vehicle_serial_number'] = $registrationCertificateObject->ExtendedData->numSerieMoteur;
		$_POST['f1_technical_ptac'] = '';
		$_POST['f2_ptac'] = '';
		$_POST['f3_ptra'] = '';
		$_POST['g_vehicle_weight'] = '';
		$_POST['g1_vehicle_empty_weight'] = '';
		$_POST['h_validity_period'] = '';
		$_POST['i_vehicle_registration_date'] = $registrationCertificateObject->RegistrationDate;
		$_POST['j_vehicle_category'] = '';
		$_POST['j1_national_type'] = $registrationCertificateObject->ExtendedData->genre;
		$_POST['j2_european_bodywork'] = '';
		$_POST['j3_national_bodywork'] = '';
		$_POST['k_type_approval_number'] = '';
		$_POST['p1_cylinder_capacity'] = $registrationCertificateObject->ExtendedData->EngineCC;
		$_POST['p2_maximum_net_power'] = '';
		$_POST['p3_fuel_type'] = $registrationCertificateObject->FuelType->CurrentTextValue;
		$_POST['p6_national_administrative_power'] = $registrationCertificateObject->ExtendedData->puissance;
		$_POST['q_power_to_weight_ratio'] = '';
		$_POST['s1_seating_capacity'] = $registrationCertificateObject->ExtendedData->nbPlace;
		$_POST['s2_standing_capacity'] = '';
		$_POST['u1_stationary_noise_level'] = '';
		$_POST['u2_motor_speed'] = '';
		$_POST['v7_co2_emission'] = $registrationCertificateObject->ExtendedData->Co2;
		$_POST['v9_environmental_category'] = '';
		$_POST['x1_first_technical_inspection_date'] = '';
		$_POST['y1_regional_tax'] = '';
		$_POST['y2_professional_tax'] = '';
		$_POST['y3_ecological_tax'] = '';
		$_POST['y4_management_tax'] = '';
		$_POST['y5_forwarding_expenses_tax'] = '';
		$_POST['y6_total_price_vehicle_registration'] = '';
		$_POST['z1_specific_details'] = '';
		$_POST['z2_specific_details'] = '';
		$_POST['z3_specific_details'] = '';
		$_POST['z4_specific_details'] = '';
		$_POST['json'] = json_encode($registrationCertificateObject);

		$action = 'create';
	}
}
