<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
        $this->family      = "demo";
        $this->description = 'DoliCar triggers.';
        $this->version     = '1.2.0';
        $this->picto       = 'dolicar@dolicar';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc(): string
    {
        return parent::getDesc();
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
        $now        = dol_now();
        $actionComm = new ActionComm($this->db);

        $actionComm->elementtype = $object->element . '@dolicar';
        $actionComm->type_code   = 'AC_OTH_AUTO';
        $actionComm->datep       = $now;
        $actionComm->fk_element  = $object->id;
        $actionComm->userownerid = $user->id;
        $actionComm->percentage  = -1;

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

                    $object->updateObjectLinked(null, '', $registrationCertificateFr->id, $registrationCertificateFr->module . '_' . $registrationCertificateFr->element);
                }
                break;

            case 'PROPAL_DELETE' :
            case 'ORDER_DELETE' :
            case 'BILL_DELETE' :
                if (GETPOSTISSET('options_registrationcertificatefr') && !empty(GETPOST('options_registrationcertificatefr'))) {
                    require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
                    $registrationCertificateFr = new RegistrationCertificateFr($this->db);
                    $registrationCertificateFr->fetch(GETPOST('options_registrationcertificatefr'));

                    $object->deleteObjectLinked(null, '', $registrationCertificateFr->id, $registrationCertificateFr->module . '_' . $registrationCertificateFr->element);
                }
                break;

            case 'REGISTRATIONCERTIFICATEFR_CREATE' :
                $actionComm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actionComm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actionComm->create($user);
                break;

            case 'REGISTRATIONCERTIFICATEFR_MODIFY' :
                $actionComm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
                $actionComm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actionComm->create($user);
                break;

            case 'REGISTRATIONCERTIFICATEFR_DELETE' :
                $actionComm->code  = 'AC_ ' . strtoupper($object->element) . '_DELETE';
                $actionComm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actionComm->create($user);
                break;

            case 'REGISTRATIONCERTIFICATEFR_ARCHIVE' :
                $actionComm->code  = 'AC_' . strtoupper($object->element) . '_ARCHIVE';
                $actionComm->label = $langs->transnoentities('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actionComm->create($user);
                break;

            default:
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}
