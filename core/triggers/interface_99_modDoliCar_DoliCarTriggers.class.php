<?php
/* Copyright (C) 2022 SuperAdmin <test@test.fr>
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
 * \file    core/triggers/interface_99_modDoliCar_DoliCarTriggers.class.php
 * \ingroup dolicar
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modDoliCar_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for DoliCar module
 */
class InterfaceDoliCarTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "DoliCar triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.1.1';
		$this->picto = 'dolicar@dolicar';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('dolicar')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Data and type of action are stored into $object and $action
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
		$now = dol_now();
		$actioncomm = new ActionComm($this->db);

		$actioncomm->elementtype = $object->element . '@dolicar';
		$actioncomm->type_code   = 'AC_OTH_AUTO';
		$actioncomm->datep       = $now;
		$actioncomm->fk_element  = $object->id;
		$actioncomm->userownerid = $user->id;
		$actioncomm->percentage  = -1;

		switch ($action) {
			case 'DOLICAR_PRODUCTLOT_MILEAGE_MODIFY':
				$actioncomm->elementtype = 'productlot';
				$actioncomm->code        = 'AC_DOLICAR_PRODUCTLOT_MILEAGE_MODIFY';
				$actioncomm->label       = $langs->trans('ProductLotMileageModifyTrigger');
				$actioncomm->note_private = $object->array_options['options_mileage'];
				$actioncomm->create($user);
				break;
			case 'REGISTRATIONCERTIFICATEFR_CREATE' :
				$actioncomm->code = 'AC_' . strtoupper($object->element) . '_CREATE';
				$actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'REGISTRATIONCERTIFICATEFR_MODIFY' :
				$actioncomm->code = 'AC_' . strtoupper($object->element) . '_MODIFY';
				$actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;

			case 'REGISTRATIONCERTIFICATEFR_DELETE' :
				$actioncomm->code = 'AC_ ' . strtoupper($object->element) . '_DELETE';
				$actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)));
				$actioncomm->create($user);
				break;
			default:
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}
		return 0;

		// Or you can execute some code here
		switch ($action) {


			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
