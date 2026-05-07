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

        $array['dolicar']['widgets']['warehouseStats'] = $this->loadWarehouseWidget($interneWarehouseID, $reparationWarehouseID);

        $array['dolicar']['lists']['overdueCtList']    = $this->loadOverdueCTList();

        $warehouseList = $this->loadWarehouseList($interneWarehouseID, $reparationWarehouseID);
        if (!empty($warehouseList)) {
            $array['dolicar']['lists']['warehouseList'] = $warehouseList;
        }

        $array['dolicar']['lists']['recentActivities'] = $this->loadRecentActivitiesList();

        return $array;
    }

    /**
     * Build the warehouse stats widget data
     *
     * @param  int   $interneWarehouseID    ID of the internal warehouse
     * @param  int   $reparationWarehouseID ID of the repair warehouse
     * @return array                        Widget data array
     */
    private function loadWarehouseWidget(int $interneWarehouseID, int $reparationWarehouseID): array
    {
        global $langs;

        $warehouseCounts = $this->getWarehouseCounts($interneWarehouseID, $reparationWarehouseID);
        $interneCount    = $warehouseCounts[$interneWarehouseID] ?? 0;
        $reparationCount = $warehouseCounts[$reparationWarehouseID] ?? 0;

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
     * Count registration certificates per warehouse.
     * GROUP BY aggregation has no ORM equivalent — raw SQL is intentional here.
     *
     * @param  int   $interneWarehouseID    ID of the internal warehouse
     * @param  int   $reparationWarehouseID ID of the repair warehouse
     * @return array                        Map of warehouse ID => count
     */
    private function getWarehouseCounts(int $interneWarehouseID, int $reparationWarehouseID): array
    {
        $warehouseIds = array_values(array_filter([$interneWarehouseID, $reparationWarehouseID]));
        if (empty($warehouseIds)) {
            return [];
        }

        $warehouseIdsStr = implode(',', array_map('intval', $warehouseIds));

        $sql  = 'SELECT ps.fk_entrepot, COUNT(DISTINCT rcfr.rowid) as nb';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr rcfr';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_lot pl ON pl.rowid = rcfr.fk_lot';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_batch pb ON pb.batch = pl.batch AND pb.qty > 0';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock AND ps.fk_product = pl.fk_product';
        $sql .= ' WHERE rcfr.entity IN (' . getEntity('registrationcertificatefr') . ')';
        $sql .= ' AND rcfr.status >= 0';
        $sql .= ' AND rcfr.fk_lot IS NOT NULL AND rcfr.fk_lot > 0';
        $sql .= ' AND ps.fk_entrepot IN (' . $warehouseIdsStr . ')';
        $sql .= ' GROUP BY ps.fk_entrepot';

        $counts = [];
        $resql  = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $counts[(int) $obj->fk_entrepot] = (int) $obj->nb;
            }
        }

        return $counts;
    }

    /**
     * Build the dashboard list showing registration certificates sorted by warehouse.
     * GROUP BY + JOIN aggregation has no ORM equivalent — raw SQL is intentional here.
     *
     * @param  int   $interneWarehouseID    ID of the internal warehouse
     * @param  int   $reparationWarehouseID ID of the repair warehouse
     * @return array                        List data array, empty if no data
     */
    private function loadWarehouseList(int $interneWarehouseID, int $reparationWarehouseID): array
    {
        global $langs;

        $warehouseIds = array_values(array_filter([$interneWarehouseID, $reparationWarehouseID]));
        if (empty($warehouseIds)) {
            return [];
        }

        $warehouseIdsStr = implode(',', array_map('intval', $warehouseIds));

        $sql  = 'SELECT rcfr.rowid, rcfr.a_registration_number, rcfr.d1_vehicle_brand, rcfr.d3_vehicle_model,';
        $sql .= ' e.rowid as warehouse_id, e.ref as warehouse_ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr rcfr';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_lot pl ON pl.rowid = rcfr.fk_lot';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_batch pb ON pb.batch = pl.batch AND pb.qty > 0';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock AND ps.fk_product = pl.fk_product';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'entrepot e ON e.rowid = ps.fk_entrepot';
        $sql .= ' WHERE rcfr.entity IN (' . getEntity('registrationcertificatefr') . ')';
        $sql .= ' AND rcfr.status >= 0';
        $sql .= ' AND rcfr.fk_lot IS NOT NULL AND rcfr.fk_lot > 0';
        $sql .= ' AND ps.fk_entrepot IN (' . $warehouseIdsStr . ')';
        $sql .= ' ORDER BY e.rowid ASC, rcfr.a_registration_number ASC';

        $listData = [];
        $resql    = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $cardUrl    = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . ((int) $obj->rowid);
                $refLink    = '<a href="' . $cardUrl . '">' . dol_escape_htmltag($obj->a_registration_number) . '</a>';
                $brandModel = dol_escape_htmltag(trim($obj->d1_vehicle_brand . ' ' . $obj->d3_vehicle_model));

                $listData[] = [
                    'Ref'       => ['value' => $refLink],
                    'Vehicle'   => ['value' => $brandModel, 'morecss' => 'center'],
                    'Warehouse' => ['value' => dol_escape_htmltag($obj->warehouse_ref), 'morecss' => 'center'],
                ];
            }
        }

        if (empty($listData)) {
            return [];
        }

        return [
            'title'  => $langs->transnoentities('DashboardWarehouseList'),
            'picto'  => 'fas fa-warehouse',
            'name'   => 'warehouseList',
            'labels' => [
                'Ref'       => 'RegistrationNumber',
                'Vehicle'   => 'Vehicle',
                'Warehouse' => 'Warehouse',
            ],
            'data'   => $listData,
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

        $cgObject = new RegistrationCertificateFr($this->db);
        // fetchAll() handles entity filtering automatically via ismultientitymanaged
        $cgList   = $cgObject->fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0']);

        if (!is_array($cgList) || empty($cgList)) {
            return $this->buildActivitiesListResult([], $langs);
        }

        $cgIds         = [];
        $lotIds        = [];
        $cgPlaques     = [];
        $linkedByType  = [];
        $linkedPlaques = [];

        foreach ($cgList as $cg) {
            $cgIds[]            = $cg->id;
            $cgPlaques[$cg->id] = $cg->a_registration_number;

            if (!empty($cg->fk_lot) && $cg->fk_lot > 0) {
                $lotIds[]                               = (int) $cg->fk_lot;
                $cgPlaques['lot_' . (int) $cg->fk_lot] = $cg->a_registration_number;
            }

            // Use ORM to fetch linked objects (invoices, orders, proposals, controls, etc.)
            $cg->fetchObjectLinked();
            foreach ($cg->linkedObjects as $type => $linkedObjArray) {
                foreach ($linkedObjArray as $linkedObjId => $linkedObj) {
                    $linkedByType[$type][]                          = (int) $linkedObjId;
                    $linkedPlaques[$type . '_' . (int) $linkedObjId] = $cg->a_registration_number;
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

        // ActionComm multi-element query — no ORM equivalent for this pattern
        $sql  = 'SELECT ac.id, ac.label, ac.datep, ac.fk_element, ac.elementtype,';
        $sql .= ' u.login, u.lastname, u.firstname';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm ac';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = ac.fk_user_action';
        $sql .= ' WHERE ac.entity IN (' . getEntity('agenda') . ')';
        $sql .= ' AND (' . implode(' OR ', $orClauses) . ')';
        $sql .= ' ORDER BY ac.datep DESC LIMIT 15';

        $listData = [];
        $resql    = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $dateStr    = dol_print_date($this->db->jdate($obj->datep), 'dayhour');
                $userStr    = trim($obj->firstname . ' ' . $obj->lastname) ?: $obj->login;
                $label      = dol_escape_htmltag($obj->label);
                $isCgDirect = strpos((string) $obj->elementtype, 'registrationcertificatefr') !== false;

                if ($isCgDirect && isset($cgPlaques[(int) $obj->fk_element])) {
                    $cardUrl = dol_buildpath('/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1) . '?id=' . ((int) $obj->fk_element);
                    $label   = '<a href="' . $cardUrl . '">' . $label . '</a>';
                }

                if (!$isCgDirect) {
                    $plaque = $cgPlaques['lot_' . (int) $obj->fk_element]
                        ?? $linkedPlaques[$obj->elementtype . '_' . (int) $obj->fk_element]
                        ?? $linkedPlaques[explode('@', $obj->elementtype)[0] . '_' . (int) $obj->fk_element]
                        ?? null;

                    if ($plaque !== null) {
                        $label .= ' <span style="border:1px solid #d35968;padding:1px 5px;border-radius:3px;font-size:0.85em;color:#d35968;">'
                               . dol_escape_htmltag($plaque) . '</span>';
                    }
                }

                $listData[] = [
                    'Ref'  => ['value' => $label],
                    'Date' => ['value' => $dateStr, 'morecss' => 'center nowraponall'],
                    'User' => ['value' => dol_escape_htmltag($userStr), 'morecss' => 'center'],
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
