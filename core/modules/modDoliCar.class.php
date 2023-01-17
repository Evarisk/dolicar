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
		$this->db = $db;
		$this->numero = 436380; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'dolicar';
		$this->family = "";
		$this->module_position = '';
		$this->familyinfo = array('Eoxia' => array('position' => '01', 'label' => $langs->trans("Eoxia")));
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans("DoliCarDescription");
		$this->descriptionlong = $langs->trans("DoliCarDescription");
		$this->editor_name = 'Eoxia';
		$this->editor_url = 'https://www.eoxia.com';
		$this->version = '0.0.1';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'dolicar256px@dolicar';

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
				'productlotcard',
				'registrationcertificatefrcard'
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
		$this->depends = array('modProduct', 'modProductBatch', 'modFacture', 'modPropale', 'modCommande', 'modCategorie');
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

		$this->const = array(
			1 => array('DOLICAR_DEFAULT_PROJECT', 'integer', 0, '', 0, 'current'),
			2 => array('DOLICAR_DEFAULT_WAREHOUSE', 'integer', 0, '', 0, 'current'),
			3 => array('DOLICAR_TAGS_SET', 'integer', 0, '', 0, 'current'),
			4 => array('DOLICAR_DEFAULT_VEHICLE_SET', 'integer', 0, '', 0, 'current'),
			5 => array('DOLICAR_DEFAULT_VEHICLE', 'integer', 0, '', 0, 'current'),
			6 => array('DOLICAR_VEHICLE_TAG', 'integer', 0, '', 0, 'current'),
			7 => array('DOLICAR_MENU_DEFAULT_VEHICLE_UPDATED', 'integer', 0, '', 0, 'current'),
			8 => array('DOLICAR_HIDE_REGISTRATIONCERTIFICATE_FIELDS', 'integer', 1, '', 0, 'current'),
			9 => array('DOLICAR_HIDE_OBJECT_DET_DOLICAR_DETAILS', 'integer', 1, '', 0, 'current'),
		);

		if (!isset($conf->dolicar) || !isset($conf->dolicar->enabled)) {
			$conf->dolicar = new stdClass();
			$conf->dolicar->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		$this->tabs[] = array('data' => 'productlot:+registrationcertificatefr:RegistrationCertificateFr:dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=productlot');
		$this->tabs[] = array('data' => 'thirdparty:+registrationcertificatefr:RegistrationCertificateFr:dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=thirdparty');
		$this->tabs[] = array('data' => 'product:+registrationcertificatefr:RegistrationCertificateFr:dolicar@dolicar:$user->rights->dolicar->registrationcertificatefr->read:/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php?fromid=__ID__&fromtype=product');

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
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of DoliCar'; // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of DoliCar'; // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of DoliCar'; // Permission label
		$this->rights[$r][4] = 'registrationcertificatefr';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolicar->registrationcertificatefr->delete)
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read admin page of DoliCar'; // Permission label
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
			'titre'=>'ModuleDoliCarName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'dolicar',
			'leftmenu'=>'',
			'url'=>'/dolicar/dolicarindex.php',
			'langs'=>'dolicar@dolicar', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->dolicar->enabled', // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolicar->registrationcertificatefr->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);

        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=dolicar',
            // This is a Left menu entry
            'type'=>'left',
            'titre' => $langs->trans('ListRegistrationCertificateFr'),
            'mainmenu'=>'dolicar',
            'leftmenu'=>'dolicar_registrationcertificatefr',
            'url'=>'/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'dolicar@dolicar',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->dolicar->enabled',
            // Use 'perms'=>'$user->rights->dolicar->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu' => 'fk_mainmenu=dolicar,fk_leftmenu=dolicar_registrationcertificatefr',
            // This is a Left menu entry
            'type' => 'left',
            'titre' => $langs->trans('NewRegistrationCertificateFr'),
            'mainmenu'=>'dolicar',
            'leftmenu'=>'dolicar_registrationcertificatefr',
            'url'=>'/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php?action=create&fk_product='.$conf->global->DOLICAR_DEFAULT_VEHICLE,
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'dolicar@dolicar',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->dolicar->enabled',
            // Use 'perms'=>'$user->rights->dolicar->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=dolicar',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => '<i class="fas fa-cog"></i>  ' . $langs->trans('DolicarConfig'),
			'mainmenu' => 'dolicar',
			'leftmenu' => 'dolicarconfig',
			'url' => '/dolicar/admin/setup.php',
			'langs' => 'dolicar@dolicar',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled' => '$conf->dolicar->enabled',  // Define condition to show or hide menu entry. Use '$conf->dolicar->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->rights->dolicar->adminpage->read',			                // Use 'perms'=>'$user->rights->dolicar->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 0,				                // 0=Menu for internal users, 1=external users, 2=both
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

		//$result = $this->_load_tables('/install/mysql/tables/', 'dolicar');
		$result = $this->_load_tables('/dolicar/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
//		$extrafields->addExtraField('mileage', $langs->transnoentities("Mileage"), 'int', 1010, '', 'product_lot', 0, 0, '', '', 1, '', 1);

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

		// Permissions
		$this->remove($options);

		$sql = array();

		//Warehouse
		if ($conf->global->DOLICAR_DEFAULT_WAREHOUSE == 0) {
			require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
			$warehouse = new Entrepot($this->db);
			$warehouse->ref = $langs->trans('ClientWarehouse');
			$warehouse->label = $langs->trans('ClientWarehouse');
			$warehouse_id = $warehouse->create($user);
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_WAREHOUSE', $warehouse_id, 'integer', 0, '', $conf->entity);
		}

		//Categorie
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$tag = new Categorie($this->db);

		if ($conf->global->DOLICAR_TAGS_SET == 0) {

			$tag->label = $langs->transnoentities('Vehicle');
			$tag->type = 'product';
			$result = $tag->create($user);

			if ($result > 0) {
				$tag->label = $langs->transnoentities('Car');
				$tag->type = 'product';
				$tag->fk_parent = $result;
				$tag->create($user);

				$tag->label = $langs->transnoentities('Truck');
				$tag->type = 'product';
				$tag->fk_parent = $result;
				$tag->create($user);

				$tag->label = $langs->transnoentities('Bicycle');
				$tag->type = 'product';
				$tag->fk_parent = $result;
				$tag->create($user);

				$tag->label = $langs->transnoentities('CommercialVehicle');
				$tag->type = 'product';
				$tag->fk_parent = $result;
				$tag->create($user);

				dolibarr_set_const($this->db, 'DOLICAR_VEHICLE_TAG', $result, 'integer', 0, '', $conf->entity);

			}
			dolibarr_set_const($this->db, 'CATEGORIE_RECURSIV_ADD', 1, 'integer', 0, '', $conf->entity);
			dolibarr_set_const($this->db, 'DOLICAR_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
		}
		if ($conf->global->CATEGORIE_RECURSIV_ADD == 0) {
			dolibarr_set_const($this->db, 'CATEGORIE_RECURSIV_ADD', 1, 'integer', 0, '', $conf->entity);
		}

		if ($conf->global->DOLICAR_VEHICLE_TAG == 0) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$tag->rechercher(0, $langs->transnoentities('Car'), 'product');
			if ($tag->id > 0) {
				dolibarr_set_const($this->db, 'DOLICAR_VEHICLE_TAG', $tag->id, 'integer', 0, '', $conf->entity);
			}
		}

		// Default product
		if ($conf->global->DOLICAR_DEFAULT_VEHICLE_SET == 0) {
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
			$product = new Product($this->db);

			$product->ref = $langs->transnoentities('DefaultVehicle');
			$product->label = $langs->transnoentities('DefaultVehicle');
			$result = $product->create($user);

			if ($result > 0) {
				dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE', $result, 'integer', 0, '', $conf->entity);
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

				$tag->fetch($conf->global->DOLICAR_VEHICLE_TAG);
				$tag->add_type($product, 'product');
			}
			dolibarr_set_const($this->db, 'DOLICAR_DEFAULT_VEHICLE_SET', 1, 'integer', 0, '', $conf->entity);
		}

		//Car brands tag
		dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG_SET', 0, 'integer', 0, '', $conf->entity);

		if ($conf->global->DOLICAR_CAR_BRANDS_TAG_SET == 0) {

			$tag->label = $langs->transnoentities('Brands');
			$tag->type = 'product';
			$result = $tag->create($user);


			if ($result > 0) {

				$filename = DOL_DOCUMENT_ROOT . '/custom/dolicar/core/car_brands.txt';
				$file = fopen( $filename, "r" );
				if ($file) {
					while (($line = fgets($file)) !== false) {
						$tag->label = $langs->transnoentities($line);
						$tag->type = 'product';
						$tag->fk_parent = $result;
						$tag->create($user);

					}
					fclose($file);
				}

				dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG', $result, 'integer', 0, '', $conf->entity);
				dolibarr_set_const($this->db, 'DOLICAR_CAR_BRANDS_TAG_SET', 1, 'integer', 0, '', $conf->entity);
			}
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
