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
 * \file    dolicar/lib/dolicar.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for Admin conf
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function dolicar_admin_prepare_head(): array
{
    // Global variables definitions
    global $langs, $conf;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('dolicar/admin/registrationcertificate.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-car pictofixedwidth"></i>' . $langs->trans('RegistrationCertificateFr') : '<i class="fas fa-car"></i>';
    $head[$h][2] = 'registrationcertificate';
    $h++;

    if (isModEnabled('easycrm')) {
        $head[$h][0] = dol_buildpath('dolicar/admin/quickcreation.php', 1);
        $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-plus pictofixedwidth"></i>' . $langs->trans('QuickCreation') : '<i class="fas fa-plus"></i>';
        $head[$h][2] = 'quickcreation';
        $h++;
    }

    $head[$h][0] = dol_buildpath('dolicar/admin/publicinterface.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-globe pictofixedwidth"></i>' . $langs->trans('PublicInterface') : '<i class="fas fa-globe"></i>';
    $head[$h][2] = 'publicinterface';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/pwa.php', 1). '?module_name=DoliCar&start_url=' . dol_buildpath('custom/dolicar/public/agenda/public_vehicle_logbook.php?entity=' . $conf->entity, 3);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-mobile pictofixedwidth"></i>' . $langs->trans('PWA') : '<i class="fas fa-mobile"></i>';
    $head[$h][2] = 'pwa';
    $h++;

    $head[$h][0] = dol_buildpath('dolicar/admin/setup.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('ModuleSettings') : '<i class="fas fa-cog"></i>';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/admin/about.php', 1) . '?module_name=DoliCar';
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About') : '<i class="fab fa-readme"></i>';
    $head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicar@dolicar');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicar@dolicar', 'remove');

    return $head;
}
