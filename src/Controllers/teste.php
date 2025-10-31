<?php
include_once('../../../../../init.php');

$adminUsername = 'admin';   // admin válido
$serviceId     = 5107;      // = tblhosting.id

$params = [
  'serviceid' => $serviceId,
  'stats'     => true,      // opcional: mais campos do serviço
];

$result = localAPI('GetClientsProducts', $params);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
