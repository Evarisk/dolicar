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
 * \file    core/tpl/public_vehicle_logbook_bottombar.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — bottom navigation bar (footer tabs)
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $baseUrl, $showScreen, $currentVehicleId, $currentVehiclePlate, $entity, $langs, $isModEnabledDigiquali
 */
?>
<!-- ===== Bottom navigation bar ===== -->
<nav class="plv2-bottombar">
    <a href="<?php echo $baseUrl . '&view=list'; ?>"
       class="plv2-bottombar__item<?php echo $showScreen === 'list' ? ' plv2-bottombar__item--active' : ''; ?>">
        <i class="fas fa-list-ul"></i>
        <span><?php echo $langs->trans('BottomBarVehiclesList'); ?></span>
    </a>
    <?php if ($currentVehicleId > 0) : ?>
        <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $currentVehicleId . '&entity=' . urlencode($entity); ?>"
           class="plv2-bottombar__item<?php echo $showScreen === 'vehicle' ? ' plv2-bottombar__item--active' : ''; ?>">
            <i class="fas fa-id-card"></i>
            <span><?php echo dol_escape_htmltag(!empty($currentVehiclePlate) ? $currentVehiclePlate : $langs->trans('BottomBarCurrentVehicle')); ?></span>
        </a>
    <?php else : ?>
        <span class="plv2-bottombar__item plv2-bottombar__item--disabled">
            <i class="fas fa-id-card"></i>
            <span><?php echo $langs->trans('BottomBarCurrentVehicle'); ?></span>
        </span>
    <?php endif; ?>
    <?php if ($currentVehicleId > 0) : ?>
        <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $currentVehicleId . '&entity=' . urlencode($entity) . '&action_type=reparation'; ?>"
           class="plv2-bottombar__item<?php echo $showScreen === 'repair' ? ' plv2-bottombar__item--active' : ''; ?>">
            <i class="fas fa-wrench"></i>
            <span><?php echo $langs->trans('BottomBarRepair'); ?></span>
        </a>
    <?php else : ?>
        <span class="plv2-bottombar__item plv2-bottombar__item--disabled">
            <i class="fas fa-wrench"></i>
            <span><?php echo $langs->trans('BottomBarRepair'); ?></span>
        </span>
    <?php endif; ?>
    <?php if ($isModEnabledDigiquali) : ?>
        <?php if ($currentVehicleId > 0) : ?>
            <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $currentVehicleId . '&entity=' . urlencode($entity) . '&action_type=control'; ?>"
               class="plv2-bottombar__item<?php echo $showScreen === 'control' ? ' plv2-bottombar__item--active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span><?php echo $langs->trans('BottomBarControl'); ?></span>
            </a>
        <?php else : ?>
            <span class="plv2-bottombar__item plv2-bottombar__item--disabled">
                <i class="fas fa-clipboard-check"></i>
                <span><?php echo $langs->trans('BottomBarControl'); ?></span>
            </span>
        <?php endif; ?>
    <?php endif; ?>
</nav>
