<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    lib/dolicar_warehouse.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for the vehicle fleet grouped by warehouse (issue #445)
 */

/**
 * Build the fleet of vehicles grouped by the warehouse currently holding them.
 *
 * A vehicle sits in a warehouse only through the stock of its product lot: the carte grise itself
 * carries no warehouse, so the lot has to be resolved through the core stock tables, which have no
 * ORM equivalent. Everything else is fetched through the ORM.
 *
 * @return array ['warehouses' => [warehouseId => ['object' => Entrepot, 'vehicles' => RegistrationCertificateFr[]]],
 *                'unassigned' => RegistrationCertificateFr[]] Vehicles whose lot is in no warehouse
 * @throws Exception
 */
function dolicar_get_fleet_by_warehouse(): array
{
    global $db;

    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
    require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

    // Minimal SQL: resolve lot ID -> warehouse ID through the batch/stock tables
    $sql  = 'SELECT pl.rowid AS lot_id, ps.fk_entrepot AS warehouse_id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lot pl';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_batch pb ON pb.batch = pl.batch AND pb.qty > 0';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock AND ps.fk_product = pl.fk_product';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'entrepot e ON e.rowid = ps.fk_entrepot';
    $sql .= ' WHERE e.entity IN (' . getEntity('stock') . ')';
    // A lot is normally stocked in a single warehouse, but a duplicated stock line would otherwise
    // make the vehicle land in an arbitrary one: the biggest stock wins, and ties resolve on the ID
    $sql .= ' ORDER BY pb.qty ASC, ps.fk_entrepot DESC';

    $lotWarehouseMap = [];
    $resql           = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $lotWarehouseMap[(int) $obj->lot_id] = (int) $obj->warehouse_id;
        }
        $db->free($resql);
    }

    $vehicleObject = new RegistrationCertificateFr($db);
    $vehicles      = $vehicleObject->fetchAll('ASC', 'a_registration_number', 0, 0, ['customsql' => 't.status >= 0']);

    $fleet      = [];
    $unassigned = [];
    if (!is_array($vehicles)) {
        return ['warehouses' => $fleet, 'unassigned' => $unassigned];
    }

    // A warehouse is fetched once even when it holds a hundred vehicles, and a failed fetch is
    // remembered so a broken reference is not retried for every vehicle of that warehouse
    $warehouseCache = [];
    foreach ($vehicles as $vehicle) {
        $warehouseId = $lotWarehouseMap[(int) $vehicle->fk_lot] ?? 0;

        if ($warehouseId > 0 && !array_key_exists($warehouseId, $warehouseCache)) {
            $warehouse = new Entrepot($db);
            if ($warehouse->fetch($warehouseId) > 0) {
                $warehouse->fetch_optionals();
                $warehouseCache[$warehouseId] = $warehouse;
                $fleet[$warehouseId]          = ['object' => $warehouse, 'vehicles' => []];
            } else {
                $warehouseCache[$warehouseId] = false;
            }
        }

        if ($warehouseId <= 0 || empty($warehouseCache[$warehouseId])) {
            $unassigned[] = $vehicle;
            continue;
        }

        $fleet[$warehouseId]['vehicles'][] = $vehicle;
    }

    // Busiest warehouse first: that is the one the fleet manager works in
    uasort($fleet, static fn(array $a, array $b): int => count($b['vehicles']) <=> count($a['vehicles']));

    return ['warehouses' => $fleet, 'unassigned' => $unassigned];
}

/**
 * Return the open warehouses a vehicle can be moved to.
 *
 * @return array [warehouseId => label] ordered by label
 */
function dolicar_get_open_warehouses(): array
{
    global $db;

    $sql  = 'SELECT rowid, ref FROM ' . MAIN_DB_PREFIX . 'entrepot';
    $sql .= ' WHERE entity IN (' . getEntity('stock') . ') AND statut > 0';
    $sql .= ' ORDER BY ref';

    $warehouses = [];
    $resql      = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $warehouses[(int) $obj->rowid] = $obj->ref;
        }
        $db->free($resql);
    }

    return $warehouses;
}

/**
 * Return the ID of the warehouse currently holding a vehicle.
 *
 * @param  RegistrationCertificateFr $vehicle Vehicle to locate, already fetched
 * @return int                                0 when the vehicle is in no warehouse, warehouse ID otherwise
 */
