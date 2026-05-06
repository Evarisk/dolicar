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
