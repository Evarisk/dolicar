/* Javascript library of module DoliCar */

/**
 * @namespace Saturne_Framework_Init
 *
 * @author Evarisk <technique@evarisk.com>
 * @copyright 2015-2023 Evarisk
 */

if ( ! window.dolicar ) {
	/**
	 * [dolicar description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Object}
	 */
	window.dolicar = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.dolicar.scriptsLoaded = false;
}

if ( ! window.dolicar.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolicar.init = function() {
		window.dolicar.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolicar.load_list_script = function() {
		if ( ! window.dolicar.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.dolicar ) {

				if ( window.dolicar[key].init ) {
					window.dolicar[key].init();
				}

				for ( slug in window.dolicar[key] ) {

					if ( window.dolicar[key] && window.dolicar[key][slug] && window.dolicar[key][slug].init ) {
						window.dolicar[key][slug].init();
					}

				}
			}

			window.dolicar.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof Saturne_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.dolicar.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.dolicar ) {
			if ( window.dolicar[key].refresh ) {
				window.dolicar[key].refresh();
			}

			for ( slug in window.dolicar[key] ) {

				if ( window.dolicar[key] && window.dolicar[key][slug] && window.dolicar[key][slug].refresh ) {
					window.dolicar[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.dolicar.init );
}

