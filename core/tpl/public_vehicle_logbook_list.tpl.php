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
 * \file    core/tpl/public_vehicle_logbook_list.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — registration certificates list screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $logoUrl, $langs, $allCertificates, $entity
 */
?>
<!-- ===== SCREEN : liste des cartes grises ===== -->
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
    <p class="plv2-section-label"><?php echo $langs->trans('RegistrationCertificatesList'); ?></p>
    <?php if (empty($allCertificates)) : ?>
        <p class="plv2-hint"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('NoRegistrationCertificate'); ?></p>
    <?php else : ?>
        <div class="plv2-search">
            <i class="fas fa-search"></i>
            <input type="text" id="plv2-cg-search" placeholder="<?php echo dol_escape_htmltag($langs->trans('SearchVehicle')); ?>" autocomplete="off">
        </div>
        <div class="plv2-cg-list">
            <?php foreach ($allCertificates as $cert) :
                if (empty($cert->fk_lot)) {
                    continue;
                }
                $cgLabel = trim($cert->d1_vehicle_brand . ' ' . $cert->d3_vehicle_model);
                if ($cgLabel === '') {
                    $cgLabel = $cert->a_registration_number;
                } ?>
                <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . (int) $cert->fk_lot . '&entity=' . urlencode($entity); ?>"
                   class="plv2-cg-item"
                   data-search="<?php echo dol_escape_htmltag(dol_strtolower($cgLabel . ' ' . $cert->a_registration_number)); ?>">
                    <div class="plv2-cg-item__icon"><i class="fas fa-car"></i></div>
                    <div class="plv2-cg-item__body">
                        <span class="plv2-cg-item__title"><?php echo dol_escape_htmltag($cgLabel); ?></span>
                        <span class="plv2-plate-badge plv2-plate-badge--light"><?php echo dol_escape_htmltag($cert->a_registration_number); ?></span>
                    </div>
                    <i class="fas fa-chevron-right plv2-cg-item__chevron"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="plv2-hint plv2-cg-noresult" style="display: none;"><i class="fas fa-search"></i> <?php echo $langs->trans('NoVehicleFound'); ?></p>
    <?php endif; ?>
</div>
