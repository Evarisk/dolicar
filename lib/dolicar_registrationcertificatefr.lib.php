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
 * \file    lib/dolicar_registrationcertificatefr.lib.php
 * \ingroup dolicar
 * \brief   Library files with common functions for RegistrationCertificateFr
 */

/**
 * Prepare registrationcertificatefr pages header
 *
 * @param  RegistrationCertificateFr $object RegistrationCertificateFr
 * @return array                     $head   Array of tabs
 * @throws Exception
 */
function registrationcertificatefr_prepare_head(RegistrationCertificateFr $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 1;
    $head = [];

    $head[$h][0] = dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_linkedobjects.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-link pictofixedwidth"></i>' . $langs->trans('LinkedObjects') : '<i class="fas fa-link"></i>';
    $head[$h][2] = 'linkedobjects';
    $h++;

    $head[$h][0] = dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_vehiclehistory.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-history pictofixedwidth"></i>' . $langs->trans('VehicleHistory') : '<i class="fas fa-history"></i>';
    $head[$h][2] = 'vehiclehistory';

    return saturne_object_prepare_head($object, $head);
}

/**
 * Return the list of document types that can be linked to a vehicle history event.
 *
 * Each entry is keyed by its short code and drives both the add-event form
 * (picto, label, selectForForms argument, field name) and the admin toggle
 * (visibility constant). The order defines the display order in the form.
 *
 * @return array<string, array{const: string, label: string, picto: string, pictofa: string, selectarg: string, field: string}>
 */
function dolicar_get_vehicle_event_linkable_types(): array
{
    return [
        'facture' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_FACTURE_ENABLED',
            'label'     => 'Invoices',
            'picto'     => 'bill',
            'pictofa'   => '',
            'selectarg' => 'Facture:compta/facture/class/facture.class.php',
            'field'     => 'event_fk_facture',
        ],
        'propal' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_PROPAL_ENABLED',
            'label'     => 'Proposals',
            'picto'     => 'propal',
            'pictofa'   => '',
            'selectarg' => 'Propal:comm/propal/class/propal.class.php',
            'field'     => 'event_fk_propal',
        ],
        'expensereport' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_EXPENSEREPORT_ENABLED',
            'label'     => 'ExpenseReports',
            'picto'     => 'trip',
            'pictofa'   => '',
            'selectarg' => 'ExpenseReport:expensereport/class/expensereport.class.php',
            'field'     => 'event_fk_expensereport',
        ],
        'order_supplier' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_SUPPLIERORDER_ENABLED',
            'label'     => 'SuppliersOrders',
            'picto'     => 'supplier_order',
            'pictofa'   => '',
            'selectarg' => 'CommandeFournisseur:fourn/class/fournisseur.commande.class.php',
            'field'     => 'event_fk_supplierorder',
        ],
        'invoice_supplier' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_SUPPLIERINVOICE_ENABLED',
            'label'     => 'SuppliersInvoices',
            'picto'     => 'supplier_invoice',
            'pictofa'   => '',
            'selectarg' => 'FactureFournisseur:fourn/class/fournisseur.facture.class.php',
            'field'     => 'event_fk_supplierinvoice',
        ],
        'control' => [
            'const'     => 'DOLICAR_VEHICLE_EVENT_CONTROL_ENABLED',
            'label'     => 'Controls',
            'picto'     => '',
            'pictofa'   => 'fa-tasks',
            'selectarg' => 'Control:custom/digiquali/class/control.class.php',
            'field'     => 'event_fk_control',
        ],
    ];
}

/**
 * Tell whether a vehicle event linkable document type is enabled.
 *
 * Defaults to enabled when the constant has never been set, so existing
 * installs keep offering every document type until an admin disables one.
 *
 * @param  string $const Visibility constant name
 * @return bool          True if the document type may be offered in the form
 */
function dolicar_vehicle_event_type_enabled(string $const): bool
{
    return getDolGlobalString($const, '1') !== '0';
}

