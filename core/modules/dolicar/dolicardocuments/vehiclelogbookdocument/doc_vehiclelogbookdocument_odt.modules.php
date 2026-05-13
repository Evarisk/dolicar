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
 * \file    core/modules/dolicar/dolicardocuments/vehiclelogbookdocument/doc_vehiclelogbookdocument_odt.modules.php
 * \ingroup dolicar
 * \brief   File of class to build ODT vehicle logbook document
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

// Load DoliCar libraries
require_once __DIR__ . '/mod_vehiclelogbookdocument_standard.php';

/**
 * Class to build vehicle logbook ODT documents
 */
class doc_vehiclelogbookdocument_odt extends SaturneDocumentModel
{
    /**
     * @var string Module
     */
    public string $module = 'dolicar';

    /**
     * @var string Document type
     */
    public string $document_type = 'vehiclelogbookdocument';

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
        $tripsList  = $moreParam['tripsList']  ?? [];
        $fuelLabels = $moreParam['fuelLabels'] ?? [];

        try {
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('vehiclelogbooks');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                $foundTagForLines = 0;
                $listLines        = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (!empty($tripsList)) {
                    foreach ($tripsList as $trip) {
                        $tripJson        = json_decode($trip->array_options['options_json'] ?? '{}', true);
                        $driver          = $tripJson['driver']            ?? '';
                        $startComment    = $tripJson['start_comment']     ?? '';
                        $endComment      = $tripJson['end_comment']       ?? '';
                        $fuelLevel       = $tripJson['fuel_level']        ?? '';
                        $returnFuelLevel = $tripJson['return_fuel_level'] ?? '';
                        $kmStart         = (int) ($trip->array_options['options_starting_mileage'] ?? 0);
                        $kmEnd           = (int) ($trip->array_options['options_arrival_mileage']  ?? 0);
                        $isOpen          = empty($trip->datef);

                        $tmpArray = [];
                        $tmpArray['driver']           = $driver;
                        $tmpArray['start_date']       = dol_print_date($trip->datep, 'dayhour');
                        $tmpArray['starting_mileage'] = $kmStart > 0 ? number_format($kmStart, 0, ',', ' ') . ' km' : '';
                        $tmpArray['start_fuel']       = isset($fuelLabels[$fuelLevel]) ? $fuelLabels[$fuelLevel] : '';
                        $tmpArray['start_comment']    = $startComment;
                        $tmpArray['end_date']         = !$isOpen ? dol_print_date($trip->datef, 'dayhour') : '';
                        $tmpArray['arrival_mileage']  = $kmEnd > 0 ? number_format($kmEnd, 0, ',', ' ') . ' km' : '';
                        $tmpArray['end_fuel']         = isset($fuelLabels[$returnFuelLevel]) ? $fuelLabels[$returnFuelLevel] : '';
                        $tmpArray['end_comment']      = $endComment;
                        $tmpArray['start_signature']  = '';
                        $tmpArray['end_signature']    = '';

                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                    }
                } else {
                    $tmpArray                      = [];
                    $tmpArray['driver']            = '';
                    $tmpArray['start_date']        = '';
                    $tmpArray['starting_mileage']  = '';
                    $tmpArray['start_fuel']        = '';
                    $tmpArray['start_comment']     = $outputLangs->transnoentities('NoVehicleHistory');
                    $tmpArray['end_date']          = '';
                    $tmpArray['arrival_mileage']   = '';
                    $tmpArray['end_fuel']          = '';
                    $tmpArray['end_comment']       = '';
                    $tmpArray['start_signature']   = '';
                    $tmpArray['end_signature']     = '';

                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
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

        $productLot = new ProductLot($this->db);
        if (!empty($object->fk_lot)) {
            $productLot->fetch((int) $object->fk_lot);
        }

        $fuelLabels = [
            'reserve'       => $outputLangs->transnoentities('FuelReserve'),
            'quarter'       => '1/4',
            'half'          => '1/2',
            'threequarters' => '3/4',
            'full'          => $outputLangs->transnoentities('FuelFull'),
        ];

        $tripsList        = [];
        $totalTripsDone   = 0;
        $currentMileage   = 0;

        if (!empty($object->fk_lot)) {
            $actionCommHelper = new ActionComm($this->db);
            $allTrips         = $actionCommHelper->getActions(
                0,
                (int) $object->fk_lot,
                'productlot',
                ' AND fk_element = ' . (int) $object->fk_lot . ' AND code = "AC_PRODUCTLOT_ADD_PUBLIC_VEHICLE_LOG_BOOK"',
                'datep',
                'DESC'
            );

            if (is_array($allTrips)) {
                foreach ($allTrips as $trip) {
                    $trip->fetch_optionals();
                    $tripsList[] = $trip;

                    if (!empty($trip->datef)) {
                        $totalTripsDone++;
                        $kmEnd = (int) ($trip->array_options['options_arrival_mileage'] ?? 0);
                        if ($kmEnd > $currentMileage) {
                            $currentMileage = $kmEnd;
                        }
                    } else {
                        $kmStart = (int) ($trip->array_options['options_starting_mileage'] ?? 0);
                        if ($kmStart > $currentMileage) {
                            $currentMileage = $kmStart;
                        }
                    }
                }
            }
        }

        $firstRegDate = !empty($object->b_first_registration_date)
            ? dol_print_date($object->b_first_registration_date, 'day')
            : '';

        $tmpArray = [];
        $tmpArray['registration_number']          = $object->a_registration_number ?? '';
        $tmpArray['object_ref']                   = $object->ref ?? '';
        $tmpArray['object_license_plate']         = $object->a_registration_number ?? '';
        $tmpArray['object_brand']                 = $object->d1_vehicle_brand ?? '';
        $tmpArray['object_model']                 = $object->d3_vehicle_model ?? '';
        $tmpArray['object_fuel_type']             = $object->p3_fuel_type ?? '';
        $tmpArray['object_first_registration_date'] = $firstRegDate;
        $tmpArray['object_vin']                   = !empty($productLot->batch) ? $productLot->batch : '';
        $tmpArray['object_socname']               = !empty($thirdParty->name) ? $thirdParty->name : '';
        $tmpArray['object_total_trips']           = (string) count($tripsList);
        $tmpArray['object_total_trips_done']      = (string) $totalTripsDone;
        $tmpArray['object_current_mileage']       = $currentMileage > 0 ? number_format($currentMileage, 0, ',', ' ') . ' km' : '';
        $tmpArray['currentdate']                  = dol_print_date(dol_now(), 'day');
        $tmpArray['myuser_lastname']              = strtoupper($user->lastname);
        $tmpArray['myuser_firstname']             = ucfirst($user->firstname);
        $tmpArray['mycompany_name']               = !empty($mysoc->name) ? $mysoc->name : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

        $moreParam['tmparray']    = $tmpArray;
        $moreParam['tripsList']   = $tripsList;
        $moreParam['fuelLabels']  = $fuelLabels;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
