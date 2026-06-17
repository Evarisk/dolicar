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
  window.dolicar.registrationcertificate.reorderFieldsOnCreate();
  window.dolicar.registrationcertificate.initQuickControlCreator();
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
  $(document).on('click', '.dolicar-control-add-btn', window.dolicar.registrationcertificate.submitQuickControl);
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
/**
 * Move warehouse field after chassis number field on create form
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.reorderFieldsOnCreate = function() {
  let $table        = $('.tableforfieldcreate');
  let $warehouseRow = $table.find('.field_warehouse_id');
  let $fkLotRow     = $table.find('.field_fk_lot');

  if ($warehouseRow.length && $fkLotRow.length) {
    $fkLotRow.after($warehouseRow);
  }
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
  let form     = document.getElementById('registrationcertificatefr_form');
  let formData = new FormData(form);

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let productID      = $(this).val();

  window.saturne.loader.display($('.field_fk_lot'));
  window.saturne.loader.display($('.field_d1_vehicle_brand'));

  let actionPost = '';
  if (!document.URL.match('action=')) {
    let action     = formData.get('action');
    if (action === 'add') {
      actionPost = 'action=create';
    } else if (action === 'update') {
      actionPost = 'action=edit';
    }
  }

  $.ajax({
    url: document.URL + querySeparator + actionPost + '&fk_product=' + productID + '&token=' + token,
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
 * Move the quick DigiQuali control creator widget into the fk_lot row
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.initQuickControlCreator = function() {
  var $widget   = $('#dolicar-quick-control-widget');
  var $fkLotRow = $('.tableforfield').find('tr.field_fk_lot');

  if ($widget.length === 0 || $fkLotRow.length === 0) {
    return;
  }

  var $creator = $widget.children('.dolicar-control-inline-creator').first();
  $fkLotRow.find('td:last').append($creator);

  var $select = $creator.find('.dolicar-control-model-select');

  var sheetsData      = $widget.data('sheets') || [];
  var placeholderText = '-- Modèle --';

  $select.empty().append($('<option>').val('').text(placeholderText));
  $.each(sheetsData, function(i, item) {
    $select.append($('<option>').val(item.id).text(item.text));
  });

  $select.on('change', window.dolicar.registrationcertificate.updateQuickControlButton);

  window.dolicar.registrationcertificate.updateQuickControlButton.call($select[0]);
};

/**
 * Enable or disable the add button based on the model select value
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.updateQuickControlButton = function() {
  var $select  = $(this);
  var $creator = $select.closest('.dolicar-control-inline-creator');
  var $btn     = $creator.find('.dolicar-control-add-btn');
  var val      = $select.val();

  if (val && val !== '') {
    $btn.prop('disabled', false).removeClass('button-disable');
  } else {
    $btn.prop('disabled', true).addClass('button-disable');
  }
};

/**
 * Submit the hidden quick-control form with the selected sheet id
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.submitQuickControl = function() {
  var $btn    = $(this);
  var sheetId = $btn.closest('.dolicar-control-inline-creator').find('.dolicar-control-model-select').val();

  if (!sheetId || sheetId === '') {
    return;
  }

  var $form = $('#dolicar-quick-control-form');
  $form.find('[name="fk_sheet"]').val(sheetId);
  $form.submit();
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

  // Avoid duplicate entries when the button is clicked several times before the request completes
  let $validateButton = $('.public-vehicle-log-book-validate');
  if ($validateButton.hasClass('button-disable')) {
    return;
  }
  $validateButton.addClass('button-disable');

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
      // Redirect to the dedicated confirmation screen so the user gets a clear feedback
      window.location.href = document.URL + querySeparator + 'success=1';
    },
    error: function() {
      $validateButton.removeClass('button-disable');
    }
  });
};
