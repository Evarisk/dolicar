/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/vehicleLogbook.js
 * \ingroup dolicar
 * \brief   JavaScript module for the public vehicle logbook form
 */

'use strict';

/**
 * Init vehicleLogbook JS
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @type {Object}
 */
window.dolicar.vehicleLogbook = {};

/**
 * VehicleLogbook init
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.init = function() {
  window.dolicar.vehicleLogbook.event();
};

/**
 * VehicleLogbook event — bind delegated handlers
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.event = function() {
  // The arrival mileage warning this module was written for has been dropped. The module stays as
  // the home of the public logbook form handlers rather than being deleted, PR #491 moving the
  // driver picker of the page into it.
};
