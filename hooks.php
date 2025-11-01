<?php

require_once('src/Services/index.php');

add_hook('ClientAreaHeadOutput', 1, function (array $vars) {

    if ($vars["action"] == "productdetails") {
        $serviceId = (int)($vars['serviceid'] ?? $vars['id'] ?? ($_GET['id'] ?? 0));

        $params = [
            'serviceid' => $serviceId,
            'stats'     => true,
        ];

        $result = localAPI('GetClientsProducts', $params);

        $output = manage_status_vps($serviceId, $result);
        $output .= manage_abas_vps($serviceId, $result);
        return $output;
    } elseif ($vars["action"] == "clientarea") {
        $serviceId = (int)($vars['serviceid'] ?? $vars['id'] ?? ($_GET['id'] ?? 0));

        $params = [
            'serviceid' => $serviceId,
            'stats'     => true,
        ];

        $result = localAPI('GetClientsProducts', $params);

        $output = manage_status_clientarea();
        return $output;
    }

    return "<script>console.log($vars)</script>";

});