function dolicar_get_vehicle_warehouse_id(RegistrationCertificateFr $vehicle): int
{
    global $db;

    if ((int) $vehicle->fk_lot <= 0) {
        return 0;
    }

    $sql  = 'SELECT ps.fk_entrepot AS warehouse_id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lot pl';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_batch pb ON pb.batch = pl.batch AND pb.qty > 0';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock AND ps.fk_product = pl.fk_product';
    $sql .= ' WHERE pl.rowid = ' . (int) $vehicle->fk_lot;

    $resql = $db->query($sql);
    if (!$resql) {
        return 0;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return !empty($obj) ? (int) $obj->warehouse_id : 0;
}

/**
 * Move a vehicle to another warehouse.
 *
 * A vehicle is one unit of its product lot, so the move is a stock decrease in the source warehouse
 * followed by an increase in the destination one, the way the module already moves batches when the
 * internal warehouse changes.
 *
 * @param  RegistrationCertificateFr $vehicle         Vehicle to move, already fetched
 * @param  int                       $toWarehouseId   Destination warehouse ID
 * @param  int                       $fromWarehouseId Source warehouse ID, 0 when the vehicle is in no warehouse yet
 * @param  User                      $user            User doing the move
 * @return int                                        < 0 if KO, > 0 if OK
 */
function dolicar_transfer_vehicle_warehouse(RegistrationCertificateFr $vehicle, int $toWarehouseId, int $fromWarehouseId, User $user): int
{
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

    $langs->load('dolicar@dolicar');

    if ($toWarehouseId <= 0 || $toWarehouseId === $fromWarehouseId) {
        return -1;
    }
    if ((int) $vehicle->fk_product <= 0 || (int) $vehicle->fk_lot <= 0) {
        return -1;
    }

    $product = new Product($db);
    if ($product->fetch((int) $vehicle->fk_product) <= 0) {
        return -1;
    }

    $lot = new ProductLot($db);
    if ($lot->fetch((int) $vehicle->fk_lot) <= 0 || empty($lot->batch)) {
        return -1;
    }

    $label = $langs->transnoentities('VehicleWarehouseTransferLabel', $vehicle->a_registration_number);

    $db->begin();

    // 4th argument of correct_stock_batch(): 1 decreases the stock, 0 increases it
    if ($fromWarehouseId > 0) {
        $result = $product->correct_stock_batch($user, $fromWarehouseId, 1, 1, $label, 0, '', '', $lot->batch, '', 'dolicar_registrationcertificate', $vehicle->id);
        if ($result < 0) {
            $db->rollback();
            return -1;
        }
    }

    $result = $product->correct_stock_batch($user, $toWarehouseId, 1, 0, $label, 0, '', '', $lot->batch, '', 'dolicar_registrationcertificate', $vehicle->id);
    if ($result < 0) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    return 1;
}

/**
 * Look up the coordinates of a warehouse from its postal address.
 *
 * Uses the OpenStreetMap Nominatim service, which requires an identifying User-Agent and tolerates a
 * low request rate only: this is meant for the one-off geocoding of a fleet of warehouses, never for
 * a lookup on page load.
 *
 * @param  Entrepot $warehouse Warehouse to locate, already fetched
 * @return array               ['latitude' => string, 'longitude' => string], empty when not found
 */
function dolicar_geocode_warehouse(Entrepot $warehouse): array
{
    $addressParts = array_filter([$warehouse->address, $warehouse->zip, $warehouse->town]);
    if (empty($addressParts)) {
        return [];
    }

    require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode(implode(' ', $addressParts));

    // getURLContent() carries the proxy and SSL settings of the instance, which a raw curl call would ignore
    $response = getURLContent($url, 'GET', '', 1, ['User-Agent: DoliCar Dolibarr warehouse geocoding'], ['https'], 0);
    if ((int) ($response['http_code'] ?? 0) !== 200 || empty($response['content'])) {
        dol_syslog('dolicar_geocode_warehouse failed for warehouse ' . $warehouse->id . ' : ' . ($response['curl_error_msg'] ?? 'http ' . ($response['http_code'] ?? 0)), LOG_WARNING);
        return [];
    }

    $decoded = json_decode($response['content'], true);
    if (!is_array($decoded) || empty($decoded[0]['lat']) || empty($decoded[0]['lon'])) {
        return [];
    }

    return ['latitude' => (string) $decoded[0]['lat'], 'longitude' => (string) $decoded[0]['lon']];
}

/**
 * Fill in the missing coordinates of every warehouse holding vehicles, from their postal address.
 *
 * Warehouses already carrying coordinates are left alone, so a manually corrected position is never
 * overwritten and the action stays cheap to run again.
 *
 * @param  array $fleet Fleet as returned by dolicar_get_fleet_by_warehouse()
 * @param  User  $user  User saving the coordinates
 * @return int          Number of warehouses located
 */
function dolicar_geocode_fleet_warehouses(array $fleet, User $user): int
{
    $geocodedCount = 0;
    $lookupCount   = 0;

    foreach ($fleet['warehouses'] as $warehouseData) {
        $warehouse = $warehouseData['object'];

        if (!empty($warehouse->array_options['options_dolicar_latitude']) && !empty($warehouse->array_options['options_dolicar_longitude'])) {
            continue;
        }

        // Nominatim allows one request per second: a fleet of warehouses would be throttled otherwise
        if ($lookupCount > 0) {
            sleep(1);
        }
        $lookupCount++;

        $coordinates = dolicar_geocode_warehouse($warehouse);
        if (empty($coordinates)) {
            continue;
        }

        $warehouse->array_options['options_dolicar_latitude']  = $coordinates['latitude'];
        $warehouse->array_options['options_dolicar_longitude'] = $coordinates['longitude'];
        if ($warehouse->insertExtraFields('', $user) > 0) {
            $geocodedCount++;
        }
    }

    return $geocodedCount;
}

/**
 * Print one block of the fleet listing: the title of the warehouse, then a row per vehicle carrying
 * the form that moves it somewhere else right away.
 *
 * @param  string $blockId           Anchor of the block, targeted by the indicators above it
 * @param  string $blockTitle        Title shown on top of the table, already HTML
 * @param  array  $vehicles          Vehicles held by this warehouse
 * @param  int    $fromWarehouseId   Warehouse currently holding them, 0 for the unassigned ones
 * @param  array  $warehouseOptions  Possible destinations, as returned by dolicar_get_open_warehouses()
 * @param  int    $permissionToWrite Whether the transfer form is printed
 * @return void
 */
function dolicar_print_warehouse_block(string $blockId, string $blockTitle, array $vehicles, int $fromWarehouseId, array $warehouseOptions, int $permissionToWrite)
{
    global $langs;

    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

    print '<div class="dolicar-warehouse-block" id="' . dol_escape_htmltag($blockId) . '">';
    print load_fiche_titre($blockTitle . ' <span class="badge badge-secondary">' . count($vehicles) . '</span>', '', '');

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('RegistrationNumber') . '</td>';
    print '<td>' . $langs->transnoentities('Vehicle') . '</td>';
    print '<td>' . $langs->transnoentities('VINNumber') . '</td>';
    print '<td class="center">' . $langs->transnoentities('Status') . '</td>';
    if (!empty($permissionToWrite)) {
        print '<td class="right">' . $langs->transnoentities('TransferToWarehouse') . '</td>';
    }
    print '</tr>';

    foreach ($vehicles as $vehicle) {
        $brandModel = trim($vehicle->d1_vehicle_brand . ' ' . $vehicle->d3_vehicle_model);

        print '<tr class="oddeven">';
        print '<td class="nowraponall">' . $vehicle->getNomUrl(1) . '</td>';
        print '<td>' . dol_escape_htmltag($brandModel) . '</td>';
        print '<td class="nowraponall">' . dol_escape_htmltag($vehicle->e_vehicle_serial_number) . '</td>';
        print '<td class="center">' . $vehicle->getLibStatut(5) . '</td>';

        if (!empty($permissionToWrite)) {
            print '<td class="right nowraponall">';
            print '<form method="POST" action="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '" class="dolicar-warehouse-transfer">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="transfer_vehicle">';
            print '<input type="hidden" name="vehicle_id" value="' . (int) $vehicle->id . '">';
            print '<input type="hidden" name="from_warehouse_id" value="' . $fromWarehouseId . '">';
            // The destination select carries the vehicle ID: the page holds one transfer form per row
            print Form::selectarray('to_warehouse_id_' . (int) $vehicle->id, $warehouseOptions, 0, 1, 0, 0, '', 0, 0, 0, '', 'minwidth150');
            print '<input class="button small" type="submit" value="' . dol_escape_htmltag($langs->trans('Transfer')) . '">';
            print '</form>';
            print '</td>';
        }

        print '</tr>';
    }

    if (empty($vehicles)) {
        print '<tr><td colspan="5"><em>' . $langs->transnoentities('NoVehicleInWarehouse') . '</em></td></tr>';
    }

    print '</table>';
    print '</div>';
}

/**
 * Build the marker data of the fleet map, ready to be handed to Leaflet.
 *
 * Only the warehouses carrying coordinates end up on the map; the others are still listed below it,
 * which is what tells the user they are missing a position.
 *
 * @param  array $fleet Fleet as returned by dolicar_get_fleet_by_warehouse()
 * @return array        Markers, each with its label, coordinates, vehicle count and card URL
 */
function dolicar_get_fleet_map_markers(array $fleet): array
{
    $markers = [];

    foreach ($fleet['warehouses'] as $warehouseId => $warehouseData) {
        $warehouse = $warehouseData['object'];
        $latitude  = $warehouse->array_options['options_dolicar_latitude'] ?? '';
        $longitude = $warehouse->array_options['options_dolicar_longitude'] ?? '';

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            continue;
        }

        $markers[] = [
            'label'     => dol_escape_htmltag($warehouse->label ?: $warehouse->ref),
            'latitude'  => (float) $latitude,
            'longitude' => (float) $longitude,
            'vehicles'  => count($warehouseData['vehicles']),
            'url'       => DOL_URL_ROOT . '/product/stock/card.php?id=' . (int) $warehouseId,
        ];
    }

    return $markers;
}
