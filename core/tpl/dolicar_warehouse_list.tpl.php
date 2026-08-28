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
 * \file    core/tpl/dolicar_warehouse_list.tpl.php
 * \ingroup dolicar
 * \brief   Vehicles listed warehouse by warehouse, each one transferable on the spot (issue #445)
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $fleet             (array as returned by dolicar_get_fleet_by_warehouse())
 *            $warehouseOptions  (array [warehouseId => label] of the possible destinations)
 *            $permissionToWrite (int)
 */

print load_fiche_titre($langs->transnoentities('VehiclesByWarehouse'), '', '');

foreach ($fleet['warehouses'] as $warehouseId => $warehouseData) {
    $warehouse = $warehouseData['object'];

    dolicar_print_warehouse_block(
        'dolicar-warehouse-' . (int) $warehouseId,
        $warehouse->getNomUrl(1),
        $warehouseData['vehicles'],
        (int) $warehouseId,
        $warehouseOptions,
        $permissionToWrite
    );
}

// Vehicles whose lot is in no warehouse: the same form assigns them to one
if (!empty($fleet['unassigned'])) {
    dolicar_print_warehouse_block(
        'dolicar-warehouse-unassigned',
        '<i class="fas fa-exclamation-triangle"></i> ' . $langs->transnoentities('VehiclesWithoutWarehouse'),
        $fleet['unassigned'],
        0,
        $warehouseOptions,
        $permissionToWrite
    );
}

if (empty($fleet['warehouses']) && empty($fleet['unassigned'])) {
    print '<div class="opacitymedium">' . $langs->transnoentities('NoVehicleInFleet') . '</div>';
}
