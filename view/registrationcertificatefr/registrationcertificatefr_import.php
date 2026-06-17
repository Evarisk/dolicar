<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    view/registrationcertificatefr/registrationcertificatefr_import.php
 * \ingroup dolicar
 * \brief   CSV import of registration certificates (cartes grises) reusing the object business logic
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
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';
require_once __DIR__ . '/../../lib/dolicar_functions.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new RegistrationCertificateFr($db);

// Security check
$permissionToRead = $user->rights->dolicar->registrationcertificatefr->read;
$permissionToAdd  = $user->rights->dolicar->registrationcertificatefr->write;
saturne_check_access($permissionToRead);

/**
 * Description of the columns recognized by the import. Each header of the CSV file is matched
 * (case/accent/space insensitive) against the field code, its CSV header or one of its aliases.
 *
 * type: plate|string|int|double|date|fk_soc|fk_product|fk_project
 */
$importableFields = [
    'a_registration_number'            => ['type' => 'plate',       'required' => true,  'label' => 'RegistrationNumber',          'csvheader' => 'immatriculation',                'aliases' => ['plaque', 'registrationnumber', 'ref']],
    'd1_vehicle_brand'                 => ['type' => 'string',      'required' => false, 'label' => 'VehicleBrand',                'csvheader' => 'marque',                         'aliases' => ['brand', 'd1']],
    'd2_vehicle_type'                  => ['type' => 'string',      'required' => false, 'label' => 'VehicleType',                 'csvheader' => 'type',                           'aliases' => ['d2']],
    'd21_vehicle_cnit'                 => ['type' => 'string',      'required' => false, 'label' => 'VehicleCNIT',                 'csvheader' => 'cnit',                           'aliases' => ['d21']],
    'd3_vehicle_model'                 => ['type' => 'string',      'required' => false, 'label' => 'VehicleModel',                'csvheader' => 'modele',                         'aliases' => ['model', 'd3']],
    'e_vehicle_serial_number'          => ['type' => 'string',      'required' => false, 'label' => 'VehicleSerialNumber',         'csvheader' => 'vin',                            'aliases' => ['numerodeserie', 'serial', 'chassis']],
    'b_first_registration_date'        => ['type' => 'date',        'required' => false, 'label' => 'FirstRegistrationDate',       'csvheader' => 'date_premiere_immatriculation',  'aliases' => ['date1ereimmatriculation', 'datepremierecirculation']],
    'i_vehicle_registration_date'      => ['type' => 'date',        'required' => false, 'label' => 'VehicleRegistrationDate',     'csvheader' => 'date_immatriculation',           'aliases' => ['datecartegrise']],
    'c1_owner_fullname'                => ['type' => 'string',      'required' => false, 'label' => 'OwnerFullName',               'csvheader' => 'proprietaire',                   'aliases' => ['nomproprietaire', 'owner']],
    'p1_cylinder_capacity'             => ['type' => 'int',         'required' => false, 'label' => 'CylinderCapacity',           'csvheader' => 'cylindree',                      'aliases' => ['ccm']],
    'p3_fuel_type'                     => ['type' => 'string',      'required' => false, 'label' => 'FuelType',                    'csvheader' => 'carburant',                      'aliases' => ['energie', 'fuel']],
    'p6_national_administrative_power' => ['type' => 'int',         'required' => false, 'label' => 'NationalAdministrativePower', 'csvheader' => 'puissance_fiscale',              'aliases' => ['chevauxfiscaux', 'cv']],
    's1_seating_capacity'              => ['type' => 'int',         'required' => false, 'label' => 'SeatingCapacity',            'csvheader' => 'nombre_de_places',               'aliases' => ['places', 'nbplaces']],
    'v7_co2_emission'                  => ['type' => 'int',         'required' => false, 'label' => 'CO2Emission',                 'csvheader' => 'co2',                            'aliases' => ['emissionco2']],
    'fk_soc'                           => ['type' => 'fk_soc',      'required' => false, 'label' => 'ThirdParty',                  'csvheader' => 'tiers',                          'aliases' => ['client', 'societe', 'thirdparty']],
    'fk_product'                       => ['type' => 'fk_product',  'required' => false, 'label' => 'Product',                     'csvheader' => 'produit',                        'aliases' => ['product']],
    'fk_project'                       => ['type' => 'fk_project',  'required' => false, 'label' => 'Project',                     'csvheader' => 'projet',                         'aliases' => ['project']],
    'status'                           => ['type' => 'int',         'required' => false, 'label' => 'Status',                      'csvheader' => 'statut',                         'aliases' => ['status']],
];

