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
function registrationcertificatefr_prepare_head(CommonObject $object): array
{
	// Global variables definitions
	global $conf, $db, $langs, $user;

	// Load translation files required by the page
	saturne_load_langs();

	// Initialize values
	$h = 0;
	$head = [];
	$objectType = $object->element;

	$head[$h][0] = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $object->id;
	$head[$h][1] = '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_linkedobjects.php', 1) . '?id=' . $object->id;
	$head[$h][1] = '<i class="fas fa-link pictofixedwidth"></i>' . $langs->trans('LinkedObjects');
	$head[$h][2] = 'linkedobjects';
	$h++;

	if ($user->rights->dolicar->$objectType->read) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dol_buildpath('/saturne/view/saturne_note.php', 1) . '?id=' . $object->id . '&module_name=DoliCar&object_type=' . $objectType;
			$head[$h][1] = '<i class="fas fa-comment pictofixedwidth"></i>' . $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$upload_dir = $conf->dolicar->dir_output . '/audit/' . dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $objectType, $object->id);
		$head[$h][0] = dol_buildpath('/saturne/view/saturne_document.php', 1) . '?id=' . $object->id . '&module_name=DoliCar&object_type=' . $objectType;
		$head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
		}
		$head[$h][2] = 'document';
		$h++;

		$head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliCar&object_type=' . $objectType;
		$head[$h][1] = '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events');
		if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
			$nbEvent = 0;
			// Enable caching of session count actioncomm
			require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
			$cachekey = 'count_events_session_' . $object->id;
			$dataretrieved = dol_getcache($cachekey);
			if (!is_null($dataretrieved)) {
				$nbEvent = $dataretrieved;
			} else {
				$sql = 'SELECT COUNT(id) as nb';
				$sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
				$sql .= ' WHERE fk_element = ' . ((int)$object->id);
				$sql .= " AND elementtype = '" . $objectType . '@dolicar' . "'";
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$nbEvent = $obj->nb;
				} else {
					dol_syslog('Failed to count actioncomm ' . $db->lasterror(), LOG_ERR);
				}
				dol_setcache($cachekey, $nbEvent, 120); // If setting cache fails, this is not a problem, so we do not test result.
			}
			$head[$h][1] .= '/';
			$head[$h][1] .= $langs->trans('Agenda');
			if ($nbEvent > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbEvent . '</span>';
			}
		}
		$head[$h][2] = 'agenda';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@dolicar');

	complete_head_from_modules($conf, $langs, $object, $head, $h, $objectType . '@dolicar', 'remove');

	return $head;
}

function get_registration_certificate_fields() {
	$registrationCertificateFields = [
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

function normalize_registration_number($registrationNumber)
{
	if (dol_strlen($registrationNumber) < 1) {
		return 0;
	}

	if (preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $registrationNumber)) {
		$registrationNumberLetters = preg_split('/[0-9]{3}/',$registrationNumber);
		$registrationNumberNumbers = preg_split('/[A-Z]{2}/',$registrationNumber);

		$registrationNumberFormatted = $registrationNumberLetters[0] . '-' . $registrationNumberNumbers[1] . '-' . $registrationNumberLetters[1];
		return $registrationNumberFormatted;
	} else {
		return $registrationNumber;
	}
}

