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
				require_once __DIR__ . '/../../../product/class/product.class.php';
				require_once __DIR__ . '/../../../compta/facture/class/facture.class.php';

				$facture = new Facture($db);
				$facture->fetch(GETPOST('facid') ?: GETPOST('id'));
				$facture->fetch_optionals();
				$registration_certificate_id = $facture->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				$product = new Product($db);
				$productlot = new Productlot($db);

				if ($facture->array_options['options_linked_product'] > 0) {
					$product->fetch($facture->array_options['options_linked_product']);
				}
				if ($facture->array_options['options_linked_lot'] > 0) {
					$productlot->fetch($facture->array_options['options_linked_lot']);
				}

				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()

					//Add getNomUrl
					jQuery('.facturedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
				</script>
				<?php
				if (GETPOST('action') != 'edit_extras') {
					?>
					<script>
						jQuery('.facture_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
						jQuery('.facture_extras_linked_product').not('.valuefieldlinecreate').html(<?php echo json_encode($product->getNomUrl(1)) ?>)
						jQuery('.facture_extras_linked_lot').not('.valuefieldlinecreate').html(<?php echo json_encode($productlot->getNomUrl(1)) ?>)
					</script>
					<?php
				}
				if ($conf->global->DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS) :
					?>
					<script>
						jQuery('.facturedet_extras_registrationcertificatefr').hide()
						jQuery('.facturedet_extras_mileage').hide()
						jQuery('.facturedet_extras_vehicle_model').hide()
						jQuery('.facturedet_extras_registration_number').hide()
						jQuery('.facturedet_extras_linked_product').hide()
						jQuery('.facturedet_extras_linked_lot').hide()
					</script>
				<?php
				endif;
			} elseif (GETPOST('action') == 'create' || (empty(GETPOST('facid')) && empty(GETPOST('id')))){
				?>
				<script>
					jQuery('.facture_extras_vehicle_model').hide()
					jQuery('.facture_extras_registration_number').hide()
					jQuery('.facture_extras_linked_product').hide()
					jQuery('.facture_extras_linked_lot').hide()
				</script>
				<?php
			}

		} else if ($parameters['currentcontext'] == 'propalcard') {

			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') == 'addline' || GETPOST('action') == 'update_extras' || GETPOST('action') != 'create') && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
				require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../../../product/class/product.class.php';
				require_once __DIR__ . '/../../../comm/propal/class/propal.class.php';

				$propal = new Propal($db);
				$propal->fetch(GETPOST('facid') ?: GETPOST('id'));
				$propal->fetch_optionals();
				$registration_certificate_id = $propal->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				$product = new Product($db);
				$productlot = new Productlot($db);

				if ($propal->array_options['options_linked_product'] > 0) {
					$product->fetch($propal->array_options['options_linked_product']);
				}

				if ($propal->array_options['options_linked_lot'] > 0) {
					$productlot->fetch($propal->array_options['options_linked_lot']);
				}

				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()

					//Add getNomUrl
					jQuery('.propaldet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
				</script>
				<?php
				if (GETPOST('action') != 'edit_extras') {
					?>
					<script>
						jQuery('.propal_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
						jQuery('.propal_extras_linked_product').not('.valuefieldlinecreate').html(<?php echo json_encode($product->getNomUrl(1)) ?>)
						jQuery('.propal_extras_linked_lot').not('.valuefieldlinecreate').html(<?php echo json_encode($productlot->getNomUrl(1)) ?>)
					</script>
					<?php
				}
				if ($conf->global->DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS) :
					?>
					<script>
						jQuery('.propaldet_extras_registrationcertificatefr').hide()
						jQuery('.propaldet_extras_mileage').hide()
						jQuery('.propaldet_extras_vehicle_model').hide()
						jQuery('.propaldet_extras_registration_number').hide()
						jQuery('.propaldet_extras_linked_product').hide()
						jQuery('.propaldet_extras_linked_lot').hide()

					</script>
				<?php
				endif;
			} elseif (GETPOST('action') == 'create' || (empty(GETPOST('facid')) && empty(GETPOST('id')))){
				?>
				<script>
					jQuery('.propal_extras_vehicle_model').hide()
					jQuery('.propal_extras_registration_number').hide()
					jQuery('.propal_extras_linked_product').hide()
					jQuery('.propal_extras_linked_lot').hide()
				</script>
				<?php
			}
		} else if ($parameters['currentcontext'] == 'ordercard') {
			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') == 'addline' || GETPOST('action') == 'update_extras' || GETPOST('action') != 'create') && (GETPOST('facid') > 0 || GETPOST('id') > 0)) {

				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
				require_once __DIR__ . '/../../../product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../../../product/class/product.class.php';
				require_once __DIR__ . '/../../../commande/class/commande.class.php';

				$commande = new Commande($db);
				$commande->fetch(GETPOST('facid') ?: GETPOST('id'));
				$commande->fetch_optionals();
				$registration_certificate_id = $commande->array_options['options_registrationcertificatefr'];
				$registration_certificate = new RegistrationCertificateFr($db);
				$registration_certificate->fetch($registration_certificate_id);

				$outputline =  $registration_certificate->select_registrationcertificate_list($registration_certificate_id);

				$product = new Product($db);
				$productlot = new Productlot($db);

				if ($commande->array_options['options_linked_product'] > 0) {
					$product->fetch($commande->array_options['options_linked_product']);
				}

				if ($commande->array_options['options_linked_lot'] > 0) {
					$productlot->fetch($commande->array_options['options_linked_lot']);
				}
				?>
				<script>
					jQuery('#extrafield_lines_area_create').find('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').empty()
					jQuery('#extrafield_lines_area_create').find('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').append(<?php echo json_encode($outputline) ; ?>)
					jQuery('#extrafield_lines_area_create').hide()
					jQuery('#extrafield_lines_area_edit').hide()

					//Add getNomUrl
					jQuery('.commandedet_extras_registrationcertificatefr').not('.valuefieldlinecreate').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
				</script>
				<?php
                if (GETPOST('action') != 'edit_extras') {
					?>
					<script>
						jQuery('.commande_extras_registrationcertificatefr').html(<?php echo json_encode($registration_certificate->getNomUrl(1)) ?>)
						jQuery('.commande_extras_linked_product').not('.valuefieldlinecreate').html(<?php echo json_encode($product->getNomUrl(1)) ?>)
						jQuery('.commande_extras_linked_lot').not('.valuefieldlinecreate').html(<?php echo json_encode($productlot->getNomUrl(1)) ?>)
					</script>
					<?php
				}
				if ($conf->global->DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS) :
					?>
					<script>
						jQuery('.commandedet_extras_registrationcertificatefr').hide()
						jQuery('.commandedet_extras_mileage').hide()
						jQuery('.commandedet_extras_vehicle_model').hide()
						jQuery('.commandedet_extras_registration_number').hide()
						jQuery('.commandedet_extras_linked_product').hide()
						jQuery('.commandedet_extras_linked_lot').hide()
					</script>
					<?php
				endif;
			} elseif (GETPOST('action') == 'create' || (empty(GETPOST('facid')) && empty(GETPOST('id')))){
				?>
				<script>
					jQuery('.commande_extras_vehicle_model').hide()
					jQuery('.commande_extras_registration_number').hide()
					jQuery('.commande_extras_linked_product').hide()
					jQuery('.commande_extras_linked_lot').hide()
				</script>
				<?php
			}
		} else if ($parameters['currentcontext'] == 'productlotcard' && GETPOST('action') != 'create') {
			require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
			require_once __DIR__ . '/../../../compta/facture/class/facture.class.php';
			require_once __DIR__ . '/../../../commande/class/commande.class.php';
			require_once __DIR__ . '/../../../comm/propal/class/propal.class.php';

			$registration_certificate = new RegistrationCertificateFr($this->db);
			$facture = new Facture($this->db);
			$facturedet = new FactureLigne($this->db);
			$propal = new Propal($this->db);
			$propaldet = new PropaleLigne($this->db);
			$commande = new Commande($this->db);
			$commandedet = new OrderLine($this->db);

			$RC_list = $registration_certificate->fetchAll('', '','','',array('fk_lot'=> GETPOST('id')));
			$linked_rc_ids = array();
			$linked_facture_ids = array();
			if (!empty($RC_list)) {
				foreach ($RC_list as $rc) {
					$linked_rc_ids[] = $rc->id;
					$objectsLinkedList[$rc->id] = $rc->getObjectsLinked();
				}
			}
			$pictopath = dol_buildpath('/dolicar/img/dolicar.png', 1);
			$pictoDoliCar = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

			$outputline = '<table><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">'. $pictoDoliCar . $langs->transnoentities('ObjectsLinked') .'</div></td></tr></table>';
			$outputline .= '<table><div class="div-table-responsive-no-min"><table class="liste formdoc noborder centpercent"><tbody>';
			$outputline .= '<tr class="liste_titre">';
			$outputline .= '<td class="float">'. $langs->transnoentities('ObjectType') .'</td>&nbsp;';
			$outputline .= '<td class="float">'. $langs->transnoentities('Object') .'</td>&nbsp;';
			$outputline .= '<td class="float">'. $langs->transnoentities('Mileage') .'</td>&nbsp;';
			$outputline .= '<td class="float">'. $langs->transnoentities('Date') .'</td>&nbsp;';
			$outputline .= '</tr>';

			if (!empty($objectsLinkedList)) {
				foreach ($objectsLinkedList as $subList) {

					if (!empty($subList)) {
						foreach ($subList as $key => $object_ids) {
							switch ($key) {
								case 'facture':
									foreach ($object_ids as $object_id) {

										$facture->fetch($object_id);
										$facture->fetch_optionals();
										$outputline .= '<tr>';

										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
										$outputline .= '<td>'. $facture->getNomUrl() .'</td>';
										$outputline .= '<td>'.  $facture->array_options['options_mileage'] .'</td>';
										$outputline .= '<td>'.  dol_print_date($facture->date_creation, 'dayhour') .'</td>';
										$outputline .= '</tr>';
									}
									break;
//								case 'facturedet':
//									foreach ($object_ids as $object_id) {
//
//										$facturedet->fetch($object_id);
//										$facturedet->fetch_optionals();
//										$facture->fetch($facturedet->fk_facture);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $facture->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $facturedet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($facturedet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
								case 'propal':
									foreach ($object_ids as $object_id) {

										$propal->fetch($object_id);
										$propal->fetch_optionals();
										$outputline .= '<tr>';

										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
										$outputline .= '<td>'. $propal->getNomUrl() .'</td>';
										$outputline .= '<td>'.  $propal->array_options['options_mileage'] .'</td>';
										$outputline .= '<td>'.  dol_print_date($propal->date_creation, 'dayhour') .'</td>';
										$outputline .= '</tr>';
									}
									break;
//								case 'propaldet':
//									foreach ($object_ids as $object_id) {
//
//										$propaldet->fetch($object_id);
//										$propaldet->fetch_optionals();
//										$propal->fetch($propaldet->fk_propal);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $propal->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $propaldet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($propaldet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
								case 'commande':
									foreach ($object_ids as $object_id) {

										$commande->fetch($object_id);
										$commande->fetch_optionals();
										$outputline .= '<tr>';

										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
										$outputline .= '<td>'. $commande->getNomUrl() .'</td>';
										$outputline .= '<td>'.  $commande->array_options['options_mileage'] .'</td>';
										$outputline .= '<td>'.  dol_print_date($commande->date_creation, 'dayhour') .'</td>';
										$outputline .= '</tr>';
									}
									break;
//								case 'commandedet':
//									foreach ($object_ids as $object_id) {
//
//										$commandedet->fetch($object_id);
//										$commandedet->fetch_optionals();
//										$commande->fetch($commandedet->fk_commande);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $commande->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $commandedet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($commandedet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
							}
						}
					}
				}
			}
			$outputline .= '</tbody></table></div>';
			?>
			<script>
				jQuery('.fiche .tabBar .fichecenter').append(<?php echo json_encode($outputline) ?>)
			</script>
			<?php
		} else if ($parameters['currentcontext'] == 'registrationcertificatefrcard') {

			//Filter products selector with product with "Vehicle" category
			require_once __DIR__ . '/../../../categories/class/categorie.class.php';
			$category = new Categorie($db);
			$registration_certificate = new RegistrationCertificateFr($db);
			GETPOST('id') > 0 ? $registration_certificate->fetch(GETPOST('id')) : '';

			$category->fetch($conf->global->DOLICAR_VEHICLE_TAG);
			$objects_in_categ = $category->getObjectsInCateg('product');
			$product_ids = array();
			if (!empty($objects_in_categ)) {
				foreach ($objects_in_categ as $object_in_categ) {
					$product_ids[$object_in_categ->id] = $object_in_categ->id;
				}
			}

			$brand_name = get_vehicle_brand(GETPOST('fk_product')?:$registration_certificate->fk_product);

			?>
			<script>
				//remove products that have not the vehicle tag
				let array_ids = <?php echo json_encode($product_ids); ?>;
				jQuery('#fk_product').find('option').each(function() {
					if ($(this).attr('value') != -1) {
						Object.values(array_ids).includes($(this).attr('value')) ? '' : $(this).remove()
					}
				})
				let newProductHref = jQuery('.field_fk_product .valuefieldcreate').find('.butActionNew').attr('href')
				let mainCategoryId = <?php echo json_encode($conf->global->DOLICAR_VEHICLE_TAG); ?>;
				jQuery('#fk_product').closest('.valuefieldcreate').find('.butActionNew').attr('href',newProductHref + '&categories[]=' + mainCategoryId)

				$('#d1_vehicle_brand').attr('value',<?php echo json_encode($brand_name); ?>)
				$('#d1_vehicle_brand').prop("readonly", true)

				//set statut & statut buy to 0 in "+" button of creation form
				$('.field_fk_product').find('.butActionNew').attr('href', $('.field_fk_product').find('.butActionNew').attr('href') + '&statut=0&statut_buy=0')
			</script>
			<?php
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
		} else if ($parameters['currentcontext'] == 'invoicecard' || $parameters['currentcontext'] == 'propalcard' || $parameters['currentcontext'] == 'commandecard') {

			if ( GETPOST('action') == 'add') {

				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);
				$registrationcertificatefr->fetch(GETPOST('options_registrationcertificatefr'));

				$_POST['options_registration_number'] = $registrationcertificatefr->a_registration_number;
				$_POST['options_vehicle_model'] = $registrationcertificatefr->d3_vehicle_model;
				$_POST['options_linked_product'] = $registrationcertificatefr->fk_product;
				$_POST['options_linked_lot'] = $registrationcertificatefr->fk_lot;
			}

			if (GETPOST('action') == 'addline') {

				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);
				$registrationcertificatefr->fetch($object->array_options['options_registrationcertificatefr']);

				$product = new Product($this->db);
				$product->fetch($registrationcertificatefr->fk_product);

				$productlot = new ProductLot($this->db);
				$productlot->fetch($registrationcertificatefr->fk_lot);

				$_POST['options_registrationcertificatefr'] = $object->array_options['options_registrationcertificatefr'];
				$_POST['options_registration_number'] = $object->array_options['options_registration_number'];
				$_POST['options_vehicle_model'] = $object->array_options['options_vehicle_model'];
				$_POST['options_mileage'] = $object->array_options['options_mileage'];
				$_POST['options_linked_lot'] = $object->array_options['options_linked_lot'];
			}

			if (GETPOST('action') == 'update_extras') {
				require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

				$registrationcertificatefr = new RegistrationCertificateFr($this->db);

				if (GETPOST('attribute') == 'registrationcertificatefr') {

					$registrationcertificatefr_id = GETPOST('options_registrationcertificatefr');
					$registrationcertificatefr->fetch($registrationcertificatefr_id);
					$object->array_options['options_registration_number'] = $registrationcertificatefr->a_registration_number;
					$object->array_options['options_vehicle_model'] = $registrationcertificatefr->d3_vehicle_model;
					$object->array_options['options_linked_product'] = $registrationcertificatefr->fk_product;
					$object->array_options['options_linked_lot'] = $registrationcertificatefr->fk_lot;
					$object->update($user);

				}
				if (GETPOST('attribute') == 'mileage') {
					$mileage = GETPOST('options_mileage');
					foreach ($object->lines as $line) {
						if ($object->array_options['options_registrationcertificatefr'] == $line->array_options['options_registrationcertificatefr']) {
							$line->array_options['options_mileage'] = $mileage;
							$line->update($user);
						}
					}
				}
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
			require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
			$registrationcertificatefr = new RegistrationCertificateFr($this->db);
			$registrationcertificatefr->fetch($object->array_options['options_registrationcertificatefr']);
			$object->fetch_optionals();
			$object->note_public = $langs->transnoentities('RegistrationNumber') . ' : ' . $object->array_options['options_registration_number'] . '<br>';
			$object->note_public .= $langs->transnoentities('VehicleModel') . ' : ' . $object->array_options['options_vehicle_model'] . '<br>';
			$object->note_public .= $langs->transnoentities('Mileage') . ' : ' . $object->array_options['options_mileage'] . '<br>';
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
	public function quickCreationAction(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		if ($parameters['currentcontext'] == 'dolicar_quickcreation') {
			require_once __DIR__ . '/../class/registrationcertificatefr.class.php';
			if (isModEnabled('productbatch')) {
				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
			}
			if (isModEnabled('categorie')) {
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			}

			$object = new RegistrationCertificateFr($this->db);
			if (isModEnabled('product')) {
				$product = new Product($this->db);
			}
			if (isModEnabled('productbatch')) {
				$productLot = new Productlot($this->db);
			}
			if (isModEnabled('categorie')) {
				$category = new Categorie($this->db);
			}

			$createRegistrationCertificate = 1;
			require_once __DIR__ . '/../core/tpl/dolicar_registrationcertificatefr_immatriculation_api_fetch_action.tpl.php';

			if (dol_strlen($backtopage) > 0){
				$this->resprints = $backtopage;
			}

			return 1;
		}
	}
}
