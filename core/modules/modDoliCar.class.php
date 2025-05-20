<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \defgroup dolicar Module DoliCar
 * \brief    DoliCar module descriptor
 *
 * \file    core/modules/modDoliCar.class.php
 * \ingroup dolicar
 * \brief   Description and activation file for module DoliCar
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module DoliCar
 */
class modDoliCar extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs;
        $this->db = $db;

        if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            saturne_load_langs(['dolicar@dolicar']);
        } else {
            $this->error++;
            $this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'DoliCar', 'Saturne');
        }

        // Id for module (must be unique)
        $this->numero = 436380;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'dolicar';

        // Family can be 'base' (core modules), 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic' (transverse modules), 'interface' (link with external tools), 'other', '...'
        // It is used to group modules by family in module setup page
        $this->family = '';

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => $langs->trans('Evarisk')]];
        // Module label (no space allowed), used if translation string 'ModuleDoliCarName' not found (DoliCar is name of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModuleDoliCarDesc' not found (DoliCar is name of module)
        $this->description = $langs->trans('DoliCarDescription');
        // Used only if file README.md and README-LL.md not found
        $this->descriptionlong = $langs->trans('DoliCarDescription');

        // Author
        $this->editor_name = 'Evarisk';
        $this->editor_url  = 'https://www.evarisk.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '21.0.0';

        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where DoliCar is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
        $this->picto = 'dolicar_color@dolicar';

        $this->module_parts = [
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models' directory (core/modules/xxx)
            'models' => 1,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => [],
            // Set this to relative path of js file if module must load a js on all pages
            'js' => [],
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => [
                'data' => [
                    'productlotcard',
                    'invoicecard',
                    'propalcard',
                    'ordercard',
                    'paiementcard',
                    'productlotcard',
                    'registrationcertificatefrcard',
                    'dolicar_quickcreation',
                    'saturnegetobjectsmetadata',
                    'propallist',
                    'orderlist',
                    'invoicelist',
                    'main',
                    'publiccontrol'
                ]
            ],
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        ];

        // Data directories to create when module is enabled
        // Example: this->dirs = array("/dolicar/temp","/dolicar/subdir");
        $this->dirs = ['/dolicar/temp'];

        // Config pages. Put here list of php page, stored into dolicar/admin directory, to use to set up module
        $this->config_page_url = ['setup.php@dolicar'];

        // Dependencies
        // A condition to hide module
        $this->hidden = false;

        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends      = ['modProduct', 'modProductBatch', 'modFacture', 'modPropale', 'modCommande', 'modCategorie', 'modSaturne', 'modProjet'];
        $this->requiredby   = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        // The language file dedicated to your module
        $this->langfiles = ['dolicar@dolicar'];

        // Prerequisites
        $this->phpmin                = [7, 4]; // Minimum version of PHP required by module
        $this->need_dolibarr_version = [20, 0]; // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation     = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('DoliSIRH_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('DoliSIRH_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $i = 0;
        $this->const = [
            // CONST REGISTRATION CERTIFICATE
            $i++ => ['DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON', 'chaine', 'mod_registrationcertificatefr_standard', '', 0, 'current'],
            $i++ => ['DOLICAR_DEFAULT_WAREHOUSE_ID', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_TAGS_SET', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_DEFAULT_VEHICLE_SET', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_DEFAULT_VEHICLE', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_VEHICLE_TAG', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_HIDE_REGISTRATIONCERTIFICATE_FIELDS', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_API_REMAINING_REQUESTS_COUNTER', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_API_REQUESTS_COUNTER', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_B_FIRST_REGISTRATION_DATE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_D1_VEHICLE_BRAND_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_D2_VEHICLE_TYPE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_D21_VEHICLE_CNIT_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_D3_VEHICLE_MODEL_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_E_VEHICLE_SERIAL_NUMBER_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_I_VEHICLE_REGISTRATION_DATE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_J1_NATIONAL_TYPE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_P1_CYLINDER_CAPACITY_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_P3_FUEL_TYPE_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_P6_NATIONAL_ADMINISTRATIVE_POWER_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_S1_SEATING_CAPACITY_VISIBLE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLICAR_V7_CO2_EMISSION_VISIBLE', 'integer', 1, '', 0, 'current'],

            // CONST PUBLIC INTERFACE
            $i++ => ['DOLICAR_PUBLIC_INTERFACE_USE_SIGNATORY', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLICAR_PUBLIC_MAX_ARRIVAL_MILEAGE', 'integer', 1000, '', 0, 'current'],

            // CONST MODULE
            $i++ => ['DOLICAR_VERSION','chaine', $this->version, '', 0, 'current'],
            $i++ => ['DOLICAR_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
            $i   => ['DOLICAR_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
        ];

        if (!isset($conf->dolicar) || !isset($conf->dolicar->enabled)) {
            $conf->dolicar = new stdClass();
            $conf->dolicar->enabled = 0;
        }

        // Array to add new pages in new tabs
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@dolicar:$user->rights->othermodule->read:/dolicar/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');
        $this->tabs   = [];
        $pictoPath    = dol_buildpath('custom/dolicar/img/dolicar_color.png', 1);
        $pictoModule  = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
        $this->tabs[] = ['data' => 'productlot:+registrationcertificatefr:' . $pictoModule . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=productlot'];
        $this->tabs[] = ['data' => 'thirdparty:+registrationcertificatefr:' . $pictoModule . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=thirdparty'];
        $this->tabs[] = ['data' => 'product:+registrationcertificatefr:' . $pictoModule . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=product'];
        $this->tabs[] = ['data' => 'project:+registrationcertificatefr:' . $pictoModule . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=project'];

        // Dictionaries
        $this->dictionaries = [];

        // Boxes/Widgets
        // Add here list of php file(s) stored in dolicar/core/boxes that contains a class to show a widget
        $this->boxes = [];

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = [];

        // Permissions provided by this module
        $this->rights = [];
        $r = 0;

        /* DOLICAR PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->trans('LireModule', 'DoliCar');    // Permission label
        $this->rights[$r][4] = 'lire';                                               // In php code, permission will be checked by test if ($user->rights->dolicar->level1->level2)
        $r++;

        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadModule', 'DoliCar');
        $this->rights[$r][4] = 'read';
        $r++;

        /* REGISTRATIONCERTIFICATEFR PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects', $langs->trans('RegistrationCertificatesFrMin'));
        $this->rights[$r][4] = 'registrationcertificatefr';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->trans('RegistrationCertificatesFrMin'));
        $this->rights[$r][4] = 'registrationcertificatefr';
        $this->rights[$r][5] = 'write';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->trans('RegistrationCertificatesFrMin'));
        $this->rights[$r][4] = 'registrationcertificatefr';
        $this->rights[$r][5] = 'delete';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'DoliCar');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';

        // Main menu entries to add
        $this->menu = [];
        $r = 0;

        // Add here entries to declare new menus
        // DOLICAR MENU
        $this->menu[$r++] = [
            'fk_menu'  => '',                                           // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'top',                                        // This is a Top menu entry
            'titre'    => 'DoliCar',
            'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => '',
            'url'      => '/dolicar/dolicarindex.php',
            'langs'    => 'dolicar@dolicar',                             // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',                     // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled.
            'perms'    => '$user->rights->dolicar->lire',
            'target'   => '',
            'user'     => 0                                              // 0=Menu for internal users, 1=external users, 2=both
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Dashboard'),
            'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => '',
            'url'      => '/dolicar/dolicarindex.php',
            'langs'    => 'dolicar@dolicar',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->dolicar->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Registrationcertificatefrs'),
            'prefix'   => '<i class="fas fa-car pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'registrationcertificatefr',
            'url'      => '/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php',
            'langs'    => 'dolicar@dolicar',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->dolicar->registrationcertificatefr->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('QuickCreation'),
            'prefix'   => '<i class="fas fa-plus-circle pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'quickcreation',
            'url'      => '/dolicar/view/registrationcertificatefr/quickcreation.php',
            'langs'    => 'dolicar@dolicar',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easycrm->enabled',
            'perms'    => '$user->rights->dolicar->read',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('ThirdParty'),
            'prefix'   => '<i class="fas fa-building pictofixedwidth" style=" color: #6c6aa8;"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'thirdparty',
            'url'      => '/societe/index.php?mainmenu=companies&leftmenu=thirdparties',
            'langs'    => 'companies',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->societe->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Proposal'),
            'prefix'   => '<i class="fas fa-file-signature infobox-propal pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'propal',
            'url'      => '/comm/propal/index.php?mainmenu=commercial&leftmenu=propals',
            'langs'    => 'propal',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->propale->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Invoice'),
            'prefix'   => '<i class="fas fa-file-invoice-dollar infobox-commande pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'invoice',
            'url'      => '/compta/facture/index.php?mainmenu=billing&leftmenu=customers_bills',
            'langs'    => 'bills',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->propale->lire',
            'target'   => '',
            'user'     => 2
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Order'),
            'prefix'   => '<i class="fas fa-file-invoice infobox-commande pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'order',
            'url'      => '/commande/index.php?mainmenu=commercial&leftmenu=orders',
            'langs'    => 'orders',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->commande->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Product'),
            'prefix'   => '<i class="fas fa-cube pictofixedwidth" style="color : #a69944"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'product',
            'url'      => '/product/index.php?mainmenu=products&leftmenu=product&type=0',
            'langs'    => 'products',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->produit->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Batch'),
            'prefix'   => '<i class="fas fa-barcode pictofixedwidth" style=" color: #a69944;"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'produtlot',
            'url'      => '/product/stock/productlot_list.php?mainmenu=products',
            'langs'    => 'productbatch',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled',
            'perms'    => '$user->rights->produit->lire && $user->rights->stock->lire',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolicar',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('PublicInterface'),
            'prefix'   => '<i class="fas fa-globe pictofixedwidth"></i>',
            'mainmenu' => 'dolicar',
            'leftmenu' => 'public_interface',
            'url'      => '/custom/dolicar/public/agenda/public_vehicle_logbook.php?entity=' . $conf->entity,
            'langs'    => 'dolicar@dolicar',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolicar->enabled && $conf->global->SATURNE_ENABLE_PUBLIC_INTERFACE',
            'perms'    => 1,
            'target'   => '',
            'user'     => 0
        ];
    }

    /**
     * Function called when module is enabled
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database
     * It also creates data directories
     *
     * @param  string     $options Options when enabling module ('', 'noboxes')
     * @return int                 1 if OK, 0 if KO
     * @throws Exception
     */
    public function init($options = ''): int
    {
        global $conf, $langs, $user;

        // Permissions
        $this->remove($options);

        $sql = [];

        $result = $this->_load_tables('/dolicar/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        dolibarr_set_const($this->db, 'DOLICAR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, 'DOLICAR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

        // Create extrafields during init
        $commonExtraFieldsValue = ['entity' => 0, 'langfile' => 'dolicar@dolicar', 'enabled' => "isModEnabled('dolicar')"];

        $extraFieldsArrays = [
            'registration_number'       => ['Label' => 'RegistrationNumber',        'type' => 'varchar', 'length' => 255, 'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 10, 'list' => 5],
            'vehicle_model'             => ['Label' => 'VehicleModel',              'type' => 'varchar', 'length' => 255, 'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 20, 'list' => 5],
            'VIN_number'                => ['Label' => 'VINNumber',                 'type' => 'varchar', 'length' => 128, 'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 30, 'list' => 5],
            'first_registration_date'   => ['Label' => 'FirstRegistrationDate',     'type' => 'date',                     'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 40, 'list' => 5],
            'mileage'                   => ['Label' => 'Mileage',                   'type' => 'int',                      'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 50, 'list' => 1, 'alwayseditable' => 1],
            'registrationcertificatefr' => ['Label' => 'RegistrationCertificateFr', 'type' => 'link',                     'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 60, 'list' => 1, 'alwayseditable' => 1, 'params' => ['RegistrationCertificateFr:dolicar/class/registrationcertificatefr.class.php' => NULL]],
            'linked_product'            => ['Label' => 'LinkedProduct',             'type' => 'link',                     'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 70, 'list' => 5,                        'params' => ['Product:product/class/product.class.php:0:(t.entity:=:__ENTITY__) AND (t.fk_product_type:=:0)' => NULL]],
            'linked_lot'                => ['Label' => 'LinkedProductBatch',        'type' => 'link',                     'elementtype' => ['propal', 'commande', 'facture'], 'position' => $this->numero . 80, 'list' => 5,                        'params' => ['ProductLot:product/stock/class/productlot.class.php:0:(t.entity:=:__ENTITY__)' => NULL]],

            'starting_mileage' => ['Label' => 'StartingMileage', 'type' => 'int',  'elementtype' => ['actioncomm'], 'position' => 10, 'alwayseditable' => 1, 'list' => 1, 'enabled' => "isModEnabled('dolicar') && isModEnabled('agenda')"],
            'arrival_mileage'  => ['Label' => 'ArrivalMileage',  'type' => 'int',  'elementtype' => ['actioncomm'], 'position' => 20, 'alwayseditable' => 1, 'list' => 1, 'enabled' => "isModEnabled('dolicar') && isModEnabled('agenda')"],
            'json'             => ['Label' => 'JSON',            'type' => 'text', 'elementtype' => ['actioncomm'], 'position' => 30, 'alwayseditable' => 1, 'list' => 0, 'enabled' => "isModEnabled('dolicar') && isModEnabled('agenda')"]
        ];

        saturne_manage_extrafields($extraFieldsArrays, $commonExtraFieldsValue);

        if (getDolGlobalInt('DOLICAR_EXTRAFIELDS_BACKWARD_COMPATIBILITY') == 0) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extraFields = new ExtraFields($this->db);

            $extraFieldsArrays = [
                'registration_number'       =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'vehicle_model'             =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'VIN_number'                =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'first_registration_date'   =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'mileage'                   =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'registrationcertificatefr' =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'linked_product'            =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']],
                'linked_lot'                =>  ['elementtype' => ['propaldet', 'commandedet', 'facturedet']]
            ];

            foreach ($extraFieldsArrays as $key => $extraField) {
                foreach ($extraField['elementtype'] as $extraFieldElementType) {
                    $extraFields->delete($key, $extraFieldElementType);
                }
            }
            dolibarr_set_const($this->db, 'DOLICAR_EXTRAFIELDS_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
        }

        // Warehouse
        if (getDolGlobalInt('DOLICAR_DEFAULT_WAREHOUSE_ID') <= 0) {
            require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

            $wareHouse = new Entrepot($this->db);

            $wareHouse->ref    = $langs->transnoentities('DoliCarWarehouse');
            $wareHouse->label  = $langs->transnoentities('DoliCarWarehouse');
            $wareHouse->statut = Entrepot::STATUS_OPEN_ALL;

            $wareHouseID = $wareHouse->create($user);

            dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_WAREHOUSE_ID', $wareHouseID, 'integer', 0, '', $conf->entity);
        }

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $category = new Categorie($this->db);

        // Categorie
        if (getDolGlobalInt('DOLICAR_TAGS_SET') == 0) {
            $category->label = $langs->transnoentities('Vehicle');
            $category->type  = 'product';
            $vehicleTagID    = $category->create($user);

            if ($vehicleTagID > 0) {
                $category->label     = $langs->transnoentities('Car');
                $category->fk_parent = $vehicleTagID;
                $category->create($user);

                $category->label     = $langs->transnoentities('Truck');
                $category->fk_parent = $vehicleTagID;
                $category->create($user);

                $category->label     = $langs->transnoentities('Bicycle');
                $category->fk_parent = $vehicleTagID;
                $category->create($user);

                $category->label     = $langs->transnoentities('CommercialVehicle');
                $category->fk_parent = $vehicleTagID;
                $category->create($user);

                dolibarr_set_const($this->db, 'DOLICAR_VEHICLE_TAG', $vehicleTagID, 'integer', 0, '', $conf->entity);
            }

            dolibarr_set_const($this->db, 'DOLICAR_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
        }

        // Car brands tag
        if (getDolGlobalInt('DOLICAR_CAR_BRANDS_TAG_SET') == 0) {
            $category->label = $langs->transnoentities('Brands');
            $category->type  = 'product';
            $brandTagID      = $category->create($user);

            if ($brandTagID > 0) {
                $filename = DOL_DOCUMENT_ROOT . '/custom/dolicar/core/car_brands.txt';
                $file     = fopen($filename, 'r');
                if ($file) {
                    while (($line = fgets($file)) !== false) {
                        $category->label     = $langs->transnoentities($line);
                        $category->fk_parent = $brandTagID;
                        $category->create($user);
                    }
                    fclose($file);
                }

                $category->label     = $langs->transnoentities('DefaultBrand');
                $category->fk_parent = $brandTagID;
                $defaultBrandTagID   = $category->create($user);

                dolibarr_set_const($this->db, 'DOLICAR_CAR_DEFAULT_BRAND_TAG', $defaultBrandTagID, 'integer', 0, '', $conf->entity);
                dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG', $brandTagID, 'integer', 0, '', $conf->entity);
                dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG_SET', 1, 'integer', 0, '', $conf->entity);
            }
        }

        // Default product
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        $product = new Product($this->db);

        if (getDolGlobalInt('DOLICAR_DEFAULT_VEHICLE_SET') == 0) {
            // In order to avoid product creation error
            $conf->global->BARCODE_PRODUCT_ADDON_NUM = 0;

            $product->ref          = $langs->transnoentities('DefaultVehicle');
            $product->label        = $langs->transnoentities('DefaultVehicle');
            $product->status_batch = 1;
            $defaultVehicleID      = $product->create($user);

            if ($defaultVehicleID > 0) {
                dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE', $defaultVehicleID, 'integer', 0, '', $conf->entity);

                $category->fetch(getDolGlobalInt('DOLICAR_VEHICLE_TAG'));
                $category->add_type($product, 'product');
                $category->fetch(getDolGlobalInt('DOLICAR_CAR_DEFAULT_BRAND_TAG'));
                $category->add_type($product, 'product');

                dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE_SET', 1, 'integer', 0, '', $conf->entity);
            }
        }

        return $this->_init($sql, $options);
    }
}
