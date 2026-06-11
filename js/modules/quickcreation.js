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
 * \file    js/modules/quickcreation.js
 * \ingroup dolicar
 * \brief   JavaScript module for the registration certificate quick creation flow
 */

'use strict';

/**
 * Init quickcreation JS
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @type {Object}
 */
window.dolicar.quickcreation = {};

/**
 * QuickCreation init
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.init = function() {
  window.dolicar.quickcreation.event();
};

/**
 * QuickCreation event — bind all delegated handlers
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.event = function() {
  $(document).on('click', '.qc-source-btn', window.dolicar.quickcreation.toggleSourceDropdown);
  $(document).on('click', '.qc-source-option', window.dolicar.quickcreation.selectSource);
  $(document).on('click', '.qc-section-header.collapsible', window.dolicar.quickcreation.toggleSection);
  $(document).on('input', '.qc-plate-input', window.dolicar.quickcreation.formatPlate);
  $(document).on('click', window.dolicar.quickcreation.closeDropdownsOnOutsideClick);
  $(document).on('change', '.qc-dropzone input[type="file"]', window.dolicar.quickcreation.updateDropzone);
  $(document).on('dragover dragleave drop', '.qc-dropzone', window.dolicar.quickcreation.handleDropzoneDrag);
  $(document).on('submit', '.qc-ai-scan-form', window.dolicar.quickcreation.lockAiScanSubmit);
};

/**
 * Toggle the source API dropdown open/closed
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {Event} event - Click event
 * @return {void}
 */
window.dolicar.quickcreation.toggleSourceDropdown = function(event) {
  event.stopPropagation();
  var $wrap = $(this).closest('.qc-source-wrap');
  var isOpen = $wrap.hasClass('open');

  $('.qc-source-wrap.open').not($wrap).removeClass('open');
  $wrap.toggleClass('open', !isOpen);
};

/**
 * Select a source from the dropdown and update the button label
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {Event} event - Click event
 * @return {void}
 */
window.dolicar.quickcreation.selectSource = function(event) {
  event.stopPropagation();
  var $option = $(this);
  var $wrap   = $option.closest('.qc-source-wrap');
  var label   = $option.find('.qc-opt-title').text();
  var value   = $option.data('source');

  $wrap.find('.qc-source-option').removeClass('selected');
  $option.addClass('selected');
  $wrap.find('.qc-source-text').text(label);
  $wrap.find('input[type="hidden"]').val(value);
  $wrap.removeClass('open');
};

/**
 * Close all open source dropdowns when clicking outside
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {Event} event - Click event
 * @return {void}
 */
window.dolicar.quickcreation.closeDropdownsOnOutsideClick = function(event) {
  if (!$(event.target).closest('.qc-source-wrap').length) {
    $('.qc-source-wrap.open').removeClass('open');
  }
};

/**
 * Toggle a collapsible section body open or closed
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.toggleSection = function() {
  var $header = $(this);
  $header.toggleClass('collapsed');
  $header.next('.qc-section-body').toggleClass('collapsed');
};

/**
 * Force uppercase on plate input fields
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.formatPlate = function() {
  var pos   = this.selectionStart;
  this.value = this.value.toUpperCase();
  this.setSelectionRange(pos, pos);
};

/**
 * Reflect the selected JSON file in the dropzone and enable the submit button
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.updateDropzone = function() {
  var $dropzone = $(this).closest('.qc-dropzone');
  var hasFile   = this.files && this.files.length > 0;

  $dropzone.toggleClass('has-file', hasFile);
  $dropzone.find('.qc-dropzone-filename').text(hasFile ? this.files[0].name : '');
  $dropzone.closest('form, .dolicar-json-import').find('button[type="submit"], .dolicar-json-apply').prop('disabled', !hasFile);
};

/**
 * Lock the AI scan submit button with a spinner while the analysis runs server-side
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.dolicar.quickcreation.lockAiScanSubmit = function() {
  var $button = $(this).find('button[type="submit"]');
  $button.css('pointer-events', 'none').css('opacity', '0.7');
  $button.find('i').attr('class', 'fas fa-spinner fa-spin');
};

/**
 * Handle drag & drop of a JSON file onto the dropzone
 *
 * @memberof DoliCar_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @param  {Event} event - dragover, dragleave or drop event
 * @return {void}
 */
window.dolicar.quickcreation.handleDropzoneDrag = function(event) {
  event.preventDefault();
  event.stopPropagation();

  var $dropzone = $(this);
  $dropzone.toggleClass('dragover', event.type === 'dragover');

  if (event.type === 'drop') {
    var files = event.originalEvent.dataTransfer.files;
    if (files && files.length > 0) {
      var input = $dropzone.find('input[type="file"]')[0];
      input.files = files;
      $(input).trigger('change');
    }
  }
};
