<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/public_vehicle_logbook_archived.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — archived vehicle screen (sold, out of the fleet : no new entry)
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $langs, $registrationCertificateFR, $baseUrl
 */
?>
<!-- ===== Archived vehicle ===== -->
<div class="plv2-success">
    <div class="plv2-success__icon plv2-success__icon--blue">
        <i class="fas fa-archive"></i>
    </div>
    <h2><?php echo $langs->trans('PublicVehicleLogBookArchivedTitle'); ?></h2>
    <p><?php echo $langs->trans('PublicVehicleLogBookArchivedDesc'); ?></p>

    <div class="plv2-success__recap">
        <div class="plv2-success__row">
            <span><?php echo $langs->trans('Vehicle'); ?></span>
            <span><?php echo dol_escape_htmltag(trim($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model) . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
        </div>
    </div>

    <div class="plv2-success__actions">
        <a href="<?php echo dol_escape_htmltag($baseUrl); ?>" class="plv2-btn plv2-btn--primary plv2-btn--full">
            <i class="fas fa-search"></i> <?php echo $langs->trans('PublicVehicleLogBookSearchAnother'); ?>
        </a>
    </div>
</div>
