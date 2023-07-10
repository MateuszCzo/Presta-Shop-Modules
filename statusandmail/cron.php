<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');

$context = Context::getContext();
$statusAndMailModule = Module::getInstanceByName('statusandmail');
$filename = 'info/' . date('Y-m-d') . ' ' . date('H:i:s') . '.txt';
$message = '';

if ($statusAndMailModule instanceof Module) {
    $message .= $statusAndMailModule->processStatusUpdates();
} else {
    PrestaShopLogger::addLog('Something went wrong with statusandmail module.');
    $message .= 'Something went wrong with statusandmail module.';
}

$file = fopen($filename, 'w');
fwrite($file, $message);
fclose($file);

echo nl2br($message);

?>
