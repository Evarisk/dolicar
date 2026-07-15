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
 * \file    core/tpl/public_vehicle_logbook_vehicle.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — vehicle sheet + action choice screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $logoUrl, $langs, $registrationCertificateFR, $lastArrivalMileage, $isVehicleOut,
 *          $lastUnfinishedActionComm, $vehicleUrl, $recentActionComms, $conf
 */
?>
<!-- ===== SCREEN 2 : fiche véhicule + choix action ===== -->
<!-- No back button here: navigation between vehicles is handled by the bottom bar -->
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
    <div class="plv2-vehicle-card">
        <div class="plv2-vehicle-card__head">
            <div class="plv2-vehicle-card__icon"><i class="fas fa-car"></i></div>
            <div>
                <h3><?php echo dol_escape_htmltag($registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></h3>
                <span class="plv2-plate-badge"><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number); ?></span>
            </div>
        </div>

        <div class="plv2-vehicle-card__info">
            <div class="plv2-info-grid">
                <?php if (!empty($registrationCertificateFR->fk_soc)) : ?>
                    <div class="plv2-info-cell">
                        <span class="plv2-info-cell__label"><?php echo $langs->trans('ThirdParty'); ?></span>
                        <span class="plv2-info-cell__value"><?php echo dol_escape_htmltag($registrationCertificateFR->thirdparty->name ?? ''); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($registrationCertificateFR->p3_fuel_type)) : ?>
                    <div class="plv2-info-cell">
                        <span class="plv2-info-cell__label"><?php echo $langs->trans('FuelType'); ?></span>
                        <span class="plv2-info-cell__value"><?php echo dol_escape_htmltag($registrationCertificateFR->p3_fuel_type); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($registrationCertificateFR->b_first_registration_date)) : ?>
                    <div class="plv2-info-cell">
                        <span class="plv2-info-cell__label"><?php echo $langs->trans('FirstRegistrationDate'); ?></span>
                        <span class="plv2-info-cell__value"><?php echo dol_print_date($registrationCertificateFR->b_first_registration_date, 'day'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($lastArrivalMileage)) : ?>
                    <div class="plv2-info-cell">
                        <span class="plv2-info-cell__label"><?php echo $langs->trans('KnownMileage'); ?></span>
                        <span class="plv2-info-cell__value"><?php echo number_format($lastArrivalMileage, 0, ',', ' ') . ' km'; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isVehicleOut) : ?>
            <div class="plv2-vehicle-card__state plv2-vehicle-card__state--out">
                <i class="fas fa-sign-out-alt"></i>
                <div>
                    <strong><?php echo $langs->trans('VehicleCurrentlyOut'); ?></strong>
                    <?php if (!empty($lastUnfinishedActionComm[0])) :
                        $driverName = json_decode($lastUnfinishedActionComm[0]->array_options['options_json'] ?? '{}', true)['driver'] ?? '';
                        echo '<span>' . ($driverName ? dol_escape_htmltag($driverName) . ' · ' : '') . dol_print_date($lastUnfinishedActionComm[0]->datep, 'dayhour') . '</span>';
                    endif; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="plv2-vehicle-card__state plv2-vehicle-card__state--in">
                <i class="fas fa-sign-in-alt"></i>
                <strong><?php echo $langs->trans('VehicleCurrentlyIn'); ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <p class="plv2-section-label"><?php echo $langs->trans('WhatDoYouWantToDo'); ?></p>

    <div class="plv2-action-buttons">
        <a href="<?php echo $vehicleUrl . '&action_type=depart'; ?>"
           class="plv2-action-btn plv2-action-btn--depart<?php echo $isVehicleOut ? ' plv2-action-btn--disabled' : ''; ?>">
            <div class="plv2-action-btn__icon"><i class="fas fa-sign-out-alt"></i></div>
            <div class="plv2-action-btn__label"><?php echo $langs->trans('TakeVehicle'); ?></div>
            <div class="plv2-action-btn__sub"><?php echo $langs->trans('DeclareDeparture'); ?></div>
        </a>
        <a href="<?php echo $vehicleUrl . '&action_type=retour'; ?>"
           class="plv2-action-btn plv2-action-btn--retour<?php echo !$isVehicleOut ? ' plv2-action-btn--disabled' : ''; ?>">
            <div class="plv2-action-btn__icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="plv2-action-btn__label"><?php echo $langs->trans('ReturnVehicle'); ?></div>
            <div class="plv2-action-btn__sub"><?php echo $langs->trans('DeclareReturn'); ?></div>
        </a>
        <a href="<?php echo $vehicleUrl . '&action_type=probleme'; ?>"
           class="plv2-action-btn plv2-action-btn--probleme">
            <div class="plv2-action-btn__icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="plv2-action-btn__label"><?php echo $langs->trans('ReportProblem'); ?></div>
            <div class="plv2-action-btn__sub"><?php echo $langs->trans('ReportProblemSub'); ?></div>
        </a>
    </div>

    <?php if (!empty($recentActionComms)) : ?>
        <p class="plv2-section-label"><?php echo $langs->trans('RecentTrips'); ?></p>
        <div class="plv2-history">
            <?php
            $fuelIcons = ['reserve' => 'fa-battery-empty', 'quarter' => 'fa-battery-quarter', 'half' => 'fa-battery-half', 'threequarters' => 'fa-battery-three-quarters', 'full' => 'fa-battery-full'];
            $fuelLabels = ['reserve' => $langs->trans('FuelReserve'), 'quarter' => '1/4', 'half' => '1/2', 'threequarters' => '3/4', 'full' => $langs->trans('FuelFull')];
            foreach ($recentActionComms as $ac) :
                $acJson      = json_decode($ac->array_options['options_json'] ?? '{}', true);
                $acDriver    = $acJson['driver'] ?? '—';
                $acIsOpen    = empty($ac->datef);
                $acKmStart   = $ac->array_options['options_starting_mileage'] ?? null;
                $acKmEnd           = $ac->array_options['options_arrival_mileage'] ?? null;
                $acFuelLevel       = $acJson['fuel_level'] ?? null;
                $acReturnFuelLevel = $acJson['return_fuel_level'] ?? null;
                $acStartComment    = $acJson['start_comment'] ?? null;
                $acEndComment      = $acJson['end_comment'] ?? null;
            ?>
                <div class="plv2-history-item">
                    <div class="plv2-history-item__head">
                        <div class="plv2-history-item__driver">
                            <i class="fas fa-user-circle"></i>
                            <?php echo dol_escape_htmltag($acDriver); ?>
                        </div>
                        <span class="plv2-badge <?php echo $acIsOpen ? 'plv2-badge--out' : 'plv2-badge--done'; ?>">
                            <?php echo $acIsOpen ? $langs->trans('TripInProgress') : $langs->trans('TripDone'); ?>
                        </span>
                    </div>
                    <div class="plv2-history-item__row">
                        <span class="plv2-history-item__type plv2-history-item__type--depart">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $langs->trans('Departure'); ?>
                        </span>
                        <span><?php echo dol_print_date($ac->datep, 'dayhour'); ?></span>
                        <?php if (!empty($acKmStart)) : ?>
                            <span class="plv2-history-item__km"><?php echo number_format((int) $acKmStart, 0, ',', ' ') . ' km'; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($acFuelLevel) && isset($fuelIcons[$acFuelLevel])) : ?>
                            <span class="plv2-history-item__fuel">
                                <i class="fas <?php echo $fuelIcons[$acFuelLevel]; ?>"></i>
                                <?php echo $fuelLabels[$acFuelLevel]; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($acStartComment)) : ?>
                        <div class="plv2-history-item__comment">
                            <i class="fas fa-comment-dots"></i>
                            <?php echo dol_escape_htmltag($acStartComment); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$acIsOpen) : ?>
                        <div class="plv2-history-item__row">
                            <span class="plv2-history-item__type plv2-history-item__type--retour">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $langs->trans('Return'); ?>
                            </span>
                            <span><?php echo dol_print_date($ac->datef, 'dayhour'); ?></span>
                            <?php if (!empty($acKmEnd)) : ?>
                                <span class="plv2-history-item__km"><?php echo number_format((int) $acKmEnd, 0, ',', ' ') . ' km'; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($acReturnFuelLevel) && isset($fuelIcons[$acReturnFuelLevel])) : ?>
                                <span class="plv2-history-item__fuel">
                                    <i class="fas <?php echo $fuelIcons[$acReturnFuelLevel]; ?>"></i>
                                    <?php echo $fuelLabels[$acReturnFuelLevel]; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($acEndComment)) : ?>
                            <div class="plv2-history-item__comment">
                                <i class="fas fa-comment-dots"></i>
                                <?php echo dol_escape_htmltag($acEndComment); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                    $acMediaDir   = $conf->dolicar->dir_output . '/vehicle_trip/' . (int) $ac->id;
                    $acPhotoCount = 0;
                    $acAudioCount = 0;
                    if (dol_is_dir($acMediaDir)) {
                        // Recursive: trip media now live in depart/ and retour/ subfolders (issue #457)
                        foreach (dol_dir_list($acMediaDir, 'files', 1, '', '(\.meta|_preview.*\.png)$') as $acMediaFile) {
                            if (image_format_supported($acMediaFile['name']) >= 0) {
                                $acPhotoCount++;
                            } elseif (preg_match('/\.(wav|mp3|ogg|m4a)$/i', $acMediaFile['name'])) {
                                $acAudioCount++;
                            }
                        }
                    }
                    ?>
                    <?php if ($acPhotoCount > 0 || $acAudioCount > 0) : ?>
                        <div class="plv2-history-item__media">
                            <?php if ($acPhotoCount > 0) : ?>
                                <span><i class="fas fa-camera"></i> <?php echo $acPhotoCount; ?></span>
                            <?php endif; ?>
                            <?php if ($acAudioCount > 0) : ?>
                                <span><i class="fas fa-microphone"></i> <?php echo $acAudioCount; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <a class="plv2-history-seeall" href="<?php echo $vehicleUrl . '&view=history'; ?>">
            <i class="fas fa-history"></i> <?php echo $langs->trans('SeeAllTripHistory'); ?>
        </a>
    <?php endif; ?>
</div>
