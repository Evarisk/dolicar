<?php
/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/public_vehicle_logbook_repair.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — create a repair (history event) screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $vehicleUrl, $logoUrl, $langs, $registrationCertificateFR, $lastArrivalMileage, $repairUploadSubDir
 */
?>
<!-- ===== SCREEN : créer une réparation ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                <span class="plv2-action-badge plv2-action-badge--reparation"><?php echo $langs->trans('Repair'); ?></span>
                <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
            </div>
        </div>
    </div>
</div>

<form id="public-vehicle-repair-form" method="POST" action="<?php echo $vehicleUrl; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="add_repair">

    <div class="plv2-form">
        <div class="plv2-card-row">
            <div class="plv2-card">
                <h3><i class="fas fa-calendar"></i> <?php echo $langs->trans('RepairDate'); ?></h3>
                <div class="plv2-form-group">
                    <input type="datetime-local"
                           name="repair_date"
                           value="<?php echo dol_print_date(dol_now(), '%Y-%m-%dT%H:%M'); ?>"
                           required>
                </div>
            </div>

            <div class="plv2-card">
                <h3><i class="fas fa-gauge"></i> <?php echo $langs->trans('RepairMileage'); ?></h3>
                <div class="plv2-form-group">
                    <input type="number"
                           name="repair_mileage"
                           class="plv2-km-input"
                           min="<?php echo (int) ($lastArrivalMileage ?? 0); ?>"
                           placeholder="000000">
                    <?php if (!empty($lastArrivalMileage)) : ?>
                        <div class="plv2-km-hint">
                            <?php echo $langs->trans('LastKnownMileage'); ?> : <strong><?php echo number_format($lastArrivalMileage, 0, ',', ' ') . ' km'; ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="plv2-card">
            <div class="plv2-card-head">
                <span class="plv2-card-head__title"><i class="fas fa-wrench"></i> <?php echo $langs->trans('RepairDescription'); ?></span>
                <div class="dolicar-trip-media-row">
                    <?php print saturne_render_media_block('dolicar', $repairUploadSubDir, 'repair_', '', ['show_photo' => true, 'show_audio' => true, 'show_file' => false]); ?>
                </div>
            </div>
            <div class="plv2-form-group">
                <textarea name="repair_comment"
                          rows="3"
                          placeholder="<?php echo dol_escape_htmltag($langs->trans('RemarksPlaceholder')); ?>"></textarea>
            </div>
        </div>
    </div>

    <div class="plv2-submit-area">
        <button type="submit" class="plv2-btn plv2-btn--full plv2-btn--primary">
            <i class="fas fa-check"></i>
            <?php echo $langs->trans('ValidateRepair'); ?>
        </button>
    </div>
</form>
