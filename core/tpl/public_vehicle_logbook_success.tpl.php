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
 * \file    core/tpl/public_vehicle_logbook_success.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — confirmation screen (trip / problem / repair)
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $isVehicleOut, $lastUnfinishedActionComm, $lastActionComm, $langs, $registrationCertificateFR, $vehicleUrl, $baseUrl
 */

$problemSuccess  = GETPOSTINT('problem');
$repairSuccess   = GETPOSTINT('repair');
$successIsDepart = $isVehicleOut;
$successAction   = $successIsDepart ? ($lastUnfinishedActionComm[0] ?? null) : ($lastActionComm[0] ?? null);
?>
<!-- ===== SCREEN 4 : confirmation ===== -->
<div class="plv2-success">
    <?php if ($problemSuccess) : ?>
        <div class="plv2-success__icon plv2-success__icon--green">
            <i class="fas fa-check"></i>
        </div>
        <h2><?php echo $langs->trans('ProblemReportSent'); ?></h2>
        <p><?php echo $langs->trans('ProblemReportSentDesc'); ?></p>

        <div class="plv2-success__recap">
            <div class="plv2-success__row">
                <span><?php echo $langs->trans('Vehicle'); ?></span>
                <span><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
            </div>
        </div>
    <?php elseif ($repairSuccess) : ?>
        <div class="plv2-success__icon plv2-success__icon--green">
            <i class="fas fa-check"></i>
        </div>
        <h2><?php echo $langs->trans('RepairRecorded'); ?></h2>
        <p><?php echo $langs->trans('RepairRecordedDesc'); ?></p>

        <div class="plv2-success__recap">
            <div class="plv2-success__row">
                <span><?php echo $langs->trans('Vehicle'); ?></span>
                <span><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
            </div>
        </div>
    <?php else : ?>
        <div class="plv2-success__icon <?php echo $successIsDepart ? 'plv2-success__icon--blue' : 'plv2-success__icon--green'; ?>">
            <i class="fas fa-check"></i>
        </div>
        <h2><?php echo $successIsDepart ? $langs->trans('DepartureRecorded') : $langs->trans('ReturnRecorded'); ?></h2>
        <p><?php echo $langs->trans('ActionCommCreatedInAgenda'); ?></p>

        <div class="plv2-success__recap">
            <div class="plv2-success__row">
                <span><?php echo $langs->trans('Vehicle'); ?></span>
                <span><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model . ' (' . $registrationCertificateFR->a_registration_number . ')'); ?></span>
            </div>
            <div class="plv2-success__row">
                <span><?php echo $langs->trans('Type'); ?></span>
                <span class="<?php echo $successIsDepart ? 'plv2-text--blue' : 'plv2-text--green'; ?>">
                    <?php echo $successIsDepart ? $langs->trans('Departure') : $langs->trans('Return'); ?>
                </span>
            </div>
            <?php if (!empty($successAction)) : ?>
                <div class="plv2-success__row">
                    <span><?php echo $langs->trans('Date'); ?></span>
                    <span><?php echo dol_print_date($successIsDepart ? $successAction->datep : $successAction->datef, 'dayhour'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="plv2-success__actions">
        <a href="<?php echo $vehicleUrl; ?>" class="plv2-btn plv2-btn--primary plv2-btn--full">
            <?php echo $langs->trans('NewEntry'); ?>
        </a>
        <a href="<?php echo $baseUrl; ?>" class="plv2-btn plv2-btn--ghost plv2-btn--full">
            <?php echo $langs->trans('ClosePublicLogBook'); ?>
        </a>
    </div>
</div>
