<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    registrationcertificatefr_vehiclehistory.php
 * \ingroup dolicar
 * \brief   Page to view and add vehicle history events for a carte grise
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Global variables definitions
global $conf, $db, $form, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

// Initialize technical objects
$object      = new RegistrationCertificateFr($db);
$extrafields = new ExtraFields($db);
$form        = new Form($db);

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead = $user->rights->dolicar->registrationcertificatefr->read;
$permissiontoadd  = $user->rights->dolicar->registrationcertificatefr->write;
saturne_check_access($permissionToRead);

// Ensure parent event category exists and children are initialized
$parentCategoryId = getDolGlobalInt('DOLICAR_VEHICLE_EVENT_CATEGORY_ID');
if ($parentCategoryId > 0) {
    $checkCat = new Categorie($db);
    if ($checkCat->fetch($parentCategoryId) <= 0) {
        $parentCategoryId = 0;
    }
}
if ($parentCategoryId <= 0) {
    $findParent = new Categorie($db);
    if ($findParent->fetch(0, 'DoliCar Véhicule Événement', Categorie::TYPE_ACTIONCOMM) > 0) {
        $parentCategoryId = $findParent->id;
    } else {
        $newParent            = new Categorie($db);
        $newParent->label     = 'DoliCar Véhicule Événement';
        $newParent->type      = Categorie::TYPE_ACTIONCOMM;
        $newParent->fk_parent = 0;
        $newParent->visible   = 1;
        $parentCategoryId     = $newParent->create($user);
    }

    if ($parentCategoryId > 0) {
        dolibarr_set_const($db, 'DOLICAR_VEHICLE_EVENT_CATEGORY_ID', $parentCategoryId, 'chaine', 0, '', $conf->entity);

        $defaultChildren = [
            ['label' => 'Contrôle technique', 'color' => '5BA86E'],
            ['label' => 'Révision',           'color' => 'E8A317'],
            ['label' => 'Accident',           'color' => 'E05353'],
            ['label' => 'Autre',              'color' => '888888'],
        ];
        foreach ($defaultChildren as $childDef) {
            $child            = new Categorie($db);
            $child->label     = $childDef['label'];
            $child->type      = Categorie::TYPE_ACTIONCOMM;
            $child->fk_parent = $parentCategoryId;
            $child->color     = $childDef['color'];
            $child->visible   = 1;
            if (!$child->already_exists()) {
                $child->create($user);
            }
        }
    }
}

// Icon mapping for default event types (label → Font Awesome class)
$iconMap = [
    'Contrôle technique' => 'fa-check-circle',
    'Révision'           => 'fa-wrench',
    'Accident'           => 'fa-exclamation-triangle',
    'Autre'              => 'fa-circle',
];

// Load child categories and build display structures for the TPL
$catById   = [];
$catLabels = [];
if ($parentCategoryId > 0) {
    $parentCat = new Categorie($db);
    if ($parentCat->fetch($parentCategoryId) > 0) {
        $filles = $parentCat->get_filles();
        if (is_array($filles)) {
            foreach ($filles as $cat) {
                $catIcon  = $iconMap[$cat->label] ?? 'fa-circle';
                $catColor = '#' . ltrim($cat->color, '#');

                $catById[$cat->id]   = $cat;
                $catLabels[$cat->id] = [
                    'label'     => $cat->label,
                    'data-html' => '<i class="fas ' . $catIcon . '" style="color:' . $catColor . '"></i> ' . dol_escape_htmltag($cat->label),
                ];
            }
        }
    }
}

// Load all vehicle history events and resolve their category in one pass
$eventsList = [];
$evtCatById = [];
if (!empty($object->fk_lot) && $object->fk_lot > 0 && !empty($catById)) {
    $actionCommHelper = new ActionComm($db);
    $catHelper        = new Categorie($db);
    $allEvents        = $actionCommHelper->getActions(0, (int) $object->fk_lot, 'productlot', '', 'a.datep', 'DESC');

    if (is_array($allEvents)) {
        foreach ($allEvents as $evt) {
            $evtCatIds = $catHelper->containing($evt->id, Categorie::TYPE_ACTIONCOMM, 'id');
            if (!is_array($evtCatIds)) {
                continue;
            }
            foreach ($evtCatIds as $evtCatId) {
                if (isset($catById[(int) $evtCatId])) {
                    $eventsList[]               = $evt;
                    $evtCatById[(int) $evt->id] = $catById[(int) $evtCatId];
                    break;
                }
            }
        }
    }
}

