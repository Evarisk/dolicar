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
 * \file    dolicar/lib/dolicar_functions.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for DoliCar
 */

/**
 * Create default product lot
 *
 * @param  int $productID    Product ID
 * @return int $productLotID 0 < if KO, Product lot ID created
 */
function create_default_product_lot(int $productID): int
{
    // Global variables definitions
    global $db, $langs, $user;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

    // Initialize technical objects
    $productLot = new Productlot($db);

    $productLot->fk_product = $productID;
    $productLot->batch      = generate_random_id();

    $productLotID = $productLot->create($user);
    if ($productLotID > 0) {
        $product = new Product($db);
        $product->fetch($productID);
        $product->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'),0,'','', $productLot->batch,'','dolicar_registrationcertificate',0);
        return $productLotID;
    } else {
        return -1;
    }
}

/**
 * Get vehicle brand name with product ID
 *
 * @param  int    $productID Product ID
 * @return string $brandName Brand name
 */
function get_vehicle_brand(int $productID): string
{
    // Global variables definitions
    global $db;

    // Initialize technical objects
    $product  = new Product($db);
    $category = new Categorie($db);

    $brandName = '';

    if (!empty($productID) && $productID > 0) {
        $product->fetch($productID);
        $categories = $product->getCategoriesCommon('product');
        if (is_array($categories) && !empty($categories)) {
            foreach($categories as $categoryID) {
                $category->fetch($categoryID);
                if ($category->fk_parent == getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')) {
                    $brandName = $category->label;
                }
            }
        }
    }

    return $brandName;
}
