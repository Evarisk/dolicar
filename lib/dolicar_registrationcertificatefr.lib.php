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

