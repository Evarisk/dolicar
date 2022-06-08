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
 * \file    dolicar/lib/dolicar_functions.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for DoliCar
 */

/**
 * Prepare admin pages header
 *
 * @return float|int
 */
function createDefaultLot($product_id)
{
	global $db, $user, $conf, $langs;

	require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';

	$productlot = new Productlot($db);
	$productlot->fk_product = $product_id;
	$productlot->batch = generate_random_id();

	$lot_id = $productlot->create($user);
	if ($lot_id > 0) {
		$product = new Product($db);
		$product->fetch($product_id);
		$product->correct_stock_batch(
			$user,
			$conf->global->DOLICAR_DEFAULT_WAREHOUSE,
			1,
			0,
			$langs->trans('ClientVehicle'), // label movement
			0,
			'',
			'',
			$productlot->batch,
			'',
			'dolicar_registrationcertificate',
			0
		);
		return $lot_id;
	} else {
		return 0;
	}


}

/**
 *  Generate a random id
 *
 *  @param  int 	$car 	Length of string to generate key
 *  @return string
 */
function generate_random_id($car = 16)
{
	$string = "";
	$chaine = "abcdefghijklmnopqrstuvwxyz123456789";
	mt_srand((double) microtime() * 1000000);
	for ($i = 0; $i < $car; $i++) {
		$string .= $chaine[mt_rand() % strlen($chaine)];
	}
	return $string;
}


// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 *  Return a HTML select list of a dictionary
 *
 *  @param  string	$htmlname          	Name of select zone
 *  @param	string	$dictionarytable	Dictionary table
 *  @param	string	$keyfield			Field for key
 *  @param	string	$labelfield			Label field
 *  @param	string	$selected			Selected value
 *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
 *  @param  string  $moreattrib         More attributes on HTML select tag
 * 	@return	void
 */
function dolicar_select_dictionary($htmlname, $dictionarytable, $keyfield = 'code', $labelfield = 'label', $selected = '', $useempty = 0, $moreattrib = '')
{
	// phpcs:enable
	global $langs, $db;

	$langs->load("admin");

	$sql  = "SELECT rowid, " . $keyfield . ", " . $labelfield;
	$sql .= " FROM " . MAIN_DB_PREFIX . $dictionarytable;
	$sql .= " ORDER BY " . $labelfield;

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i   = 0;
		if ($num) {
			$output = '<select id="select' . $htmlname . '" class="flat selectdictionary" name="' . $htmlname . '"' . ($moreattrib ? ' ' . $moreattrib : '') . '>';
			if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
				$output .= '<option value="-1">&nbsp;</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if ($selected == $obj->rowid || $selected == $langs->transnoentities($obj->$keyfield)) {
					$output .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '" selected>';
				} else {
					$output .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '">';
				}
				$output .= $langs->transnoentities($obj->$labelfield);
				$output .= '</option>';
				$i++;
			}
			$output .= "</select>";
		} else {
			$output = $langs->trans("DictionaryEmpty");
		}

		return $output;
	} else {
		dol_print_error($db);
	}
}
