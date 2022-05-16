-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_dolicar_registrationcertificatefr(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	fk_soc integer, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer NOT NULL, 
	ref_ext varchar(128), 
	a_registration_number varchar(128) NOT NULL, 
	b_first_registration_date datetime, 
	c1_owner_fullname varchar(255), 
	c3_registration_address text,
	c41_ownerNumber integer, 
	c41_second_owner_name varchar(128), 
	e_vehicle_serial_number varchar(128), 
	f1_techincal_ptac integer, 
	f2_ptac integer, 
	c4a_owner_vehicle boolean, 
	d2_vehicle_type varchar(128), 
	d21_vehicle_cnit varchar(128), 
	d3_vehicle_model varchar(128) NOT NULL, 
	d1_vehicle_brand varchar(128), 
	f3_ptra integer, 
	g_vehicle_weight integer, 
	g1_vehicle_empty_weight integer, 
	h_validity_period varchar(128), 
	i_vehicle_registration_date datetime, 
	j_vehicleCategory varchar(128), 
	j1_national_type varchar(128), 
	j2_european_bodywork varchar(128), 
	j3_national_bodywork varchar(128), 
	k_type_approval_number varchar(128), 
	p1_cylinder_capacity integer, 
	p2_maximum_net_power integer, 
	p3_fuel_type varchar(128), 
	p6_national_administrative_power integer, 
	q_power_to_weight_ratio integer, 
	s1_seatingCapacity integer, 
	s2_standing_capacity integer, 
	u1_stationary_noise_level integer, 
	u2_motor_speed integer, 
	v7_co2_emission integer, 
	v9_environmental_category varchar(128), 
	x1_first_technical_inspection_date datetime, 
	y1_regional_tax double(24,8), 
	y2_professional_tax double(24,8), 
	y3_ecological_tax double(24,8), 
	y4_management_tax double(24,8), 
	y5_forwarding_expenses_tax double(24,8), 
	y6_total_price_vehicle_registration double(24,8), 
	z1_specific_details text,
	z2_specific_details text,
	z3_specific_details text,
	z4_specific_details text,
	fk_project integer, 
	fk_lot integer
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
