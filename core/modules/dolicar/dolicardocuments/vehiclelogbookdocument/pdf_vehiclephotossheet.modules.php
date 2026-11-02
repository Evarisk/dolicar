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
 * \file    core/modules/dolicar/dolicardocuments/vehiclelogbookdocument/pdf_vehiclephotossheet.modules.php
 * \ingroup dolicar
 * \brief   File of class to build the vehicle departure/return photos sheet (native TCPDF)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DoliCar shared PDF renderer and data helpers
require_once __DIR__ . '/../dolicar_pdf_document.php';

/**
 * Class to build the vehicle photos sheet (departure / return photos, one section per trip)
 */
class pdf_vehiclephotossheet
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
        $this->name        = 'vehiclephotossheet';
        $this->description = $langs->trans('VehiclePhotosSheetPDFDescription');
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
        global $conf, $mysoc;

        if (empty($moreParam) && is_object($objectDocument) && !empty($objectDocument->context['moreparams'])) {
            $moreParam = $objectDocument->context['moreparams'];
        }

        $object = $moreParam['object'] ?? null;
        if (!is_object($object)) {
            $this->error = 'Missing source object for vehicle photos sheet generation';
            return -1;
        }

        $outputLangs->loadLangs(['dolicar@dolicar', 'main', 'companies']);

        $productLot = new ProductLot($this->db);
        if (!empty($object->fk_lot)) {
            $productLot->fetch((int) $object->fk_lot);
        }

        // Only completed trips carry both departure and return photos
        $trips = DolicarPdfData::fetchTrips($this->db, $object, true);

        $companyName = !empty($mysoc->name) ? $mysoc->name : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

        /*
         * Build the PDF (native TCPDF, fixed columns)
         */
        $pdf              = new DolicarPdfTcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->logoPath    = DOL_DATA_ROOT . '/mycompany/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
        $pdf->docTitle    = $outputLangs->transnoentities('VehiclePhotosSheet');
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

        if (empty($trips)) {
            $render->sectionTitle($outputLangs->transnoentities('VehiclePhotos'));
            $render->photoGrid([], $outputLangs->transnoentities('NoCompletedTrip'));
        }

        $first = true;
        foreach ($trips as $trip) {
            // Each trip starts on a fresh page so its départ/retour photos stay grouped together
            if (!$first) {
                $pdf->AddPage();
            }
            $first = false;

            $render->sectionTitle($outputLangs->transnoentities('TripOf', dol_print_date($trip['datep'], 'day')));
            $render->keyValueGrid([
                [$outputLangs->transnoentities('LicensePlate'), $object->a_registration_number ?? ''],
                [$outputLangs->transnoentities('Driver'), $trip['driver']],
                [$outputLangs->transnoentities('DepartureDate'), dol_print_date($trip['datep'], 'dayhour')],
                [$outputLangs->transnoentities('ReturnDate'), dol_print_date($trip['datef'], 'dayhour')],
            ]);
            $render->spacer(3);

            if (!empty($trip['legacy_photos'])) {
                // Trip recorded before the départ/retour split: a single combined section
                $render->sectionTitle($outputLangs->transnoentities('TripPhotos'));
                $render->photoGrid($trip['legacy_photos'], $outputLangs->transnoentities('NoPhoto'));
                $render->spacer(4);
                continue;
            }

            $render->sectionTitle($outputLangs->transnoentities('PhotosDeparture'));
            $render->photoGrid($trip['depart_photos'], $outputLangs->transnoentities('NoPhoto'));
            $render->spacer(4);

            $render->sectionTitle($outputLangs->transnoentities('PhotosReturn'));
            $render->photoGrid($trip['return_photos'], $outputLangs->transnoentities('NoPhoto'));
            $render->spacer(4);
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

        $fileName = dol_sanitizeFileName(dol_print_date(dol_now(), '%Y%m%d') . '-' . $object->ref . '-fiche-photos') . '.pdf';
        $file     = $dir . '/' . $fileName;

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
