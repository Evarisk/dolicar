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

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
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
		global $conf, $db, $hookmanager, $langs, $user;

		$outputlangs = $langs;
		$outputlangs = $parameters['outputlangs'];
		$outputlangs->load('deliveryaddress@deliveryaddress');
		$txt = '';
		$wysiwyg = !empty($conf->fckeditor->enabled);

		$ret = 0;
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		if (
		(in_array('ordercard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_ORDERCARD))
		|| (in_array('propalcard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_PROPALCARD))
		|| (in_array('invoicecard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_INVOICECARD))
		|| (in_array('ordersuppliercard', explode(':', $parameters['context'])) && empty($conf->global->DOLICAR_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD))
		)
		{
			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');
			$TContacts = array();
			if (method_exists($object, 'liste_contact')) {
				$TContacts = $object->liste_contact();
			}
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$txt.= $this->addConctactToString($object, $c, $outputlangs, $wysiwyg);
					break;
				}
			}

			if (!empty($conf->global->DOLICAR_SHOW_INFO_REPONSABLE_RECEPTION))
			{
				$TContacts = array();
				if(method_exists($object, 'liste_contact')) $TContacts = $object->liste_contact(-1, 'internal');
				foreach($TContacts as $c)
				{
					// Responsable réception commande fournisseur
					if($c['code'] == 'SHIPPING')
					{
						$u = new User($db);
						$u->fetch($c['id']);

						if (empty($object->note_public)) $txt .= "\n";

						$title = $outputlangs->trans("ReceiptContact")." :\n";
						$name = dolGetFirstLastname($u->firstname, $u->lastname)."\n";
						if($wysiwyg) $name = '<strong>'.$name.'</strong>';

						$phone = $outputlangs->transnoentities("Phone").': ';
						if (!empty($u->office_phone)) $phone.= $u->office_phone;
						if (!empty($u->office_phone) && !empty($u->user_mobile)) $phone.= ' / '.$u->user_mobile;
						else if (!empty($u->user_mobile)) $phone .= $u->user_mobile;

						if (!empty($conf->global->DOLICAR_SEPARATOR_BETWEEN_NOTES)){
							switch ($conf->global->DOLICAR_SEPARATOR_BETWEEN_NOTES) {
								case 'returnChar1':
									$sep="\r\n";
									break;
								case 'returnChar2':
									$sep="\r\n\r\n";
									break;
								case 'dash':
									$sep="\r\n-----------\r\n";
									break;
							}
						} else {
							$sep="\r\n";
						}
						$end = !empty($object->note_public) ? $sep : "";

						$txt.= $title . $name . $phone . $end;
						break;
					}
				}
			}
		}

//		if (
//			!empty($parameters['DELIVERYADDRESS_DISPLAY_BILLED']) // IN case of custom PDF
//			||  (in_array('expeditioncard',explode(':',$parameters['context'])) && !empty($conf->global->DELIVERYADDRESS_DISPLAY_BILLED_ON_EXPEDITIONCARD))
//			|| 	(in_array('deliverycard',explode(':',$parameters['context'])) && !empty($conf->global->DELIVERYADDRESS_DISPLAY_BILLED_ON_DELIVERYCARD))
//		) {
//
//			dol_include_once('/contact/class/contact.class.php');
//			dol_include_once('/core/lib/pdf.lib.php');
//
//
//			$TContacts = array();
//
//			if (empty($object->commande)){
//				$object->commande = new Commande($db);
//
//				if ($object->element == "delivery"){
//					// We get the shipment that is the origin of delivery receipt
//					$expedition = new Expedition($db);
//					$result = $expedition->fetch($object->origin_id);
//					$TContacts = $expedition->liste_contact();
//
//					if ($expedition->origin == 'commande')
//					{
//						$object->commande->fetch($expedition->origin_id);
//					}
//				}
//				else if ($object->element == "shipping" && $object->origin == 'commande') {
//					$object->commande->fetch($object->origin_id);
//				}
//			}
//
//			if (!empty($object->commande) && method_exists($object->commande, 'liste_contact')) $TContacts = $object->commande->liste_contact();
//
//			foreach ($TContacts as $c) {
//				if ($c['code'] == 'BILLING') {
//					$txt.= $this->addConctactToString($object, $c, $outputlangs, $wysiwyg);
//					break;
//				}
//			}
//		}

		if(!empty($txt)){
			// Gestion des sauts de lignes si la note était en HTML de base
			if (!isset($object->note_public_original)) {
				$object->note_public_original = $object->note_public;
			}
			if($wysiwyg) $object->note_public = dol_nl2br($txt).$object->note_public;
			else $object->note_public = $txt.$object->note_public;
		}

		return 0;


		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		// clean up the object if it was altered by beforePDFCreation
		$object = $parameters['object'];
		if (isset($object->note_public_original)) {
			$object->note_public = $object->note_public_original;
		}
		return 0;

		return $ret;
	}


	/**
	 * @param commonObject $object
	 * @param array $c a contact item from commonobject->liste_contact()
	 * @param Translate $outputlangs
	 * @param bool $wysiwyg
	 * @return string
	 */
	function addConctactToString($object, $c, $outputlangs, $wysiwyg = false){

		global $db, $conf, $mysoc;

		$contact = new Contact($db);
		$contact->fetch($c['id']);
		$soc = new Societe($db);
		$soc->fetch($c['socid']);

		if($c['code'] == 'SHIPPING') {
			$title = $outputlangs->trans("DeliveryAddress") . " :\n";
		}

		if ($c['code'] == 'BILLING') {
			$title = $outputlangs->trans("BillingAddress") . " :\n";
		}

		$socname = !empty($contact->socname) ? $contact->socname . "\n" : "";
		if ($wysiwyg) $socname = '<strong>' . $socname . '</strong>';
		$maconfTVA = $conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS;
		$maconfTargetDetails = $conf->global->MAIN_PDF_ADDALSOTARGETDETAILS;
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = true;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = false;
		$address = pdf_build_address($outputlangs, $mysoc, $soc, $contact, 1, 'target');
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = $maconfTVA;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = $maconfTargetDetails;

		$phone = '';
		if (!empty($conf->global->DELIVERYADDRESS_SHOW_PHONE)) {
			if (!empty($contact->phone_pro) || !empty($contact->phone_mobile)) $phone .= ($address ? "\n" : '') . $outputlangs->transnoentities("Phone") . ": ";
			if (!empty($contact->phone_pro)) $phone .= $outputlangs->convToOutputCharset($contact->phone_pro);
			if (!empty($contact->phone_pro) && !empty($contact->phone_mobile)) $phone .= " / ";
			if (!empty($contact->phone_mobile)) $phone .= $outputlangs->convToOutputCharset($contact->phone_mobile);
		}
		if (!empty($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES)) {
			switch ($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES) {
				case 'returnChar1':
					$sep = "\r\n";
					break;
				case 'returnChar2':
					$sep = "\r\n\r\n";
					break;
				case 'dash':
					$sep = "\r\n-----------\r\n";
					break;
			}
		} else {
			$sep = "\r\n";
		}

		$end = !empty($object->note_public) ? $sep : "";

		return  $title . $socname . $address . $phone . $end;
	}

	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("dolicar@dolicar");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'dolicar') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("DoliCar");
			$this->results['picto'] = 'dolicar@dolicar';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
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
