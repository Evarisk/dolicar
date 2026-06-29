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
 * \file    core/modules/dolicar/dolicardocuments/vehiclelogbookdocument/pdf_vehiclestatesheet.modules.php
 * \ingroup dolicar
 * \brief   File of class to build the vehicle "before / after" state sheet (native TCPDF)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load DoliCar shared PDF renderer and data helpers
require_once __DIR__ . '/../dolicar_pdf_document.php';

/**
 * Class to build the vehicle "before / after" state sheet (one départ/retour block per trip)
 */
class pdf_vehiclestatesheet
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Model name
     */
    public $name;

    /**
     * @var string Model description (short text)
     */
    public $description;

    /**
     * @var string Document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module
     */
    public $phpmin = [7, 0];

    /**
     * @var string Dolibarr version of the loaded document
     */
    public $version = 'dolibarr';

    /**
     * @var string Module
     */
    public string $module = 'dolicar';

    /**
     * @var string Document type
     */
    public string $document_type = 'vehiclelogbookdocument';

    /**
     * @var int Let Dolibarr core update last_main_doc after generation
     */
    public $update_main_doc_field = 1;

    /**
     * @var array Result of the generation (fullpath is read by commonGenerateDocument)
     */
    public $result = [];

    /**
     * @var string Last error message
     */
    public $error = '';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        $this->db          = $db;
        $this->name        = 'vehiclestatesheet';
        $this->description = $langs->trans('VehicleStateSheetPDFDescription');
        $this->type        = 'pdf';
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
    public function write_file($objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
    {
        global $conf, $mysoc, $user;

        if (empty($moreParam) && is_object($objectDocument) && !empty($objectDocument->context['moreparams'])) {
            $moreParam = $objectDocument->context['moreparams'];
        }

        $object = $moreParam['object'] ?? null;
        if (!is_object($object)) {
            $this->error = 'Missing source object for vehicle state sheet generation';
            return -1;
        }

        $outputLangs->loadLangs(['dolicar@dolicar', 'main', 'companies']);

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

        // A single trip can be requested (per-trip download from the public PWA history list)
        $tripId = (int) ($moreParam['trip_id'] ?? 0);

        // Only completed trips (departure + return) carry a meaningful before/after comparison
        $trips = DolicarPdfData::fetchTrips($this->db, $object, true, $tripId);

        $companyName = !empty($mysoc->name) ? $mysoc->name : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

        /*
         * Build the PDF (native TCPDF, fixed columns)
         */
        $pdf              = new DolicarPdfTcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->logoPath    = DOL_DATA_ROOT . '/mycompany/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
        $pdf->docTitle    = $outputLangs->transnoentities('VehicleStateSheet');
        $pdf->docSubtitle = ($object->a_registration_number ?: $object->ref) . ' · ' . $outputLangs->transnoentities('EditedOn') . ' ' . dol_print_date(dol_now(), 'day');
        $pdf->footerLegal = $companyName . ' · ' . dol_print_date(dol_now(), 'day');

        $pdf->SetCreator('DoliCar');
        $pdf->SetAuthor($companyName);
        $pdf->SetTitle($pdf->docTitle . ' - ' . $object->ref);
        $pdf->SetMargins(15, 36, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(12);
        $pdf->SetAutoPageBreak(false, 18);
        $pdf->AddPage();

        $render = new DolicarPdfRender($pdf, 15, 15);

        // Vehicle information
        $render->sectionTitle($outputLangs->transnoentities('Vehicle'));
        $render->keyValueGrid([
            [$outputLangs->transnoentities('Ref'), $object->ref],
            [$outputLangs->transnoentities('LicensePlate'), $object->a_registration_number ?? ''],
            [$outputLangs->transnoentities('BrandAndModel'), trim(($object->d1_vehicle_brand ?? '') . ' ' . ($object->d3_vehicle_model ?? ''))],
            [$outputLangs->transnoentities('VinOrLotNumber'), !empty($productLot->batch) ? $productLot->batch : ''],
            [$outputLangs->transnoentities('Fuel'), $object->p3_fuel_type ?? ''],
            [$outputLangs->transnoentities('OwnerThirdParty'), !empty($thirdParty->name) ? $thirdParty->name : ''],
        ]);
        $render->spacer(4);

        if (empty($trips)) {
            $render->sectionTitle($outputLangs->transnoentities('VehicleStateBeforeAfter'));
            $render->photoGrid([], $outputLangs->transnoentities('NoCompletedTrip'));
        }

        foreach ($trips as $trip) {
            $render->sectionTitle($outputLangs->transnoentities('TripOf', dol_print_date($trip['datep'], 'day')));

            $kmStart = $trip['starting_mileage'] > 0 ? number_format($trip['starting_mileage'], 0, ',', ' ') . ' km' : '';
            $kmEnd   = $trip['arrival_mileage'] > 0 ? number_format($trip['arrival_mileage'], 0, ',', ' ') . ' km' : '';

            $render->beforeAfterGrid(
                $outputLangs->transnoentities('Departure'),
                $outputLangs->transnoentities('Return'),
                [
                    ['label' => $outputLangs->transnoentities('Date'),     'left' => dol_print_date($trip['datep'], 'dayhour'),                 'right' => dol_print_date($trip['datef'], 'dayhour')],
                    ['label' => $outputLangs->transnoentities('Driver'),   'left' => $trip['driver'],                                          'right' => $trip['driver']],
                    ['label' => $outputLangs->transnoentities('Mileage'),  'left' => $kmStart,                                                 'right' => $kmEnd],
                    ['label' => $outputLangs->transnoentities('Fuel'),     'left' => $fuelLabels[$trip['fuel_level']] ?? '',                   'right' => $fuelLabels[$trip['return_fuel_level']] ?? ''],
                    ['label' => $outputLangs->transnoentities('Comments'), 'left' => $trip['start_comment'],                                   'right' => $trip['end_comment']],
                    ['label' => $outputLangs->transnoentities('Signature'), 'image' => true, 'leftImg' => $trip['start_signature'], 'rightImg' => $trip['end_signature']],
                ]
            );
            $render->spacer(5);
        }

        /*
         * Save the file
         */
        $dirOutput = !empty($conf->dolicar->multidir_output[$conf->entity]) ? $conf->dolicar->multidir_output[$conf->entity] : $conf->dolicar->dir_output;
        if (empty($dirOutput)) {
            $this->error = 'Configuration manquante: conf->dolicar->dir_output';
            return -1;
        }
        $dir = $dirOutput . '/vehiclelogbookdocument/' . dol_sanitizeFileName($object->ref);
        if (!file_exists($dir) && dol_mkdir($dir) < 0) {
            $this->error = 'Impossible de créer le répertoire: ' . $dir;
            return -1;
        }

        $fileSuffix = $tripId > 0 ? '-fiche-etat-trajet-' . $tripId : '-fiche-etat';
        $fileName   = dol_sanitizeFileName(dol_print_date(dol_now(), '%Y%m%d') . '-' . $object->ref . $fileSuffix) . '.pdf';
        $file       = $dir . '/' . $fileName;

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = 'Erreur lors de la création du PDF : ' . $exception->getMessage();
            return -1;
        }
        if (!file_exists($file)) {
            $this->error = 'PDF non généré (fichier introuvable après Output) : ' . $file;
            return -1;
        }

        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        $this->result = ['fullpath' => $file];

        return 1;
    }
}
