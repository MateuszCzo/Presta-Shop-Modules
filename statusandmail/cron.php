<?php

include(dirname(__FILE__) . '/../config/config.inc.php');

$statusAndMailModulePath = dirname(__FILE__) . '/statusandmail.php';

if (file_exists($statusAndMailModulePath)) {
    include($statusAndMailModulePath);
  	$statusAndMailModule = new StatusAndMail();
	$statusAndMailModule->install();
	$statusAndMailModule->downloadOrderStatus();
} else {
    PrestaShopLogger::addLog('StatusAndMail module file not found.');
}

?>