/**
 * Return the sub directories holding the medias of a vehicle history event.
 *
 * Events are created from three places, each storing its medias in its own directory: the
 * back-office add-event form, the public logbook repair form and the public logbook problem
 * report. Only existing directories are returned, so an event without media renders nothing.
 *
 * @param  int      $actionCommId Vehicle history event ID
 * @return string[]               Sub directories, relative to the DoliCar output dir
 */
function dolicar_get_vehicle_event_media_sub_dirs(int $actionCommId): array
{
    global $conf;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    if ($actionCommId <= 0) {
        return [];
    }

    $subDirs = [];
    foreach (['vehicle_event', 'vehicle_repair', 'problem_report'] as $subDirPrefix) {
        $subDir = $subDirPrefix . '/' . $actionCommId;
        if (dol_is_dir($conf->dolicar->dir_output . '/' . $subDir)) {
            $subDirs[] = $subDir;
        }
    }

    return $subDirs;
}

/**
 * Build the medias cell of a vehicle history event.
 *
 * Photos go through the Saturne gallery so a click opens the media viewer, while documents and
 * voice memos — which the gallery ignores — are listed as plain links.
 *
 * @param  int    $actionCommId Vehicle history event ID
 * @return string               HTML of the medias attached to the event, empty when it has none
 */
function dolicar_get_vehicle_event_media_html(int $actionCommId): string
{
    global $conf;

    // Load Saturne libraries
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    $out = '';
    foreach (dolicar_get_vehicle_event_media_sub_dirs($actionCommId) as $subDir) {
        $out .= saturne_render_media_block('dolicar', $subDir, 'evt' . $actionCommId . '-' . str_replace('/', '-', $subDir), '', ['show_photo' => true, 'show_file' => false, 'show_audio' => false, 'show_upload' => false]);

        foreach (saturne_get_media_files('dolicar', $subDir) as $mediaFile) {
            if (image_format_supported($mediaFile['name']) >= 0) {
                continue;
            }

            $fileUrl = DOL_URL_ROOT . '/document.php?modulepart=dolicar&entity=' . $conf->entity . '&file=' . urlencode($subDir . '/' . $mediaFile['name']);

            $out .= '<div class="inline-block">';
            $out .= '<a href="' . dol_escape_htmltag($fileUrl) . '" target="_blank"><i class="fas fa-paperclip paddingright"></i>' . dol_escape_htmltag($mediaFile['name']) . '</a>';
            $out .= '</div><br>';
        }
    }

    return $out;
}

/**
 * Collect the warranties covering a vehicle.
 *
 * A warranty is recorded on the invoice that grants it, so the vehicle card gathers them through
 * the invoices linked to the events of its history. One query resolves those invoices — walking
 * the events one by one to read their links would cost a query per event.
 *
 * @param  RegistrationCertificateFr $registrationCertificate Registration certificate of the vehicle
 * @return array                                              [['warranty' => array, 'invoice' => Facture]], longest running first
 */
