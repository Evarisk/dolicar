<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 		Eoxia 					<dev@eoxia.com>
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
 * 	\defgroup   dolicar     Module DoliCar
 *  \brief      DoliCar module descriptor.
 *
 *  \file       htdocs/custom/dolicar/core/modules/modDoliCar.class.php
 *  \ingroup    dolicar
 *  \brief      Description and activation file for module DoliCar
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DoliCar
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
		global $langs, $conf;

		if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
			require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
			saturne_load_langs(['dolicar@dolicar']);
		} else {
			$this->error++;
			$this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'DoliCar', 'Saturne');
		}

		$this->db = $db;
		$this->numero = 436380; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'dolicar';
		$this->family = "";
		$this->module_position = '';
		$this->familyinfo = array('Evarisk' => array('position' => '01', 'label' => $langs->trans("Evarisk")));
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans("DoliCarDescription");
		$this->descriptionlong = $langs->trans("DoliCarDescription");
		$this->editor_name = 'Evarisk';
		$this->editor_url = 'https://www.evarisk.com';
		$this->version = '1.1.1';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'dolicar_color@dolicar';

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 1,
			'printing' => 0,
			'theme' => 0,
			'css' => array(
			),
			'js' => array(
			),
			'hooks' => array(
				'productlotcard',
				'invoicecard',
				'propalcard',
				'ordercard',
                'paiementcard',
				'productlotcard',
				'registrationcertificatefrcard',
				'dolicar_quickcreation',
				'get_sheet_linkable_objects',

			),
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		$this->dirs = array("/dolicar/temp");

		// Config pages. Put here list of php page, stored into dolicar/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@dolicar");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('modProduct', 'modProductBatch', 'modFacture', 'modPropale', 'modCommande', 'modCategorie', 'modSaturne', 'modProjet');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("dolicar@dolicar");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)

		$i = 0;
		$this->const = array(
			// CONST REGISTRATION CERTIFICATE
			$i++ => array('DOLICAR_DEFAULT_PROJECT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_DEFAULT_WAREHOUSE_ID', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_TAGS_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_DEFAULT_VEHICLE_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_DEFAULT_VEHICLE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_VEHICLE_TAG', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_MENU_DEFAULT_VEHICLE_UPDATED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DOLICAR_HIDE_REGISTRATIONCERTIFICATE_FIELDS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS', 'integer', 1, '', 0, 'current'),
            $i++ => array('DOLICAR_A_REGISTRATION_NUMBER_VISIBLE', 'integer', 1, '', 0, 'current'),
            $i++ => array('DOLICAR_API_REMAINING_REQUESTS_COUNTER', 'integer', 0, '', 0, 'current'),
            $i++ => array('DOLICAR_API_REQUESTS_COUNTER', 'integer', 0, '', 0, 'current'),

			// CONST MODULE
			$i++ => ['DOLICAR_VERSION','chaine', $this->version, '', 0, 'current'],
			$i++ => ['DOLICAR_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
			$i++ => ['DOLICAR_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],

		);

		if (!isset($conf->dolicar) || !isset($conf->dolicar->enabled)) {
			$conf->dolicar = new stdClass();
			$conf->dolicar->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		$pictopath    = dol_buildpath('/custom/dolicar/img/dolicar_color.png', 1);
		$picto        = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');
		$this->tabs[] = array('data' => 'productlot:+registrationcertificatefr:' . $picto . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=productlot');
		$this->tabs[] = array('data' => 'thirdparty:+registrationcertificatefr:' . $picto . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=thirdparty');
		$this->tabs[] = array('data' => 'product:+registrationcertificatefr:' . $picto . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=product');
		$this->tabs[] = array('data' => 'project:+registrationcertificatefr:' . $picto . ucfirst($langs->trans('RegistrationCertificateFr')) . ':dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=project');

		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'dolicar@dolicar',
			// List of tables we want to see into dictonnary editor
			'tabname' => array(
				MAIN_DB_PREFIX . "c_car_brands",
			),
			// Label of tables
			'tablib' => array(
				"CarBrands"
			),
			// Request to select fields
			'tabsql' => array(
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'c_car_brands as f',
			),
			// Sort order
			'tabsqlsort' => array(
				"label ASC"
			),
			// List of fields (result of select to show dictionary)
			'tabfield' => array(
				"ref,label,description"
			),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue' => array(
				"ref,label,description"
			),
			// List of fields (list of fields for insert)
			'tabfieldinsert' => array(
				"ref,label,description"
			),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid' => array(
				"rowid"
			),
			// Condition to show each dictionary
			'tabcond' => array(
				$conf->dolicar->enabled
			)
		);

		// Boxes/Widgets
		// Add here list of php file(s) stored in dolicar/core/boxes that contains a class to show a widget.
		$this->boxes = array(
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
		);

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('LireModule', 'DoliCar');
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = 1;
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadModule', 'DoliCar');
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = 1;
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObject', $langs->trans('RegistrationCertificatesFrMin')); // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObject', $langs->trans('RegistrationCertificatesFrMin')); // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObject', $langs->trans('RegistrationCertificatesFrMin')); // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->delete)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'DoliCar');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->delete)
		$r++;

		$langs->load("dolicar@dolicar");

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'DoliCar',
			'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'',
			'url'=>'/dolicar/dolicarindex.php',
			'langs'=>'dolicar@dolicar', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->dolicar->enabled', // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled.
			'perms' => '$user->rights->dolicar->lire',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=dolicar', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Top menu entry
			'titre'=> $langs->trans('Dashboard'),
			'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'',
			'url'=>'/dolicar/dolicarindex.php',
			'langs'=>'dolicar@dolicar', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->dolicar->enabled', // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled.
			'perms' => '$user->rights->dolicar->lire',
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=dolicar',
            // This is a Left menu entry
            'type'=>'left',
            'titre' => $langs->trans('ListRegistrationCertificateFr'),
			'prefix'   => '<i class="fas fa-car pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
            'leftmenu'=>'dolicar_registrationcertificatefr',
            'url'=>'/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'dolicar@dolicar',
            'position'=>1000+$r,
            // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->dolicar->enabled',
            // Use 'perms'=>'$user->rights->dolicar->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=dolicar', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', // This is a Top menu entry
			'titre'    => $langs->transnoentities('QuickCreation'),
			'prefix'   => '<i class="fas fa-plus-circle pictofixedwidth"></i>',
			'mainmenu' => 'dolicar',
			'leftmenu' => 'quickcreation',
			'url'      => '/dolicar/view/registrationcertificatefr/quickcreation.php', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'langs'    => 'dolicar@dolicar',
			'position' => 1000 + $r,
			'enabled'  => '$conf->easycrm->enabled', // Define condition to show or hide menu entry. Use '$conf->easycrm->enabled' if entry must be visible if module is enabled.
			'perms'    => '$user->rights->dolicar->read', // Use 'perms'=>'$user->rights->easycrm->myobject->read' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0, // 0=Menu for internal users, 1=external users, 2=both
		];
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('ThirdParty'),
			'prefix'   => '<i class="fas fa-building paddingright pictofixedwidth" style=" color: #6c6aa8;"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_companies',
			'url'=>'/societe/index.php?mainmenu=companies',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('Propale'),
			'prefix'   => '<i class="fas fa-file-signature infobox-propal paddingright pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_propales',
			'url'=>'/comm/propal/index.php?mainmenu=commercial&leftmenu=propals',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('Invoice'),
			'prefix'   => '<i class="fas fa-file-invoice-dollar infobox-commande paddingright pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_invoices',
			'url'=>'/compta/facture/index.php?mainmenu=billing&leftmenu=customers_bills',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('Order'),
			'prefix'   => '<i class="fas fa-file-invoice infobox-commande paddingright pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_orders',
			'url'=>'/commande/index.php?mainmenu=commercial&leftmenu=orders',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('Product'),
			'prefix'   => '<i class="fas fa-cube pictofixedwidth" style="color : #a69944"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_products',
			'url'=>'/product/index.php?mainmenu=products',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=dolicar',
			'type' => 'left',
			'titre' => $langs->trans('Lot'),
			'prefix'   => '<i class="fas fa-barcode paddingright pictofixedwidth"></i>',
			'mainmenu'=>'dolicar',
			'leftmenu'=>'dolicar_lot',
			'url'=>'/product/index.php?mainmenu=products',
			'langs'=>'dolicar@dolicar',
			'position'=>1050+$r,
			'enabled'=>'$conf->dolicar->enabled',
			'perms'=>'1',
			'target'=>'',
			'user'=>2
		);
		/* END MODULEBUILDER LEFTMENU REGISTRATIONCERTIFICATEFR */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user;

		$langs->load('dolicar@dolicar');

		if ($this->error > 0) {
			setEventMessages('', $this->errors, 'errors');
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		dolibarr_set_const($this->db, 'DOLICAR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'DOLICAR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

		//$result = $this->_load_tables('/install/mysql/tables/', 'dolicar');
		$result = $this->_load_tables('/dolicar/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$extrafields->addExtraField('registrationcertificate_metada', $langs->transnoentities("RegistrationCertificateMetadata"), 'text', 1080, '', 'product_lot', 0, 0, '', '', 1, '', 1);

		// // Facture extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'facture', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'facture', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'facture', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'facture', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'facture', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'facture', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'facture', 0, 0, '', '', 1, '', 1);

		// Facturedet extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'facturedet', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'facturedet', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'facturedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'facturedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'facturedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'facturedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'facturedet', 0, 0, '', '', 1, '', 1);

		// Propal extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'propal', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'propal', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'propal', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'propal', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'propal', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'propal', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'propal', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'propal', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'propal', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'propal', 0, 0, '', '', 1, '', 1);

		// Propaldet extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'propaldet', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'propaldet', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'propaldet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'propaldet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'propaldet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'propaldet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'propaldet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'propaldet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'propaldet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'propaldet', 0, 0, '', '', 1, '', 1);

		// Commande extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'commande', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'commande', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'commande', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'commande', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'commande', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'commande', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'commande', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'commande', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'commande', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'commande', 0, 0, '', '', 1, '', 1);

		// Commandedet extrafields
		// Update
		$extrafields->update('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar','255', 'commandedet', 0, 0, 1040, '', 1,'', 1);
		$extrafields->update('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', '255', 'commandedet', 0, 0, 1070, 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}',1, '', 1);

		// Add
		$extrafields->addExtraField('registrationcertificatefr', $langs->transnoentities("RegistrationCertificateFr"), 'sellist', 1030, '', 'commandedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:80:"dolicar_registrationcertificatefr:a_registration_number:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extrafields->addExtraField('vehicle_model', $langs->transnoentities("VehicleModel"), 'varchar', 1040, '255', 'commandedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1050, '', 'commandedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('registration_number', $langs->transnoentities("RegistrationNumber"), 'varchar', 1060, '255', 'commandedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('linked_product', $langs->transnoentities("LinkedProduct"), 'sellist', 1070, '255', 'commandedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:36:"product:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('linked_lot', $langs->transnoentities("LinkedProductBatch"), 'sellist', 1080, '255', 'commandedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"product_lot:batch:rowid::entity = $ENTITY$";N;}}', 1, '', 1);
		$extrafields->addExtraField('first_registration_date', $langs->transnoentities("FirstRegistrationDate"), 'varchar', 1090, '255', 'commandedet', 0, 0, '', '', 1, '', 1);
		$extrafields->addExtraField('VIN_number', $langs->transnoentities("VINNumber"), 'varchar', 1100, '255', 'commandedet', 0, 0, '', '', 1, '', 1);

		// Permissions
		$this->remove($options);

		$sql = array();

		require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
		$warehouse = new Entrepot($this->db);

		//Warehouse
		if ($conf->global->DOLICAR_DEFAULT_WAREHOUSE_ID <= 0) {
			$warehouse->ref = $langs->trans('DolicarWarehouse');
			$warehouse->label = $langs->trans('DolicarWarehouse');
			$warehouse_id = $warehouse->create($user);
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_WAREHOUSE_ID', $warehouse_id, 'integer', 0, '', $conf->entity);
		}

		if ($conf->global->DOLICAR_DEFAULT_WAREHOUSE_STATUS_UPDATED == 0) {
			$warehouse->fetch($conf->global->DOLICAR_DEFAULT_WAREHOUSE_ID);
			$warehouse->statut = 1;
			$warehouseUpdated = $warehouse->update($warehouse->id, $user);
			if ($warehouseUpdated > 0) {
				dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_WAREHOUSE_STATUS_UPDATED', 1, 'integer', 0, '', $conf->entity);
			}
		}

		//Categorie
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$tag = new Categorie($this->db);

		if ($conf->global->DOLICAR_TAGS_SET == 0) {

			$tag->label = $langs->transnoentities('Vehicle');
			$tag->type = 'product';
			$vehicleTag = $tag->create($user);

			if ($vehicleTag > 0) {
				$tag->label = $langs->transnoentities('Car');
				$tag->type = 'product';
				$tag->fk_parent = $vehicleTag;
				$tag->create($user);

				$tag->label = $langs->transnoentities('Truck');
				$tag->type = 'product';
				$tag->fk_parent = $vehicleTag;
				$tag->create($user);

				$tag->label = $langs->transnoentities('Bicycle');
				$tag->type = 'product';
				$tag->fk_parent = $vehicleTag;
				$tag->create($user);

				$tag->label = $langs->transnoentities('CommercialVehicle');
				$tag->type = 'product';
				$tag->fk_parent = $vehicleTag;
				$tag->create($user);

				dolibarr_set_const($this->db, 'DOLICAR_VEHICLE_TAG', $vehicleTag, 'integer', 0, '', $conf->entity);

			}
			dolibarr_set_const($this->db, 'CATEGORIE_RECURSIV_ADD', 1, 'integer', 0, '', $conf->entity);
			dolibarr_set_const($this->db, 'DOLICAR_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
		}
		if ($conf->global->CATEGORIE_RECURSIV_ADD == 0) {
			dolibarr_set_const($this->db, 'CATEGORIE_RECURSIV_ADD', 1, 'integer', 0, '', $conf->entity);
		}

		if ($conf->global->DOLICAR_VEHICLE_TAG == 0) {
			$tag->rechercher(0, $langs->transnoentities('Car'), 'product');
			if ($tag->id > 0) {
				dolibarr_set_const($this->db, 'DOLICAR_VEHICLE_TAG', $tag->id, 'integer', 0, '', $conf->entity);
			}
		}

		//Car brands tag
		if ($conf->global->DOLICAR_CAR_BRANDS_TAG_SET == 0) {

			$tag->label = $langs->transnoentities('Brands');
			$tag->type = 'product';
			$brandTag = $tag->create($user);

			if ($brandTag > 0) {

				$filename = DOL_DOCUMENT_ROOT . '/custom/dolicar/core/car_brands.txt';
				$file = fopen( $filename, "r" );
				if ($file) {
					while (($line = fgets($file)) !== false) {
						$tag->label = $langs->transnoentities($line);
						$tag->type = 'product';
						$tag->fk_parent = $brandTag;
						$tag->create($user);
					}
					fclose($file);
				}

				$tag->label = $langs->transnoentities('DefaultBrand');
				$tag->type = 'product';
				$tag->fk_parent = $brandTag;
				$defaultBrandTag = $tag->create($user);

				dolibarr_set_const($this->db, 'DOLICAR_CAR_DEFAULT_BRAND_TAG', $defaultBrandTag, 'integer', 0, '', $conf->entity);
				dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG', $brandTag, 'integer', 0, '', $conf->entity);
				dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG_SET', 1, 'integer', 0, '', $conf->entity);
			}
		} elseif ($conf->global->DOLICAR_CAR_BRANDS_TAG_SET == 1 && $conf->global->DOLICAR_CAR_DEFAULT_BRAND_TAG == 0) {
			$tag->label = $langs->transnoentities('DefaultBrand');
			$tag->type = 'product';
			$tag->fk_parent = $conf->global->DOLICAR_CAR_BRANDS_TAG;
			$defaultBrandTag = $tag->create($user);
			dolibarr_set_const($this->db, 'DOLICAR_CAR_DEFAULT_BRAND_TAG', $defaultBrandTag, 'integer', 0, '', $conf->entity);
		}

		// Default product
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$product = new Product($this->db);

		if ($conf->global->DOLICAR_DEFAULT_VEHICLE_SET == 0) {
			$product->ref = $langs->transnoentities('DefaultVehicle');
			$product->label = $langs->transnoentities('DefaultVehicle');
			$product->status_batch = 1;
			$defaultVehicle = $product->create($user);

			if ($defaultVehicle > 0) {
				dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE', $defaultVehicle, 'integer', 0, '', $conf->entity);
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

				$tag->fetch($conf->global->DOLICAR_VEHICLE_TAG);
				$tag->add_type($product, 'product');
				$tag->fetch($conf->global->DOLICAR_CAR_DEFAULT_BRAND_TAG);
				$tag->add_type($product, 'product');
			}
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE_SET', 1, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DOLICAR_DEFAULT_VEHICLE_SET == 1) {
			$product->fetch($conf->global->DOLICAR_DEFAULT_VEHICLE);
			$tag->fetch($conf->global->DOLICAR_CAR_DEFAULT_BRAND_TAG);
			$tag->add_type($product, 'product');
			$product->status_batch = 1;
			$product->update($product->id, $user);
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE_SET', 2, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DOLICAR_DEFAULT_VEHICLE_SET == 2) {
			$product->fetch($conf->global->DOLICAR_DEFAULT_VEHICLE);
			$product->status_batch = 1;
			$product->update($product->id, $user);
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE_SET', 3, 'integer', 0, '', $conf->entity);
		}
		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
