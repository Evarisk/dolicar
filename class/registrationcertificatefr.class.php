<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    class/registrationcertificatefr.class.php
 * \ingroup dolicar
 * \brief   This file is a CRUD class file for RegistrationCertificateFr (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DoliCar libraries
require_once __DIR__ .'/../lib/dolicar_functions.lib.php';

/**
 * Class for RegistrationCertificateFr
 */
class RegistrationCertificateFr extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'dolicar';

    /**
     * @var string Element type of object
     */
    public $element = 'registrationcertificatefr';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'dolicar_registrationcertificatefr';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var int Does object support categories ? 0 = No, 1 = Yes
     */
    public int $isCategoryManaged = 0;

    /**
     * @var string Name of icon for registrationcertificatefr. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'registrationcertificatefr@dolicar' if picto is file 'img/object_registrationcertificatefr.png'
     */
    public string $picto = 'fontawesome_fa-car_fas_#d35968';

    public const STATUS_DRAFT     = 0;
    public const STATUS_DELETED   = -1;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'                               => ['type' => 'integer',      'label' => 'TechnicalID',                   'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                                 => ['type' => 'varchar(128)', 'label' => 'Ref',                           'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'showinpwa' => 1, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'comment' => 'Reference of object'],
        'ref_ext'                             => ['type' => 'varchar(128)', 'label' => 'RefExt',                        'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => -2],
        'entity'                              => ['type' => 'integer',      'label' => 'Entity',                        'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation'                       => ['type' => 'datetime',     'label' => 'DateCreation',                  'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                                 => ['type' => 'timestamp',    'label' => 'DateModification',              'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => -2],
        'import_key'                          => ['type' => 'varchar(14)',  'label' => 'ImportId',                      'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -2],
        'status'                              => ['type' => 'integer',      'label' => 'Status',                        'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => -2, 'index' => 1, 'default' => 1, 'arrayofkeyval' => [1 => 'Validated', 3 => 'Archived']],
        'a_registration_number'               => ['type' => 'varchar(128)', 'label' => 'RegistrationNumber',            'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 1, 'showinpwa' => 1],
        'b_first_registration_date'           => ['type' => 'date',         'label' => 'FirstRegistrationDate',         'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => -3, 'config' => 1],
        'c1_owner_fullname'                   => ['type' => 'varchar(255)', 'label' => 'OwnerFullName',                 'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => -3, 'config' => 1],
        'c3_registration_address'             => ['type' => 'html',         'label' => 'RegistrationAddress',           'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'c4a_vehicle_owner'                   => ['type' => 'boolean',      'label' => 'VehicleOwner',                  'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'c41_second_owner_number'             => ['type' => 'integer',      'label' => 'SecondOwnerNumber',             'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'c41_second_owner_name'               => ['type' => 'varchar(128)', 'label' => 'SecondOwnerName',               'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'd1_vehicle_brand'                    => ['type' => 'varchar(128)', 'label' => 'VehicleBrand',                  'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => -3, 'default' => 'DefaultBrand', 'config' => 1],
        'd2_vehicle_type'                     => ['type' => 'varchar(128)', 'label' => 'VehicleType',                   'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'd21_vehicle_cnit'                    => ['type' => 'varchar(128)', 'label' => 'VehicleCNIT',                   'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'd3_vehicle_model'                    => ['type' => 'varchar(128)', 'label' => 'VehicleModel',                  'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'e_vehicle_serial_number'             => ['type' => 'varchar(128)', 'label' => 'VehicleSerialNumber',           'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'f1_technical_ptac'                   => ['type' => 'integer',      'label' => 'TechnicalPTAC',                 'enabled' => 1, 'position' => 190, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'f2_ptac'                             => ['type' => 'integer',      'label' => 'PTAC',                          'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'f3_ptra'                             => ['type' => 'integer',      'label' => 'PTRA',                          'enabled' => 1, 'position' => 210, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'g_vehicle_weight'                    => ['type' => 'integer',      'label' => 'VehicleWeight',                 'enabled' => 1, 'position' => 220, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'g1_vehicle_empty_weight'             => ['type' => 'integer',      'label' => 'VehicleEmptyWeight',            'enabled' => 1, 'position' => 230, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'h_validity_period'                   => ['type' => 'varchar(128)', 'label' => 'ValidityPeriod',                'enabled' => 1, 'position' => 240, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'i_vehicle_registration_date'         => ['type' => 'datetime',     'label' => 'VehicleRegistrationDate',       'enabled' => 1, 'position' => 250, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'j_vehicle_category'                  => ['type' => 'varchar(128)', 'label' => 'VehicleCategory',               'enabled' => 1, 'position' => 260, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'j1_national_type'                    => ['type' => 'varchar(128)', 'label' => 'NationalType',                  'enabled' => 1, 'position' => 270, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'j2_european_bodywork'                => ['type' => 'varchar(128)', 'label' => 'EuropeanBodyWork',              'enabled' => 1, 'position' => 280, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'j3_national_bodywork'                => ['type' => 'varchar(128)', 'label' => 'NationalBodyWork',              'enabled' => 1, 'position' => 290, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'k_type_approval_number'              => ['type' => 'varchar(128)', 'label' => 'TypeApprovalNumber',            'enabled' => 1, 'position' => 300, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'p1_cylinder_capacity'                => ['type' => 'integer',      'label' => 'CylinderCapacity',              'enabled' => 1, 'position' => 310, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'p2_maximum_net_power'                => ['type' => 'integer',      'label' => 'MaximumNetPower',               'enabled' => 1, 'position' => 320, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'p3_fuel_type'                        => ['type' => 'varchar(128)', 'label' => 'FuelType',                      'enabled' => 1, 'position' => 330, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'p6_national_administrative_power'    => ['type' => 'integer',      'label' => 'NationalAdministrativePower',   'enabled' => 1, 'position' => 340, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'q_power_to_weight_ratio'             => ['type' => 'integer',      'label' => 'PowerToWeightRatio',            'enabled' => 1, 'position' => 350, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        's1_seating_capacity'                 => ['type' => 'integer',      'label' => 'SeatingCapacity',               'enabled' => 1, 'position' => 360, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        's2_standing_capacity'                => ['type' => 'integer',      'label' => 'StandingCapacity',              'enabled' => 1, 'position' => 370, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'u1_stationary_noise_level'           => ['type' => 'integer',      'label' => 'StationaryNoiseLevel',          'enabled' => 1, 'position' => 380, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'u2_motor_speed'                      => ['type' => 'integer',      'label' => 'MotorSpeed',                    'enabled' => 1, 'position' => 390, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'v7_co2_emission'                     => ['type' => 'integer',      'label' => 'CO2Emission',                   'enabled' => 1, 'position' => 400, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'v9_environmental_category'           => ['type' => 'varchar(128)', 'label' => 'EnvironmentalCategory',         'enabled' => 1, 'position' => 410, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'x1_first_technical_inspection_date'  => ['type' => 'datetime',     'label' => 'FirstTechnicalInspectionDate',  'enabled' => 1, 'position' => 420, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y1_regional_tax'                     => ['type' => 'double(24,8)', 'label' => 'RegionalTax',                   'enabled' => 1, 'position' => 430, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y2_professional_tax'                 => ['type' => 'double(24,8)', 'label' => 'ProfessionalTax',               'enabled' => 1, 'position' => 440, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y3_ecological_tax'                   => ['type' => 'double(24,8)', 'label' => 'EcologicalTax',                 'enabled' => 1, 'position' => 450, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y4_management_tax'                   => ['type' => 'double(24,8)', 'label' => 'ManagementTax',                 'enabled' => 1, 'position' => 460, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y5_forwarding_expenses_tax'          => ['type' => 'double(24,8)', 'label' => 'ForwardingExpensesTax',         'enabled' => 1, 'position' => 470, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'y6_total_price_vehicle_registration' => ['type' => 'double(24,8)', 'label' => 'TotalPriceVehicleRegistration', 'enabled' => 1, 'position' => 480, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'z1_specific_details'                 => ['type' => 'html',         'label' => 'SpecificDetails1',              'enabled' => 1, 'position' => 490, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'z2_specific_details'                 => ['type' => 'html',         'label' => 'SpecificDetails2',              'enabled' => 1, 'position' => 500, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'z3_specific_details'                 => ['type' => 'html',         'label' => 'SpecificDetails3',              'enabled' => 1, 'position' => 510, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'z4_specific_details'                 => ['type' => 'html',         'label' => 'SpecificDetails4',              'enabled' => 1, 'position' => 520, 'notnull' => 0, 'visible' => -3, 'config' => 1],
        'json'                                => ['type' => 'html',         'label' => 'JSON',                          'enabled' => 1, 'position' => 530, 'notnull' => 0, 'visible' => 0],
        'fk_product'                          => ['type' => 'integer:Product:product/class/product.class.php:1',             'label' => 'Product',      'picto' => 'product', 'enabled' => 1, 'position' => 12,  'notnull' => 0, 'visible' => 1,  'showinpwa' => 1, 'index' => 1, 'css' => 'minwidth150 maxwidth500 widthcentpercentminusx product-object', 'foreignkey' => 'product.rowid'],
        'fk_lot'                              => ['type' => 'integer:Productlot:product/stock/class/productlot.class.php:1', 'label' => 'DolicarBatch', 'picto' => 'lot',     'enabled' => 1, 'position' => 13,  'notnull' => 0, 'visible' => 5,  'showinpwa' => 1, 'index' => 1, 'css' => 'minwidth200 maxwidth500 widthcentpercentminusx',                'foreignkey' => 'productlot.rowid'],
        'fk_soc'                              => ['type' => 'integer:Societe:societe/class/societe.class.php:1',             'label' => 'ThirdParty',   'picto' => 'company', 'enabled' => 1, 'position' => 14,  'notnull' => 0, 'visible' => 1,  'index' => 1, 'css' => 'minwidth150 maxwidth500 widthcentpercentminusx',                'foreignkey' => 'societe.rowid'],
        'fk_project'                          => ['type' => 'integer:Project:projet/class/project.class.php:1',              'label' => 'Project',      'picto' => 'project', 'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1,  'index' => 1, 'css' => 'minwidth150 maxwidth500 widthcentpercentminusx',                'foreignkey' => 'projet.rowid'],
        'fk_user_creat'                       => ['type' => 'integer:User:user/class/user.class.php',                        'label' => 'UserAuthor',   'picto' => 'user',    'enabled' => 1, 'position' => 540, 'notnull' => 1, 'visible' => -2,               'css' => 'minwidth150 maxwidth500 widthcentpercentminusx',                'foreignkey' => 'user.rowid'],
        'fk_user_modif'                       => ['type' => 'integer:User:user/class/user.class.php',                        'label' => 'UserModif',    'picto' => 'user',    'enabled' => 1, 'position' => 550, 'notnull' => 0, 'visible' => -2,               'css' => 'minwidth150 maxwidth500 widthcentpercentminusx',                'foreignkey' => 'user.rowid']
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status = 1;

    public $fk_soc;
    public $a_registration_number;
    public $b_first_registration_date;
    public $c1_owner_fullname;
    public $c3_registration_address;
    public $c4a_vehicle_owner;
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
    public $j_vehicle_category;
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

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);

        foreach ($this->fields as $key => $val) {
            if (isset($val['config']) &&  $val['config'] == 1) {
                $confName = 'DOLICAR_' . dol_strtoupper($key) . '_VISIBLE';
                if (getDolGlobalInt($confName) == 0) {
                    $this->fields[$key]['visible'] = 0;
                }
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User        $user      User that creates
     * @param  int<0,1>    $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,max>            Return integer 0 < if KO, ID of created object if OK
     */
    public function create(User $user, int $noTrigger = 0): int
    {
        global $langs;

        $registrationNumber          = normalize_registration_number(dol_strtoupper($this->a_registration_number));
        $this->ref                   = $registrationNumber;
        $this->a_registration_number = $registrationNumber;

        if ($this->status !== self::STATUS_DRAFT) {
            if (empty($this->fk_product) || $this->fk_product == -1) {
                $this->fk_product = getDolGlobalInt('DOLICAR_DEFAULT_VEHICLE');
            }
            if (empty($this->fk_lot) || $this->fk_lot == -1) {
                $this->fk_lot = create_default_product_lot($this->fk_product);
            }
            if (empty($this->d1_vehicle_brand) || $this->d1_vehicle_brand == -1) {
                $this->d1_vehicle_brand = $langs->transnoentities('DefaultBrand');
            }
        }

        return $this->createCommon($user, $noTrigger);
    }

    /**
     * Update object into database
     *
     * @param  User       $user      User that modifies
     * @param  int<0,1>   $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,max>           Return integer 0 < if KO, ID of created object if OK
     */
    public function update(User $user, int $noTrigger = 0): int
    {
        global $langs;

        $registrationNumber          = normalize_registration_number(dol_strtoupper($this->a_registration_number));
        $this->ref                   = $registrationNumber;
        $this->a_registration_number = $registrationNumber;

        if (empty($this->d1_vehicle_brand) || $this->d1_vehicle_brand == -1) {
            $this->d1_vehicle_brand = $langs->transnoentities('DefaultBrand');
        }
        return $this->updateCommon($user, $noTrigger);
    }

    /**
     * Delete object in database
     *
     * Registration certificate deletion must be definitive (hard delete) so no soft-deleted row is kept in database.
     *
     * @param  User      $user       User that deletes
     * @param  int<0,1>  $noTrigger  0 = launch triggers after, 1 = disable triggers
     * @param  bool      $softDelete Keep object in database with a deleted status instead of removing it
     * @return int<-1,1>             Return integer 0 < if KO, > 0 if OK
     */
    public function delete(User $user, int $noTrigger = 0, bool $softDelete = false): int
    {
        return parent::delete($user, $noTrigger, $softDelete);
    }

    /**
     * Return tooltip content array
     *
     * @param  array $params Tooltip params
     * @return array         Tooltip content
     */
    public function getTooltipContentArray($params): array
    {
        global $langs;

        $langs->load('dolicar@dolicar');

        return parent::getTooltipContentArray($params);
    }

    /**
     * Return the status
     *
     * @param  int    $status ID status
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string         Label of status
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');

            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_DRAFT) {
            $statusType = 'status0';
        }
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_ARCHIVED) {
            $statusType = 'status8';
        }
        if ($status == self::STATUS_DELETED) {
            $statusType = 'status9';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    public function getRegistrationCertificateData($searchValue, $searchType = 'plaque', $confirmRetry = false): array
    {
        global $conf, $db, $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $api            = getDolGlobalString('DOLICAR_REGISTRATION_CERTIFICATE_API');
        $pendingDraftId = 0;

        if (dol_strlen($searchValue) > 0) {
            if ($searchType == 'plaque') {
                $searchValue = normalize_registration_number($searchValue);
                $result      = $this->fetch(0, $searchValue);
            } else {
                $result = $this->fetch(0, '', ' AND e_vehicle_serial_number = "' . $db->escape($searchValue) . '"');
            }

            if ($result > 0) {
                if ($this->status == self::STATUS_DRAFT) {
                    if (!empty($this->json)) {
                        $draftData = json_decode($this->json);
                        $draftId   = $this->id;
                        $this->id  = 0;
                        if ($draftData !== null) {
                            if (isset($draftData->_api_error) && $draftData->_api_error === true) {
                                if (!$confirmRetry) {
                                    // Ask user to confirm before consuming a new token
                                    return [
                                        'api'         => $api,
                                        'error_draft' => true,
                                        'draft_id'    => $draftId,
                                        'error'       => isset($draftData->_api_error_message) ? $draftData->_api_error_message : '',
                                    ];
                                }
                                // User confirmed retry: clear error JSON and reuse this draft
                                $pendingDraftId   = $draftId;
                                $retryDraft       = new self($db);
                                $retryDraft->fetch($draftId);
                                $retryDraft->json = '';
                                $retryDraft->update($user);
                            } else {
                                // Valid cached draft: return without consuming API token
                                setEventMessages($langs->trans('LicencePlateInformationsFromDatabase'), []);
                                return ['api' => $api, 'data' => $draftData, 'draft_id' => $draftId, 'from_cache' => true];
                            }
                        }
                    } else {
                        // Draft with empty JSON (pending): reuse it
                        $pendingDraftId = $this->id;
                        $this->id       = 0;
                    }
                } else {
                    setEventMessages($langs->trans('LicencePlateWasAlreadyExisting'), []);
                    header('Location: ' . dol_buildpath('dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $this->id);
                    exit;
                }
            }
        }

        // Create a pending draft in DB before consuming an API token
        if ($pendingDraftId == 0) {
            $pendingDraft         = new self($db);
            $pendingDraft->status = self::STATUS_DRAFT;
            if ($searchType == 'plaque') {
                $pendingDraft->a_registration_number = $searchValue;
            } else {
                $pendingDraft->a_registration_number   = '';
                $pendingDraft->e_vehicle_serial_number = $searchValue;
            }
            $createdId = $pendingDraft->create($user);
            if ($createdId > 0) {
                $pendingDraftId = $createdId;
            }
        }

        if ($api == 'apiplaqueimmatriculation.com') {
            $apiKey = getDolGlobalString('DOLICAR_APIIMMATRICULATION_API_KEY');

            if ($searchType == 'vin') {
                $url        = 'https://api.apiplaqueimmatriculation.com/vin?vin=' . urlencode($searchValue) . '&token=' . urlencode($apiKey);
                $httpMethod = 'GET';
            } else {
                $url        = 'https://api.apiplaqueimmatriculation.com/plaque?immatriculation=' . urlencode($searchValue) . '&token=' . urlencode($apiKey) . '&pays=FR';
                $httpMethod = 'POST';
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => $this->module . '-Agent/' . DOL_VERSION,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => $httpMethod
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $registrationCertificateObjectJson = json_decode($response);

            if (isset($registrationCertificateObjectJson->code_erreur) && $registrationCertificateObjectJson->code_erreur != 200) {
                $errorMessage = isset($registrationCertificateObjectJson->message) ? $registrationCertificateObjectJson->message : 'Error';
                $this->saveApiErrorToDraft($pendingDraftId, $errorMessage);
                return ['api' => $api, 'error' => $errorMessage, 'draft_id' => $pendingDraftId];
            }

            if (isset($registrationCertificateObjectJson->data) && is_object($registrationCertificateObjectJson->data)) {
                $this->saveJsonToDraft($pendingDraftId, $registrationCertificateObjectJson->data);
                $this->populateAndSaveDraft($pendingDraftId, $api, $registrationCertificateObjectJson->data);
                return ['api' => $api, 'data' => $registrationCertificateObjectJson->data, 'draft_id' => $pendingDraftId];
            }

            $errorMessage = isset($registrationCertificateObjectJson->message) ? $registrationCertificateObjectJson->message : 'Invalid API response';
            $this->saveApiErrorToDraft($pendingDraftId, $errorMessage);
            return ['api' => $api, 'error' => $errorMessage, 'draft_id' => $pendingDraftId];
        }

        if ($api == 'immatriculationapi.com') {
            $username = getDolGlobalString('DOLICAR_IMMATRICULATION_API_USERNAME');
            $apiUrl   = 'https://www.immatriculationapi.com/api/reg.asmx/CheckFrance';

            if (dol_strlen($username) > 0) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $apiUrl . '?RegistrationNumber=' . urlencode($searchValue) . '&username=' . urlencode($username),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT      => $this->module . '-Agent/' . DOL_VERSION,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT        => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => 'GET',
                ]);
                $xmlData   = curl_exec($curl);
                $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $curlError = curl_error($curl);
                curl_close($curl);

                if (empty($xmlData) || !empty($curlError)) {
                    $this->saveApiErrorToDraft($pendingDraftId, $curlError);
                    setEventMessages($langs->trans('BadAPIUsernameOrBadLicencePlateFormat', $searchValue), [], 'errors');
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
                    exit;
                } else {
                    $xml = @simplexml_load_string($xmlData);
                    if ($xml === false || !isset($xml->vehicleJson)) {
                        dol_syslog(__METHOD__ . ' immatriculationapi.com: unexpected response (not XML or missing vehicleJson): ' . $xmlData, LOG_ERR);
                        $this->saveApiErrorToDraft($pendingDraftId, $xmlData);
                        setEventMessages($langs->trans('BadAPIUsernameOrBadLicencePlateFormat', $searchValue), [], 'errors');
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
                        exit;
                    }
                    $strJson                       = (string) $xml->vehicleJson;
                    $registrationCertificateObject = json_decode($strJson);
                    dolibarr_set_const($db, 'DOLICAR_API_REMAINING_REQUESTS_COUNTER', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER') - 1, 'integer', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'DOLICAR_API_REQUESTS_COUNTER', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER') + 1, 'integer', 0, '', $conf->entity);
                    setEventMessages($langs->trans('LicencePlateInformationsCharged'), []);
                    setEventMessages($langs->trans('RemainingRequests', getDolGlobalString('DOLICAR_API_REMAINING_REQUESTS_COUNTER')), []);

                    $this->saveJsonToDraft($pendingDraftId, $registrationCertificateObject);
                    $this->populateAndSaveDraft($pendingDraftId, $api, $registrationCertificateObject);
                    return ['api' => $api, 'data' => $registrationCertificateObject, 'draft_id' => $pendingDraftId];
                }
            } else {
                setEventMessages($langs->trans('BadAPIUsername'), [], 'errors');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&a_registration_number=' . GETPOST('registrationNumber'));
                exit;
            }
        }

        return [];
    }

    /**
     * Save an API error marker into an existing draft's json field.
     */
    private function saveApiErrorToDraft(int $draftId, string $errorMessage): void
    {
        global $db, $user;

        if ($draftId <= 0) {
            return;
        }
        $draft       = new self($db);
        $draft->fetch($draftId);
        $draft->json = json_encode([
            '_api_error'           => true,
            '_api_error_message'   => $errorMessage,
            '_api_error_timestamp' => dol_now(),
        ]);
        $draft->update($user);
    }

    /**
     * Save only the raw JSON of an API response into a draft — guarantees the cache mechanism
     * works even if populateAndSaveDraft fails on a specific vehicle's data structure.
     */
    private function saveJsonToDraft(int $draftId, object $data): void
    {
        global $db, $user;

        if ($draftId <= 0) {
            return;
        }
        $draft       = new self($db);
        $draft->fetch($draftId);
        $draft->json = json_encode($data);
        $draft->update($user);
    }

    /**
     * Populate all fields of an existing draft from a successful API response and save it.
     * Keeps STATUS_DRAFT so the next search for the same plate hits the DB cache instead of the API.
     */
    private function populateAndSaveDraft(int $draftId, string $api, object $data): void
    {
        global $db, $user;

        if ($draftId <= 0) {
            return;
        }
        $draft = new self($db);
        $draft->fetch($draftId);

        if ($api === 'apiplaqueimmatriculation.com') {
            if (isset($data->plaque) && !empty($data->plaque)) {
                $draft->a_registration_number = $data->plaque;
            }
            if (isset($data->date1erCir_fr) && !empty($data->date1erCir_fr)) {
                $parts = explode('-', $data->date1erCir_fr);
                if (count($parts) === 3) {
                    $draft->b_first_registration_date = dol_mktime(12, 0, 0, (int) $parts[1], (int) $parts[0], (int) $parts[2]);
                }
            }
            $draft->d1_vehicle_brand                 = isset($data->marque)       ? (string) $data->marque       : '';
            $draft->d2_vehicle_type                  = isset($data->type_moteur)  ? (string) $data->type_moteur  : '';
            $draft->d21_vehicle_cnit                 = isset($data->cnit)         ? (string) $data->cnit         : '';
            $draft->d3_vehicle_model                 = isset($data->modele)       ? (string) $data->modele       : '';
            $draft->e_vehicle_serial_number          = isset($data->vin)          ? (string) $data->vin          : '';
            $draft->j1_national_type                 = isset($data->genreVCG)     ? (string) $data->genreVCG     : '';
            $draft->p1_cylinder_capacity             = isset($data->ccm)          ? (int) preg_replace('/[^0-9]/', '', $data->ccm) : 0;
            $draft->p3_fuel_type                     = isset($data->energieNGC)   ? (string) $data->energieNGC   : '';
            $draft->p6_national_administrative_power = isset($data->puisFisc)     ? (int) $data->puisFisc        : 0;
            $draft->s1_seating_capacity              = isset($data->nr_passagers) ? (int) $data->nr_passagers    : 0;
            $draft->v7_co2_emission                  = isset($data->co2)          ? (int) preg_replace('/[^0-9]/', '', $data->co2) : 0;
            $draft->f2_ptac                          = isset($data->ptac)         ? (int) preg_replace('/[^0-9]/', '', $data->ptac) : 0;
            $draft->g_vehicle_weight                 = isset($data->poids)        ? (int) preg_replace('/[^0-9]/', '', $data->poids) : 0;
            $draft->j2_european_bodywork             = isset($data->carrosserieCG)   ? (string) $data->carrosserieCG   : '';
            $draft->j3_national_bodywork             = isset($data->carrosserie)     ? (string) $data->carrosserie     : '';
            $draft->j_vehicle_category               = isset($data->genreVCGNGC)     ? (string) $data->genreVCGNGC     : '';
            $draft->k_type_approval_number           = isset($data->type_mine)       ? (string) $data->type_mine       : '';
            $draft->p2_maximum_net_power             = isset($data->puisFiscReelKW)  ? (int) preg_replace('/[^0-9]/', '', $data->puisFiscReelKW) : 0;
            $draft->v9_environmental_category        = isset($data->energie)         ? (string) $data->energie         : '';
            $draft->h_validity_period                = isset($data->date30)          ? (string) $data->date30          : '';
            if (isset($data->date1erCir_us) && !empty($data->date1erCir_us)) {
                $dateUs = explode('-', $data->date1erCir_us);
                if (count($dateUs) === 3) {
                    $draft->i_vehicle_registration_date = dol_mktime(12, 0, 0, (int) $dateUs[1], (int) $dateUs[2], (int) $dateUs[0]);
                }
            }
        } elseif ($api === 'immatriculationapi.com') {
            $ext = isset($data->ExtendedData) ? $data->ExtendedData : null;
            if ($ext !== null && isset($ext->datePremiereMiseCirculation) && !empty($ext->datePremiereMiseCirculation)) {
                $parts = str_split($ext->datePremiereMiseCirculation, 2);
                if (count($parts) >= 4) {
                    $draft->b_first_registration_date = dol_mktime(12, 0, 0, (int) $parts[1], (int) $parts[0], (int) ($parts[2] . $parts[3]));
                }
            }
            $draft->d1_vehicle_brand                 = isset($data->CarMake->CurrentTextValue)  ? (string) $data->CarMake->CurrentTextValue  : '';
            $draft->d2_vehicle_type                  = isset($ext->typeVehicule)                ? (string) $ext->typeVehicule                : '';
            $draft->d21_vehicle_cnit                 = isset($ext->CNIT)                        ? (string) $ext->CNIT                        : '';
            $draft->d3_vehicle_model                 = isset($ext->libelleModele)               ? (string) $ext->libelleModele               : '';
            $draft->e_vehicle_serial_number          = isset($ext->numSerieMoteur)              ? (string) $ext->numSerieMoteur              : '';
            $draft->i_vehicle_registration_date      = isset($data->RegistrationDate)           ? (string) $data->RegistrationDate           : '';
            $draft->j1_national_type                 = isset($ext->genre)                       ? (string) $ext->genre                       : '';
            $draft->p1_cylinder_capacity             = isset($ext->EngineCC)                    ? (int)    $ext->EngineCC                    : 0;
            $draft->p3_fuel_type                     = isset($data->FuelType->CurrentTextValue) ? (string) $data->FuelType->CurrentTextValue : '';
            $draft->p6_national_administrative_power = isset($ext->puissance)                   ? (int)    $ext->puissance                   : 0;
            $draft->s1_seating_capacity              = isset($ext->nbPlace)                     ? (int)    $ext->nbPlace                     : 0;
            $draft->v7_co2_emission                  = isset($ext->Co2)                         ? (int)    $ext->Co2                         : 0;
        }

        $draft->update($user);
    }

    /**
     * Update car brands list file from apiplaqueimmatriculation.com API.
     *
     * @return int            >0 if OK, <0 if error
     */
    public static function updateCarBrandsFromApi(): int
    {
        global $conf, $langs;

        // Endpoint provided in specs (example for brand id 93)
        $baseUrl = 'https://api.apiplaqueimmatriculation.com/marques';

        // Use configured API key if available, else fallback to demo token
        $token = getDolGlobalString('DOLICAR_APIIMMATRICULATION_API_KEY');

        $url = $baseUrl . '?token=' . urlencode($token);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'dolicar-Agent/' . DOL_VERSION,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'
        ));

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || !empty($curlError)) {
            setEventMessages($langs->trans('ErrorUpdateCarBrandsFromApi', $curlError), [], 'errors');
            return -1;
        }

        $json = json_decode($response);

        if (!is_object($json) || empty($json->success) || empty($json->data) || !is_array($json->data)) {
            setEventMessages($langs->trans('ErrorUpdateCarBrandsFromApiBadResponse'), [], 'errors');
            return -2;
        }

        // Extract brand names from API response (nom_marque)
        $brands = [];
        foreach ($json->data as $item) {
            if (is_object($item) && !empty($item->nom_marque)) {
                $label = trim((string) $item->nom_marque);
                if ($label !== '') {
                    $brands[$label] = true; // use associative array to ensure uniqueness
                }
            }
        }

        if (empty($brands)) {
            setEventMessages($langs->trans('ErrorUpdateCarBrandsFromApiNoData'), [], 'errors');
            return -3;
        }

        // Sort brands alphabetically
        $brandLabels = array_keys($brands);
        sort($brandLabels, SORT_NATURAL | SORT_FLAG_CASE);

        // Path to car_brands.txt from this class file
        $carBrandsFile = __DIR__ . '/../core/car_brands.txt';

        $content = implode("\n", $brandLabels) . "\n";
        $result = @file_put_contents($carBrandsFile, $content, LOCK_EX);

        if ($result === false) {
            setEventMessages($langs->trans('ErrorUpdateCarBrandsFile'), [], 'errors');
            return -4;
        }

        setEventMessages($langs->trans('CarBrandsUpdated'), [], 'mesgs');
        return 1;
    }

    /**
     * Load the dashboard
     *
     * @param array $regestrationCertifatesFr Array of registration certificates
     * @return void
     */
    public static function load_dashboard(array $regestrationCertifatesFr) {
        global $langs;

        $registrationCertifateFrStats = ['Ok' => 0, 'Ko' => 0, 'N/A' => 0];
        foreach ($regestrationCertifatesFr as $registrationCertifateFr) {
            $registrationCertifateFr->fetchObjectLinked($registrationCertifateFr->id, $registrationCertifateFr->table_element, null, 'digiquali_control');
            if (!empty($registrationCertifateFr->linkedObjects['digiquali_control'])) {
                $controls = $registrationCertifateFr->linkedObjects['digiquali_control'];
                $controls = array_filter($controls, function ($control) {
                    return $control->status == Control::STATUS_LOCKED && !empty($control->control_date);
                });
                usort($controls, function ($a, $b) {
                    return $b->control_date - $a->control_date;
                });

                $latestControl = null;
                foreach ($controls as $control) {
                    if ($latestControl === null || $control->control_date > $latestControl->control_date) {
                        $latestControl = $control;
                    }
                }

                if (!empty($latestControl)) {
                    $nextControl = 0;
                    if (!empty($latestControl->next_control_date)) {
                        $nextControl = (int) round(($latestControl->next_control_date - dol_now('tzuser')) / (3600 * 24));
                    }
                    if ($latestControl->verdict == 1 && ($nextControl > 0 || empty($latestControl->next_control_date))) {
                        $registrationCertifateFrStats['Ok']++;
                    } else {
                        $registrationCertifateFrStats['Ko']++;
                    }
                }
            } else {
                $registrationCertifateFrStats['N/A']++;
            }
        }

        $array = [];

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('DashboardCarState');
        $array['picto'] = 'fontawesome_fa-car_fas_#d35968';
        $array['name']  = 'DashboardCarState';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = 1;
        $array['dataset']    = 1;

        $array['labels'] = [
            [
                'label' => $langs->transnoentities('N/A'),
                'color' => '#8A8A8A'
            ],
            [
                'label' => $langs->transnoentities('OK'),
                'color' => '#47E58E'
            ],
            [
                'label' => $langs->transnoentities('KO'),
                'color' => '#E05353'
            ]
        ];

        $array['data'] = [$registrationCertifateFrStats['N/A'], $registrationCertifateFrStats['Ok'], $registrationCertifateFrStats['Ko']];

        return $array;
    }

}

