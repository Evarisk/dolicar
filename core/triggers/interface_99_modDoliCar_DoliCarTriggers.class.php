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
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
                    $registrationCertificateFr = new RegistrationCertificateFr($this->db);
                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));

                    $registrationCertificateFr->add_object_linked($object->element, $object->id);
                }
                break;

            case 'PROPAL_MODIFY' :
            case 'ORDER_MODIFY' :
            case 'BILL_MODIFY' :
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
                    $registrationCertificateFr = new RegistrationCertificateFr($this->db);
                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));

                    $object->updateObjectLinked(null, '', $registrationCertificateFr->id, $object->table_element);
                }
                break;

            case 'PROPAL_DELETE' :
            case 'ORDER_DELETE' :
            case 'BILL_DELETE' :
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
                    $registrationCertificateFr = new RegistrationCertificateFr($this->db);
                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));

                    $object->deleteObjectLinked(null, '', $registrationCertificateFr->id, $object->table_element);
                }
                break;
        }

        return 0;
    }
}
