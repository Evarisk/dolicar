<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    lib/dolicar_registrationcertificatefr.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for RegistrationCertificateFr
 */

/**
 * Prepare array of tabs for RegistrationCertificateFr
 *
 * @param	RegistrationCertificateFr	$object		RegistrationCertificateFr
 * @return 	array					Array of tabs
 */
function registration_certificate_prepare_head($object)
{
	global $db, $langs, $conf;

	$langs->load("dolicar@dolicar");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->dolicar->dir_output."/registrationcertificatefr/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/dolicar/view/registrationcertificatefr/registrationcertificatefr_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/dolicar/view/registrationcertificatefr/registrationcertificatefr_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@dolicar:/dolicar/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@dolicar:/dolicar/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'registrationcertificatefr@dolicar');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'registrationcertificatefr@dolicar', 'remove');

	return $head;
}

function get_registration_certificate_fields() {
	$registrationCertificateFields = [
		'A_REGISTRATION_NUMBER'               => 'RegistrationNumber',
		'B_FIRST_REGISTRATION_DATE'           => 'FirstRegistrationDate',
		'C1_OWNER_FULLNAME'                   => 'OwnerFullName',
		'C3_REGISTRATION_ADDRESS'             => 'RegistrationAddress',
		'C4A_VEHICLE_OWNER'                   => 'VehicleOwner',
		'C41_SECOND_OWNER_NUMBER'             => 'SecondOwnerNumber',
		'C41_SECOND_OWNER_NAME'               => 'SecondOwnerName',
		'D1_VEHICLE_BRAND'                    => 'VehicleBrand',
		'D2_VEHICLE_TYPE'                     => 'VehicleType',
		'D21_VEHICLE_CNIT'                    => 'VehicleCNIT',
		'D3_VEHICLE_MODEL'                    => 'VehicleModel',
		'E_VEHICLE_SERIAL_NUMBER'             => 'VehicleSerialNumber',
		'F1_TECHNICAL_PTAC'                   => 'TechnicalPTAC',
		'F2_PTAC'                             => 'PTAC',
		'F3_PTRA'                             => 'PTRA',
		'G_VEHICLE_WEIGHT'                    => 'VehicleWeight',
		'G1_VEHICLE_EMPTY_WEIGHT'             => 'VehicleEmptyWeight',
		'H_VALIDITY_PERIOD'                   => 'ValidityPeriod',
		'I_VEHICLE_REGISTRATION_DATE'         => 'VehicleRegistrationDate',
		'J_VEHICLE_CATEGORY'                  => 'VehicleCategory',
		'J1_NATIONAL_TYPE'                    => 'NationalType',
		'J2_EUROPEAN_BODYWORK'                => 'EuropeanBodyWork',
		'J3_NATIONAL_BODYWORK'                => 'NationalBodyWork',
		'K_TYPE_APPROVAL_NUMBER'              => 'TypeApprovalNumber',
		'P1_CYLINDER_CAPACITY'                => 'CylinderCapacity',
		'P2_MAXIMUM_NET_POWER'                => 'MaximumNetPower',
		'P3_FUEL_TYPE'                        => 'FuelType',
		'P6_NATIONAL_ADMINISTRATIVE_POWER'    => 'NationalAdministrativePower',
		'Q_POWER_TO_WEIGHT_RATIO'             => 'PowerToWeightRatio',
		'S1_SEATING_CAPACITY'                 => 'SeatingCapacity',
		'S2_STANDING_CAPACITY'                => 'StandingCapacity',
		'U1_STATIONARY_NOISE_LEVEL'           => 'StationaryNoiseLevel',
		'U2_MOTOR_SPEED'                      => 'MotorSpeed',
		'V7_CO2_EMISSION'                     => 'CO2Emission',
		'V9_ENVIRONMENTAL_CATEGORY'           => 'EnvironmentalCategory',
		'X1_FIRST_TECHNICAL_INSPECTION_DATE'  => 'FirstTechnicalInspectionDate',
		'Y1_REGIONAL_TAX'                     => 'RegionalTax',
		'Y2_PROFESSIONAL_TAX'                 => 'ProfessionalTax',
		'Y3_ECOLOGICAL_TAX'                   => 'EcologicalTax',
		'Y4_MANAGEMENT_TAX'                   => 'ManagementTax',
		'Y5_FORWARDING_EXPENSES_TAX'          => 'ForwardingExpensesTax',
		'Y6_TOTAL_PRICE_VEHICLE_REGISTRATION' => 'TotalPriceVehicleRegistration',
		'Z1_SPECIFIC_DETAILS'                 => 'SpecificDetails1',
		'Z2_SPECIFIC_DETAILS'                 => 'SpecificDetails2',
		'Z3_SPECIFIC_DETAILS'                 => 'SpecificDetails3',
		'Z4_SPECIFIC_DETAILS'                 => 'SpecificDetails4',
	];
	return $registrationCertificateFields;
}
