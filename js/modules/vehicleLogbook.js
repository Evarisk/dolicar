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
  $(document).on('input change', '.plv2-km-input[data-warning-mileage]', window.dolicar.vehicleLogbook.checkMileageWarning);
  $(document).on('click', '#plv2-driver-type .plv2-seg__btn', window.dolicar.vehicleLogbook.selectDriverType);
  $(document).on('change', '#driver_user_id', window.dolicar.vehicleLogbook.rememberDriverUser);
  $(document).on('change', '#plv2-driver-free input', window.dolicar.vehicleLogbook.rememberFreeDriver);
};

/**
 * Show a non blocking warning when the arrival mileage goes over the configured trip length.
 * The driver can still submit: the threshold only asks for a second look at the typed value.
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.checkMileageWarning = function() {
  var $input   = $(this);
  var warnFrom = parseInt($input.data('warning-mileage'), 10);
  var mileage  = parseInt($input.val(), 10);

  $('#plv2-km-warning').toggleClass('is-visible', !isNaN(warnFrom) && !isNaN(mileage) && mileage > warnFrom);
};

/**
 * Switch the driver picker: internal user, external third party / contact, or free identity.
 * The name of a free driver is only required while its block is the visible one, otherwise the
 * browser would refuse to submit a form holding a required field it cannot focus.
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.selectDriverType = function() {
  var type = $(this).data('type');

  $('#plv2-driver-type .plv2-seg__btn').removeClass('active');
  $(this).addClass('active');
  $('#plv2-driver-type-value').val(type);

  $('#plv2-driver-internal').prop('hidden', type !== 'internal');
  $('#plv2-driver-external').prop('hidden', type !== 'external');
  $('#plv2-driver-free').prop('hidden', type !== 'free');
  $('#plv2-driver-free-lastname').prop('required', type === 'free');
};

/**
 * Remember the picked internal driver so the next departure form comes pre-selected
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.rememberDriverUser = function() {
  var driverId = $(this).val();

  if (driverId) {
    window.dolicar.vehicleLogbook.setCookie('plv2_driver_id', driverId);
  }
};

/**
 * Remember the typed free identity (name, first name, phone) for the next departure
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleLogbook.rememberFreeDriver = function() {
  var cookies = {
    'plv2_driver_lastname' : $('#plv2-driver-free-lastname').val(),
    'plv2_driver_firstname': $('#plv2-driver-free-firstname').val(),
    'plv2_driver_phone'    : $('#plv2-driver-free-phone').val()
  };

  for (var name in cookies) {
    if (cookies.hasOwnProperty(name)) {
      window.dolicar.vehicleLogbook.setCookie(name, cookies[name] || '');
    }
  }
};

/**
 * Write a one year cookie on the public interface
 *
 * @memberof DoliCar_VehicleLogbook
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @param  {string} name  Cookie name
 * @param  {string} value Cookie value
 * @return {void}
 */
window.dolicar.vehicleLogbook.setCookie = function(name, value) {
  document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax';
};
