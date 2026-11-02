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
 * \file    js/modules/linkedObjects.js
 * \ingroup dolicar
 * \brief   JavaScript module to search/filter the registration certificate linked objects table
 */

'use strict';

/**
 * Init linkedObjects JS
 *
 * @memberof DoliCar_LinkedObjects
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @type {Object}
 */
window.dolicar.linkedObjects = {};

/**
 * LinkedObjects init
 *
 * @memberof DoliCar_LinkedObjects
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.linkedObjects.init = function() {
  window.dolicar.linkedObjects.event();
};

/**
 * LinkedObjects event — bind delegated handlers
 *
 * @memberof DoliCar_LinkedObjects
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.linkedObjects.event = function() {
  $(document).on('input', '.dolicar-linked-objects-filter', window.dolicar.linkedObjects.filter);
  $(document).on('change', '.dolicar-linked-objects-verdict-filter', window.dolicar.linkedObjects.filter);
};

/**
 * Filter the linked objects table rows from the per-column search inputs and the verdict select.
 * A row is shown only when every active filter matches its own column (native Dolibarr list behaviour).
 *
 * @memberof DoliCar_LinkedObjects
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.linkedObjects.filter = function() {
  var $table         = $('.dolicar-linked-objects-table');
  var $textFilters   = $table.find('.dolicar-linked-objects-filter');
  var verdict        = $table.find('.dolicar-linked-objects-verdict-filter').val() || '';

  $table.find('.dolicar-linked-object-row').each(function() {
    var $row = $(this);
    var show = true;

    $textFilters.each(function() {
      var value = ($(this).val() || '').toLowerCase();
      if (value === '') {
        return;
      }
      var column   = parseInt($(this).attr('data-col'), 10);
      var cellText = $row.children('td').eq(column).text().toLowerCase();
      if (cellText.indexOf(value) === -1) {
        show = false;
      }
    });

    if (show && verdict !== '' && String($row.attr('data-verdict')) !== verdict) {
      show = false;
    }

    $row.toggle(show);
  });
};
