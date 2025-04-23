<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \brief   DoliCar trigger
*/

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggers for DoliCar module
 */
class InterfaceDoliCarTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = 'demo';
        $this->description = 'DoliCar triggers.';
        $this->version     = '1.2.0';
        $this->picto       = 'dolicar@dolicar';
    }

    /**
     * Function called when a Dolibarr business event is done
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param  string       $action Event action code
     * @param  CommonObject $object Object
     * @param  User         $user   Object user
     * @param  Translate    $langs  Object langs
     * @param  Conf         $conf   Object conf
     * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK
     * @throws Exception
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        if (!isModEnabled('dolicar')) {
            return 0; // If module is not enabled, we do nothing
        }

        // Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $actionComm = new ActionComm($this->db);

        $triggerType = dol_ucfirst(dol_strtolower(explode('_', $action)[1]));

        $actionComm->code        = 'AC_' . $action;
        $actionComm->type_code   = 'AC_OTH_AUTO';
        $actionComm->fk_element  = $object->id;
        $actionComm->elementtype = $object->element . '@' . $object->module;
        $actionComm->label       = $langs->transnoentities('Object' . $triggerType . 'Trigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
        $actionComm->datep       = dol_now();
        $actionComm->userownerid = $user->id;
        $actionComm->percentage  = -1;

        if (getDolGlobalInt('DOLICAR_ADVANCED_TRIGGER') && !empty($object->fields)) {
            $actionComm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        $objects      = ['REGISTRATIONCERTIFICATEFR'];
        $triggerTypes = ['CREATE', 'MODIFY', 'DELETE', 'ARCHIVE'];

        $actions = array_merge(
            array_merge(...array_map(fn($s) => array_map(fn($p) => "{$p}_{$s}", $objects), $triggerTypes)),
        );

        if (in_array($action, $actions, true)) {
            $actionComm->create($user);
        }

        switch ($action) {
            case 'PROPAL_CREATE' :
            case 'ORDER_CREATE' :
            case 'BILL_CREATE' :
                if (isset($object->array_options['options_registrationcertificatefr']) && $object->array_options['options_registrationcertificatefr'] > 0) {
                    require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
                    $registrationCertificateFr = new RegistrationCertificateFr($this->db);
                    $registrationCertificateFr->fetch($object->array_options['options_registrationcertificatefr']);

                    $registrationCertificateFr->add_object_linked($object->element, $object->id);

                    $object->note_public  = $langs->transnoentities('RegistrationNumber') . ' : ' . (dol_strlen($object->array_options['options_registration_number']) > 0 ? $object->array_options['options_registration_number'] : $langs->transnoentities('NoData')) . '<br>';
                    $object->note_public .= $langs->transnoentities('VehicleModel') . ' : ' . (dol_strlen($object->array_options['options_vehicle_model']) > 0 ? $object->array_options['options_vehicle_model'] : $langs->transnoentities('NoData')) . '<br>';
                    $object->note_public .= $langs->transnoentities('VINNumber') . ' : ' .  (dol_strlen($object->array_options['options_VIN_number']) > 0 ? $object->array_options['options_VIN_number'] : $langs->transnoentities('NoData')) . '<br>';
                    $object->note_public .= $langs->transnoentities('FirstRegistrationDate') . ' : ' . ($object->array_options['options_first_registration_date'] > 0 ? dol_print_date($object->array_options['options_first_registration_date'], 'day') : $langs->transnoentities('NoData')) . '<br>';
                    $object->note_public .= $langs->transnoentities('Mileage') . ' : ' . ($object->array_options['options_mileage'] > 0 ? price($object->array_options['options_mileage'], 0,'',1, 0) : 0) . ' ' . $langs->trans('km') . '<br>';

                    $object->setValueFrom('note_public', $object->note_public);
                }
                break;
        }

        return 0;
    }
}
