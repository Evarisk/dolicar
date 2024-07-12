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

  $('#public-vehicle-log-book-form').on('submit', function(event) {
    event.preventDefault();
    if (!$(this).find('.public-vehicle-log-book-validate').hasClass('button-disable')) {
      window.dolicar.registrationcertificate.createPublicVehicleLogBook();
    }
  });

  $(document).on('touchstart mousedown', '.canvas-signature', function () {
    window.saturne.toolbox.removeAddButtonClass('public-vehicle-log-book-validate', 'button-grey button-disable', 'button-blue');
  });

  $(document).on('click', '.signature-erase', function () {
    window.saturne.toolbox.removeAddButtonClass('public-vehicle-log-book-validate', 'button-blue', 'button-grey button-disable');
  });
};


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

	window.saturne.loader.display($('.lot-content'));

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

/**
 * Create public vehicle log book entry
 *
 * @memberof DoliCar_RegistrationCertificate
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @return {void}
 */
window.dolicar.registrationcertificate.createPublicVehicleLogBook = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  const formData = new FormData($('#public-vehicle-log-book-form')[0]);
  if (window.saturne.signature.canvas) {
    const signature = window.saturne.signature.canvas.toDataURL();
    formData.append('signature', JSON.stringify(signature));
  }

  $.ajax({
    url: document.URL + querySeparator + 'action=add&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    data: formData,
    success: function() {
      $('.card__confirmation').css('display', 'flex');
      $('.public-vehicle-log-book-confirmation-close').on('click', function() {
        $('.card__confirmation').css('display', 'none');
        window.location.reload();
      });
    },
    error: function() {}
  });
};
