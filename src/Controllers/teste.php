<?php
include_once('../../../../../init.php');
use WHMCS\Database\Capsule;

$adminUsername = 'admin'; // troque para um admin válido
$serviceId     = 5107;    // do seu link

try {
    $result = localAPI('GetServers', [
        'serviceId'   => $serviceId,
        'fetchStatus' => true, // opcional; remova se não quiser status
    ]);
} catch (Throwable $e) {
    $result = ['result' => 'error', 'message' => $e->getMessage()];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
