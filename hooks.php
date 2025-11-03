<?php

require_once('src/Services/index.php');

add_hook('ClientAreaFooterOutput', 1, function (array $vars) {

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
    } elseif ($vars["filename"] == "clientarea") {
        $serviceId = (int)($vars['serviceid'] ?? $vars['id'] ?? ($_GET['id'] ?? 0));

        $params = [
            'serviceid' => $serviceId,
            'stats'     => true,
        ];

        $result = localAPI('GetClientsProducts', $params);

        $output = manage_status_clientarea();
        return $output;
    }

// dentro do callback do ClientAreaHeaderOutput
return '<script></script>';

});



add_hook('EmailPreSend', 1, function($vars) {

    if ($vars['messagename'] == 'New Dedicated Server Information' || $vars['messagename'] == 'Dedicated/VPS Server Welcome Email') {
        $merge_fields['abortsend'] = true;
        sendEmailRd($merge_fields);
            logActivity("foi acionado");

    }
    return $merge_fields;
});
