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
 * \file    core/tpl/public_vehicle_logbook_problem.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — report a problem screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $vehicleUrl, $logoUrl, $langs, $registrationCertificateFR, $problemUploadSubDir
 */
?>
<!-- ===== SCREEN : signaler un problème ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                <span class="plv2-action-badge plv2-action-badge--probleme"><?php echo $langs->trans('ReportProblem'); ?></span>
                <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
            </div>
        </div>
    </div>
</div>

<form id="public-vehicle-problem-form" method="POST" action="<?php echo $vehicleUrl; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="report_problem">

    <div class="plv2-form">
        <div class="plv2-card">
            <div class="plv2-form-group">
                <label for="problem_comment"><?php echo $langs->trans('Comment'); ?></label>
                <textarea id="problem_comment" name="problem_comment" rows="4" placeholder="<?php echo dol_escape_htmltag($langs->trans('DescribeProblem')); ?>"></textarea>
            </div>
            <div class="plv2-form-group">
                <label><?php echo $langs->trans('PhotoAndVoiceMemo'); ?></label>
                <div class="dolicar-problem-media-row">
                    <?php print saturne_render_media_block('dolicar', $problemUploadSubDir, 'problem_', '', ['show_photo' => true, 'show_audio' => true, 'show_file' => false]); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="plv2-submit-area">
        <button type="submit" class="plv2-btn plv2-btn--full plv2-btn--primary">
            <i class="fas fa-paper-plane"></i>
            <?php echo $langs->trans('SendReport'); ?>
        </button>
    </div>
</form>
