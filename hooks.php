<?php

require_once('src/Services/index.php');

add_hook('ClientAreaFooterOutput', 1, function (array $vars) {

    if ($vars["action"] == "productdetails") {
    $serviceId = (int)($vars['serviceid'] ?? $vars['id'] ?? ($_GET['id'] ?? 0));

    return "<script>console.log('serviceId (hook): {$serviceId}');</script>";
        return "<script>
        const url = new URL(window.location.href);
        const id = url.searchParams.get('id');
        console.log('id da URL:', id);
        
        console.log($varsJson);</script>";
    }

    return "";

});
