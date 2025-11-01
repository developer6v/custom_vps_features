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
    if (stripos($productname, 'VPS') !== false || stripos($productname, 'n8n') !== false) {

            return "
                <style>
                  
                    .panel-nav .nav-tabs li:has(> a[href='#email']),
                    .panel-nav .nav-tabs li:has(> a[href='#configoptions']),
                    .panel-nav .nav-tabs li:has(> a[href='#additionalinfo']),
                    .panel-nav .nav-tabs li:has(> a[href='#cloudflare-config'])
                    { 
                    display: none !important; 
                    }

                    .merged-additionalinfo {
                        margin-top: 16px;
                        padding-top: 12px;
                        border-top: 1px solid rgba(0,0,0,.08);
                    }
                    .merged-additionalinfo > .merged-title {
                        font-weight: 600;
                        margin: 0 0 8px;
                        font-size: 14px;
                        line-height: 1.2;
                    }
                </style>
                <script>
                  (function(){
                    try {
                      console.log('encontrou abas', {$resultJson});

                      var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
                      var addPane    = document.querySelector('.tab-content > .tab-pane#additionalinfo');

                      // só prossegue se existir #domain e #additionalinfo
                      if (!domainPane || !addPane) return;

                      // evita duplicar se já fundiu
                      if (domainPane.querySelector('.merged-additionalinfo')) return;

                      // cria wrapper
                      var wrap = document.createElement('div');
                      wrap.className = 'merged-additionalinfo';

                      var title = document.createElement('h4');
                      title.className = 'merged-title';
                      title.textContent = 'Informações adicionais';
                      wrap.appendChild(title);

                      // move TODO o conteúdo (filhos) de #additionalinfo para o wrapper
                      while (addPane.firstChild) {
                        wrap.appendChild(addPane.firstChild);
                      }

                      // injeta no final da aba do servidor
                      domainPane.appendChild(wrap);
                    } catch(e) {
                      console.error('merge additionalinfo -> domain falhou:', e);
                    }
                  })();
                </script>
            ";
    }

    return "<script>console.log('não encontrou abas', {$resultJson});</script>";
}

?>
