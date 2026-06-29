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
 * \file    core/tpl/public_vehicle_logbook_form.tpl.php
 * \ingroup dolicar
 * \brief   Public vehicle logbook — departure / return form screen
 */

/**
 * Rendered by public/agenda/public_vehicle_logbook.php (inherits its scope).
 * Expects: $actionType, $vehicleUrl, $logoUrl, $langs, $registrationCertificateFR, $form,
 *          $preselectedDriverId, $conf, $user, $lastArrivalMileage, $lastUnfinishedActionComm,
 *          $tripUploadSubDir, $publicInterfaceUseSignatory
 */

$isDepart = ($actionType === 'depart');
?>
<!-- ===== SCREEN 3 : formulaire départ / retour ===== -->
<div class="plv2-header plv2-header--dark">
    <div class="plv2-header__top">
        <a href="<?php echo $vehicleUrl; ?>" class="plv2-back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="plv2-header__logo">
            <img src="<?php echo $logoUrl; ?>" alt="DoliCar" class="plv2-header__logo-img">
            <div class="plv2-header__logo-text">
                <span class="plv2-action-badge <?php echo $isDepart ? 'plv2-action-badge--depart' : 'plv2-action-badge--retour'; ?>">
                    <?php echo $isDepart ? $langs->trans('Departure') : $langs->trans('Return'); ?>
                </span>
                <small><?php echo dol_escape_htmltag($registrationCertificateFR->a_registration_number . ' · ' . $registrationCertificateFR->d1_vehicle_brand . ' ' . $registrationCertificateFR->d3_vehicle_model); ?></small>
            </div>
        </div>
    </div>
</div>