// Collect elements already linked to an existing event to prevent assigning the same one twice
$alreadyLinkedFactureIds = [];
$alreadyLinkedPropalIds  = [];
$alreadyLinkedControlIds = [];
foreach ($eventsList as $linkedEvt) {
    $linkedEvt->fetchObjectLinked(null, null, null, null, 'OR', 1, 'sourcetype', 0);
    foreach (($linkedEvt->linkedObjectsIds['facture'] ?? []) as $facId) {
        $alreadyLinkedFactureIds[(int) $facId] = (int) $facId;
    }
    foreach (($linkedEvt->linkedObjectsIds['propal'] ?? []) as $propalId) {
        $alreadyLinkedPropalIds[(int) $propalId] = (int) $propalId;
    }
    foreach (($linkedEvt->linkedObjectsIds['control'] ?? []) as $controlId) {
        $alreadyLinkedControlIds[(int) $controlId] = (int) $controlId;
    }
}

/*
 * Actions
 */

if ($action == 'add_vehicle_event' && !empty($permissiontoadd) && !empty($object->fk_lot) && $object->fk_lot > 0) {
    $error           = 0;
    $eventCategoryId = GETPOSTINT('event_category_id');
    $eventDate       = dol_mktime(12, 0, 0, GETPOSTINT('event_datemonth'), GETPOSTINT('event_dateday'), GETPOSTINT('event_dateyear'));
    $eventMileage    = GETPOSTINT('event_mileage');
    $eventNote       = GETPOST('event_note', 'restricthtml');
    $fkFacture       = GETPOSTINT('event_fk_facture');
    $fkPropal        = GETPOSTINT('event_fk_propal');
    $fkControl       = GETPOSTINT('event_fk_control');

    $selectedCat = new Categorie($db);
    if ($eventCategoryId <= 0 || $selectedCat->fetch($eventCategoryId) <= 0 || (int) $selectedCat->fk_parent !== $parentCategoryId) {
        setEventMessages($langs->transnoentities('VehicleEventType') . ' ' . $langs->transnoentities('NotValid'), null, 'errors');
        $error++;
    }

    // Prevent assigning an element already linked to another event of this vehicle
    if (!$error && $fkFacture > 0 && isset($alreadyLinkedFactureIds[$fkFacture])) {
        setEventMessages($langs->transnoentities('VehicleEventElementAlreadyLinked', $langs->transnoentities('Invoices')), null, 'errors');
        $error++;
    }
    if (!$error && $fkPropal > 0 && isset($alreadyLinkedPropalIds[$fkPropal])) {
        setEventMessages($langs->transnoentities('VehicleEventElementAlreadyLinked', $langs->transnoentities('Proposals')), null, 'errors');
        $error++;
    }
    if (!$error && $fkControl > 0 && isset($alreadyLinkedControlIds[$fkControl])) {
        setEventMessages($langs->transnoentities('VehicleEventElementAlreadyLinked', $langs->transnoentities('Controls')), null, 'errors');
        $error++;
    }

    if (!$error) {
        $actionComm               = new ActionComm($db);
        $actionComm->code         = 'AC_OTH';
        $actionComm->type_code    = 'AC_OTH';
        $actionComm->fk_element   = (int) $object->fk_lot;
        $actionComm->elementtype  = 'productlot';
        $actionComm->label        = $selectedCat->label;
        $actionComm->datep        = $eventDate ?: dol_now();
        $actionComm->userownerid  = $user->id;
        $actionComm->percentage   = -1;
        $actionComm->note_private = $eventNote;

        $result = $actionComm->create($user);
        if ($result > 0 && $eventMileage > 0) {
            $actionComm->array_options['options_starting_mileage'] = $eventMileage;
            $actionComm->insertExtraFields();
        }

        if ($result > 0) {
            $actionComm->setCategories([$eventCategoryId]);

            if ($fkFacture > 0) {
                $actionComm->add_object_linked('facture', $fkFacture);
            }
            if ($fkPropal > 0) {
                $actionComm->add_object_linked('propal', $fkPropal);
            }
            if ($fkControl > 0) {
                $actionComm->add_object_linked('control', $fkControl);
            }

            setEventMessages($langs->transnoentities('VehicleEventAdded'), null, 'mesgs');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
            exit;
        } else {
            setEventMessages($actionComm->error, $actionComm->errors, 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('VehicleHistory') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'vehiclehistory', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';
    require_once __DIR__ . '/../../core/tpl/registrationcertificatefr_vehicle_history.tpl.php';
    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
