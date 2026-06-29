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
 * \file    core/tpl/public_vehicle_logbook_history.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — full trips history (departures/returns) with per-trip PDF download
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $logoUrl, $langs, $registrationCertificateFR, $historyTrips, $id, $entity, $conf
 */

$fuelIcons  = ['reserve' => 'fa-battery-empty', 'quarter' => 'fa-battery-quarter', 'half' => 'fa-battery-half', 'threequarters' => 'fa-battery-three-quarters', 'full' => 'fa-battery-full'];
$fuelLabels = ['reserve' => $langs->trans('FuelReserve'), 'quarter' => '1/4', 'half' => '1/2', 'threequarters' => '3/4', 'full' => $langs->trans('FuelFull')];
$historyUrl = $_SERVER['PHP_SELF'] . '?id=' . $id . '&entity=' . urlencode($entity);
?>
<!-- ===== SCREEN : historique complet des prises / rendus ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                DoliCar
                <small><?php echo $langs->trans('VehicleTripHistory'); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="plv2-content">
    <div class="plv2-history-head">
        <span class="plv2-plate-badge"><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number); ?></span>
        <span class="plv2-history-head__count"><?php echo count($historyTrips); ?> <?php echo $langs->trans('Trips'); ?></span>
    </div>

    <?php if (empty($historyTrips)) : ?>
        <div class="plv2-empty">
            <i class="fas fa-route"></i>
            <p><?php echo $langs->trans('NoTripRecorded'); ?></p>
        </div>
    <?php else : ?>
        <div class="plv2-history">
            <?php foreach ($historyTrips as $ac) :
                $acJson            = json_decode($ac->array_options['options_json'] ?? '{}', true);
                $acDriver          = $acJson['driver'] ?? '—';
                $acIsOpen          = empty($ac->datef);
                $acKmStart         = $ac->array_options['options_starting_mileage'] ?? null;
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

                    <?php if (!$acIsOpen) : ?>
                        <a class="plv2-history-item__download" href="<?php echo $historyUrl . '&action=download_trip_pdf&trip_id=' . (int) $ac->id; ?>">
                            <i class="fas fa-file-pdf"></i> <?php echo $langs->trans('DownloadStateSheet'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
