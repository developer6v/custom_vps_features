<?php

require_once('src/Services/index.php');

add_hook('ClientAreaFooterOutput', 1, function (array $vars) {

    if ($vars["action"] == "productdetails") {
        $serviceId = (int)($vars['serviceid'] ?? $vars['id'] ?? ($_GET['id'] ?? 0));
        return manage_status_vps($serviceId);
    }

    return "";

});
