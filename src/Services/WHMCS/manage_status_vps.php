<?php

function manage_status_vps($serviceId) {
    $params = [
        'serviceid' => $serviceId,
        'stats'     => true,
    ];

    $result = localAPI('GetClientsProducts', $params);

    $productname = $result["products"]["product"][0]["name"] ?? '';
    if (stripos($productname, 'VPS') !== false) { 
        $ip = $result["products"]["product"][0]["dedicatedip"] ?? '';
        if ($ip == "" || !$ip) {
            return "
                <script>
                console.log('encontrou');
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.querySelector('.status');
                    if (el) { el.textContent = 'Pendente'; }
                });
                </script>
            ";
        }
    }

    // Retorna também o result do localAPI no console
    \$payload = " . json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    ) . ";

    return '<script>console.log(\"não encontrou\", ' . json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    ) . ');</script>';
}

?>
