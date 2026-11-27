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
            'selectarg' => 'Control:digiquali/class/control.class.php',
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
    dol_include_once('/saturne/lib/medias.lib.php');

    $out = '';
    foreach (dolicar_get_vehicle_event_media_sub_dirs($actionCommId) as $subDir) {
        $out .= saturne_render_media_block('dolicar', $subDir, 'evt' . $actionCommId . '-' . str_replace('/', '-', $subDir), '', ['show_photo' => true, 'show_file' => false, 'show_audio' => false, 'show_upload' => false]);

        // dolicar_get_upload_temp_files(), not saturne_get_media_files(): the latter drops
        // everything that is neither an image nor an audio record, so documents would never show up
        foreach (dolicar_get_upload_temp_files($subDir) as $mediaFile) {
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
 * the invoices linked to the events of its history — customer invoices for what the garage grants,
 * supplier invoices for what it received on the parts. One query resolves those invoices: walking
 * the events one by one to read their links would cost a query per event.
 *
 * @param  RegistrationCertificateFr $registrationCertificate Registration certificate of the vehicle
 * @return array                                              [['warranty' => array, 'invoice' => Facture|FactureFournisseur]], longest running first
 */
function dolicar_get_vehicle_warranties(RegistrationCertificateFr $registrationCertificate): array
{
    global $db;

    if (empty($registrationCertificate->fk_lot)) {
        return [];
    }

    // Load DoliCar libraries
    require_once __DIR__ . '/dolicar_functions.lib.php';

    $invoiceClasses = [];
    if (isModEnabled('facture')) {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        $invoiceClasses['facture'] = 'Facture';
    }
    if (isModEnabled('supplier_invoice') || isModEnabled('fournisseur')) {
        require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
        $invoiceClasses['invoice_supplier'] = 'FactureFournisseur';
    }

    if (empty($invoiceClasses)) {
        return [];
    }

    // A vehicle event links its invoice as the source of the link and itself as the target
    $sql  = 'SELECT DISTINCT ee.fk_source AS invoice_id, ee.sourcetype';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element AS ee';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . "actioncomm AS a ON a.id = ee.fk_target AND ee.targettype = 'action'";
    $sql .= " WHERE ee.sourcetype IN ('" . implode("', '", array_keys($invoiceClasses)) . "')";
    $sql .= ' AND a.fk_element = ' . (int) $registrationCertificate->fk_lot;
    $sql .= " AND a.elementtype = 'productlot'";

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog('dolicar_get_vehicle_warranties: ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $vehicleWarranties = [];
    while ($obj = $db->fetch_object($resql)) {
        $invoiceClass = $invoiceClasses[$obj->sourcetype];

        $invoice = new $invoiceClass($db);
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
 * One line excerpt of a note shown next to a document of the vehicle history.
 *
 * Notes may be HTML (written with <br> by the DoliCar triggers, or by the WYSIWYG editor): the tags are
 * removed before truncating, so that none is printed as text nor cut in half.
 *
 * @param  string|null $note   Note of the linked document, plain text or HTML
 * @param  int         $length Maximum length of the excerpt
 * @return string              Excerpt ready for HTML output
 */
function dolicar_vehicle_event_note_excerpt(?string $note, int $length = 80): string
{
    return dol_escape_htmltag(dol_trunc(dol_string_nohtmltag((string) $note, 1), $length));
}

/**
 * Vehicle block that DoliCar writes in the public note of a proposal, an order or an invoice.
 *
 * @param  CommonObject $object  Proposal, order or invoice carrying the DoliCar extrafields
 * @param  int|null     $mileage Mileage to show, null for the one of the extrafield
 * @return string                One "label : value<br>" line per vehicle data
 */
function dolicar_get_vehicle_note_block(CommonObject $object, ?int $mileage = null): string
{
    global $langs;

    $langs->loadLangs(['dolicar@dolicar']);

    $options = $object->array_options;
    $mileage = $mileage ?? (int) ($options['options_mileage'] ?? 0);

    // A timestamp when set by the creation form, already a formatted date when set from the registration certificate
    $firstRegistrationDate = $options['options_first_registration_date'] ?? '';
    if (is_numeric($firstRegistrationDate)) {
        $firstRegistrationDate = $firstRegistrationDate > 0 ? dol_print_date($firstRegistrationDate, 'day') : '';
    }

    $values = [
        'DoliCarRegistrationNumber' => $options['options_registration_number'] ?? '',
        'VehicleModel'              => $options['options_vehicle_model'] ?? '',
        'VINNumber'                 => $options['options_VIN_number'] ?? '',
        'FirstRegistrationDate'     => $firstRegistrationDate,
    ];

    $block = '';
    foreach ($values as $key => $value) {
        $value  = dol_strlen((string) $value) > 0 ? $value : $langs->transnoentities('NoData');
        $block .= $langs->transnoentities($key) . ' : ' . $value . '<br>';
    }
    $mileageLabel = ($mileage > 0 ? price($mileage, 0, '', 1, 0) : 0) . ' ' . $langs->trans('km');
    $block       .= $langs->transnoentities('Mileage') . ' : ' . $mileageLabel . '<br>';

    return $block;
}

/**
 * Put the vehicle block in a public note, keeping what the note already holds (issue #328).
 *
 * A block written before is replaced where it stands, otherwise the block goes on top of the note.
 *
 * @param  string|null $notePublic Current public note
 * @param  string      $block      Vehicle block, see dolicar_get_vehicle_note_block()
 * @return string                  Public note with the vehicle block
 */
function dolicar_merge_vehicle_note(?string $notePublic, string $block): string
{
    global $langs;

    $langs->loadLangs(['dolicar@dolicar']);

    $notePublic = (string) $notePublic;

    // The lines of dolicar_get_vehicle_note_block(), in their order, whatever their values
    $linePatterns = [];
    foreach (['DoliCarRegistrationNumber', 'VehicleModel', 'VINNumber', 'FirstRegistrationDate', 'Mileage'] as $key) {
        $linePatterns[] = preg_quote($langs->transnoentities($key), '/') . '\s*:[^<]*<br\s*\/?>\s*';
    }

    $count  = 0;
    $merged = preg_replace_callback('/' . implode('', $linePatterns) . '/u', function () use ($block) {
        return $block;
    }, $notePublic, 1, $count);

    if ($count > 0 && $merged !== null) {
        return $merged;
    }

    return $block . $notePublic;
}

/**
 * Find the vehicle history event an invoice is already attached to.
 *
 * The automatic push and a manual link from the add-event form land in the same element_element row,
 * so an invoice already linked by hand is never pushed a second time.
 *
 * @param  int $invoiceId Invoice ID
 * @param  int $lotId     Product lot ID of the vehicle
 * @return int            0 when the invoice is not in the history of this vehicle, event ID otherwise
 */
function dolicar_get_vehicle_history_event_of_invoice(int $invoiceId, int $lotId): int
{
    global $db;

    if ($invoiceId <= 0 || $lotId <= 0) {
        return 0;
    }

    // ActionComm->element is 'action', which is what add_object_linked() stores as targettype
    $sql  = 'SELECT ee.fk_target FROM ' . MAIN_DB_PREFIX . 'element_element ee';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'actioncomm a ON a.id = ee.fk_target';
    $sql .= " WHERE ee.sourcetype = 'facture' AND ee.fk_source = " . $invoiceId;
    $sql .= " AND ee.targettype = 'action'";
    $sql .= " AND a.elementtype = 'productlot' AND a.fk_element = " . $lotId;

    $resql = $db->query($sql);
    if (!$resql) {
        return 0;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return !empty($obj) ? (int) $obj->fk_target : 0;
}

/**
 * Collect the documents already attached to an event of a vehicle history, by type (issue #426).
 *
 * One query on element_element for the whole history, so a document cannot be picked again for
 * another event of the same vehicle. Covers every linkable type and the expense report lines.
 *
 * @param  int                           $lotId Product lot ID of the vehicle
 * @return array<string, array<int,int>>        Linked IDs keyed by element type, then by ID
 */
function dolicar_get_vehicle_history_linked_element_ids(int $lotId): array
{
    global $db;

    $linkedElementIds = [];
    if ($lotId <= 0) {
        return $linkedElementIds;
    }

    $sourceTypes   = array_keys(dolicar_get_vehicle_event_linkable_types());
    $sourceTypes[] = 'expensereportline';

    // ActionComm->element is 'action', which is what add_object_linked() stores as targettype
    $sql  = 'SELECT ee.sourcetype, ee.fk_source FROM ' . MAIN_DB_PREFIX . 'element_element ee';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'actioncomm a ON a.id = ee.fk_target';
    $sql .= " WHERE ee.targettype = 'action'";
    $sql .= " AND ee.sourcetype IN ('" . implode("','", array_map([$db, 'escape'], $sourceTypes)) . "')";
    $sql .= " AND a.elementtype = 'productlot' AND a.fk_element = " . $lotId;

    $resql = $db->query($sql);
    if (!$resql) {
        return $linkedElementIds;
    }

    while ($obj = $db->fetch_object($resql)) {
        $linkedElementIds[$obj->sourcetype][(int) $obj->fk_source] = (int) $obj->fk_source;
    }
    $db->free($resql);

    return $linkedElementIds;
}

/**
 * Current mileage of a vehicle: the highest one recorded in its history.
 *
 * A trip of the public logbook carries a mileage at each end, any other event (maintenance, invoice
 * pushed at validation) the one it was recorded at. One query on the extrafields of the events of the lot.
 *
 * @param  int $lotId Product lot ID of the vehicle
 * @return int        0 when no mileage was ever recorded, the mileage otherwise
 */
function dolicar_get_vehicle_current_mileage(int $lotId): int
{
    global $db;

    if ($lotId <= 0) {
        return 0;
    }

    $sql  = 'SELECT MAX(ef.starting_mileage) AS starting_mileage, MAX(ef.arrival_mileage) AS arrival_mileage';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm a';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'actioncomm_extrafields ef ON ef.fk_object = a.id';
    $sql .= " WHERE a.elementtype = 'productlot' AND a.fk_element = " . $lotId;

    $resql = $db->query($sql);
    if (!$resql) {
        return 0;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return !empty($obj) ? max((int) $obj->starting_mileage, (int) $obj->arrival_mileage) : 0;
}

/**
 * Push an invoice issued for a vehicle into the history of that vehicle (issue #464).
 *
 * Creates the event on the product lot of the vehicle, carries the mileage of the invoice over to
 * the event and links the invoice to it, so the history line shows the reference, the amount and the
 * PDF preview exactly like a manually linked invoice does.
 *
 * The event carries no DoliCar category: like the public logbook ones it is recognized by its code,
 * which keeps it visible even before the event categories are initialized by the history page.
 *
 * @param  Facture $invoice Invoice already fetched, its extrafields are loaded when missing
 * @param  User    $user    User the event is created for
 * @return int              < 0 if KO, 0 when the invoice carries no vehicle or is already in the history, event ID otherwise
 */
function dolicar_push_invoice_to_vehicle_history(Facture $invoice, User $user): int
{
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    require_once __DIR__ . '/../class/registrationcertificatefr.class.php';

    // Called from the invoice validation, where the DoliCar translations are not loaded
    $langs->load('dolicar@dolicar');

    if (empty($invoice->array_options)) {
        $invoice->fetch_optionals();
    }

    $registrationCertificateId = (int) ($invoice->array_options['options_registrationcertificatefr'] ?? 0);
    if ($registrationCertificateId <= 0) {
        return 0;
    }

    // The history hangs on the product lot of the vehicle, not on the carte grise itself
    $registrationCertificate = new RegistrationCertificateFr($db);
    if ($registrationCertificate->fetch($registrationCertificateId) <= 0 || (int) $registrationCertificate->fk_lot <= 0) {
        return 0;
    }

    if (dolicar_get_vehicle_history_event_of_invoice((int) $invoice->id, (int) $registrationCertificate->fk_lot) > 0) {
        return 0;
    }

    // From BILL_VALIDATE, the invoice still has its (PROVn) ref: Facture::validate() calls its triggers before taking
    // the final ref, kept meanwhile in newref, as the agenda trigger of Dolibarr core reads it
    $invoiceRef = !empty($invoice->newref) ? $invoice->newref : $invoice->ref;

    $event              = new ActionComm($db);
    $event->code        = 'AC_DOLICAR_VEHICLE_INVOICE';
    $event->type_code   = 'AC_OTH_AUTO';
    $event->fk_element  = (int) $registrationCertificate->fk_lot;
    $event->elementtype = 'productlot';
    $event->label       = $langs->transnoentities('VehicleInvoiceEventLabel', $invoiceRef);
    $event->datep       = $invoice->date ?: dol_now();
    $event->userownerid = $user->id;
    $event->percentage  = -1;

    $eventId = $event->create($user);
    if ($eventId <= 0) {
        return -1;
    }

    // The mileage entered on the invoice becomes the mileage of the history line
    $invoiceMileage = (int) ($invoice->array_options['options_mileage'] ?? 0);
    if ($invoiceMileage > 0) {
        $event->array_options['options_starting_mileage'] = $invoiceMileage;
        $event->insertExtraFields();
    }

    $event->add_object_linked('facture', $invoice->id);

    return $eventId;
}

/**
 * Push every non draft invoice carrying a vehicle into the history of that vehicle (issue #464).
 *
 * Catches up on the invoices issued before the automatic push existed. Safe to run again, the push
 * itself skips the invoices already present in a history.
 *
 * @param  User $user User the events are created for
 * @return int        Number of invoices added to a vehicle history
 */
function dolicar_push_all_invoices_to_vehicle_history(User $user): int
{
    global $db;

    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

    $sql  = 'SELECT f.rowid FROM ' . MAIN_DB_PREFIX . 'facture f';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fe ON fe.fk_object = f.rowid';
    $sql .= ' WHERE f.entity IN (' . getEntity('invoice') . ')';
    $sql .= ' AND f.fk_statut > ' . Facture::STATUS_DRAFT;
    $sql .= ' AND fe.registrationcertificatefr > 0';

    $resql = $db->query($sql);
    if (!$resql) {
        return 0;
    }

    $pushedCount = 0;
    while ($obj = $db->fetch_object($resql)) {
        $invoice = new Facture($db);
        if ($invoice->fetch((int) $obj->rowid) > 0 && dolicar_push_invoice_to_vehicle_history($invoice, $user) > 0) {
            $pushedCount++;
        }
    }
    $db->free($resql);

    return $pushedCount;
}

/**
 * Return the tags a file attached to a carte grise can carry, in display order.
 *
 * The tag is stored on the file index (ecm_files extra field dolicar_file_tag), so it follows the file
 * when it is renamed and goes away with it. The tags of a same document share their picto and their
 * colour (css modifier of the dolicar-file-tag pill).
 *
 * @return array<string, array{label: string, pictofa: string, css: string}>
 */
function dolicar_get_file_tags(): array
{
    return [
        'registration_certificate_front' => ['label' => 'FileTagRegistrationCertificateFront', 'pictofa' => 'fa-id-card',         'css' => 'registration-certificate'],
        'registration_certificate_back'  => ['label' => 'FileTagRegistrationCertificateBack',  'pictofa' => 'fa-id-card',         'css' => 'registration-certificate'],
        'insurance_front'                => ['label' => 'FileTagInsuranceFront',               'pictofa' => 'fa-shield-alt',      'css' => 'insurance'],
        'insurance_back'                 => ['label' => 'FileTagInsuranceBack',                'pictofa' => 'fa-shield-alt',      'css' => 'insurance'],
        'technical_inspection'           => ['label' => 'FileTagTechnicalInspection',          'pictofa' => 'fa-clipboard-check', 'css' => 'technical-inspection'],
        'environmental_sticker'          => ['label' => 'FileTagEnvironmentalSticker',         'pictofa' => 'fa-leaf',            'css' => 'environmental-sticker'],
    ];
}

/**
 * Collect the files attached to a carte grise with their tag.
 *
 * Reads the file index of the documents tab directory: listing that tab indexes every file found on
 * disk, so each line of its list has an index ID here.
 *
 * @param  RegistrationCertificateFr $object Carte grise
 * @return array<int, string>                Tag code ('' when none), keyed by file index ID
 */
function dolicar_get_registration_certificate_file_tags(RegistrationCertificateFr $object): array
{
    global $conf, $db;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $fileTags = [];
    $entity   = $object->entity ?: $conf->entity;
    if (empty($object->id) || empty($conf->dolicar->multidir_output[$entity])) {
        return $fileTags;
    }

    // The extra field is created by the module activation: nothing to read until the module is reactivated
    $extraFields = new ExtraFields($db);
    $extraFields->fetch_name_optionals_label('ecm_files');
    if (!isset($extraFields->attributes['ecm_files']['type']['dolicar_file_tag'])) {
        return $fileTags;
    }

    // Same directory as the documents tab, relative to the data root like the file index
    $uploadDir   = $conf->dolicar->multidir_output[$entity] . '/' . $object->element . '/' . get_exdir(0, 0, 0, 1, $object);
    $relativeDir = trim(preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $uploadDir), '/');

    $sql  = 'SELECT ef.rowid, efe.dolicar_file_tag FROM ' . MAIN_DB_PREFIX . 'ecm_files ef';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'ecm_files_extrafields efe ON efe.fk_object = ef.rowid';
    $sql .= " WHERE ef.filepath = '" . $db->escape($relativeDir) . "'";
    $sql .= ' AND ef.entity = ' . ((int) $entity);

    $resql = $db->query($sql);
    if (!$resql) {
        return $fileTags;
    }

    while ($obj = $db->fetch_object($resql)) {
        $fileTags[(int) $obj->rowid] = (string) $obj->dolicar_file_tag;
    }
    $db->free($resql);

    return $fileTags;
}

/**
 * Tag a file attached to a carte grise, or remove its tag.
 *
 * @param  RegistrationCertificateFr $object    Carte grise the file is attached to
 * @param  int                       $ecmFileId File index ID, from the documents tab list
 * @param  string                    $tag       Tag code, '' to remove the tag
 * @return int                                  1 if OK, 0 or -1 if KO
 */
function dolicar_set_registration_certificate_file_tag(RegistrationCertificateFr $object, int $ecmFileId, string $tag): int
{
    global $db;

    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

    // The browser sends the file ID: only a known tag, on a file of this carte grise
    if (($tag !== '' && !array_key_exists($tag, dolicar_get_file_tags())) || !array_key_exists($ecmFileId, dolicar_get_registration_certificate_file_tags($object))) {
        return -1;
    }

    $ecmFile = new EcmFiles($db);
    if ($ecmFile->fetch($ecmFileId) <= 0) {
        return -1;
    }

    // fetch() loaded the other extra fields of the file, updateExtraField() only writes this one
    $ecmFile->array_options['options_dolicar_file_tag'] = $tag;

    return $ecmFile->updateExtraField('dolicar_file_tag');
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

