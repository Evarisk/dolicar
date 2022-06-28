ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `fk_product` integer NOT NULL AFTER `z4_specific_details`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` DROP `d3_vehicle_model`;
ALTER TABLE `llx_dolicar_registrationcertificatefr` ADD `d3_vehicle_model` varchar(128) NULL AFTER `d21_vehicle_cnit`;