/**
 * Normalize a CSV header or alias so the matching is case/accent/space insensitive.
 *
 * @param  string $value Raw header value
 * @return string        Normalized comparison key (lowercase alphanumeric only)
 */
function dolicar_import_normalize_header(string $value): string
{
    $accentMap = ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c'];
    $value     = strtr(mb_strtolower(trim($value), 'UTF-8'), $accentMap);

    return preg_replace('/[^a-z0-9]/', '', $value);
}

/**
 * Parse a date string (YYYY-MM-DD or DD/MM/YYYY) into a timestamp.
 *
 * @param  string    $value Raw date value
 * @return int|string       Timestamp, or '' if empty/unparseable
 */
function dolicar_import_parse_date(string $value)
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $matches = [];
    if (preg_match('#^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$#', $value, $matches)) {
        return dol_mktime(0, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }
    if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $value, $matches)) {
        return dol_mktime(0, 0, 0, (int) $matches[2], (int) $matches[1], (int) $matches[3]);
    }

    return '';
}

/*
 * Actions
 */

// Download the CSV template
if ($action === 'download_template') {
    $headers = [];
    foreach ($importableFields as $fieldDef) {
        $headers[] = $fieldDef['csvheader'];
    }

    $example = [
        'immatriculation'               => 'AA-123-BB',
        'marque'                        => 'Renault',
        'modele'                        => 'Clio',
        'vin'                           => 'VF1RFA00067123456',
        'date_premiere_immatriculation' => '2019-03-15',
        'carburant'                     => 'Essence',
        'puissance_fiscale'             => '5',
        'nombre_de_places'              => '5',
        'tiers'                         => '',
    ];
    $exampleLine = [];
    foreach ($importableFields as $fieldDef) {
        $exampleLine[] = isset($example[$fieldDef['csvheader']]) ? $example[$fieldDef['csvheader']] : '';
    }

    top_httphead('text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="modele_import_cartes_grises.csv"');

    // UTF-8 BOM so Excel opens accents correctly
    print "\xEF\xBB\xBF";
    print implode(';', $headers) . "\n";
    print implode(';', $exampleLine) . "\n";
    exit;
}

$importResults = [];
$importSummary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 0];
$importDone    = false;

