<?php


function manage_status_vps($serviceId) {
    $params = [
        'serviceid' => $serviceId,
        'stats'     => true,    
    ];

    $result = localAPI('GetClientsProducts', $params);
    $productname = $result["products"]["product"][0]["name"];
    if (strpos($productname, 'VPS') !== false) {
        $ip = $result["products"]["product"][0]["dedicatedip"];
        if ($ip == "" || !$ip) {
            return "
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.querySelector('.status');
                    if (el) { el.textContent = 'Pendente'; }
                });
                </script>
            ";
        }
    }

    return "<script>console.log('não encontrou');</script>";
}



?>