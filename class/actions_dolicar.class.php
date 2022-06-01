<?php
/* Copyright (C) 2022 Eoxia <dev@eoxia.fr>
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
 * \file    dolicar/class/actions_dolicar.class.php
 * \ingroup dolicar
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsDoliCar
 */
class ActionsDoliCar
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $db, $conf, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'invoicecard') {

			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') == 'addline' || GETPOST('action') == 'update_extras' || GETPOST('action') != 'create') && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
				require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../../../compta/facture/class/facture.class.php';

				$facture = new Facture($db);
				$facture->fetch(GETPOST('facid') ?: GETPOST('id'));
				$facture->fetch_optionals();
				$registration_certificate_id = $facture->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()
					let rows = jQuery('.valuefieldlinecreate.facturedet_extras_dolicar_data')
					let mileage = ''
					rows.each((i, obj) => {
						if ( $(obj).find('.facturedet_extras_dolicar_data').text().match(/mileage:/)) {
							mileage = <?php echo json_encode($langs->transnoentities('Mileage') . ' : ') ?> + $(obj).find('.facturedet_extras_dolicar_data').text().split(/mileage:/)[1].split(/}/)[0]
							$(obj).text(mileage)
						} else {
							$(obj).hide()
						}
					})
					//Add getNomUrl
					jQuery('.facture_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
					jQuery('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
				</script>
				<?php
			}

			if ( GETPOST('action') != 'create' && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				global $user;
				require_once __DIR__ . '/../../../compta/facture/class/facture.class.php';

				$facture = new Facture($db);
				$facture->fetch(GETPOST('facid') ?: GETPOST('id'));
				$facture->fetch_optionals();

				if (empty($facture->array_options["options_dolicar_data"])) {

					require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
					require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

					$registrationcertificatefr = new RegistrationCertificateFr($db);
					$registrationcertificatefr->fetch($facture->array_options['options_registrationcertificatefr']);

					$product = new Product($db);
					$product->fetch($registrationcertificatefr->d3_vehicle_model);

					$productlot = new ProductLot($db);
					$productlot->fetch($registrationcertificatefr->fk_lot);

					$dolicar_data = array(
						'registration_number' => $registrationcertificatefr->a_registration_number,
						'vehicle_model' => $product->ref,
						'mileage' => $productlot->array_options['options_mileage']
					);

					$dolicar_data_json = json_encode($dolicar_data);
					$facture->array_options["options_dolicar_data"] = $dolicar_data_json;
					$facture->update($user);
				}

				$decoded_json = json_decode($facture->array_options['options_dolicar_data']);

				$output = '<tr><td>'.$langs->trans('Mileage').'</td><td colspan="2">';
				$output .= $decoded_json->mileage;
				$output .= '</td></tr>';

				?>
				<script>
					jQuery('.facture_extras_registrationcertificatefr').parent().parent().append(<?php echo json_encode($output)?>)
				</script>
				<?php
			}
		} else if ($parameters['currentcontext'] == 'propalcard') {

			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') == 'addline' || GETPOST('action') == 'update_extras' || GETPOST('action') != 'create') && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
				require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../../../comm/propal/class/propal.class.php';

				$propal = new Propal($db);
				$propal->fetch(GETPOST('facid') ?: GETPOST('id'));
				$propal->fetch_optionals();
				$registration_certificate_id = $propal->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()
					let rows = jQuery('.valuefieldlinecreate.propaldet_extras_dolicar_data')
					let mileage = ''
					rows.each((i, obj) => {
						if ( $(obj).find('.propaldet_extras_dolicar_data').text().match(/mileage:/)) {
							mileage = <?php echo json_encode($langs->transnoentities('Mileage') . ' : ') ?> + $(obj).find('.propaldet_extras_dolicar_data').text().split(/mileage:/)[1].split(/}/)[0]
							$(obj).text(mileage)
						} else {
							$(obj).hide()
						}
					})
					//Add getNomUrl
					jQuery('.propal_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
					jQuery('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
				</script>
				<?php
			}

			if ( GETPOST('action') != 'create' && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				global $user;
				require_once __DIR__ . '/../../../comm/propal/class/propal.class.php';


				$propal = new Propal($db);
				$propal->fetch(GETPOST('facid') ?: GETPOST('id'));
				$propal->fetch_optionals();

				if (empty($propal->array_options["options_dolicar_data"])) {

					require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
					require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

					$registrationcertificatefr = new RegistrationCertificateFr($db);
					$registrationcertificatefr->fetch($propal->array_options['options_registrationcertificatefr']);

					$product = new Product($db);
					$product->fetch($registrationcertificatefr->d3_vehicle_model);

					$productlot = new ProductLot($db);
					$productlot->fetch($registrationcertificatefr->fk_lot);

					$dolicar_data = array(
						'registration_number' => $registrationcertificatefr->a_registration_number,
						'vehicle_model' => $product->ref,
						'mileage' => $productlot->array_options['options_mileage']
					);

					$dolicar_data_json = json_encode($dolicar_data);
					$propal->array_options["options_dolicar_data"] = $dolicar_data_json;
					$propal->update($user);
				}

				$decoded_json = json_decode($propal->array_options['options_dolicar_data']);

				$output = '<tr><td>'.$langs->trans('Mileage').'</td><td colspan="2">';
				$output .= $decoded_json->mileage;
				$output .= '</td></tr>';

				?>
				<script>
					jQuery('.propal_extras_registrationcertificatefr').parent().parent().append(<?php echo json_encode($output)?>)
				</script>
				<?php
			}
		} else if ($parameters['currentcontext'] == 'ordercard') {

			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') == 'addline' || GETPOST('action') == 'update_extras' || GETPOST('action') != 'create') && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
				require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../../../commande/class/commande.class.php';

				$commande = new Commande($db);
				$commande->fetch(GETPOST('facid') ?: GETPOST('id'));
				$commande->fetch_optionals();
				$registration_certificate_id = $commande->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()
					let rows = jQuery('.valuefieldlinecreate.commandedet_extras_dolicar_data')
					let mileage = ''
					rows.each((i, obj) => {
						if ( $(obj).find('.commandedet_extras_dolicar_data').text().match(/mileage:/)) {
							mileage = <?php echo json_encode($langs->transnoentities('Mileage') . ' : ') ?> + $(obj).find('.commandedet_extras_dolicar_data').text().split(/mileage:/)[1].split(/}/)[0]
							$(obj).text(mileage)
						} else {
							$(obj).hide()
						}
					})
					//Add getNomUrl
					jQuery('.commande_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
					jQuery('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl()) ?>)
				</script>
				<?php
			}

			if ( GETPOST('action') != 'create' && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				global $user;
				require_once __DIR__ . '/../../../commande/class/commande.class.php';


				$commande = new Commande($db);
				$commande->fetch(GETPOST('facid') ?: GETPOST('id'));
				$commande->fetch_optionals();

				if (empty($commande->array_options["options_dolicar_data"])) {

					require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
					require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

					$registrationcertificatefr = new RegistrationCertificateFr($db);
					$registrationcertificatefr->fetch($commande->array_options['options_registrationcertificatefr']);

					$product = new Product($db);
					$product->fetch($registrationcertificatefr->d3_vehicle_model);

					$productlot = new ProductLot($db);
					$productlot->fetch($registrationcertificatefr->fk_lot);

					$dolicar_data = array(
						'registration_number' => $registrationcertificatefr->a_registration_number,
						'vehicle_model' => $product->ref,
						'mileage' => $productlot->array_options['options_mileage']
					);

					$dolicar_data_json = json_encode($dolicar_data);
					$commande->array_options["options_dolicar_data"] = $dolicar_data_json;
					$commande->update($user);
				}

				$decoded_json = json_decode($commande->array_options['options_dolicar_data']);

				$output = '<tr><td>'.$langs->trans('Mileage').'</td><td colspan="2">';
				$output .= $decoded_json->mileage;
				$output .= '</td></tr>';

				?>
				<script>
					jQuery('.commande_extras_registrationcertificatefr').parent().parent().append(<?php echo json_encode($output)?>)
				</script>
				<?php
			}
		}

		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter$

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'productlotcard') {        // do something only for the context 'somecontext1' or 'somecontext2'
			if ($action == 'update_extras') {
				$object->call_trigger('DOLICAR_PRODUCTLOT_MILEAGE_MODIFY', $user);
			}

			if (!$error) {
				$this->results = array('myreturn' => 999);
				$this->resprints = 'A text to show';
				return 0; // or return 1 to replace standard code
			} else {
				$this->errors[] = 'Error message';
				return -1;
			}
		} else if ($parameters['currentcontext'] == 'invoicecard') {
			if (GETPOST('action') == 'addline') {

				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);
				$registrationcertificatefr->fetch($object->array_options['options_registrationcertificatefr']);

				$product = new Product($this->db);
				$product->fetch($registrationcertificatefr->d3_vehicle_model);

				$productlot = new ProductLot($this->db);
				$productlot->fetch($registrationcertificatefr->fk_lot);

				$dolicar_data = array(
					'registration_number' => $registrationcertificatefr->a_registration_number,
					'vehicle_model' => $product->ref,
					'mileage' => $productlot->array_options['options_mileage']
				);

				$dolicar_data_json = json_encode($dolicar_data);
				$_POST['options_dolicar_data'] = $dolicar_data_json;
			}
		} else if ($parameters['currentcontext'] == 'propalcard') {
			if (GETPOST('action') == 'addline') {

				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);
				$registrationcertificatefr->fetch($object->array_options['options_registrationcertificatefr']);

				$product = new Product($this->db);
				$product->fetch($registrationcertificatefr->d3_vehicle_model);

				$productlot = new ProductLot($this->db);
				$productlot->fetch($registrationcertificatefr->fk_lot);

				$dolicar_data = array(
					'registration_number' => $registrationcertificatefr->a_registration_number,
					'vehicle_model' => $product->ref,
					'mileage' => $productlot->array_options['options_mileage']
				);

				$dolicar_data_json = json_encode($dolicar_data);
				$_POST['options_dolicar_data'] = $dolicar_data_json;
			}
		} else if ($parameters['currentcontext'] == 'ordercard') {
			if (GETPOST('action') == 'addline') {

				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);
				$registrationcertificatefr->fetch($object->array_options['options_registrationcertificatefr']);

				$product = new Product($this->db);
				$product->fetch($registrationcertificatefr->d3_vehicle_model);

				$productlot = new ProductLot($this->db);
				$productlot->fetch($registrationcertificatefr->fk_lot);

				$dolicar_data = array(
					'registration_number' => $registrationcertificatefr->a_registration_number,
					'vehicle_model' => $product->ref,
					'mileage' => $productlot->array_options['options_mileage']
				);

				$dolicar_data_json = json_encode($dolicar_data);
				$_POST['options_dolicar_data'] = $dolicar_data_json;
			}
		}
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("DoliCarMassAction").'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $db, $langs, $user;

		$ret = 0;
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		if (
		(in_array('ordercard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_ORDERCARD))
		|| (in_array('propalcard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_PROPALCARD))
		|| (in_array('invoicecard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_INVOICECARD))
		) {

			$object->fetch_optionals();

			$dolicar_data_decoded = json_decode($object->array_options["options_dolicar_data"]);

			$object->note_public = '';

			foreach($dolicar_data_decoded as $key => $data) {
				switch ($key) {
					case 'registration_number' :
						$object->note_public .= $langs->transnoentities('RegistrationNumber') . ' : ' . $data . '<br>';
						break;
					case 'vehicle_model' :
						$object->note_public .= $langs->transnoentities('VehicleModel') . ' : ' . $data . '<br>';
						break;
					case 'mileage' :
						$object->note_public .=$langs->transnoentities('Mileage') . ' : ' . $data . '<br>';
						break;
				}
			}
		}

		return $ret;
	}

	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->dolicar->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// utilisé si on veut faire disparaitre des onglets.
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('dolicar@dolicar');
			// utilisé si on veut ajouter des onglets.
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/dolicar/dolicar_tab.php', 1) . '?id=' . $id . '&amp;module='.$element;
				$parameters['head'][$counter][1] = $langs->trans('DoliCarTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'dolicaremails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// en V14 et + $parameters['head'] est modifiable par référence
				return 0;
			}
		}
	}

	/* Add here any other hooked methods... */
}
