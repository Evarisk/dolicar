ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `fk_product` integer NOT NULL AFTER `z4_specific_details`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` DROP `d3_vehicle_model`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `d3_vehicle_model` varchar(128) NULL AFTER `d21_vehicle_cnit`;

-- 1.0.0
ALTER TABLE llx_dolicar_registrationcertificatefr CHANGE c4a_owner_vehicle `c4a_vehicle_owner` boolean;
ALTER TABLE llx_dolicar_registrationcertificatefr CHANGE c41_ownerNumber `c41_second_owner_number` integer;
ALTER TABLE llx_dolicar_registrationcertificatefr CHANGE f1_techincal_ptac `f1_technical_ptac` integer;
ALTER TABLE llx_dolicar_registrationcertificatefr CHANGE j_vehicleCategory `j_vehicle_category` varchar(128);
ALTER TABLE llx_dolicar_registrationcertificatefr CHANGE s1_seatingCapacity `s1_seating_capacity` integer;
ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `json` longtext AFTER `z4_specific_details`;
