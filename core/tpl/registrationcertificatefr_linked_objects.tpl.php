<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/registrationcertificatefr_linked_objects.tpl.php
 * \ingroup dolicar
 * \brief   Template page for registrationcertificatefr linked objects
 */

/**
 * The following vars must be defined :
 * Global   : $db, $langs
 * Variable : $fromProductLot
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

// Load DoliCar libraries
require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

$registrationCertificate = new RegistrationCertificateFr($db);
$propal                  = new Propal($db);
$commande                = new Commande($db);
$facture                 = new Facture($db);

$registrationCertificates = $registrationCertificate->fetchAll('', '',0,0, ['customsql' => ($fromProductLot ? 't.fk_lot = ' : 't.rowid = ') . GETPOST('id')]);
if (is_array($registrationCertificates) && !empty($registrationCertificates)) {
    foreach ($registrationCertificates as $registrationCertificate) {
        $objectsLinkedList[$registrationCertificate->id] = $registrationCertificate->getLinkedObjects();
    }

    $out  = load_fiche_titre($langs->transnoentities('LinkedObjects'), '', 'dolicar_color@dolicar');
    $out .= '<table class="noborder centpercent">';
    $out .= '<tr class="liste_titre">';
    $out .= '<td>' . $langs->trans('ObjectType') . '</td>';
    $out .= '<td>' . $langs->trans('Object') . '</td>';
    $out .= '<td>' . $langs->trans('Mileage') . '</td>';
    $out .= '<td>' . $langs->trans('Date') . '</td>';
    $out .= '</tr>';

    function renderTableRows($object, $objectIDS, $langs, &$out, $key) {
        foreach ($objectIDS as $objectID) {
            $object->fetch($objectID);
            $object->fetch_optionals();
            $out .= '<tr>';
            $out .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
            $out .= '<td>'. $object->getNomUrl(1) .'</td>';
            $out .= '<td>'. $object->array_options['options_mileage'] .'</td>';
            $out .= '<td>'. dol_print_date($object->date_creation, 'dayhour') .'</td>';
            $out .= '</tr>';
        }
    }

    if (!empty($objectsLinkedList)) {
        foreach ($objectsLinkedList as $subList) {
            if (!empty($subList)) {
                foreach ($subList as $key => $objectIDS) {
                    switch ($key) {
                        case 'facture':
                            renderTableRows($facture, $objectIDS, $langs, $out, $key);
                            break;
                        case 'propal':
                            renderTableRows($propal, $objectIDS, $langs, $out, $key);
                            break;
                        case 'commande':
                            renderTableRows($commande, $objectIDS, $langs, $out, $key);
                            break;
                    }
                }
            } else {
                $out .= '<tr><td colspan="4">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
            }
        }
    } else {
        $out .= '<tr><td colspan="4">' . $langs->trans('NoLinkedObjectsToPrint') . '</td></tr>';
    }
    $out .= '</table>';
    ?>

    <script>
        jQuery('.fichecenter').append(<?php echo json_encode($out); ?>);
    </script>
    <?php
}
