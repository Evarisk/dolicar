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
 * \file    lib/dolicar_registrationcertificatefr.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for RegistrationCertificateFr
 */

/**
 * Prepare registrationcertificatefr pages header
 *
 * @param  RegistrationCertificateFr $object RegistrationCertificateFr
 * @return array                     $head   Array of tabs
 * @throws Exception
 */
function registrationcertificatefr_prepare_head(RegistrationCertificateFr $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 1;
    $head = [];

    $head[$h][0] = dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_linkedobjects.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-link pictofixedwidth"></i>' . $langs->trans('LinkedObjects') : '<i class="fas fa-link"></i>';
    $head[$h][2] = 'linkedobjects';

    return saturne_object_prepare_head($object, $head);
}

/**
 * Get registration certificate fields
 *
 * @return array Array of registration certificate fields
 */
function getRegistrationCertificateFields(): array
{
    return [
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
}

/**
 * Normalize with regex registration number field
 *
 * @param  string $registrationNumber Registration number
 * @return string|int                 0 < if KO, registration number default value or formatted
 */
function normalize_registration_number(string $registrationNumber)
{
    if (dol_strlen($registrationNumber) > 0) {
        if (preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $registrationNumber)) {
            $registrationNumberLetters = preg_split('/[0-9]{3}/', $registrationNumber);
            $registrationNumberNumbers = preg_split('/[A-Z]{2}/', $registrationNumber);

            return $registrationNumberLetters[0] . '-' . $registrationNumberNumbers[1] . '-' . $registrationNumberLetters[1];
        } else {
            return $registrationNumber;
        }
    } else {
        return -1;
    }
}

