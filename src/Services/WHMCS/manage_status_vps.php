<?php

function manage_status_vps($serviceId) {
    $params = [
        'serviceid' => $serviceId,
        'stats'     => true,
    ];

    $result = localAPI('GetClientsProducts', $params);
    $resultJson = json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    );

    $productname = $result["products"]["product"][0]["name"] ?? '';
    if (stripos($productname, 'VPS') !== false) {
        $ip = $result["products"]["product"][0]["dedicatedip"] ?? '';
        if ($ip == "" || !$ip) {
            return "
                <style>
                  .status{ display:none; }
                  .status--pendente{ display:inline; color:#E6A15A; } /* laranja suave */
                </style>
                <script>
                  console.log('encontrou', {$resultJson});
                  document.addEventListener('DOMContentLoaded', function () {
                    var el = document.querySelector('.status');
                    if (el) {
                      el.textContent = 'Pendente';
                      el.classList.add('status--pendente');
                    }
                  });
                </script>
            ";
        }
    }

    return "<script>console.log('não encontrou', {$resultJson});</script>";
}

?>