if ($action === 'import' && $permissionToAdd) {
    $updateExisting = GETPOSTINT('update_existing') == 1;

    if (empty($_FILES['userfile']['name']) || !is_uploaded_file($_FILES['userfile']['tmp_name']) || $_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
        setEventMessages($langs->trans('ErrorNoFileUploaded'), [], 'errors');
    } else {
        $content = file_get_contents($_FILES['userfile']['tmp_name']);

        // Strip UTF-8 BOM and normalize encoding to UTF-8
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        // Drop trailing empty lines
        while (!empty($lines) && trim(end($lines)) === '') {
            array_pop($lines);
        }

        if (count($lines) < 2) {
            setEventMessages($langs->trans('ErrorEmptyCSVFile'), [], 'errors');
        } else {
            // Guess the delimiter from the header line
            $headerLine = $lines[0];
            $delimiter  = (substr_count($headerLine, ';') >= substr_count($headerLine, ',')) ? ';' : ',';
            $headerCols = str_getcsv($headerLine, $delimiter);

            // Build column index -> field code map
            $aliasToField = [];
            foreach ($importableFields as $fieldCode => $fieldDef) {
                $aliasToField[dolicar_import_normalize_header($fieldCode)]            = $fieldCode;
                $aliasToField[dolicar_import_normalize_header($fieldDef['csvheader'])] = $fieldCode;
                foreach ($fieldDef['aliases'] as $alias) {
                    $aliasToField[dolicar_import_normalize_header($alias)] = $fieldCode;
                }
            }

            $columnMap = [];
            foreach ($headerCols as $index => $headerName) {
                $normalized = dolicar_import_normalize_header($headerName);
                if ($normalized !== '' && isset($aliasToField[$normalized])) {
                    $columnMap[$index] = $aliasToField[$normalized];
                }
            }

            if (!in_array('a_registration_number', $columnMap, true)) {
                setEventMessages($langs->trans('ErrorMissingRegistrationNumberColumn'), [], 'errors');
            } else {
                // Avoid product creation number error when the object creates default product/lot
                $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

                $thirdpartyCache = [];
                $productCache    = [];
                $projectCache    = [];

                $nbLines = count($lines);
                for ($i = 1; $i < $nbLines; $i++) {
                    if (trim($lines[$i]) === '') {
                        continue;
                    }
                    $cols       = str_getcsv($lines[$i], $delimiter);
                    $lineNumber = $i + 1;

                    // Map raw values by field code
                    $values = [];
                    foreach ($columnMap as $index => $fieldCode) {
                        $values[$fieldCode] = isset($cols[$index]) ? trim($cols[$index]) : '';
                    }

                    $plateRaw = isset($values['a_registration_number']) ? $values['a_registration_number'] : '';
                    if ($plateRaw === '') {
                        $importResults[] = ['line' => $lineNumber, 'plate' => '', 'status' => 'error', 'message' => $langs->trans('ImportErrorEmptyPlate')];
                        $importSummary['error']++;
                        continue;
                    }

                    $normalizedRef = normalize_registration_number(dol_strtoupper($plateRaw));
                    if ($normalizedRef === -1 || $normalizedRef === '') {
                        $normalizedRef = dol_strtoupper($plateRaw);
                    }

                    // Existing record ? (ref equals normalized registration number)
                    $current = new RegistrationCertificateFr($db);
                    $found   = $current->fetch(0, $normalizedRef);
                    $isUpdate = ($found > 0);

                    if ($isUpdate && !$updateExisting) {
                        $importResults[] = ['line' => $lineNumber, 'plate' => $normalizedRef, 'status' => 'skipped', 'message' => $langs->trans('ImportLineAlreadyExists')];
                        $importSummary['skipped']++;
                        continue;
                    }

                    $target = $isUpdate ? $current : new RegistrationCertificateFr($db);
                    $rowNotes = [];

                    // Apply values
                    foreach ($values as $fieldCode => $rawValue) {
                        if (!isset($importableFields[$fieldCode])) {
                            continue;
                        }
                        $type = $importableFields[$fieldCode]['type'];

                        switch ($type) {
                            case 'plate':
                                $target->a_registration_number = $rawValue;
                                break;
                            case 'string':
                                if ($rawValue !== '') {
                                    $target->$fieldCode = $rawValue;
                                }
                                break;
                            case 'int':
                                if ($rawValue !== '') {
                                    $target->$fieldCode = (int) preg_replace('/[^0-9\-]/', '', $rawValue);
                                }
                                break;
                            case 'double':
                                if ($rawValue !== '') {
                                    $target->$fieldCode = (float) str_replace([' ', ','], ['', '.'], $rawValue);
                                }
                                break;
                            case 'date':
                                $parsedDate = dolicar_import_parse_date($rawValue);
                                if ($parsedDate !== '') {
                                    $target->$fieldCode = $parsedDate;
                                }
                                break;
                            case 'fk_soc':
                                if ($rawValue !== '') {
                                    if (!isset($thirdpartyCache[$rawValue])) {
                                        $soc = new Societe($db);
                                        $res = is_numeric($rawValue) ? $soc->fetch((int) $rawValue) : $soc->fetch(0, $rawValue);
                                        $thirdpartyCache[$rawValue] = ($res > 0) ? $soc->id : 0;
                                    }
                                    if ($thirdpartyCache[$rawValue] > 0) {
                                        $target->fk_soc = $thirdpartyCache[$rawValue];
                                    } else {
                                        $rowNotes[] = $langs->trans('ImportThirdPartyNotFound', $rawValue);
                                    }
                                }
                                break;
                            case 'fk_product':
                                if ($rawValue !== '') {
                                    if (!isset($productCache[$rawValue])) {
                                        $prod = new Product($db);
                                        $res  = is_numeric($rawValue) ? $prod->fetch((int) $rawValue) : $prod->fetch(0, $rawValue);
                                        $productCache[$rawValue] = ($res > 0) ? $prod->id : 0;
                                    }
                                    if ($productCache[$rawValue] > 0) {
                                        $target->fk_product = $productCache[$rawValue];
                                    } else {
                                        $rowNotes[] = $langs->trans('ImportProductNotFound', $rawValue);
                                    }
                                }
                                break;
                            case 'fk_project':
                                if ($rawValue !== '') {
                                    if (!isset($projectCache[$rawValue])) {
                                        $proj = new Project($db);
                                        $res  = is_numeric($rawValue) ? $proj->fetch((int) $rawValue) : $proj->fetch(0, $rawValue);
                                        $projectCache[$rawValue] = ($res > 0) ? $proj->id : 0;
                                    }
                                    if ($projectCache[$rawValue] > 0) {
                                        $target->fk_project = $projectCache[$rawValue];
                                    } else {
                                        $rowNotes[] = $langs->trans('ImportProjectNotFound', $rawValue);
                                    }
                                }
                                break;
                        }
                    }

                    // For new validated certificates without an explicit product, create the real
                    // vehicle product (brand + model) and its lot (VIN) instead of the default vehicle
                    if (!$isUpdate && (int) $target->status !== RegistrationCertificateFr::STATUS_DRAFT && empty($target->fk_product)) {
                        $vehicleProductLot = dolicar_get_or_create_vehicle_product_lot(
                            (string) $target->d1_vehicle_brand,
                            (string) $target->d3_vehicle_model,
                            '',
                            (string) $target->e_vehicle_serial_number
                        );
                        if ($vehicleProductLot['fk_product'] > 0) {
                            $target->fk_product = $vehicleProductLot['fk_product'];
                        }
                        if ($vehicleProductLot['fk_lot'] > 0) {
                            $target->fk_lot = $vehicleProductLot['fk_lot'];
                        }
                    }

                    // Persist through the object business logic (normalization, default product/lot, triggers)
                    $result = $isUpdate ? $target->update($user) : $target->create($user);

                    if ($result > 0) {
                        $note = implode(' ', $rowNotes);
                        if ($isUpdate) {
                            $importResults[] = ['line' => $lineNumber, 'plate' => $target->ref, 'status' => 'updated', 'message' => $note];
                            $importSummary['updated']++;
                        } else {
                            $importResults[] = ['line' => $lineNumber, 'plate' => $target->ref, 'status' => 'created', 'message' => $note];
                            $importSummary['created']++;
                        }
                    } else {
                        $importResults[] = ['line' => $lineNumber, 'plate' => $normalizedRef, 'status' => 'error', 'message' => $target->errorsToString()];
                        $importSummary['error']++;
                    }
                }

                $importDone = true;
                setEventMessages($langs->trans('ImportSummaryMessage', $importSummary['created'], $importSummary['updated'], $importSummary['skipped'], $importSummary['error']), [], ($importSummary['error'] > 0 ? 'warnings' : 'mesgs'));
            }
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('ImportRegistrationCertificate');
$helpUrl = 'FR:Module_DoliCar';

saturne_header(0, '', $title, $helpUrl);

print load_fiche_titre($title, '', 'fa-file-import');

print '<span class="opacitymedium">' . $langs->trans('ImportRegistrationCertificateDesc') . '</span><br><br>';

// Upload form
print '<form name="import" action="' . $_SERVER['PHP_SELF'] . '" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="import">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre"><td colspan="2">' . $langs->trans('ImportFromCSV') . '</td></tr>';

print '<tr class="oddeven"><td class="titlefieldcreate">' . $langs->trans('CSVFile') . '</td>';
print '<td><input type="file" name="userfile" accept=".csv,text/csv" required></td></tr>';

print '<tr class="oddeven"><td>' . $langs->trans('UpdateExistingRecords') . '</td>';
print '<td><input type="checkbox" name="update_existing" value="1" checked> <span class="opacitymedium">' . $langs->trans('UpdateExistingRecordsHelp') . '</span></td></tr>';

print '</table>';
print '</div>';

print '<div class="center" style="margin-top:12px">';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=download_template&token=' . newToken() . '">' . $langs->trans('DownloadCSVTemplate') . '</a>';
print '<input type="submit" class="button" name="launchimport" value="' . dol_escape_htmltag($langs->trans('LaunchImport')) . '"' . ($permissionToAdd ? '' : ' disabled') . '>';
print '</div>';

print '</form>';

// Import results
if ($importDone) {
    print '<br>';
    print load_fiche_titre($langs->trans('ImportResults'), '', '');

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Line') . '</td>';
    print '<td>' . $langs->trans('RegistrationNumber') . '</td>';
    print '<td>' . $langs->trans('Status') . '</td>';
    print '<td>' . $langs->trans('Message') . '</td>';
    print '</tr>';

    $statusBadge = [
        'created' => '<span class="badge badge-status4 badge-status">' . $langs->trans('ImportStatusCreated') . '</span>',
        'updated' => '<span class="badge badge-status1 badge-status">' . $langs->trans('ImportStatusUpdated') . '</span>',
        'skipped' => '<span class="badge badge-status0 badge-status">' . $langs->trans('ImportStatusSkipped') . '</span>',
        'error'   => '<span class="badge badge-status8 badge-status">' . $langs->trans('ImportStatusError') . '</span>',
    ];

    foreach ($importResults as $importResult) {
        print '<tr class="oddeven">';
        print '<td>' . (int) $importResult['line'] . '</td>';
        print '<td>' . dol_escape_htmltag($importResult['plate']) . '</td>';
        print '<td>' . (isset($statusBadge[$importResult['status']]) ? $statusBadge[$importResult['status']] : dol_escape_htmltag($importResult['status'])) . '</td>';
        print '<td>' . dol_escape_htmltag($importResult['message']) . '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

// Recognized columns documentation
print '<br>';
print load_fiche_titre($langs->trans('RecognizedColumns'), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('CSVColumn') . '</td>';
print '<td>' . $langs->trans('FieldName') . '</td>';
print '<td class="center">' . $langs->trans('Required') . '</td>';
print '</tr>';

foreach ($importableFields as $fieldDef) {
    print '<tr class="oddeven">';
    print '<td><strong>' . dol_escape_htmltag($fieldDef['csvheader']) . '</strong></td>';
    print '<td>' . dol_escape_htmltag($langs->trans($fieldDef['label'])) . '</td>';
    print '<td class="center">' . ($fieldDef['required'] ? '<span style="color:var(--colorbacktitle2,#e05d2c)">' . $langs->trans('Yes') . '</span>' : $langs->trans('No')) . '</td>';
    print '</tr>';
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
