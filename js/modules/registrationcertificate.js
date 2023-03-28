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
	$(document).ready(() => {
		let url = $('.lot-creation-url').val()
		$(document).find('.field_fk_lot .butActionNew').attr('target', '_blank')
		$(document).find('.field_fk_lot .butActionNew').attr('href', url)
	})
}


/**
 * Actualize
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
	console.log(productId)
	let querySeparator = '?'
	document.URL.match(/\?/) ? querySeparator = '&' : 1

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
