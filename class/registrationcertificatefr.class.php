<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/registrationcertificatefr.class.php
 * \ingroup     dolicar
 * \brief       This file is a CRUD class file for RegistrationCertificateFr (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once __DIR__.'/../lib/dolicar_functions.lib.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for RegistrationCertificateFr
 */
class RegistrationCertificateFr extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'dolicar';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'registrationcertificatefr';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'dolicar_registrationcertificatefr';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for registrationcertificatefr. Must be the part after the 'object_' into object_registrationcertificatefr.png
	 */
	public $picto = 'fontawesome_fa-car_fas_#d35968';

	const STATUS_VALIDATED = 1;
	const STATUS_LOCKED    = 2;
	const STATUS_ARCHIVED  = 3;

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'index'=>1, 'searchall'=>1, 'validate'=>'1', 'comment'=>"Reference of object"),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:(status:=:1)', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>14, 'notnull'=>-1, 'visible'=>1, 'index'=>1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'help'=>"LinkToThirparty", 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>540, 'notnull'=>1, 'visible'=>-2,),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>550, 'notnull'=>-1, 'visible'=>-2,),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>0, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validé', '9'=>'Annulé')),
		'ref_ext' => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0,),
		'a_registration_number' => array('type'=>'varchar(128)', 'label'=>'RegistrationNumber', 'enabled'=>'1', 'position'=>11, 'notnull'=>1, 'visible'=>1,),
		'b_first_registration_date' => array('type'=>'date', 'label'=>'FirstRegistrationDate', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>3,),
		'c1_owner_fullname' => array('type'=>'varchar(255)', 'label'=>'OwnerFullName', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>3,),
		'c3_registration_address' => array('type'=>'text', 'label'=>'RegistrationAddress', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>3,),
		'c4a_vehicle_owner' => array('type'=>'boolean', 'label'=>'VehicleOwner', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>3,),
		'c41_second_owner_number' => array('type'=>'integer', 'label'=>'SecondOwnerNumber', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>3,),
		'c41_second_owner_name' => array('type'=>'varchar(128)', 'label'=>'SecondOwnerName', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>3,),
		'd1_vehicle_brand' => array('type'=>'varchar(128)', 'label'=>'VehicleBrand', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>3,),
		'd2_vehicle_type' => array('type'=>'varchar(128)', 'label'=>'VehicleType', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>3,),
		'd21_vehicle_cnit' => array('type'=>'varchar(128)', 'label'=>'VehicleCNIT', 'enabled'=>'1', 'position'=>170, 'notnull'=>0, 'visible'=>3,),
		'd3_vehicle_model' => array('type'=>'varchar(128)', 'label'=>'VehicleModel', 'enabled'=>'1', 'position'=>175, 'notnull'=>0, 'visible'=>3,),
		'e_vehicle_serial_number' => array('type'=>'varchar(128)', 'label'=>'VehicleSerialNumber', 'enabled'=>'1', 'position'=>190, 'notnull'=>0, 'visible'=>3,),
		'f1_technical_ptac' => array('type'=>'integer', 'label'=>'TechnicalPTAC', 'enabled'=>'1', 'position'=>200, 'notnull'=>0, 'visible'=>3,),
		'f2_ptac' => array('type'=>'integer', 'label'=>'PTAC', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>3,),
		'f3_ptra' => array('type'=>'integer', 'label'=>'PTRA', 'enabled'=>'1', 'position'=>220, 'notnull'=>0, 'visible'=>3,),
		'g_vehicle_weight' => array('type'=>'integer', 'label'=>'VehicleWeight', 'enabled'=>'1', 'position'=>230, 'notnull'=>0, 'visible'=>3,),
		'g1_vehicle_empty_weight' => array('type'=>'integer', 'label'=>'VehicleEmptyWeight', 'enabled'=>'1', 'position'=>240, 'notnull'=>0, 'visible'=>3,),
		'h_validity_period' => array('type'=>'varchar(128)', 'label'=>'ValidityPeriod', 'enabled'=>'1', 'position'=>250, 'notnull'=>0, 'visible'=>3,),
		'i_vehicle_registration_date' => array('type'=>'datetime', 'label'=>'VehicleRegistrationDate', 'enabled'=>'1', 'position'=>260, 'notnull'=>0, 'visible'=>3,),
		'j_vehicle_category' => array('type'=>'varchar(128)', 'label'=>'VehicleCategory', 'enabled'=>'1', 'position'=>270, 'notnull'=>0, 'visible'=>3,),
		'j1_national_type' => array('type'=>'varchar(128)', 'label'=>'NationalType', 'enabled'=>'1', 'position'=>280, 'notnull'=>0, 'visible'=>3,),
		'j2_european_bodywork' => array('type'=>'varchar(128)', 'label'=>'EuropeanBodyWork', 'enabled'=>'1', 'position'=>290, 'notnull'=>0, 'visible'=>3,),
		'j3_national_bodywork' => array('type'=>'varchar(128)', 'label'=>'NationalBodyWork', 'enabled'=>'1', 'position'=>300, 'notnull'=>0, 'visible'=>3,),
		'k_type_approval_number' => array('type'=>'varchar(128)', 'label'=>'TypeApprovalNumber', 'enabled'=>'1', 'position'=>310, 'notnull'=>0, 'visible'=>3,),
		'p1_cylinder_capacity' => array('type'=>'integer', 'label'=>'CylinderCapacity', 'enabled'=>'1', 'position'=>320, 'notnull'=>0, 'visible'=>3,),
		'p2_maximum_net_power' => array('type'=>'integer', 'label'=>'MaximumNetPower', 'enabled'=>'1', 'position'=>330, 'notnull'=>0, 'visible'=>3,),
		'p3_fuel_type' => array('type'=>'varchar(128)', 'label'=>'FuelType', 'enabled'=>'1', 'position'=>340, 'notnull'=>0, 'visible'=>3,),
		'p6_national_administrative_power' => array('type'=>'integer', 'label'=>'NationalAdministrativePower', 'enabled'=>'1', 'position'=>350, 'notnull'=>0, 'visible'=>3,),
		'q_power_to_weight_ratio' => array('type'=>'integer', 'label'=>'PowerToWeightRatio', 'enabled'=>'1', 'position'=>360, 'notnull'=>0, 'visible'=>3,),
		's1_seating_capacity' => array('type'=>'integer', 'label'=>'SeatingCapacity', 'enabled'=>'1', 'position'=>370, 'notnull'=>0, 'visible'=>3,),
		's2_standing_capacity' => array('type'=>'integer', 'label'=>'StationaryCapacity', 'enabled'=>'1', 'position'=>380, 'notnull'=>0, 'visible'=>3,),
		'u1_stationary_noise_level' => array('type'=>'integer', 'label'=>'StationaryNoiseLevel', 'enabled'=>'1', 'position'=>390, 'notnull'=>0, 'visible'=>3,),
		'u2_motor_speed' => array('type'=>'integer', 'label'=>'MotorSpeed', 'enabled'=>'1', 'position'=>400, 'notnull'=>0, 'visible'=>3,),
		'v7_co2_emission' => array('type'=>'integer', 'label'=>'CO2Emission', 'enabled'=>'1', 'position'=>410, 'notnull'=>0, 'visible'=>3,),
		'v9_environmental_category' => array('type'=>'varchar(128)', 'label'=>'EnvironmentalCategory', 'enabled'=>'1', 'position'=>420, 'notnull'=>0, 'visible'=>3,),
		'x1_first_technical_inspection_date' => array('type'=>'datetime', 'label'=>'FirstTechnicalInspectionDate', 'enabled'=>'1', 'position'=>430, 'notnull'=>0, 'visible'=>3,),
		'y1_regional_tax' => array('type'=>'double(24,8)', 'label'=>'RegionalTax', 'enabled'=>'1', 'position'=>440, 'notnull'=>0, 'visible'=>3,),
		'y2_professional_tax' => array('type'=>'double(24,8)', 'label'=>'ProfessionalTax', 'enabled'=>'1', 'position'=>450, 'notnull'=>0, 'visible'=>3,),
		'y3_ecological_tax' => array('type'=>'double(24,8)', 'label'=>'EcologicalTax', 'enabled'=>'1', 'position'=>460, 'notnull'=>0, 'visible'=>3,),
		'y4_management_tax' => array('type'=>'double(24,8)', 'label'=>'ManagementTax', 'enabled'=>'1', 'position'=>470, 'notnull'=>0, 'visible'=>3,),
		'y5_forwarding_expenses_tax' => array('type'=>'double(24,8)', 'label'=>'ForwardingExpensesTax', 'enabled'=>'1', 'position'=>480, 'notnull'=>0, 'visible'=>3,),
		'y6_total_price_vehicle_registration' => array('type'=>'double(24,8)', 'label'=>'TotalPriceVehicleRegistration', 'enabled'=>'1', 'position'=>490, 'notnull'=>0, 'visible'=>3,),
		'z1_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails1', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>3,),
		'z2_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails2', 'enabled'=>'1', 'position'=>510, 'notnull'=>0, 'visible'=>3,),
		'z3_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails3', 'enabled'=>'1', 'position'=>520, 'notnull'=>0, 'visible'=>3,),
		'z4_specific_details' => array('type'=>'text', 'label'=>'SpecificDetails4', 'enabled'=>'1', 'position'=>530, 'notnull'=>0, 'visible'=>3,),
		'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'LinkedProduct', 'enabled'=>'1', 'position'=>13, 'notnull'=>0, 'visible'=>1, 'css'=>'maxwidth500'),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>'1', 'position'=>16, 'notnull'=>-1, 'visible'=>-1, 'index'=>1, 'css'=>'maxwidth500', 'validate'=>'1',),
		'fk_lot' => array('type'=>'integer:Productlot:product/stock/class/productlot.class.php:1', 'label'=>'DolicarBatch', 'enabled'=>'1', 'position'=>15, 'notnull'=>-1, 'visible'=>-1, 'index'=>1, 'css'=>'maxwidth500', 'validate'=>'1',),
		'json' => array('type'=>'text', 'label'=>'JSON', 'enabled'=>'1', 'position'=>15, 'notnull'=>-1, 'visible'=>3, 'index'=>1, 'css'=>'maxwidth500'),
	);
	// BEGIN MODULEBUILDER PROPERTIES
	public $rowid;
	public $ref;
	public $fk_soc;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $entity;
	public $status;
	public $ref_ext;
	public $a_registration_number;
	public $b_first_registration_date;
	public $c1_owner_fullname;
	public $c3_registration_address;
	public $c41_ownerNumber;
	public $c41_second_owner_name;
	public $e_vehicle_serial_number;
	public $f1_techincal_ptac;
	public $f2_ptac;
	public $c4a_owner_vehicle;
	public $fk_product;
	public $d1_vehicle_brand;
	public $d2_vehicle_type;
	public $d21_vehicle_cnit;
	public $d3_vehicle_model;
	public $f3_ptra;
	public $g_vehicle_weight;
	public $g1_vehicle_empty_weight;
	public $h_validity_period;
	public $i_vehicle_registration_date;
	public $j_vehicleCategory;
	public $j1_national_type;
	public $j2_european_bodywork;
	public $j3_national_bodywork;
	public $k_type_approval_number;
	public $p1_cylinder_capacity;
	public $p2_maximum_net_power;
	public $p3_fuel_type;
	public $p6_national_administrative_power;
	public $q_power_to_weight_ratio;
	public $s1_seatingCapacity;
	public $s2_standing_capacity;
	public $u1_stationary_noise_level;
	public $u2_motor_speed;
	public $v7_co2_emission;
	public $v9_environmental_category;
	public $x1_first_technical_inspection_date;
	public $y1_regional_tax;
	public $y2_professional_tax;
	public $y3_ecological_tax;
	public $y4_management_tax;
	public $y5_forwarding_expenses_tax;
	public $y6_total_price_vehicle_registration;
	public $z1_specific_details;
	public $z2_specific_details;
	public $z3_specific_details;
	public $z4_specific_details;
	public $fk_project;
	public $fk_lot;
	public $json;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'dolicar_registrationcertificatefrline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_registrationcertificatefr';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'RegistrationCertificateFrline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('dolicar_registrationcertificatefrdet');

	// /**
	//  * @var RegistrationCertificateFrLine[]     Array of subtable lines
	//  */
	// public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->dolicar->registrationcertificatefr->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf, $langs;
		$registrationNumber = $this->a_registration_number;
		$registrationNumber = strtoupper($registrationNumber);

		$registrationNumber = normalize_registration_number($registrationNumber);

		$this->a_registration_number = $registrationNumber;
		$this->ref = $registrationNumber;
		$this->status = 1;
		if (empty($this->fk_lot) || $this->fk_lot == -1) {
			$lot_id = createDefaultLot($this->fk_product);
			$this->fk_lot = $lot_id;
		}
		if (empty($this->fk_product) || $this->fk_product == -1) {
			$this->fk_product = $conf->global->DOLICAR_DEFAULT_VEHICLE;
		}
		if (empty($this->d1_vehicle_brand) || $this->d1_vehicle_brand == -1) {
			$this->d1_vehicle_brand = $langs->trans('DefaultBrand');
		}

		$resultcreate = $this->createCommon($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = $object->a_registration_number;
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_VALIDATED;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (!empty($object->socid) && property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->table_element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
				} else {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		global $langs;
		$this->ref = $this->a_registration_number;
		if (empty($this->d1_vehicle_brand) || $this->d1_vehicle_brand == -1) {
			$this->d1_vehicle_brand = $langs->trans('DefaultBrand');
		}
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->registrationcertificatefr->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->registrationcertificatefr->registrationcertificatefr_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($num)."',";
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('REGISTRATIONCERTIFICATEFR_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'registrationcertificatefr/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'registrationcertificatefr/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->dolicar->dir_output.'/registrationcertificatefr/'.$oldref;
				$dirdest = $conf->dolicar->dir_output.'/registrationcertificatefr/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->dolicar->dir_output.'/registrationcertificatefr/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->dolicar_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'REGISTRATIONCERTIFICATEFR_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dolicar->dolicar_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'REGISTRATIONCERTIFICATEFR_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("RegistrationCertificateFr").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowRegistrationCertificateFr");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('registrationcertificatefrdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("dolicar@dolicar");
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
		}

		$statusType = 'status'.$status;

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT rowid, date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if (!empty($obj->fk_user_author)) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if (!empty($obj->fk_user_valid)) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if (!empty($obj->fk_user_cloture)) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new RegistrationCertificateFrLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_registrationcertificatefr = '.((int) $this->id)));

		if (is_numeric($result)) {
			$this->error = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("dolicar@dolicar");

		if (empty($conf->global->DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON)) {
			$conf->global->DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON = 'mod_registrationcertificatefr_standard';
		}

		if (!empty($conf->global->DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON)) {
			$mybool = false;

			$file = $conf->global->DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON.".php";
			$classname = $conf->global->DOLICAR_REGISTRATIONCERTIFICATEFR_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/dolicar/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Output html form to select a third party.
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
	 *
	 * @param  string 		$selected Preselected type
	 * @param  string 		$htmlname Name of field in form
	 * @param  string 		$filter Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 * @param  string 		$showempty Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * @param  int 			$forcecombo Force to use standard HTML select component without beautification
	 * @param  array 		$events Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 * @param  int 			$outputmode 0=HTML select string, 1=Array
	 * @param  int 			$limit Limit number of answers
	 * @param  string 		$morecss Add more css styles to the SELECT component
	 * @param  int	 		$moreparam Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 * @param  bool 		$multiple add [] in the name of element and add 'multiple' attribut
	 * @param  int 			$noroot
	 * @return string HTML string with
	 * @throws Exception
	 */
	public function selectRegistrationCertificateList($selected = '', $htmlname = 'options_registrationcertificatefr', $filter = [], $showempty = '1', $forcecombo = 0, $events = array(), $outputmode = 0, $limit = 0, $morecss = 'minwidth100 maxwidth300', $moreparam = 0, $multiple = false, $noroot = 0, $contextpage = '', $multientitymanagedoff = true)
	{
        global $form;

        $product = new Product($this->db);

        $objectList = saturne_fetch_all_object_type('registrationcertificatefr', '', '', $limit, 0, $filter);
        $registrationCertificatesData  = [];
        if (is_array($objectList) && !empty($objectList)) {
            foreach ($objectList as $registrationCertificate) {
                $product->fetch($registrationCertificate->fk_product);
                $registrationCertificatesData[$registrationCertificate->id] = $registrationCertificate->ref . ' - ' . $product->label;
            }
        }

        return $form::selectarray($htmlname, $registrationCertificatesData, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("dolicar@dolicar");

		if (!dol_strlen($modele)) {
			$modele = 'standard_registrationcertificatefr';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->REGISTRATIONCERTIFICATEFR_ADDON_PDF)) {
				$modele = $conf->global->REGISTRATIONCERTIFICATEFR_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/dolicar/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getFactureLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['facture'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getFactureDetLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."facturedet_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['facturedet'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getPropalLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['propal'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getPropalDetLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."propaldet_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['propaldet'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getCommandeLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['commande'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getCommandeDetLinked()
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet_extrafields as t";
		$sql .= " WHERE 1 = 1";
		$sql .= ' AND registrationcertificatefr =' . $this->id;
		$sql .= ' ORDER BY t.mileage DESC';

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records['commandedet'][$obj->fk_object] = $obj->fk_object;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function getObjectsLinked()
	{
		$facture_list = $this->getFactureLinked();
		$facturedet_list = $this->getFactureDetLinked();
		$propal_list = $this->getPropalLinked();
		$propaldet_list = $this->getPropalDetLinked();
		$commande_list = $this->getCommandeLinked();
		$commandedet_list = $this->getCommandeDetLinked();


		return array_merge($facture_list, $facturedet_list, $propal_list, $propaldet_list, $commande_list, $commandedet_list);
	}
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class RegistrationCertificateFrLine. You can also remove this and generate a CRUD class for lines objects.
 */
class RegistrationCertificateFrLine extends CommonObjectLine
{
	// To complete with content of an object RegistrationCertificateFrLine
	// We should have a field rowid, fk_registrationcertificatefr and position

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}
