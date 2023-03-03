<?php

$moduleName = 'DoliCar';
$moduleNameLowerCase = strtolower($moduleName);

// Load Saturne environment
if (file_exists(__DIR__ . '/../saturne/saturne.main.inc.php')) {
	require_once __DIR__ . '/../saturne/saturne.main.inc.php';
} else {
	die('Include of saturne main fails');
}
