/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
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
 * \file    js/dolicar.js
 * \ingroup dolicar
 * \brief   JavaScript file for module DoliCar
 */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author    Evarisk <technique@evarisk.com>
 * @copyright 2021-2024 Evarisk
 */

'use strict';

if (!window.dolicar) {
  /**
   * Init Dolicar JS
   *
   * @memberof Saturne_Framework_Init
   *
   * @since   1.0.0
   * @version 1.2.0
   *
   * @type {Object}
   */
  window.dolicar = {};

  /**
   * Init load script dolicar JS
   *
   * @memberof Saturne_Framework_Init
   *
   * @since   1.0.0
   * @version 1.2.0
   *
   * @type {Boolean}
   */
  window.dolicar.scriptsLoaded = false;
}

if (!window.dolicar.scriptsLoaded) {
  /**
   * Dolicar init
   *
   * @memberof Saturne_Framework_Init
   *
   * @since   1.0.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.dolicar.init = function() {
    window.dolicar.load_list_script();
  };

  /**
   * Load script/module of dolicar
   *
   * @memberof Saturne_Framework_Init
   *
   * @since   1.0.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.dolicar.load_list_script = function() {
    if (!window.dolicar.scriptsLoaded) {
      for (let key in window.dolicar) {
        if (window.dolicar.hasOwnProperty(key) && typeof window.dolicar[key] === 'object') {
          if (typeof window.dolicar[key].init === 'function') {
            window.dolicar[key].init();
          }

          for (let slug in window.dolicar[key]) {
            if (window.dolicar[key].hasOwnProperty(slug) && typeof window.dolicar[key][slug] === 'object' && typeof window.dolicar[key][slug].init === 'function') {
              window.dolicar[key][slug].init();
            }
          }
        }
      }

      window.dolicar.scriptsLoaded = true;
    }
  };

  /**
   * Reload script/module of dolicar
   *
   * @memberof Saturne_Framework_Init
   *
   * @returns {void}
   */
  window.dolicar.refresh = function() {
    for (let key in window.dolicar) {
      if (window.dolicar.hasOwnProperty(key) && typeof window.dolicar[key] === 'object') {
        if (typeof window.dolicar[key].refresh === 'function') {
          window.dolicar[key].refresh();
        }

        for (let slug in window.dolicar[key]) {
          if (window.dolicar[key].hasOwnProperty(slug) && window.dolicar[key][slug] && typeof window.dolicar[key][slug].refresh === 'function') {
            window.dolicar[key][slug].refresh();
          }
        }
      }
    }
  };

  $(document).ready(window.dolicar.init);
}
