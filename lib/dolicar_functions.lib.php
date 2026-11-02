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
 * Get or create the vehicle product and its lot from registration certificate data.
 *
 * Reproduces the product/lot business logic of the quick creation wizard so it can be reused
 * (e.g. by the CSV import): the product reference is built from brand + model + version, the
 * brand category is created if missing, and the lot is created from the VIN (or a default lot).
 * Falls back to the default vehicle product when no brand/model is available.
 *
 * @param  string $brand   Vehicle brand (D.1)
 * @param  string $model   Vehicle model (D.3)
 * @param  string $version Vehicle version/type (D.2), may be empty
 * @param  string $vin     Vehicle serial number / VIN (E), may be empty
 * @return array{fk_product: int, fk_lot: int, is_new_lot: bool}
 */
function dolicar_get_or_create_vehicle_product_lot(string $brand, string $model, string $version, string $vin): array
{
    global $db, $conf, $langs, $user;

    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    // Avoid product creation error when an automatic barcode numbering is enabled
    $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

    $product  = new Product($db);
    $category = new Categorie($db);

    $brand   = trim($brand);
    $model   = trim($model);
    $version = trim($version);
    $vin     = trim($vin);

    $fkProduct = 0;
    $fkLot     = 0;
    $isNewLot  = false;

    // Fetch or create the product from "brand model version"
    $productRef = trim($brand . ' ' . $model . ' ' . $version);
    if ($productRef !== '') {
        $product->ref          = $productRef;
        $product->label        = $productRef;
        $product->status_batch = 1;

        // Try sanitized ref first (matches how it was originally created), then exact ref
        $product->fetch(0, dol_sanitizeFileName(dol_string_nospecial($productRef)));
        $fkProduct = $product->id;

        if ($fkProduct <= 0) {
            $product->fetch(0, $productRef);
            $fkProduct = $product->id;
        }

        if ($fkProduct <= 0) {
            $fkProduct = $product->create($user);
            if ($fkProduct <= 0) {
                // Creation failed — product may already exist with a variant of the ref
                $product->fetch(0, $productRef);
                $fkProduct = $product->id > 0 ? $product->id : 0;
            }
            if ($fkProduct > 0 && $brand !== '') {
                $category->fetch(0, $brand);
                if ($category->id <= 0) {
                    $category->label       = $brand;
                    $category->description = $brand;
                    $category->visible     = 1;
                    $category->type        = 'product';
                    $category->fk_parent   = getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG');
                    $categoryID            = $category->create($user);
                } else {
                    $categoryID = $category->id;
                }
                if ($categoryID > 0) {
                    $product->setCategories([$categoryID, getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG')]);
                }
            }
        }
    }

    // Fallback to the shared default vehicle when no usable brand/model was provided
    if ($fkProduct <= 0) {
        $fkProduct = getDolGlobalInt('DOLICAR_DEFAULT_VEHICLE');
    }

    // Fetch or create the lot
    if ($fkProduct > 0) {
        if ($vin !== '') {
            $productLot             = new Productlot($db);
            $productLot->batch      = $vin;
            $productLot->fk_product = $fkProduct;
            $fkLot                  = $productLot->create($user);

            if ($fkLot > 0) {
                $isNewLot = true;
                // Put 1 unit in stock for the newly created lot (default warehouse)
                $stockProduct = new Product($db);
                $stockProduct->fetch($fkProduct);
                $stockProduct->correct_stock_batch($user, getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID'), 1, 0, $langs->transnoentities('ClientVehicle'), 0, '', '', $vin, '', 'dolicar_registrationcertificate', 0);
            } else {
                // Lot already exists for this product + VIN: fetch its id
                $existingLot = new Productlot($db);
                if ($existingLot->fetch(0, $fkProduct, $vin) > 0) {
                    $fkLot = $existingLot->id;
                } else {
                    $fkLot = 0;
                }
            }
        } else {
            $fkLot    = create_default_product_lot($fkProduct);
            $isNewLot = $fkLot > 0;
        }
    }

    return ['fk_product' => (int) $fkProduct, 'fk_lot' => (int) $fkLot, 'is_new_lot' => $isNewLot];
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
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
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
