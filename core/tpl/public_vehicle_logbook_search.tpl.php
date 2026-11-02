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
 * \file    core/tpl/public_vehicle_logbook_search.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — plate search screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $logoUrl, $langs, $baseUrl, $registrationNumber, $registrationCertificateFR
 */
?>
<!-- ===== SCREEN 1 : saisie plaque ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                DoliCar
                <small><?php echo $langs->trans('PublicVehicleLogBook'); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="plv2-content">
    <?php if (!empty($registrationNumber) && empty($registrationCertificateFR->id)) : ?>
        <div class="plv2-notice plv2-notice--error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong><?php echo $langs->trans('PlateNotFound'); ?></strong>
                <p><?php echo $langs->trans('LicencePlateNotFoundInDB'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo $baseUrl; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="get_registration_number">
        <div class="plv2-card plv2-plate-card">
            <div class="plv2-plate-card__icon"><i class="fas fa-id-card"></i></div>
            <h2><?php echo $langs->trans('EnterPlate'); ?></h2>
            <p><?php echo $langs->trans('QRCodeMayHavePrefilled'); ?></p>
            <input type="text"
                   name="registration_number"
                   class="plv2-plate-input"
                   placeholder="AB-123-CD"
                   value="<?php echo dol_escape_htmltag($registrationNumber); ?>"
                   maxlength="11"
                   required
                   autocomplete="off">
            <button type="submit" class="plv2-btn plv2-btn--primary plv2-btn--full">
                <i class="fas fa-arrow-right"></i>
                <?php echo $langs->trans('Continue'); ?>
            </button>
        </div>
    </form>
</div>
