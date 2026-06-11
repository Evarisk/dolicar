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
  $(document).on('click', '.dolicar-json-apply', window.dolicar.registrationcertificate.applyJsonToEditForm);
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

/**
 * Mapping between official French carte grise rubric codes (uppercase) and edit form field names
 * Mirror of RegistrationCertificateFr::CARTE_GRISE_FIELDS_MAP — rubric C.4.1 is handled separately
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @type {Object}
 */
window.dolicar.registrationcertificate.carteGriseFieldsMap = {
  'A':     ['a_registration_number', 'string'],
  'B':     ['b_first_registration_date', 'date'],
  'C.1':   ['c1_owner_fullname', 'string'],
  'C.3':   ['c3_registration_address', 'string'],
  'C.4A':  ['c4a_vehicle_owner', 'bool'],
  'D.1':   ['d1_vehicle_brand', 'string'],
  'D.2':   ['d2_vehicle_type', 'string'],
  'D.2.1': ['d21_vehicle_cnit', 'string'],
  'D.3':   ['d3_vehicle_model', 'string'],
  'E':     ['e_vehicle_serial_number', 'string'],
  'F.1':   ['f1_technical_ptac', 'int'],
  'F.2':   ['f2_ptac', 'int'],
  'F.3':   ['f3_ptra', 'int'],
  'G':     ['g_vehicle_weight', 'int'],
  'G.1':   ['g1_vehicle_empty_weight', 'int'],
  'H':     ['h_validity_period', 'string'],
  'I':     ['i_vehicle_registration_date', 'date'],
  'J':     ['j_vehicle_category', 'string'],
  'J.1':   ['j1_national_type', 'string'],
  'J.2':   ['j2_european_bodywork', 'string'],
  'J.3':   ['j3_national_bodywork', 'string'],
  'K':     ['k_type_approval_number', 'string'],
  'P.1':   ['p1_cylinder_capacity', 'int'],
  'P.2':   ['p2_maximum_net_power', 'int'],
  'P.3':   ['p3_fuel_type', 'string'],
  'P.6':   ['p6_national_administrative_power', 'int'],
  'Q':     ['q_power_to_weight_ratio', 'int'],
  'S.1':   ['s1_seating_capacity', 'int'],
  'S.2':   ['s2_standing_capacity', 'int'],
  'U.1':   ['u1_stationary_noise_level', 'int'],
  'U.2':   ['u2_motor_speed', 'int'],
  'V.7':   ['v7_co2_emission', 'int'],
  'V.9':   ['v9_environmental_category', 'string'],
  'X.1':   ['x1_first_technical_inspection_date', 'date'],
  'Y.1':   ['y1_regional_tax', 'double'],
  'Y.2':   ['y2_professional_tax', 'double'],
  'Y.3':   ['y3_ecological_tax', 'double'],
  'Y.4':   ['y4_management_tax', 'double'],
  'Y.5':   ['y5_forwarding_expenses_tax', 'double'],
  'Y.6':   ['y6_total_price_vehicle_registration', 'double'],
  'Z.1':   ['z1_specific_details', 'string'],
  'Z.2':   ['z2_specific_details', 'string'],
  'Z.3':   ['z3_specific_details', 'string'],
  'Z.4':   ['z4_specific_details', 'string']
};

/**
 * Normalize a plate like the PHP normalize_registration_number (AB123CD => AB-123-CD)
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {*}      value - Raw plate value
 * @return {string}         Normalized plate
 */
window.dolicar.registrationcertificate.normalizePlate = function(value) {
  var plate = String(value).trim().toUpperCase();
  var match = plate.match(/^([A-Z]{2})(\d{3})([A-Z]{2})$/);
  return match ? match[1] + '-' + match[2] + '-' + match[3] : plate;
};

/**
 * Parse a carte grise date (dd/mm/yyyy, dd-mm-yyyy or yyyy-mm-dd) into day/month/year parts
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {string}      value - Date string
 * @return {Object|null}         {day, month, year} or null if invalid
 */
window.dolicar.registrationcertificate.parseDate = function(value) {
  var day, month, year;
  var match = String(value).trim().match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
  if (match) {
    day = parseInt(match[1], 10); month = parseInt(match[2], 10); year = parseInt(match[3], 10);
  } else {
    match = String(value).trim().match(/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/);
    if (!match) {
      return null;
    }
    year = parseInt(match[1], 10); month = parseInt(match[2], 10); day = parseInt(match[3], 10);
  }
  var date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
    return null;
  }
  return {day: day, month: month, year: year};
};

/**
 * Parse the carte grise rubric C.4a value into 0/1 (mirror of PHP parseCarteGriseBool)
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {*}      value - Rubric value
 * @return {number}         1 if owner, 0 if not
 */
window.dolicar.registrationcertificate.parseBool = function(value) {
  if (typeof value === 'boolean') {
    return value ? 1 : 0;
  }
  if (!isNaN(parseFloat(value)) && isFinite(value)) {
    return parseFloat(value) !== 0 ? 1 : 0;
  }
  return /n\W{0,2}est\s+pas|non|false|^no$/i.test(String(value).trim()) ? 0 : 1;
};

/**
 * Set a simple form field (input, select, textarea or CKEditor) and mark it as filled
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {jQuery}  $form - Edit form
 * @param  {string}  name  - Field name
 * @param  {*}       value - Value to set
 * @return {boolean}         True if the field exists and was set
 */
