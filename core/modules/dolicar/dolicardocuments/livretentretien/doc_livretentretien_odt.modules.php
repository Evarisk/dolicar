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
 * \file    core/modules/dolicar/dolicardocuments/livretentretien/doc_livretentretien_odt.modules.php
 * \ingroup dolicar
 * \brief   File of class to build ODT livret d'entretien document
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

// Load DoliCar libraries
require_once __DIR__ . '/mod_livretentretien_standard.php';

/**
 * Class to build livret d'entretien ODT documents
 */
class doc_livretentretien_odt extends SaturneDocumentModel
{
    /**
     * @var string Module
     */
    public string $module = 'dolicar';

    /**
     * @var string Document type
     */
    public string $document_type = 'livretentretien';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Return description of a module
     *
     * @param  Translate $langs Lang object to use for output
     * @return string           Description
     */
    public function info(Translate $langs): string
    {
        return parent::info($langs);
    }

    /**
     * Fill all odt tags for segments lines
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @return int                    1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        // Data is pre-loaded in write_file to avoid duplicate DB calls
        $eventsList = $moreParam['eventsList'] ?? [];
        $catById    = $moreParam['catById']    ?? [];
        $evtCatById = $moreParam['evtCatById'] ?? [];
        $evtCostById = $moreParam['evtCostById'] ?? [];

        try {
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('interventions');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                $foundTagForLines = 0;
                $listLines        = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (!empty($eventsList)) {
                    foreach ($eventsList as $evt) {
                        $evt->fetch_optionals();
                        $km      = (int) ($evt->array_options['options_starting_mileage'] ?? 0);
                        $evtCat  = $evtCatById[(int) $evt->id] ?? null;
                        $cost    = $evtCostById[(int) $evt->id] ?? 0;

                        $typeLabel = $evtCat ? $evtCat->label : $evt->label;

                        $tmpArray = [];
                        $tmpArray['interventions_date']       = dol_print_date($evt->datep, 'day');
                        $tmpArray['interventions_mileage']    = $km > 0 ? $km . ' km' : '';
                        $tmpArray['interventions_type_label'] = $typeLabel;
                        $tmpArray['interventions_comment']    = strip_tags($evt->note_private ?? '');
                        $tmpArray['interventions_parts']      = '';
                        $tmpArray['interventions_cost']       = $cost > 0 ? price($cost) : '';
                        $tmpArray['interventions_doc_ref']    = '';

                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                    }
                } else {
                    $tmpArray = [];
                    $tmpArray['interventions_date']       = '';
                    $tmpArray['interventions_mileage']    = '';
                    $tmpArray['interventions_type_label'] = $outputLangs->trans('NoVehicleHistory');
                    $tmpArray['interventions_comment']    = '';
                    $tmpArray['interventions_parts']      = '';
                    $tmpArray['interventions_cost']       = '';
                    $tmpArray['interventions_doc_ref']    = '';

                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
                $odfHandler->mergeSegment($listLines);
            }

            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('schedules');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                $foundTagForLines = 0;
                $listLines        = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                $tmpArray = [];
                $tmpArray['schedules_label']       = '';
                $tmpArray['schedules_interval']    = '';
                $tmpArray['schedules_last_km']     = '';
                $tmpArray['schedules_status_text'] = '';

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
            }
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }

        return 0;
    }

    /**
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
    {
        global $conf, $mysoc, $user;

        if (empty($moreParam) && !empty($objectDocument->context['moreparams'])) {
            $moreParam = $objectDocument->context['moreparams'];
        }

        $object = $moreParam['object'];

        $thirdParty = new Societe($this->db);
        if (!empty($object->fk_soc)) {
            $thirdParty->fetch($object->fk_soc);
        }

        // Load child event categories from the parent category
        $parentCategoryId = getDolGlobalInt('DOLICAR_VEHICLE_EVENT_CATEGORY_ID');
        $catById          = [];

        if ($parentCategoryId > 0) {
            $parentCat = new Categorie($this->db);
            if ($parentCat->fetch($parentCategoryId) > 0) {
                $filles = $parentCat->get_filles();
                if (is_array($filles)) {
                    foreach ($filles as $cat) {
                        $catById[$cat->id] = $cat;
                    }
                }
            }
        }

        // Load all events for this lot and resolve their category in one pass
        $eventsList  = [];
        $evtCatById  = [];
        $evtCostById = [];

        if (!empty($object->fk_lot) && !empty($catById)) {
            $actionCommHelper = new ActionComm($this->db);
            $catHelper        = new Categorie($this->db);
            $allEvents        = $actionCommHelper->getActions(0, (int) $object->fk_lot, 'productlot', '', 'a.datep', 'ASC');

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

        // Compute per-event invoice total and global totals
        $totalInterventions   = count($eventsList);
        $lastInterventionDate = '';
        $currentMileage       = 0;
        $totalCost            = 0.0;

        foreach ($eventsList as $evt) {
            $lastInterventionDate = $evt->datep;

            $evt->fetch_optionals();
            $km = (int) ($evt->array_options['options_starting_mileage'] ?? 0);
            if ($km > $currentMileage) {
                $currentMileage = $km;
            }

            $evt->fetchObjectLinked(null, null, null, null, 'OR', 1, 'sourcetype', 0);
            $evtCost = 0.0;
            foreach (($evt->linkedObjectsIds['facture'] ?? []) as $facId) {
                $tmpFac = new Facture($this->db);
                if ($tmpFac->fetch($facId) > 0) {
                    $evtCost += (float) $tmpFac->total_ttc;
                }
            }
            $evtCostById[(int) $evt->id] = $evtCost;
            $totalCost                   += $evtCost;
        }

        $firstRegDate = !empty($object->b_first_registration_date)
            ? dol_print_date($object->b_first_registration_date, 'day')
            : '';

        $tmpArray = [];
        $tmpArray['object_ref']                     = $object->ref;
        $tmpArray['object_license_plate']           = $object->a_registration_number ?? '';
        $tmpArray['object_brand']                   = $object->d1_vehicle_brand ?? '';
        $tmpArray['object_model']                   = $object->d3_vehicle_model ?? '';
        $tmpArray['object_energy']                  = $object->p3_fuel_type ?? '';
        $tmpArray['object_date_first_registration'] = $firstRegDate;
        $tmpArray['object_socname']                 = !empty($thirdParty->name) ? $thirdParty->name : '';
        $tmpArray['object_current_mileage']         = $currentMileage > 0 ? $currentMileage . ' km' : '';
        $tmpArray['object_total_interventions']     = (string) $totalInterventions;
        $tmpArray['object_total_cost']              = $totalCost > 0 ? price($totalCost) : '';
        $tmpArray['object_last_intervention_date']  = !empty($lastInterventionDate)
            ? dol_print_date($lastInterventionDate, 'day')
            : '';
        $tmpArray['object_total_pending_schedules'] = '';
        $tmpArray['currentdate']                    = dol_print_date(dol_now(), 'day');
        $tmpArray['myuser_lastname']                = strtoupper($user->lastname);
        $tmpArray['myuser_firstname']               = ucfirst($user->firstname);
        $tmpArray['mycompany_name']                 = !empty($mysoc->name) ? $mysoc->name : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

        $moreParam['tmparray']    = $tmpArray;
        $moreParam['eventsList']  = $eventsList;
        $moreParam['catById']     = $catById;
        $moreParam['evtCatById']  = $evtCatById;
        $moreParam['evtCostById'] = $evtCostById;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
