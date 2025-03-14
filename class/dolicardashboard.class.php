<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    class/dolicardashboard.class.php
 * \ingroup digiquali
 * \brief   Class file for manage DolicarDashboard
 */

/**
 * Class for DolicarDashboard
 */
class DolicarDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $langs;

        $array = ['dolicar' => ['widgets' => [], 'graphs' => []]];

        $regestrationCertifatesFr         = saturne_fetch_all_object_type('registrationcertificatefr', '', '', 0, 0, [], 'AND', true, true, false, '');
        $numberOfRegestrationCertifatesFr = count($regestrationCertifatesFr);

        $array['dolicar']['graphs'][] = RegistrationCertificateFr::load_dashboard($regestrationCertifatesFr);

        $array['dolicar']['widgets']['informations'] = [
            'title'      => $langs->transnoentities('Informations'),
            'picto'      => 'fas fa-car',
            'label'      => [$langs->transnoentities('Nombre de carte grises'), $langs->transnoentities('DashboardRemainingRequestsRequest')],
            'content'    => [$numberOfRegestrationCertifatesFr, getDolGlobalInt('DOLICAR_API_REMAINING_REQUESTS_COUNTER')],
            'widgetName' => 'informations'
        ];

        return $array;
    }
}