function dolicar_get_vehicle_warranties(RegistrationCertificateFr $registrationCertificate): array
{
    global $db;

    if (!isModEnabled('facture') || empty($registrationCertificate->fk_lot)) {
        return [];
    }

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

    // Load DoliCar libraries
    require_once __DIR__ . '/dolicar_functions.lib.php';

    // A vehicle event links its invoice as the source of the link and itself as the target
    $sql  = 'SELECT DISTINCT ee.fk_source AS invoice_id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element AS ee';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . "actioncomm AS a ON a.id = ee.fk_target AND ee.targettype = 'action'";
    $sql .= " WHERE ee.sourcetype = 'facture'";
    $sql .= ' AND a.fk_element = ' . (int) $registrationCertificate->fk_lot;
    $sql .= " AND a.elementtype = 'productlot'";

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog('dolicar_get_vehicle_warranties: ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $vehicleWarranties = [];
    while ($obj = $db->fetch_object($resql)) {
        $invoice = new Facture($db);
        if ($invoice->fetch((int) $obj->invoice_id) <= 0) {
            continue;
        }
        $invoice->fetch_optionals();

        foreach (dolicar_get_invoice_warranties($invoice) as $warranty) {
            $vehicleWarranties[] = ['warranty' => $warranty, 'invoice' => $invoice];
        }
    }
    $db->free($resql);

    // Latest end dates first, warranties without one last
    usort($vehicleWarranties, static function (array $first, array $second) {
        return strcmp((string) ($second['warranty']['date_end'] ?? ''), (string) ($first['warranty']['date_end'] ?? ''));
    });

    return $vehicleWarranties;
}

/**
 * Build a "magnifier" link opening a linked object's last generated PDF in the Dolibarr
 * document preview modal (used in the vehicle history "linked documents" column).
 *
 * last_main_doc is stored relative to the data root, i.e. it carries the module sub-dir prefix
 * (e.g. "propale/REF/REF.pdf" — note the module dir name may differ from the modulepart, like
 * "propale" vs "propal"). document.php resolves the modulepart to $dirOutput, so the path handed to
 * getAdvancedPreviewUrl() must be relative to that dir: the module output dir is stripped from the
 * real file path. The loupe is only rendered when the physical file actually exists, so a stale
 * last_main_doc never yields a broken "file does not exist" preview.
 *
 * @param  CommonObject $linkedObject Linked object carrying last_main_doc + entity (already fetched)
 * @param  string       $modulePart   document.php modulepart matching the object type (e.g. 'facture')
 * @param  string       $dirOutput    Absolute module output dir, i.e. $conf->{module}->dir_output
 * @return string                     HTML <a> with a search-plus picto, or '' when nothing to preview
 */
function dolicar_vehicle_event_doc_preview_link($linkedObject, string $modulePart, string $dirOutput): string
{
    global $langs;

    if (empty($linkedObject->last_main_doc) || empty($dirOutput)) {
        return '';
    }

    // last_main_doc is relative to DOL_DATA_ROOT — no loupe when the file is gone
    $absFile = DOL_DATA_ROOT . '/' . $linkedObject->last_main_doc;
    if (!dol_is_file($absFile)) {
        return '';
    }

    // Path relative to the modulepart base dir. Use the real module output dir (not a guessed name)
    // so quirks like "propale" vs "propal" resolve correctly. Fallback to <ref>/<file> if the dir
    // is not a prefix of the file (unexpected layout).
    $dirOutput    = rtrim($dirOutput, '/');
    $relativePath = (strpos($absFile, $dirOutput . '/') === 0)
        ? substr($absFile, strlen($dirOutput) + 1)
        : dol_sanitizeFileName($linkedObject->ref) . '/' . basename($absFile);

    $preview = getAdvancedPreviewUrl($modulePart, $relativePath, 1, '&entity=' . (int) $linkedObject->entity);
    if (empty($preview) || empty($preview['url'])) {
        return '';
    }

    return ' <a href="' . $preview['url'] . '"'
        . (!empty($preview['css']) ? ' class="' . $preview['css'] . '"' : '')
        . (!empty($preview['mime']) ? ' mime="' . $preview['mime'] . '"' : '')
        . (!empty($preview['target']) ? ' target="' . $preview['target'] . '"' : '')
        . ' title="' . dol_escape_htmltag($langs->trans('Preview')) . '">'
        . img_picto($langs->trans('Preview'), 'search-plus')
        . '</a>';
}

/**
 * Normalize with regex registration number field
 *
 * @param  string $registrationNumber Registration number
 * @return string|int                 0 < if KO, registration number default value or formatted
 */
function normalize_registration_number(string $registrationNumber)
{
    if (dol_strlen($registrationNumber) > 0) {
        if (preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $registrationNumber)) {
            $registrationNumberLetters = preg_split('/[0-9]{3}/', $registrationNumber);
            $registrationNumberNumbers = preg_split('/[A-Z]{2}/', $registrationNumber);

            return $registrationNumberLetters[0] . '-' . $registrationNumberNumbers[1] . '-' . $registrationNumberLetters[1];
        } else {
            return $registrationNumber;
        }
    } else {
        return -1;
    }
}

