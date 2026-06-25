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
 * \file    core/tpl/public_vehicle_logbook_control.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — create a DigiQuali control screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $vehicleUrl, $logoUrl, $langs, $registrationCertificateFR, $controlSheets, $form, $loggedUserId, $conf
 */
?>
<!-- ===== SCREEN : créer un contrôle DigiQuali ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                <span class="plv2-action-badge plv2-action-badge--control"><?php echo $langs->trans('Control'); ?></span>
                <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
            </div>
        </div>
    </div>
</div>

<?php if (empty($controlSheets)) : ?>
    <div class="plv2-content">
        <p class="plv2-hint"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('NoControlSheetAvailable'); ?></p>
    </div>
<?php else : ?>
    <!-- target="_blank": create the control and open the DigiQuali answer page in a new tab, keeping this logbook tab open -->
    <form id="public-vehicle-control-form" method="POST" action="<?php echo $vehicleUrl; ?>" target="_blank">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="create_control">

        <div class="plv2-form">
            <div class="plv2-card">
                <h3><i class="fas fa-clipboard-check"></i> <?php echo $langs->trans('ControlSheet'); ?> <span class="plv2-req">*</span></h3>
                <div class="plv2-form-group">
                    <select name="fk_sheet" required>
                        <option value="-1"><?php echo dol_escape_htmltag($langs->trans('SelectControlSheet')); ?></option>
                        <?php foreach ($controlSheets as $controlSheet) : ?>
                            <option value="<?php echo (int) $controlSheet->id; ?>"><?php echo dol_escape_htmltag($controlSheet->ref . ' - ' . $controlSheet->label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="plv2-card">
                <h3><i class="fas fa-user-tie"></i> <?php echo $langs->trans('Controller'); ?> <span class="plv2-req">*</span></h3>
                <div class="plv2-form-group">
                    <?php echo $form->select_dolusers($loggedUserId, 'fk_user_controller', 1, null, 0, '', '', (string) $conf->entity); ?>
                </div>
            </div>
        </div>

        <div class="plv2-submit-area">
            <button type="submit" class="plv2-btn plv2-btn--full plv2-btn--primary">
                <i class="fas fa-arrow-right"></i>
                <?php echo $langs->trans('CreateAndFillControl'); ?>
            </button>
        </div>
    </form>
<?php endif; ?>
