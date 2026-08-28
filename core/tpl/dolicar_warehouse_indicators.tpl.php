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
 * \file    core/tpl/dolicar_warehouse_indicators.tpl.php
 * \ingroup dolicar
 * \brief   Vehicle count per warehouse, on top of the fleet page (issue #445)
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $fleet (array as returned by dolicar_get_fleet_by_warehouse())
 */

$totalVehicles = count($fleet['unassigned']);
foreach ($fleet['warehouses'] as $warehouseData) {
    $totalVehicles += count($warehouseData['vehicles']);
}

print '<div class="dolicar-warehouse-indicators">';

print '<div class="dolicar-warehouse-indicator dolicar-warehouse-indicator-total">';
print '<div class="dolicar-warehouse-indicator-value">' . $totalVehicles . '</div>';
print '<div class="dolicar-warehouse-indicator-label"><i class="fas fa-car"></i> ' . $langs->transnoentities('TotalVehicles') . '</div>';
print '</div>';

foreach ($fleet['warehouses'] as $warehouseId => $warehouseData) {
    $warehouse    = $warehouseData['object'];
    $vehicleCount = count($warehouseData['vehicles']);
    $share        = $totalVehicles > 0 ? round($vehicleCount * 100 / $totalVehicles) : 0;

    print '<a class="dolicar-warehouse-indicator" href="#dolicar-warehouse-' . (int) $warehouseId . '">';
    print '<div class="dolicar-warehouse-indicator-value">' . $vehicleCount . '</div>';
    print '<div class="dolicar-warehouse-indicator-label"><i class="fas fa-warehouse"></i> ' . dol_escape_htmltag($warehouse->label ?: $warehouse->ref) . '</div>';
    print '<div class="dolicar-warehouse-indicator-share">' . $share . ' %</div>';
    print '</a>';
}

if (!empty($fleet['unassigned'])) {
    print '<div class="dolicar-warehouse-indicator dolicar-warehouse-indicator-warning">';
    print '<div class="dolicar-warehouse-indicator-value">' . count($fleet['unassigned']) . '</div>';
    print '<div class="dolicar-warehouse-indicator-label"><i class="fas fa-exclamation-triangle"></i> ' . $langs->transnoentities('VehiclesWithoutWarehouse') . '</div>';
    print '</div>';
}

print '</div>';
