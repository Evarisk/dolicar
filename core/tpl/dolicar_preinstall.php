<?php
global $conf, $db;

if ($conf->global->DOLICAR_MENU_DEFAULT_VEHICLE_UPDATED == 0) {

	require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

	$url = '/dolicar/view/registrationcertificatefr/registrationcertificatefr_card.php?action=create&fk_product=' . $conf->global->DOLICAR_DEFAULT_VEHICLE;

	$sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
	$sql .= " url='".$db->escape($url)."'";
	$sql .= " WHERE leftmenu='dolicar_registrationcertificatefr'";
	$sql .= " AND entity=" . $conf->entity;

	$resql = $db->query($sql);
	if (!$resql) {
		$error = "Error ".$db->lasterror();
		return -1;
	}
	dolibarr_set_const($db, 'DOLICAR_MENU_DEFAULT_VEHICLE_UPDATED', 1, 'integer', 0, '', $conf->entity);
	?>
	<script>
		window.location.reload()
	</script>
<?php
}
