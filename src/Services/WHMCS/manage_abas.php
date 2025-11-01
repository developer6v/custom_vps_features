<?php

function manage_abas_vps($serviceId, $result) {

    $resultJson = json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    );
/*
informações cloudflare
email
opções configuraveis
informacoes adicionais

*/
    
    $productname = $result["products"]["product"][0]["name"] ?? '';
    if (stripos($productname, 'VPS') !== false) {
        $ip = $result["products"]["product"][0]["dedicatedip"] ?? '';
        if ($ip == "" || !$ip) {
            return "
                <style>
                  
                  
                  
                  
                </style>
                <script>
                  console.log('encontrou abas', {$resultJson});
                 
                </script>
            ";
        }
    }

    return "<script>console.log('não encontrou abas', {$resultJson});</script>";
}

?>
