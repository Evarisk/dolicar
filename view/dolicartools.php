<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    view/dolicartools.php
 * \ingroup dolicar
 * \brief   Tools page for DoliCar
 */

// Load DoliCar environment
if (file_exists('../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
    require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
    die('Include of dolicar main fails');
}

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

global $conf, $db, $langs, $user;

saturne_load_langs();

$action = GETPOST('action', 'alpha');

// Security check
$permissiontoread = $user->rights->dolicar->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'fix_lot_stock_quantity') {
    $product    = new Product($db);
    $productLot = new ProductLot($db);
    $errors     = [];
    $fixed      = 0;

    $sql  = 'SELECT pb.batch, pb.qty, ps.fk_product, ps.fk_entrepot';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'productbatch pb';
    $sql .= ' JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock';
    $sql .= ' JOIN ' . MAIN_DB_PREFIX . 'product_lot pl ON pl.fk_product = ps.fk_product AND pl.batch = pb.batch';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr rc ON rc.fk_lot = pl.rowid';
    $sql .= ' WHERE pb.qty > 1';

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $result = $product->fetch($obj->fk_product);
            if ($result > 0) {
                $excess = $obj->qty - 1;
                $result = $product->correct_stock_batch(
                    $user,
                    $obj->fk_entrepot,
                    $excess,
                    1,
                    $langs->transnoentities('FixLotStockQuantityLabel'),
                    0,
                    '',
                    '',
                    $obj->batch,
                    '',
                    'dolicar_registrationcertificate',
                    0
                );
                if ($result > 0) {
                    $fixed++;
                } else {
                    $errors[] = $product->error;
                }
            }
        }
        $db->free($resql);
    } else {
        $errors[] = $db->lasterror();
    }

    if (!empty($errors)) {
        setEventMessages('', $errors, 'errors');
    } else {
        setEventMessages($langs->transnoentities('LotStockQuantityFixed', $fixed), [], 'mesgs');
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title = $langs->trans('Tools');

saturne_header(0, '', $title);

print load_fiche_titre($title, '', 'wrench');

// Section: fix lot stock quantity > 1
print load_fiche_titre($langs->trans('FixLotStockQuantity'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

// Count affected lots
$sql  = 'SELECT COUNT(*) as nb, SUM(pb.qty - 1) as excess';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'productbatch pb';
$sql .= ' JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock';
$sql .= ' JOIN ' . MAIN_DB_PREFIX . 'product_lot pl ON pl.fk_product = ps.fk_product AND pl.batch = pb.batch';
$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr rc ON rc.fk_lot = pl.rowid';
$sql .= ' WHERE pb.qty > 1';

$resql   = $db->query($sql);
$nbLots  = 0;
$excess  = 0;
if ($resql) {
    $obj    = $db->fetch_object($resql);
    $nbLots = (int)$obj->nb;
    $excess = (int)$obj->excess;
    $db->free($resql);
}

print '<form name="fix_lot_stock_quantity" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="fix_lot_stock_quantity">';

print '<tr class="oddeven"><td>';
print $langs->trans('FixLotStockQuantity');
print '</td><td>';
print '<div class="wpeo-notice ' . ($nbLots > 0 ? 'notice-warning' : 'notice-success') . '" style="' . ($nbLots > 0 ? 'display:block' : '') . '">';
print '<div class="notice-content">';

if ($nbLots > 0) {
    $sqlDetail  = 'SELECT pb.batch, pb.qty, p.ref as product_ref, p.label as product_label,';
    $sqlDetail .= ' rc.rowid as rc_id, rc.ref as rc_ref, rc.a_registration_number';
    $sqlDetail .= ' FROM ' . MAIN_DB_PREFIX . 'productbatch pb';
    $sqlDetail .= ' JOIN ' . MAIN_DB_PREFIX . 'product_stock ps ON ps.rowid = pb.fk_product_stock';
    $sqlDetail .= ' JOIN ' . MAIN_DB_PREFIX . 'product_lot pl ON pl.fk_product = ps.fk_product AND pl.batch = pb.batch';
    $sqlDetail .= ' JOIN ' . MAIN_DB_PREFIX . 'product p ON p.rowid = ps.fk_product';
    $sqlDetail .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr rc ON rc.fk_lot = pl.rowid';
    $sqlDetail .= ' WHERE pb.qty > 1';
    $sqlDetail .= ' ORDER BY p.ref';

    $resqlDetail = $db->query($sqlDetail);

    $rcCardUrl = dol_buildpath('/custom/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php', 1);

    print '<div class="notice-subtitle"><strong>' . $langs->transnoentities('FixLotStockQuantityWarning', $nbLots, $excess) . '</strong></div>';
    print '<br>';
    print '<table class="noborder" style="width:100%">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('RegistrationCertificateFr') . '</td>';
    print '<td>' . $langs->trans('RegistrationNumber') . '</td>';
    print '<td>' . $langs->trans('DolicarBatch') . '</td>';
    print '<td>' . $langs->trans('Product') . '</td>';
    print '<td class="center">' . $langs->trans('Qty') . '</td>';
    print '</tr>';
    if ($resqlDetail) {
        while ($obj = $db->fetch_object($resqlDetail)) {
            $rcLink = '<a href="' . $rcCardUrl . '?id=' . $obj->rc_id . '">' . dol_escape_htmltag($obj->rc_ref) . '</a>';
            print '<tr class="oddeven">';
            print '<td>' . $rcLink . '</td>';
            print '<td>' . dol_escape_htmltag($obj->a_registration_number) . '</td>';
            print '<td>' . dol_escape_htmltag($obj->batch) . '</td>';
            print '<td>' . dol_escape_htmltag($obj->product_ref) . ' – ' . dol_escape_htmltag($obj->product_label) . '</td>';
            print '<td class="center"><strong style="color:var(--colorbacktitle2,#e05d2c)">' . $obj->qty . '</strong></td>';
            print '</tr>';
        }
        $db->free($resqlDetail);
    }
    print '</table>';
} else {
    print '<div class="notice-subtitle"><strong>' . $langs->trans('NoLotStockQuantityToFix') . '</strong></div>';
}

print '</div></div>';
print '</td><td class="center">';
print '<input type="submit" class="button reposition wpeo-button button-load ' . ($nbLots == 0 ? 'button-disable' : '') . '" value="' . $langs->trans('Fix') . '"' . ($nbLots == 0 ? ' disabled' : '') . '>';
print '</td></tr>';
print '</form>';

print '</table>';

llxFooter();
$db->close();
