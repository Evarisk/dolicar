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
 * \file    core/modules/dolicar/dolicardocuments/livretentretien/pdf_livretentretien.modules.php
 * \ingroup dolicar
 * \brief   File of class to build PDF livret d'entretien document (native TCPDF)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load DoliCar shared PDF renderer
require_once __DIR__ . '/../dolicar_pdf_document.php';

/**
 * Class to build livret d'entretien PDF documents
 */
class pdf_livretentretien
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
    public string $document_type = 'livretentretien';

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
        $this->name        = 'livretentretien';
        $this->description = $langs->trans('LivretEntretienPDFDescription');
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
            $this->error = 'Missing source object for livret d\'entretien generation';
            return -1;
        }

        $outputLangs->loadLangs(['dolicar@dolicar', 'main', 'companies']);

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
            $totalCost                  += $evtCost;
        }

        $firstRegDate = !empty($object->b_first_registration_date)
            ? dol_print_date($object->b_first_registration_date, 'day')
            : '';

        $companyName = !empty($mysoc->name) ? $mysoc->name : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

        /*
         * Build the PDF (native TCPDF, fixed columns)
         */
        $pdf              = new DolicarPdfTcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->logoPath    = DOL_DATA_ROOT . '/mycompany/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
        $pdf->docTitle    = $outputLangs->transnoentities('LivretEntretien');
        $pdf->docSubtitle = $object->ref . ' · ' . $outputLangs->transnoentities('EditedOn') . ' ' . dol_print_date(dol_now(), 'day');
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
            [$outputLangs->transnoentities('RegistrationCardReference'), $object->ref],
            [$outputLangs->transnoentities('LicensePlate'), $object->a_registration_number ?? ''],
            [$outputLangs->transnoentities('BrandAndModel'), trim(($object->d1_vehicle_brand ?? '') . ' ' . ($object->d3_vehicle_model ?? ''))],
            [$outputLangs->transnoentities('Energy'), $object->p3_fuel_type ?? ''],
            [$outputLangs->transnoentities('FirstRegistrationDate'), $firstRegDate],
            [$outputLangs->transnoentities('OwnerThirdParty'), !empty($thirdParty->name) ? $thirdParty->name : ''],
            [$outputLangs->transnoentities('CurrentMileage'), $currentMileage > 0 ? number_format($currentMileage, 0, ',', ' ') . ' km' : ''],
        ]);
        $render->spacer(4);

        // Maintenance synthesis
        $render->sectionTitle($outputLangs->transnoentities('MaintenanceSynthesis'));
        $render->statBoxes([
            [$outputLangs->transnoentities('Interventions'), (string) $totalInterventions],
            [$outputLangs->transnoentities('TotalCostInclTax'), $totalCost > 0 ? price($totalCost, 0, $outputLangs, 1, -1, -1, $conf->currency) : '—'],
            [$outputLangs->transnoentities('LastIntervention'), !empty($lastInterventionDate) ? dol_print_date($lastInterventionDate, 'day') : '—'],
        ]);
        $render->spacer(4);

        // Interventions journal
        $render->sectionTitle($outputLangs->transnoentities('InterventionsJournal'));
        $cols = [
            ['w' => 26, 'label' => $outputLangs->transnoentities('Date'),             'align' => 'L'],
            ['w' => 24, 'label' => $outputLangs->transnoentities('Mileage'),          'align' => 'R'],
            ['w' => 36, 'label' => $outputLangs->transnoentities('InterventionType'), 'align' => 'L'],
            ['w' => 64, 'label' => $outputLangs->transnoentities('Comment'),          'align' => 'L'],
            ['w' => 30, 'label' => $outputLangs->transnoentities('CostInclTax'),      'align' => 'R'],
        ];
        $tableRows = [];
        foreach ($eventsList as $evt) {
            $evt->fetch_optionals();
            $km        = (int) ($evt->array_options['options_starting_mileage'] ?? 0);
            $evtCat    = $evtCatById[(int) $evt->id] ?? null;
            $cost      = $evtCostById[(int) $evt->id] ?? 0;
            $typeLabel = $evtCat ? $evtCat->label : $evt->label;

            $tableRows[] = [
                dol_print_date($evt->datep, 'day'),
                $km > 0 ? number_format($km, 0, ',', ' ') . ' km' : '',
                $typeLabel,
                strip_tags($evt->note_private ?? ''),
                $cost > 0 ? price($cost, 0, $outputLangs, 1, -1, -1, $conf->currency) : '',
            ];
        }
        $total = !empty($eventsList) ? [
            'label' => $outputLangs->transnoentities('TotalMaintenance'),
            'value' => $totalCost > 0 ? price($totalCost, 0, $outputLangs, 1, -1, -1, $conf->currency) : '—',
        ] : null;
        $render->table($cols, $tableRows, $outputLangs->transnoentities('NoVehicleHistory'), $total);
        $render->spacer(6);

        // Validation block
        $render->sectionTitle($outputLangs->transnoentities('Validation'));
        $render->validation(
            $outputLangs->transnoentities('DocumentEditedBy', dol_print_date(dol_now(), 'day'), trim(dol_strtoupper($user->lastname) . ' ' . ucfirst($user->firstname)), $companyName),
            $outputLangs->transnoentities('FleetManager'),
            $outputLangs->transnoentities('CompanyStamp')
        );

        /*
         * Save the file
         */
        $dirOutput = !empty($conf->dolicar->multidir_output[$conf->entity]) ? $conf->dolicar->multidir_output[$conf->entity] : $conf->dolicar->dir_output;
        if (empty($dirOutput)) {
            $this->error = 'Configuration manquante: conf->dolicar->dir_output';
            return -1;
        }
        $dir = $dirOutput . '/livretentretien/' . dol_sanitizeFileName($object->ref);
        if (!file_exists($dir) && dol_mkdir($dir) < 0) {
            $this->error = 'Impossible de créer le répertoire: ' . $dir;
            return -1;
        }

        $fileName = dol_sanitizeFileName(dol_print_date(dol_now(), '%Y%m%d') . '-' . $object->ref . '-livret-entretien') . '.pdf';
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
