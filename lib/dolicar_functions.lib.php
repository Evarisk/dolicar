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
	global $db, $user;

	require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';

	$productlot = new Productlot($db);
	$productlot->fk_product = $product_id;
	$productlot->batch = generate_random_id();
	return $productlot->create($user);
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
