-- Copyright (C) 2026 EVARISK <technique@evarisk.com>
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

-- Default email template for the public logbook problem report (issue #492).
-- This file is replayed on every module activation, hence the guard: without it a new copy of the
-- template would pile up in the list each time the module is enabled again.
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles)
SELECT 0, 'dolicar', 'dolicar_problem_report', '', 0, null, null, '(ProblemReportEmailTemplateLabel)', 100, "isModEnabled('dolicar')", 1,
       '__(ProblemReportEmailSubjectTemplate)__ __VEHICLE_PLATE__',
       '<p>__(ProblemReportEmailIntroTemplate)__</p><ul><li>__(RegistrationPlate)__ : <strong>__VEHICLE_PLATE__</strong></li><li>__(Vehicle)__ : __VEHICLE_LABEL__</li><li>__(Date)__ : __PROBLEM_DATE__</li><li>__(Comment)__ : __PROBLEM_COMMENT__</li></ul><p><a href="__VEHICLE_URL__">__(AccessVehicleSheet)__</a></p>',
       null, 1
FROM DUAL
WHERE NOT EXISTS (SELECT rowid FROM llx_c_email_templates WHERE type_template = 'dolicar_problem_report' AND module = 'dolicar');
