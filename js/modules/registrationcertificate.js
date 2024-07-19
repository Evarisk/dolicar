/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/registrationcertificate.js
 * \ingroup dolicar
 * \brief   JavaScript Registration Certificate file for module DoliCar
 */

'use strict';

/**
 * Init registrationcertificate JS
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.0.0
 * @version 1.2.0
 *
 * @type {Object}
 */
window.dolicar.registrationcertificate = {};

/**
 * RegistrationCertificate init
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.0.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.init = function() {
  window.dolicar.registrationcertificate.event();
};

/**
 * RegistrationCertificate event
 *
 * @since   1.0.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.event = function() {
  $(document).on('change', '#fk_product', window.dolicar.registrationcertificate.reloadFields);
};

  $('#public-vehicle-log-book-form').on('submit', function(event) {
    event.preventDefault();
    if (!$(this).find('.public-vehicle-log-book-validate').hasClass('button-disable')) {
      window.dolicar.registrationcertificate.createPublicVehicleLogBook();
    }
  });

  $(document).on('touchstart mousedown', '.canvas-signature', function () {
    window.saturne.toolbox.removeAddButtonClass('public-vehicle-log-book-validate', 'button-grey button-disable', 'button-blue');
  });

  $(document).on('click', '.signature-erase', function () {
    window.saturne.toolbox.removeAddButtonClass('public-vehicle-log-book-validate', 'button-blue', 'button-grey button-disable');
  });
};


/**
 * Reload product lot selector and vehicle brand
 *
 * @since   0.0.2
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.reloadFields = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let productID      = $(this).val();

  window.saturne.loader.display($('.field_fk_lot'));
  window.saturne.loader.display($('.field_d1_vehicle_brand'));

  $.ajax({
    url: document.URL + querySeparator + '&fk_product=' + productID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function (resp) {
      $('.field_fk_lot').replaceWith($(resp).find('.field_fk_lot'));
      $('.field_d1_vehicle_brand').replaceWith($(resp).find('.field_d1_vehicle_brand'));
    },
    error: function() {}
  });
};

/**
 * Create public vehicle log book entry
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.createPublicVehicleLogBook = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  const formData = new FormData($('#public-vehicle-log-book-form')[0]);
  if (window.saturne.signature.canvas) {
    const signature = window.saturne.signature.canvas.toDataURL();
    formData.append('signature', JSON.stringify(signature));
  }

  $.ajax({
    url: document.URL + querySeparator + 'action=add&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    data: formData,
    success: function() {
      $('.public-vehicle-log-book-confirmation-close').closest('.card__confirmation').css('display', 'flex');
      $('.public-vehicle-log-book-confirmation-close').on('click', function() {
        $('.public-vehicle-log-book-confirmation-close').closest('.card__confirmation').css('display', 'none');
        window.location.reload();
      });
    },
    error: function() {}
  });
};
