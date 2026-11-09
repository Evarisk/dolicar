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
 * \file    core/tpl/dolicar_warehouse_map.tpl.php
 * \ingroup dolicar
 * \brief   Map of the warehouses holding vehicles, on the fleet page (issue #445)
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $mapMarkers        (array as returned by dolicar_get_fleet_map_markers())
 *            $permissionToWrite (int)
 */

print '<div class="dolicar-warehouse-map-header">';
print load_fiche_titre($langs->transnoentities('WarehouseMap'), '', '');

// Coordinates are looked up from the postal address of the warehouses, which is the only location they carry
if (!empty($permissionToWrite)) {
    print '<form method="POST" action="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="geocode_warehouses">';
    print '<input class="button small" type="submit" value="' . dol_escape_htmltag($langs->trans('GeocodeWarehouses')) . '">';
    print '</form>';
}
print '</div>';

if (empty($mapMarkers)) {
    print '<div class="opacitymedium paddingbottom">' . $langs->transnoentities('NoWarehouseCoordinates') . '</div>';
    return;
}

// The markers travel as data, the rendering itself lives in js/modules/warehouse.js
print '<div id="dolicar-warehouse-map" class="dolicar-warehouse-map"';
print ' data-markers="' . dol_escape_htmltag(json_encode($mapMarkers)) . '"';
print ' data-vehicles-label="' . dol_escape_htmltag($langs->transnoentities('Vehicles')) . '"';
print '></div>';
