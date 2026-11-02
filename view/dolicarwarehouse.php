<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/dolicarwarehouse.php
 * \ingroup dolicar
 * \brief   Fleet of vehicles grouped by warehouse: indicators, map and quick transfer (issue #445)
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../lib/dolicar_warehouse.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['stocks']);

// Get parameters
$action = GETPOST('action', 'aZ09');

// Security check
$permissionToRead  = $user->rights->dolicar->registrationcertificatefr->read;
$permissionToWrite = $user->rights->dolicar->registrationcertificatefr->write;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'transfer_vehicle' && $permissionToWrite) {
    $vehicleId       = GETPOSTINT('vehicle_id');
    $fromWarehouseId = GETPOSTINT('from_warehouse_id');
    // One transfer form per vehicle in the page, so the destination select carries the vehicle ID
    $toWarehouseId   = GETPOSTINT('to_warehouse_id_' . $vehicleId);

    $vehicle = new RegistrationCertificateFr($db);
    if ($vehicleId <= 0 || $vehicle->fetch($vehicleId) <= 0) {
        setEventMessages($langs->transnoentities('ErrorRecordNotFound'), null, 'errors');
    } elseif ($toWarehouseId <= 0 || $toWarehouseId == $fromWarehouseId) {
        setEventMessages($langs->transnoentities('ErrorVehicleTransferSameWarehouse'), null, 'warnings');
    } elseif (dolicar_transfer_vehicle_warehouse($vehicle, $toWarehouseId, $fromWarehouseId, $user) > 0) {
        setEventMessages($langs->transnoentities('VehicleTransferred', $vehicle->a_registration_number), null, 'mesgs');
    } else {
        setEventMessages($langs->transnoentities('ErrorVehicleTransferFailed', $vehicle->a_registration_number), null, 'errors');
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'geocode_warehouses' && $permissionToWrite) {
    $geocodedCount = dolicar_geocode_fleet_warehouses(dolicar_get_fleet_by_warehouse(), $user);
    if ($geocodedCount > 0) {
        setEventMessages($langs->transnoentities('WarehousesGeocoded', $geocodedCount), null, 'mesgs');
    } else {
        // Nothing found: either every warehouse is already located, or none carries a usable address
        setEventMessages($langs->transnoentities('NoWarehouseGeocoded'), null, 'warnings');
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title = $langs->transnoentities('WarehouseFleet');

// Leaflet ships with Dolibarr but is only loaded on demand: the fleet map is the only DoliCar screen needing it
saturne_header(0, '', $title, '', '', 0, 0, ['/includes/leaflet/leaflet.js'], ['/includes/leaflet/leaflet.css']);

print load_fiche_titre($title, '', 'fas fa-warehouse');

$fleet            = dolicar_get_fleet_by_warehouse();
$mapMarkers       = dolicar_get_fleet_map_markers($fleet);
$warehouseOptions = dolicar_get_open_warehouses();

require __DIR__ . '/../core/tpl/dolicar_warehouse_indicators.tpl.php';
require __DIR__ . '/../core/tpl/dolicar_warehouse_map.tpl.php';
require __DIR__ . '/../core/tpl/dolicar_warehouse_list.tpl.php';

llxFooter();
$db->close();
