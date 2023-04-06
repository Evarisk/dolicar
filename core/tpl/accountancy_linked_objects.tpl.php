<?php

require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';
require_once __DIR__ . '/../../../../compta/facture/class/facture.class.php';
require_once __DIR__ . '/../../../../commande/class/commande.class.php';
require_once __DIR__ . '/../../../../comm/propal/class/propal.class.php';

$registration_certificate = new RegistrationCertificateFr($db);
$facture = new Facture($db);
$facturedet = new FactureLigne($db);
$propal = new Propal($db);
$propaldet = new PropaleLigne($db);
$commande = new Commande($db);
$commandedet = new OrderLine($db);

$RC_list = $registration_certificate->fetchAll('', '','','',$fromProductLot ? ['fk_lot'=> GETPOST('id')] : []);
$linked_rc_ids = array();
$linked_facture_ids = array();
if (!empty($RC_list)) {
	foreach ($RC_list as $rc) {
		$linked_rc_ids[] = $rc->id;
		$objectsLinkedList[$rc->id] = $rc->getObjectsLinked();
	}
}
$pictopath = dol_buildpath('/dolicar/img/dolicar.png', 1);
$pictoDoliCar = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

$outputline = '<table><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">'. $pictoDoliCar . $langs->transnoentities('ObjectsLinked') .'</div></td></tr></table>';
$outputline .= '<table><div class="div-table-responsive-no-min"><table class="liste formdoc noborder centpercent"><tbody>';
$outputline .= '<tr class="liste_titre">';
$outputline .= '<td class="float">'. $langs->transnoentities('ObjectType') .'</td>&nbsp;';
$outputline .= '<td class="float">'. $langs->transnoentities('Object') .'</td>&nbsp;';
$outputline .= '<td class="float">'. $langs->transnoentities('Mileage') .'</td>&nbsp;';
$outputline .= '<td class="float">'. $langs->transnoentities('Date') .'</td>&nbsp;';
$outputline .= '</tr>';

$objectsLinkedCounter = 0;

if (!empty($objectsLinkedList)) {
	foreach ($objectsLinkedList as $subList) {
		if (!empty($subList)) {
			foreach ($subList as $key => $object_ids) {
				$objectsLinkedCounter++;
				switch ($key) {
					case 'facture':
						foreach ($object_ids as $object_id) {

							$facture->fetch($object_id);
							$facture->fetch_optionals();
							$outputline .= '<tr>';

							$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
							$outputline .= '<td>'. $facture->getNomUrl(1) .'</td>';
							$outputline .= '<td>'.  $facture->array_options['options_mileage'] .'</td>';
							$outputline .= '<td>'.  dol_print_date($facture->date_creation, 'dayhour') .'</td>';
							$outputline .= '</tr>';
						}
						break;
//								case 'facturedet':
//									foreach ($object_ids as $object_id) {
//
//										$facturedet->fetch($object_id);
//										$facturedet->fetch_optionals();
//										$facture->fetch($facturedet->fk_facture);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $facture->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $facturedet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($facturedet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
					case 'propal':
						foreach ($object_ids as $object_id) {

							$propal->fetch($object_id);
							$propal->fetch_optionals();
							$outputline .= '<tr>';

							$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
							$outputline .= '<td>'. $propal->getNomUrl(1) .'</td>';
							$outputline .= '<td>'.  $propal->array_options['options_mileage'] .'</td>';
							$outputline .= '<td>'.  dol_print_date($propal->date_creation, 'dayhour') .'</td>';
							$outputline .= '</tr>';
						}
						break;
//								case 'propaldet':
//									foreach ($object_ids as $object_id) {
//
//										$propaldet->fetch($object_id);
//										$propaldet->fetch_optionals();
//										$propal->fetch($propaldet->fk_propal);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $propal->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $propaldet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($propaldet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
					case 'commande':
						foreach ($object_ids as $object_id) {

							$commande->fetch($object_id);
							$commande->fetch_optionals();
							$outputline .= '<tr>';

							$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
							$outputline .= '<td>'. $commande->getNomUrl(1) .'</td>';
							$outputline .= '<td>'.  $commande->array_options['options_mileage'] .'</td>';
							$outputline .= '<td>'.  dol_print_date($commande->date_creation, 'dayhour') .'</td>';
							$outputline .= '</tr>';
						}
						break;
//								case 'commandedet':
//									foreach ($object_ids as $object_id) {
//
//										$commandedet->fetch($object_id);
//										$commandedet->fetch_optionals();
//										$commande->fetch($commandedet->fk_commande);
//										$outputline .= '<tr>';
//
//										$outputline .= '<td class="nowrap">'. $langs->transnoentities($key) .'</td>';
//										$outputline .= '<td>'. $commande->getNomUrl() .'</td>';
//										$outputline .= '<td>'.  $commandedet->array_options['options_mileage'] .'</td>';
//										$outputline .= '<td>'.  dol_print_date($commandedet->date_creation, 'dayhour') .'</td>';
//										$outputline .= '</tr>';
//									}
//								break;
				}
			}
		}
	}
}
if ($objectsLinkedCounter == 0) {
	$outputline .= '<tr><td class="nowrap">' . $langs->trans('NoLinkedObjectsToPrint') . '</td>';
	$outputline .= '<td class="float"></td>';
	$outputline .= '<td class="float"></td>';
	$outputline .= '<td class="float"></td>';
	$outputline .= '</tr>';
}
$outputline .= '</tbody></table></div>';
?>
<script>
	jQuery('.fiche .tabBar .fichecenter').append(<?php echo json_encode($outputline) ?>)
</script>
