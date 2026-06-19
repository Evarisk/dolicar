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
  $(document).on('input', '.dolicar-linked-objects-search', window.dolicar.linkedObjects.filter);
  $(document).on('change', '.dolicar-linked-objects-verdict-filter', window.dolicar.linkedObjects.filter);
};

/**
 * Filter the linked objects table rows from the search text and the verdict select
 *
 * @memberof DoliCar_LinkedObjects
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.dolicar.linkedObjects.filter = function() {
  var search  = ($('.dolicar-linked-objects-search').val() || '').toLowerCase();
  var verdict = $('.dolicar-linked-objects-verdict-filter').val() || '';

  $('.dolicar-linked-objects-table .dolicar-linked-object-row').each(function() {
    var $row         = $(this);
    var textMatch    = $row.text().toLowerCase().indexOf(search) !== -1;
    var verdictMatch = verdict === '' || String($row.attr('data-verdict')) === verdict;

    $row.toggle(textMatch && verdictMatch);
  });
};
