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
 * \ingroup dolicar
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
        $array = ['dolicar' => ['widgets' => [], 'graphs' => [], 'lists' => []]];

        $interneWarehouseID    = getDolGlobalInt('DOLICAR_INTERNE_WAREHOUSE_ID');
        $reparationWarehouseID = getDolGlobalInt('DOLICAR_REPARATION_WAREHOUSE_ID');

        [$warehouseCounts, $warehouseList] = $this->loadWarehouseData($interneWarehouseID, $reparationWarehouseID);

        $array['dolicar']['widgets']['warehouseStats'] = $this->buildWarehouseWidget(
            $warehouseCounts[$interneWarehouseID] ?? 0,
            $warehouseCounts[$reparationWarehouseID] ?? 0
        );

        $array['dolicar']['lists']['overdueCtList'] = $this->loadOverdueCTList();

        if (!empty($warehouseList)) {
            $array['dolicar']['lists']['warehouseList'] = $warehouseList;
        }

        $array['dolicar']['lists']['recentActivities'] = $this->loadRecentActivitiesList();

        return $array;
    }

    /**
     * Build the warehouse stats widget from pre-computed counts.
     *
     * @param  int   $interneCount    CG count in the internal warehouse
     * @param  int   $reparationCount CG count in the repair warehouse
     * @return array                  Widget data array
     */
    private function buildWarehouseWidget(int $interneCount, int $reparationCount): array
    {
        global $langs;

        $listUrl = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_list.php', 1);

        return [
            'title'      => $langs->transnoentities('DashboardWarehouseStats'),
            'picto'      => 'fas fa-warehouse',
            'label'      => [
                $langs->transnoentities('DoliCarInterneWarehouse'),
                $langs->transnoentities('DoliCarReparationWarehouse'),
            ],
            'content'    => [
                '<a href="' . $listUrl . '">' . $interneCount . '</a>',
                '<a href="' . $listUrl . '">' . $reparationCount . '</a>',
            ],
            'widgetName' => 'warehouseStats',
        ];
    }

    /**
     * Load warehouse counts and list data in a single pass using ORM.
     * The product_lot → product_batch → product_stock join has no ORM equivalent
     * so a targeted SQL is used only for that mapping; all business objects
     * (RegistrationCertificateFr, Entrepot) are fetched via ORM.
     *
     * @param  int   $interneWarehouseID    ID of the internal warehouse
     * @param  int   $reparationWarehouseID ID of the repair warehouse
     * @return array                        [counts map, list array (may be empty)]
     * @throws Exception
     */
    private function loadWarehouseData(int $interneWarehouseID, int $reparationWarehouseID): array
    {
        global $langs;

        $warehouseIds = array_values(array_filter([$interneWarehouseID, $reparationWarehouseID]));
        if (empty($warehouseIds)) {
            return [[], []];
        }

        require_once __DIR__ . '/registrationcertificatefr.class.php';
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

        // Minimal SQL: resolve lot_id → warehouse_id through batch/stock tables (no ORM for these Dolibarr core tables)
        $warehouseIdsStr = implode(',', array_map('intval', $warehouseIds));
        $sql  = 'SELECT pl.rowid as lot_id, ps.fk_entrepot as warehouse_id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lot pl';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_batch pb ON pb.batch = pl.batch AND pb.qty > 0';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock AND ps.fk_product = pl.fk_product';
        $sql .= ' WHERE ps.fk_entrepot IN (' . $warehouseIdsStr . ')';

        $lotWarehouseMap = [];
        $resql           = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $lotWarehouseMap[(int) $obj->lot_id] = (int) $obj->warehouse_id;
            }
        }

        if (empty($lotWarehouseMap)) {
            return [[], []];
        }

        // Fetch CGs via ORM — fetchAll() handles entity filtering automatically
        $lotIdsStr = implode(',', array_map('intval', array_keys($lotWarehouseMap)));
        $cgObject  = new RegistrationCertificateFr($this->db);
        $cgList = $cgObject->fetchAll('ASC', 'a_registration_number', 0, 0, [
            'customsql' => 't.status >= 0 AND t.fk_lot IN (' . $lotIdsStr . ')',
        ]);

        if (!is_array($cgList) || empty($cgList)) {
            return [[], []];
        }

        // Fetch warehouses via ORM and cache them
        $warehouseCache = [];
        foreach ($warehouseIds as $wid) {
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch($wid);
            $warehouseCache[$wid] = $entrepot;
        }

        $counts       = [];
        $unsortedRows = [];

        foreach ($cgList as $cg) {
            $warehouseId = $lotWarehouseMap[(int) $cg->fk_lot] ?? null;
            if ($warehouseId === null || !isset($warehouseCache[$warehouseId])) {
                continue;
            }

            $counts[$warehouseId] = ($counts[$warehouseId] ?? 0) + 1;

            $warehouseObj = $warehouseCache[$warehouseId];
            $brandModel   = dol_escape_htmltag(trim($cg->d1_vehicle_brand . ' ' . $cg->d3_vehicle_model));
            $cardUrl      = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $cg->id;

            $unsortedRows[] = [
                '_warehouseOrder' => array_search($warehouseId, $warehouseIds, true),
                'Ref'             => ['value' => $cg->getNomUrl(1)],
                'Vehicle'         => ['value' => '<a href="' . $cardUrl . '">' . $brandModel . '</a>', 'morecss' => 'center'],
                'Warehouse'       => ['value' => $warehouseObj->getNomUrl(1), 'morecss' => 'center'],
            ];
        }

        if (empty($unsortedRows)) {
            return [$counts, []];
        }

        // Sort by warehouse config order (interne first, then reparation)
        usort($unsortedRows, static fn($a, $b) => $a['_warehouseOrder'] <=> $b['_warehouseOrder']);

        $listData = array_map(static function (array $row): array {
            unset($row['_warehouseOrder']);
            return $row;
        }, $unsortedRows);

        return [
            $counts,
            [
                'title'  => $langs->transnoentities('DashboardWarehouseList'),
                'picto'  => 'fas fa-warehouse',
                'name'   => 'warehouseList',
                'labels' => [
                    'Ref'       => 'RegistrationNumber',
                    'Vehicle'   => 'Vehicle',
                    'Warehouse' => 'Warehouse',
                ],
                'data'   => $listData,
            ],
        ];
    }

    /**
     * Build the recent activities list.
     * Uses RegistrationCertificateFr::fetchAll() and fetchObjectLinked() for ORM-based loading.
     * ActionComm multi-element query still uses SQL (no ORM equivalent).
     *
     * @return array List data array
     * @throws Exception
     */
    private function loadRecentActivitiesList(): array
    {
        global $langs;

        require_once __DIR__ . '/registrationcertificatefr.class.php';
        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

        $cgObject = new RegistrationCertificateFr($this->db);
        // fetchAll() handles entity filtering automatically via ismultientitymanaged
        $cgList   = $cgObject->fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0']);

        if (!is_array($cgList) || empty($cgList)) {
            return $this->buildActivitiesListResult([], $langs);
        }

        $cgIds        = [];
        $lotIds       = [];
        $cgById       = [];
        $cgByLotId    = [];
        $linkedByType = [];
        $cgByLinked   = [];

        foreach ($cgList as $cg) {
            $cgIds[]         = $cg->id;
            $cgById[$cg->id] = $cg;

            if (!empty($cg->fk_lot) && $cg->fk_lot > 0) {
                $lotIds[]                      = (int) $cg->fk_lot;
                $cgByLotId[(int) $cg->fk_lot] = $cg;
            }

            // Use ORM to fetch linked objects (invoices, orders, proposals, controls, etc.)
            $cg->fetchObjectLinked();
            foreach ($cg->linkedObjects as $type => $linkedObjArray) {
                foreach ($linkedObjArray as $linkedObjId => $linkedObj) {
                    $linkedByType[$type][]                         = (int) $linkedObjId;
                    $cgByLinked[$type . '_' . (int) $linkedObjId] = $cg;
                }
            }
        }

        $cgIdsStr  = implode(',', $cgIds);
        $orClauses = [];

        $orClauses[] = "(ac.elementtype LIKE '%registrationcertificatefr%' AND ac.fk_element IN ($cgIdsStr))";

        if (!empty($lotIds)) {
            $lotIdsStr   = implode(',', array_unique($lotIds));
            $orClauses[] = "(ac.elementtype = 'productlot' AND ac.fk_element IN ($lotIdsStr))";
        }

        foreach ($linkedByType as $type => $ids) {
            $idsStr      = implode(',', array_unique($ids));
            $escaped     = $this->db->escape($type);
            $orClauses[] = "(ac.fk_element IN ($idsStr) AND (ac.elementtype = '$escaped' OR ac.elementtype LIKE '$escaped@%'))";
        }

        $activityIcons = [
            'AC_REGISTRATIONCERTIFICATEFR_CREATE'  => ['fa-plus-circle',        '#5BA86E'],
            'AC_REGISTRATIONCERTIFICATEFR_MODIFY'  => ['fa-pencil-alt',          '#4A90D9'],
            'AC_REGISTRATIONCERTIFICATEFR_DELETE'  => ['fa-trash-alt',           '#E05353'],
            'AC_REGISTRATIONCERTIFICATEFR_ARCHIVE' => ['fa-archive',             '#888888'],
            'AC_PRODUCTBATCH_CREATE'               => ['fa-barcode',             '#5BA86E'],
            'AC_DOLICAR_CT'                        => ['fa-check-circle',        '#5BA86E'],
            'AC_DOLICAR_REVISION'                  => ['fa-wrench',              '#E8A317'],
            'AC_DOLICAR_ACCIDENT'                  => ['fa-exclamation-triangle','#E05353'],
            'AC_DOLICAR_AUTRE'                     => ['fa-circle',              '#888888'],
        ];

        // ActionComm multi-element query — no ORM equivalent for this pattern
        $sql  = 'SELECT ac.id, ac.code, ac.label, ac.datep, ac.fk_element, ac.elementtype, ac.fk_user_action';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm ac';
        $sql .= ' WHERE ac.entity IN (' . getEntity('agenda') . ')';
        $sql .= ' AND (' . implode(' OR ', $orClauses) . ')';
        $sql .= ' ORDER BY ac.datep DESC LIMIT 10';

        $userCache = [];
        $listData  = [];
        $resql     = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $dateStr    = dol_print_date($this->db->jdate($obj->datep), 'dayhour');
                $isCgDirect = strpos((string) $obj->elementtype, 'registrationcertificatefr') !== false;

                // Resolve which CG this activity belongs to
                if ($isCgDirect) {
                    $cg = $cgById[(int) $obj->fk_element] ?? null;
                } else {
                    $typeBase = explode('@', (string) $obj->elementtype)[0];
                    $cg       = $cgByLotId[(int) $obj->fk_element]
                        ?? $cgByLinked[$obj->elementtype . '_' . (int) $obj->fk_element]
                        ?? $cgByLinked[$typeBase . '_' . (int) $obj->fk_element]
                        ?? null;
                }

                $cgCell = $cg !== null ? $cg->getNomUrl(1) : '';

                // Icon + color badge based on action code
                $iconDef      = $activityIcons[$obj->code] ?? ['fa-circle', '#aaaaaa'];
                $activityCell = '<span style="color:' . $iconDef[1] . '"><i class="fas ' . $iconDef[0] . '" style="margin-right:8px;"></i></span>'
                              . dol_escape_htmltag($obj->label);

                // Fetch user once per unique ID and use getNomUrl
                $userId = (int) $obj->fk_user_action;
                if (!isset($userCache[$userId])) {
                    $ownerUser = new User($this->db);
                    $ownerUser->fetch($userId);
                    $userCache[$userId] = $ownerUser;
                }

                $listData[] = [
                    'CG'   => ['value' => $cgCell, 'morecss' => 'center nowraponall'],
                    'Ref'  => ['value' => $activityCell],
                    'Date' => ['value' => $dateStr, 'morecss' => 'center nowraponall'],
                    'User' => ['value' => $userCache[$userId]->getNomUrl(1), 'morecss' => 'center nowraponall'],
                ];
            }
        }

        return $this->buildActivitiesListResult($listData, $langs);
    }

    /**
     * Wrap activity rows in the list structure expected by SaturneDashboard
     *
     * @param  array     $listData Rows (may be empty)
     * @param  Translate $langs    Translation object
     * @return array               Complete list array
     */
    private function buildActivitiesListResult(array $listData, $langs): array
    {
        if (empty($listData)) {
            $listData[] = [
                'CG'   => ['value' => '', 'morecss' => 'center'],
                'Ref'  => ['value' => '<em>' . $langs->transnoentities('NoRecentActivityDolicar') . '</em>'],
                'Date' => ['value' => '', 'morecss' => 'center'],
                'User' => ['value' => '', 'morecss' => 'center'],
            ];
        }

        return [
            'title'  => $langs->transnoentities('DashboardRecentActivities'),
            'picto'  => 'fontawesome_fa-history_fas_#d35968',
            'name'   => 'recentActivities',
            'labels' => [
                'CG'   => 'RegistrationCertificateFr',
                'Ref'  => 'DashboardActivity',
                'Date' => 'Date',
                'User' => 'UserAuthor',
            ],
            'data'   => $listData,
        ];
    }

    /**
     * Build the overdue technical inspection list.
     * Uses RegistrationCertificateFr::fetchAll() and Productlot::fetch() via ORM.
     * Expiry check and day calculation are done in PHP after ORM loading.
     *
     * @return array List data array
     * @throws Exception
     */
    private function loadOverdueCTList(): array
    {
        global $langs;

        require_once __DIR__ . '/registrationcertificatefr.class.php';
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

        $cgObject  = new RegistrationCertificateFr($this->db);
        $lotObject = new Productlot($this->db);
        $now       = dol_now();

        // fetchAll() handles entity filtering automatically
        $cgList = $cgObject->fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0 AND t.fk_lot > 0']);

        $overdueItems = [];

        if (is_array($cgList)) {
            foreach ($cgList as $cg) {
                $lotObject->fetch((int) $cg->fk_lot);

                if (empty($lotObject->eatby) || $lotObject->eatby <= 0 || $lotObject->eatby >= $now) {
                    continue;
                }

                $overdueItems[] = [
                    'cg'   => $cg,
                    'lot'  => clone $lotObject,
                    'days' => (int) floor(($now - $lotObject->eatby) / 86400),
                ];
            }
        }

        // Sort by eatby ASC — most overdue (oldest expiry) first
        usort($overdueItems, static fn($a, $b) => $a['lot']->eatby <=> $b['lot']->eatby);

        $listData = [];

        foreach ($overdueItems as $item) {
            $cg   = $item['cg'];
            $lot  = $item['lot'];
            $days = $item['days'];

            if ($days >= 90) {
                $color = '#8B0000';
            } elseif ($days >= 60) {
                $color = '#C0392B';
            } elseif ($days >= 30) {
                $color = '#E06C00';
            } else {
                $color = '#E8A317';
            }

            $cardUrl    = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . $cg->id;
            $refLink    = '<a href="' . $cardUrl . '">' . dol_escape_htmltag($cg->a_registration_number) . '</a>';
            $brandModel = dol_escape_htmltag(trim($cg->d1_vehicle_brand . ' ' . $cg->d3_vehicle_model));
            $eatbyDate  = dol_print_date($lot->eatby, 'day');
            $daysHtml   = '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:12px;font-weight:bold;font-size:0.85em;">'
                        . $days . ' j</span>';

            $listData[] = [
                'Ref'        => ['value' => $refLink],
                'Vehicle'    => ['value' => $brandModel, 'morecss' => 'center'],
                'ExpiryDate' => ['value' => '<span style="color:#E05353;">' . $eatbyDate . '</span>', 'morecss' => 'center nowraponall'],
                'DaysOver'   => ['value' => $daysHtml, 'morecss' => 'center nowraponall'],
            ];
        }

        if (empty($listData)) {
            $listData[] = [
                'Ref'        => ['value' => '<em>' . $langs->transnoentities('NoCTOverdueDolicar') . '</em>'],
                'Vehicle'    => ['value' => '', 'morecss' => 'center'],
                'ExpiryDate' => ['value' => '', 'morecss' => 'center'],
                'DaysOver'   => ['value' => '', 'morecss' => 'center'],
            ];
        }

        return [
            'title'  => $langs->transnoentities('DashboardOverdueCT'),
            'picto'  => 'fontawesome_fa-exclamation-triangle_fas_#E05353',
            'name'   => 'overdueCtList',
            'labels' => [
                'Ref'        => 'RegistrationNumber',
                'Vehicle'    => 'Vehicle',
                'ExpiryDate' => 'ExpiryDate',
                'DaysOver'   => 'DaysOverdue',
            ],
            'data'   => $listData,
        ];
    }
}
