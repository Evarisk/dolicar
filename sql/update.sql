-- Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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

ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `fk_product` integer NOT NULL AFTER `z4_specific_details`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` DROP `d3_vehicle_model`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `d3_vehicle_model` varchar(128) NULL AFTER `d21_vehicle_cnit`;

-- 1.0.0
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `c4a_owner_vehicle` `c4a_vehicle_owner` boolean;
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `c41_ownerNumber` `c41_second_owner_number` integer;
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `f1_techincal_ptac` `f1_technical_ptac` integer;
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `j_vehicleCategory` `j_vehicle_category` varchar(128);
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `s1_seatingCapacity` `s1_seating_capacity` integer;
ALTER TABLE `llx_dolicar_registrationcertificatefr` CHANGE `fk_product` `fk_product` integer;
ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `json` longtext AFTER `z4_specific_details`;

-- 21.0.0
UPDATE llx_element_element SET sourcetype = 'dolicar_registrationcertificatefr' WHERE sourcetype = 'dolicar_regcertfr' AND targettype = 'digiquali_control';