window.dolicar.registrationcertificate.setField = function($form, name, value) {
  var $field = $form.find('[name="' + name + '"]');
  if (!$field.length) {
    return false;
  }
  if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances[name]) {
    CKEDITOR.instances[name].setData(String(value));
  }
  $field.val(value).trigger('change').addClass('dolicar-json-filled');
  return true;
};

/**
 * Set a Dolibarr date field: visible text input + day/month/year companion fields
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {jQuery}  $form - Edit form
 * @param  {string}  name  - Field name
 * @param  {Object}  date  - {day, month, year}
 * @return {boolean}         True if at least one component was set
 */
window.dolicar.registrationcertificate.setDateField = function($form, name, date) {
  var filled = false;
  var pad    = function(number) { return (number < 10 ? '0' : '') + number; };

  var $text = $form.find('input[name="' + name + '"]');
  if ($text.length) {
    $text.val(pad(date.day) + '/' + pad(date.month) + '/' + date.year).addClass('dolicar-json-filled');
    filled = true;
  }
  $.each({day: date.day, month: date.month, year: date.year}, function(part, value) {
    var $component = $form.find('[name="' + name + part + '"]');
    if ($component.length) {
      $component.val(value).trigger('change');
      filled = true;
    }
  });
  return filled;
};

/**
 * Read the selected carte grise JSON file and fill the edit form fields (no direct save: the user reviews then submits)
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.applyJsonToEditForm = function() {
  var $container = $(this).closest('.dolicar-json-import');
  var fileInput  = $container.find('input[type="file"]')[0];

  if (!fileInput || !fileInput.files || !fileInput.files.length) {
    return;
  }

  var reader = new FileReader();
  reader.onload = function(event) {
    var data = null;
    try {
      data = JSON.parse(event.target.result);
    } catch (error) {
      data = null;
    }
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
      window.alert($container.data('error-invalid'));
      return;
    }
    window.dolicar.registrationcertificate.fillEditForm(data, $container);
  };
  reader.readAsText(fileInput.files[0]);
};

/**
 * Fill the registration certificate edit form from a parsed carte grise JSON
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {Object} data       - Parsed carte grise JSON (rubric code => value)
 * @param  {jQuery} $container - Import container holding the translated messages
 * @return {void}
 */
window.dolicar.registrationcertificate.fillEditForm = function(data, $container) {
  var self  = window.dolicar.registrationcertificate;
  var $form = $('#registrationcertificatefr_form');
  if (!$form.length) {
    return;
  }

  // Warn if the JSON belongs to another vehicle
  var jsonPlateRaw = null;
  $.each(data, function(code, value) {
    if (String(code).trim().toUpperCase() === 'A' && value !== null && String(value).trim() !== '') {
      jsonPlateRaw = value;
      return false;
    }
  });
  var currentPlate = String($form.find('[name="a_registration_number"]').val() || '').trim();
  if (jsonPlateRaw !== null && currentPlate !== '' && self.normalizePlate(jsonPlateRaw) !== self.normalizePlate(currentPlate)) {
    var confirmMessage = String($container.data('confirm-plate'))
      .replace('{jsonPlate}', self.normalizePlate(jsonPlateRaw))
      .replace('{cardPlate}', self.normalizePlate(currentPlate));
    if (!window.confirm(confirmMessage)) {
      return;
    }
  }

  var filledCount = 0;
  $.each(data, function(code, value) {
    if (value === null || typeof value === 'object') {
      return;
    }
    if (typeof value === 'string' && value.trim() === '') {
      return;
    }
    code = String(code).trim().toUpperCase();

    if (code === 'C.4.1') {
      if (!isNaN(parseFloat(value)) && isFinite(value)) {
        filledCount += self.setField($form, 'c41_second_owner_number', parseInt(value, 10)) ? 1 : 0;
      } else {
        filledCount += self.setField($form, 'c41_second_owner_name', String(value).trim()) ? 1 : 0;
      }
      return;
    }

    var definition = self.carteGriseFieldsMap[code];
    if (!definition) {
      return;
    }
    var field  = definition[0];
    var filled = false;
    switch (definition[1]) {
      case 'date':
        var date = self.parseDate(String(value));
        filled   = date !== null && self.setDateField($form, field, date);
        break;
      case 'int':
        var integerMatch = String(value).match(/-?\d+/);
        filled = integerMatch !== null && self.setField($form, field, parseInt(integerMatch[0], 10));
        break;
      case 'double':
        var doubleMatch = String(value).match(/-?\d+(?:[.,]\d+)?/);
        filled = doubleMatch !== null && self.setField($form, field, doubleMatch[0].replace(',', '.'));
        break;
      case 'bool':
        filled = self.setField($form, field, self.parseBool(value));
        break;
      default:
        filled = self.setField($form, field, String(value).trim());
    }
    filledCount += filled ? 1 : 0;
  });

  // Open the registration certificate data panel so the user sees the filled fields
  var $groupHeader = $('.tableforfieldedit .registration-certificate-group-header');
  if ($groupHeader.length && $groupHeader.find('.registration-certificate-group-icon').hasClass('fa-chevron-right')) {
    $groupHeader.trigger('click');
  }

  // Notice inviting the user to review then save
  $('.dolicar-json-applied-banner').remove();
  var bannerMessage = String($container.data('fields-filled')).replace('{count}', filledCount);
  $form.before('<div class="dolicar-json-applied-banner"><i class="fas fa-circle-check"></i><div>' + bannerMessage + '</div></div>');
  $form[0].scrollIntoView({behavior: 'smooth', block: 'start'});
};
