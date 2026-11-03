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
 * \file    js/modules/fileTag.js
 * \ingroup dolicar
 * \brief   JavaScript file tags of the attached files of a carte grise for module DoliCar
 */

'use strict';

/**
 * Init fileTag JS
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @type {Object}
 */
window.dolicar.fileTag = {};

/**
 * FileTag init
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.dolicar.fileTag.init = function() {
  window.dolicar.fileTag.event();
  window.dolicar.fileTag.moveTagsIntoFileList();
};

/**
 * FileTag event
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.dolicar.fileTag.event = function() {
  $(document).on('change', '.dolicar-file-tag-select', window.dolicar.fileTag.saveTag);
};

/**
 * Add a tag column to the list of attached files, filled with the tag of each file
 *
 * The tags are printed in the page footer: the list has no hook per line
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.dolicar.fileTag.moveTagsIntoFileList = function() {
  let $tags  = $('#dolicar-file-tags');
  let $table = $('#tablelines');
  if ($tags.length === 0 || $table.length === 0) {
    return;
  }

  let $rows = $table.find('tr[id^="row-"]');
  if ($rows.length === 0) {
    return;
  }

  // Right after the file name, on every line so the columns stay aligned
  $table.find('tr.liste_titre').first().children().first().after($('<th class="wrapcolumntitle liste_titre"></th>').text($tags.data('title')));
  $rows.each(function() {
    let $cell = $('<td class="dolicar-file-tag-cell"></td>');
    $cell.append($tags.children('[data-ecm-file-id="' + this.id.replace('row-', '') + '"]'));
    $(this).children('td').first().after($cell);
  });

  // select2 once in the list: hidden in the footer, it would size the pills to nothing
  $table.find('.dolicar-file-tag-select').each(function() {
    let $select = $(this);
    $select.data('saved-value', $select.val());
    if (!$.fn.select2) {
      return;
    }
    $select.select2({
      width: 'auto',
      dropdownAutoWidth: true,
      minimumResultsForSearch: Infinity,
      dropdownCssClass: 'dolicar-file-tag-dropdown',
      templateSelection: function(data) {
        return window.dolicar.fileTag.renderTag(data, true);
      },
      templateResult: function(data) {
        return window.dolicar.fileTag.renderTag(data, false);
      }
    });
  });
};

/**
 * Save the tag picked for a file, back to the previous one when the save fails
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @return {void}
 */
window.dolicar.fileTag.saveTag = function() {
  let $select = $(this);
  let $tags   = $('#dolicar-file-tags');
  let token   = window.saturne.toolbox.getToken();

  // change.select2 only redraws the pill, it does not save again
  let onError = function() {
    $select.val($select.data('saved-value')).trigger('change.select2');
    $.jnotify($tags.data('error'), 'error', true);
  };

  $.ajax({
    url: $tags.data('url') + '&token=' + token,
    type: 'POST',
    data: {ecm_file_id: $select.data('ecm-file-id'), file_tag: $select.val()},
    dataType: 'json',
    success: function(resp) {
      if (!resp.success) {
        onError();
        return;
      }
      $select.data('saved-value', $select.val());
      $.jnotify($tags.data('saved'), 3000, false);
    },
    error: onError
  });
};

/**
 * Pill of a tag: picto and label tinted like the document
 *
 * The selection shows the label of the pill and a caret, the list the label of the option: on the
 * empty choice, "Add a tag" is the pill and "No tag" the line that removes the tag
 *
 * @memberof DoliCar_FileTag
 *
 * @since   1.6.0
 * @version 1.6.0
 *
 * @param  {Object}  data      select2 item
 * @param  {boolean} selection True for the selection of the select2, false for a line of its list
 * @return {jQuery|string}     Rendered tag
 */
window.dolicar.fileTag.renderTag = function(data, selection) {
  if (!data.element) {
    return data.text;
  }

  let $option = $(data.element);
  let $tag    = $('<span class="dolicar-file-tag"></span>').addClass('dolicar-file-tag--' + $option.data('css'));

  $tag.append($('<i class="fas dolicar-file-tag-picto"></i>').addClass(selection ? $option.data('picto') : $option.data('option-picto')));
  $tag.append($('<span class="dolicar-file-tag-label"></span>').text(selection ? $option.data('label') : data.text));
  if (selection) {
    $tag.addClass('dolicar-file-tag-editable').append('<i class="fas fa-chevron-down dolicar-file-tag-caret"></i>');
  }

  return $tag;
};