<form id="public-vehicle-log-book-form" method="POST" action="<?php echo $vehicleUrl; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="action_type" value="<?php echo dol_escape_htmltag($actionType); ?>">

    <div class="plv2-form">
        <!-- Identité -->
        <?php if ($isDepart) : ?>
            <div class="plv2-card">
                <h3><i class="fas fa-user"></i> <?php echo $langs->trans('Driver'); ?> <span class="plv2-req">*</span></h3>

                <div class="plv2-seg" id="plv2-driver-type">
                    <button type="button" class="plv2-seg__btn active" data-type="internal">
                        <i class="fas fa-user-tie"></i> <?php echo $langs->trans('DriverInternal'); ?>
                    </button>
                    <button type="button" class="plv2-seg__btn" data-type="external">
                        <i class="fas fa-user-friends"></i> <?php echo $langs->trans('DriverExternal'); ?>
                    </button>
                </div>
                <input type="hidden" name="driver_type" id="plv2-driver-type-value" value="internal">

                <!-- Conducteur interne -->
                <div class="plv2-form-group" id="plv2-driver-internal">
                    <?php echo $form->select_dolusers($preselectedDriverId, 'driver_user_id', 1, null, 0, '', '', (string) $conf->entity); ?>
                </div>

                <!-- Conducteur externe : tiers puis contact -->
                <div id="plv2-driver-external" style="display: none;">
                    <div class="plv2-form-group">
                        <label><?php echo $langs->trans('ThirdParty'); ?></label>
                        <?php
                        dolicarGrantThirdpartyView($user);
                        // forcecombo=0 so Dolibarr attaches its native select2 (full list rendered inline,
                        // no server-side autocomplete which would fail on this public page).
                        echo $form->select_thirdparty_list(0, 'driver_socid', '', '1', 0, 0, [], '', 0, 0, '', '', false, [], 0);
                        ?>
                    </div>
                    <div class="plv2-form-group">
                        <label><?php echo $langs->trans('Contact'); ?></label>
                        <?php echo $form->selectcontacts(-1, '', 'driver_contact_id', 1); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Date / Heure + Kilométrage (compacté sur 2 colonnes) -->
        <div class="plv2-card-row">
            <!-- Date / Heure -->
            <div class="plv2-card">
                <h3><i class="fas fa-calendar"></i> <?php echo $langs->trans('When'); ?></h3>
                <div class="plv2-form-group">
                    <label><?php echo $isDepart ? $langs->trans('StartDateAndHour') : $langs->trans('EndDateAndHour'); ?></label>
                    <input type="datetime-local"
                           name="<?php echo $isDepart ? 'start_date_and_hour' : 'end_date_and_hour'; ?>"
                           value="<?php echo dol_print_date(dol_now(), '%Y-%m-%dT%H:%M'); ?>"
                           required>
                </div>
            </div>

            <!-- Kilométrage -->
            <div class="plv2-card">
                <h3><i class="fas fa-gauge"></i> <?php echo $langs->trans('Mileage'); ?></h3>
                <div class="plv2-form-group">
                    <label>
                        <?php echo $isDepart ? $langs->trans('StartingMileage') : $langs->trans('ArrivalMileage'); ?>
                        <span class="plv2-req">*</span>
                    </label>
                    <?php if ($isDepart) :
                        $minKm = $lastArrivalMileage ?? 0; ?>
                        <input type="number"
                               name="options_starting_mileage"
                               class="plv2-km-input"
                               min="<?php echo $minKm; ?>"
                               placeholder="000000"
                               required>
                        <?php if ($minKm > 0) : ?>
                            <div class="plv2-km-hint">
                                <?php echo $langs->trans('LastKnownMileage'); ?> : <strong><?php echo number_format($minKm, 0, ',', ' ') . ' km'; ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php else :
                        $startKm    = (int) ($lastUnfinishedActionComm[0]->array_options['options_starting_mileage'] ?? 0);
                        $minKmRetour = $startKm > 0 ? $startKm + 1 : 0;
                        $maxKmRetour = $startKm + getDolGlobalInt('DOLICAR_PUBLIC_MAX_ARRIVAL_MILEAGE', 1000); ?>
                        <input type="number"
                               name="options_arrival_mileage"
                               class="plv2-km-input"
                               min="<?php echo $minKmRetour; ?>"
                               max="<?php echo $maxKmRetour; ?>"
                               placeholder="000000"
                               required>
                        <?php if ($startKm > 0) : ?>
                            <div class="plv2-km-hint">
                                <?php echo $langs->trans('DepartureMileage'); ?> : <strong><?php echo number_format($startKm, 0, ',', ' ') . ' km'; ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Niveau de carburant -->
        <div class="plv2-card">
            <h3><i class="fas fa-gas-pump"></i> <?php echo $langs->trans('FuelLevel'); ?></h3>
            <div class="plv2-fuel-btns">
                <?php
                $fuelLevels = [
                    'reserve'     => ['label' => $langs->trans('FuelReserve'), 'icon' => 'fa-battery-empty'],
                    'quarter'     => ['label' => '1/4',                         'icon' => 'fa-battery-quarter'],
                    'half'        => ['label' => '1/2',                         'icon' => 'fa-battery-half'],
                    'threequarters' => ['label' => '3/4',                       'icon' => 'fa-battery-three-quarters'],
                    'full'        => ['label' => $langs->trans('FuelFull'),      'icon' => 'fa-battery-full'],
                ];
                foreach ($fuelLevels as $val => $data) : ?>
                    <button type="button"
                            class="plv2-fuel-btn"
                            data-value="<?php echo $val; ?>"
                            onclick="plv2SelectFuel(this)">
                        <i class="fas <?php echo $data['icon']; ?>"></i>
                        <span><?php echo $data['label']; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="options_fuel_level" id="plv2-fuel-value">
        </div>

        <!-- Observation -->
        <div class="plv2-card">
            <div class="plv2-card-head">
                <span class="plv2-card-head__title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Observation'); ?></span>
                <div class="dolicar-trip-media-row">
                    <?php print saturne_render_media_block('dolicar', $tripUploadSubDir, 'trip_', '', ['show_photo' => true, 'show_audio' => true, 'show_file' => false]); ?>
                </div>
            </div>
            <div class="plv2-form-group">
                <label><?php echo $langs->trans('Remarks'); ?></label>
                <textarea name="<?php echo $isDepart ? 'start_comment' : 'end_comment'; ?>"
                          rows="3"
                          placeholder="<?php echo $langs->trans('RemarksPlaceholder'); ?>"></textarea>
            </div>
        </div>

        <!-- Signature -->
        <?php if ($publicInterfaceUseSignatory) : ?>
            <div class="plv2-card">
                <h3><i class="fas fa-signature"></i> <?php echo $langs->trans('Signature'); ?></h3>
                <div class="plv2-signature-pad" id="plv2-sig-pad">
                    <canvas class="canvas-container editable canvas-signature"></canvas>
                    <div class="plv2-signature-pad__hint"><?php echo $langs->trans('SignHere'); ?></div>
                    <button type="button"
                            class="signature-erase plv2-erase-btn wpeo-button button-square-40 button-rounded button-grey">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="plv2-submit-area">
        <button type="submit"
                class="plv2-btn plv2-btn--full <?php echo $isDepart ? 'plv2-btn--primary' : 'plv2-btn--success'; ?> <?php echo $publicInterfaceUseSignatory ? 'wpeo-button no-load public-vehicle-log-book-validate button-grey button-disable' : ''; ?>">
            <i class="fas fa-check"></i>
            <?php echo $isDepart ? $langs->trans('ValidateDeparture') : $langs->trans('ValidateReturn'); ?>
        </button>
    </div>
</form>
