<?php
/**
 * Endpoint: /index.php?m=setupStatus&action=check&serviceid=XXXX
 * Retorna JSON { setup: true|false }
 */

use WHMCS\Database\Capsule;

define('WHMCS', true);
require_once __DIR__ . '/../../../../../init.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Somente GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['setup' => false, 'error' => 'Method Not Allowed']); exit;
    }

    // Usuário logado?
    $clientId = \WHMCS\Session::get('uid');
    if (!$clientId) {
        http_response_code(401);
        echo json_encode(['setup' => false, 'error' => 'Unauthorized']); exit;
    }

    // serviceid válido?
    $serviceId = (int)($_GET['serviceid'] ?? 0);
    if ($serviceId <= 0) {
        http_response_code(400);
        echo json_encode(['setup' => false, 'error' => 'Invalid serviceid']); exit;
    }

    // Verifica propriedade do serviço
    $ownerId = Capsule::table('tblhosting')->where('id', $serviceId)->value('userid');
    if ((int)$ownerId !== (int)$clientId) {
        http_response_code(403);
        echo json_encode(['setup' => false, 'error' => 'Forbidden']); exit;
    }

    // Busca dados do serviço via localAPI
    $params = [
        'serviceid' => $serviceId,
        'stats'     => true,
    ];
    $result = localAPI('GetClientsProducts', $params);

    if (!is_array($result) || ($result['result'] ?? '') !== 'success') {
        http_response_code(500);
        echo json_encode([
            'setup'   => false,
            'error'   => 'localAPI error',
            'message' => $result['message'] ?? 'Unknown error',
        ]); exit;
    }

    $product = $result['products']['product'][0] ?? null;
    if (!$product) {
        echo json_encode(['setup' => false]); exit;
    }

    $productName = (string)($product['name'] ?? '');
    $statusRaw   = (string)($product['status'] ?? '');
    $status      = strtolower(trim($statusRaw));
    $dedicatedIp = (string)($product['dedicatedip'] ?? '');

    // ➜ Só filtra se o status for Active
    if ($status !== 'active') {
        echo json_encode(['setup' => false, 'reason' => 'status_not_active']); exit;
    }

    // --- REGRA DE "EM SETUP" (inalterada) ---
    $isVpsOrN8n = (stripos($productName, 'VPS') !== false) || (stripos($productName, 'n8n') !== false);
    $noIpYet    = ($dedicatedIp === '' || $dedicatedIp === '0.0.0.0');

    $inSetup = ($isVpsOrN8n && $noIpYet);

    echo json_encode(['setup' => (bool)$inSetup]); exit;

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['setup' => false, 'error' => 'Exception', 'message' => $e->getMessage()]); exit;
}
