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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/warehouse.js
 * \ingroup dolicar
 * \brief   JavaScript module for the fleet map of the warehouse page
 */

'use strict';

/**
 * Init warehouse JS
 *
 * @memberof DoliCar_Warehouse
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @type {Object}
 */
window.dolicar.warehouse = {};

/**
 * Warehouse init
 *
 * @memberof DoliCar_Warehouse
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.warehouse.init = function() {
  window.dolicar.warehouse.renderMap();
};

/**
 * Draw the warehouses of the fleet on a Leaflet map, one circle per warehouse sized by its vehicle count.
 * Leaflet is only loaded on the fleet page, so every other page leaves this untouched.
 *
 * @memberof DoliCar_Warehouse
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.warehouse.renderMap = function() {
  const container = document.getElementById('dolicar-warehouse-map');
  if (!container || typeof L === 'undefined') {
    return;
  }

  let markers = [];
  try {
    markers = JSON.parse(container.dataset.markers || '[]');
  } catch (error) {
    return;
  }

  if (!markers.length) {
    return;
  }

  const vehiclesLabel = container.dataset.vehiclesLabel || '';
  const map           = L.map(container, {scrollWheelZoom: false});

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom    : 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  const bounds = [];
  markers.forEach(function(marker) {
    // Circles keep the map free of any image asset, and their size reads as the weight of the warehouse
    const circle = L.circleMarker([marker.latitude, marker.longitude], {
      radius     : Math.min(10 + marker.vehicles, 30),
      color      : '#0d8aee',
      fillColor  : '#0d8aee',
      fillOpacity: 0.5,
      weight     : 2
    }).addTo(map);

    circle.bindPopup('<a href="' + marker.url + '"><strong>' + marker.label + '</strong></a><br>' + marker.vehicles + ' ' + vehiclesLabel);
    bounds.push([marker.latitude, marker.longitude]);
  });

  map.fitBounds(bounds, {padding: [40, 40], maxZoom: 13});
};
