<?php

require_once('src/Services/index.php');

add_hook('ClientAreaFooterOutput', 1, function (array $vars) {

    if ($vars["action"] == "productdetails") {
        $varsJson = json_encode(
            $vars,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        );

        return "<script>console.log($varsJson);</script>";
    }

    return "";

});
