/**
 * Initialise l'objet "registrationcertificate" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.dolicar.registrationcertificate = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.init = function() {
	window.dolicar.registrationcertificate.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.event = function() {
	$( document ).on( 'change', '#fk_product', window.dolicar.registrationcertificate.actualizeBrand );
	$( document ).on( 'change', '#fk_product', window.dolicar.registrationcertificate.actualizeProductlot );
	$( document ).ready(() => {
		$(document).find('.field_fk_soc .butActionNew').attr('target', '_blank')
		$(document).find('.field_fk_project .butActionNew').attr('target', '_blank')
	})

}


/**
 * Actualize brand input
 *
 * @since   0.0.2
 * @version 0.0.2
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.actualizeBrand = function( event ) {

	let token = $('input[name="token"]').val();

	var form = document.getElementById('registrationcertificatefr_create')? document.getElementById('registrationcertificatefr_create') : document.getElementById('registrationcertificatefr_edit')
	var formData = new FormData(form);
	let productId = formData.get('fk_product');
	let querySeparator =  window.saturne.toolbox.getQuerySeparator(document.URL)

	$.ajax({
		url: document.URL + querySeparator + 'subaction=getProductBrand&token='+token,
		data: JSON.stringify({
			productId: productId,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('#d1_vehicle_brand').attr('value', $(resp).find('.car-brand').val())
			$('#d1_vehicle_brand').prop("readonly", true)
		},
	});
};

/**
 * Actualize productlot selector
 *
 * @since   0.0.2
 * @version 0.0.2
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.actualizeProductlot = function( event ) {

	let token = $('input[name="token"]').val();

	var form = document.getElementById('registrationcertificatefr_create')? document.getElementById('registrationcertificatefr_create') : document.getElementById('registrationcertificatefr_edit')
	var formData = new FormData(form);
	let productId = formData.get('fk_product');
	let action = formData.get('action');

	if (action == 'update') {
		action = 'edit';
	}

	let querySeparator =  window.saturne.toolbox.getQuerySeparator(document.URL)

	window.saturne.loader.display($('.lot-container'));

	$.ajax({
		url: document.URL + querySeparator + 'action=' + action + '&fk_product=' + productId + '&token='+token,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.lot-container').html($(resp).find('.lot-content'))
			$('.wpeo-loader').removeClass('wpeo-loader');
		},
	});
};
