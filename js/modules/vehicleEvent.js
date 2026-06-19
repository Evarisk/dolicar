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
 * \file    js/modules/vehicleEvent.js
 * \ingroup dolicar
 * \brief   JavaScript module for the vehicle event form (expense report line picker)
 */

'use strict';

/**
 * Init vehicleEvent JS
 *
 * @memberof DoliCar_VehicleEvent
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @type {Object}
 */
window.dolicar.vehicleEvent = {};

/**
 * VehicleEvent init
 *
 * @memberof DoliCar_VehicleEvent
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleEvent.init = function() {
  window.dolicar.vehicleEvent.event();
};

/**
 * VehicleEvent event — bind delegated handlers
 *
 * @memberof DoliCar_VehicleEvent
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleEvent.event = function() {
  $(document).on('change', '#event_fk_expensereport', window.dolicar.vehicleEvent.loadExpenseReportLines);
};

/**
 * Load the lines of the selected expense report and render them as checkboxes
 *
 * @memberof DoliCar_VehicleEvent
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @return {void}
 */
window.dolicar.vehicleEvent.loadExpenseReportLines = function() {
  var fkExpenseReport = $(this).val();
  var $container      = $('#dolicar-er-lines');
  var $row            = $('#dolicar-er-lines-row');

  if (!fkExpenseReport || parseInt(fkExpenseReport, 10) <= 0) {
    $container.empty();
    $row.hide();
    return;
  }

  var url = $container.data('url') + '&action=get_expensereport_lines&fk_expensereport=' + encodeURIComponent(fkExpenseReport);

  $.getJSON(url, function(lines) {
    $container.empty();

    if (!lines || lines.length === 0) {
      $row.hide();
      return;
    }

    $.each(lines, function(index, line) {
      var $label = $('<label class="dolicar-er-line"></label>');
      var $checkbox = $('<input type="checkbox" name="event_expensereport_lines[]">').val(line.id);
      var $text = $('<span></span>').text(line.label + ' — ' + line.amount);

      $label.append($checkbox).append($text);
      $container.append($label);
    });

    $row.show();
  });
};
