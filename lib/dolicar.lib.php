<?php
/* Copyright (C) 2022 SuperAdmin <test@test.fr>
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
 * \brief   Library files with common functions for DoliCar
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function dolicar_admin_prepare_head()
{
	global $langs, $conf;

	$langs->load("dolicar@dolicar");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolicar/admin/registrationcertificate.php", 1);
	$head[$h][1] = '<i class="fas fa-car pictofixedwidth"></i>' . $langs->trans("RegistrationCertificate");
	$head[$h][2] = 'registrationcertificate';
	$h++;

	$head[$h][0] = dol_buildpath("/dolicar/admin/quickcreation.php", 1);
	$head[$h][1] = '<i class="fas fa-plus pictofixedwidth"></i>' . $langs->trans("QuickCreation");
	$head[$h][2] = 'quickcreation';
	$h++;

    $head[$h][0] = dol_buildpath('dolicar/admin/publicinterface.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-globe pictofixedwidth"></i>' . $langs->trans('PublicInterface') : '<i class="fas fa-globe"></i>';
    $head[$h][2] = 'publicinterface';
    $h++;

	$head[$h][0] = dol_buildpath("/dolicar/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans("ModuleSettings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/dolicar/admin/about.php", 1);
	$head[$h][1] = '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicar@dolicar');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolicar@dolicar', 'remove');

	return $head;
}
