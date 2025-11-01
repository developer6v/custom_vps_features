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

    $productname = $result["products"]["product"][0]["name"] ?? '';
    $username    = $result["products"]["product"][0]["username"] ?? '';
    $senha       = $result["products"]["product"][0]["password"] ?? '';

    // credenciais em JSON seguro pra injetar no JS
    $credsJson = json_encode(
        ['username' => $username, 'password' => $senha],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_HEX_AMP
    );

    if (stripos($productname, 'VPS') !== false || stripos($productname, 'n8n') !== false) {

        return "
<style>
  /* Esconde as abas do menu */
  .panel-nav .nav-tabs li:has(> a[href='#email']),
  .panel-nav .nav-tabs li:has(> a[href='#configoptions']),
  .panel-nav .nav-tabs li:has(> a[href='#additionalinfo']),
  .panel-nav .nav-tabs li:has(> a[href='#cloudflare-config']) {
    display: none !important;
  }

  /* Esconde os conteúdos dessas abas */
  .tab-content > .tab-pane#email,
  .tab-content > .tab-pane#configoptions,
  .tab-content > .tab-pane#additionalinfo,
  .tab-content > .tab-pane#cloudflare-config {
    display: none !important;
  }

  /* Blocos inseridos em #domain */
  .merged-access, .merged-configoptions {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid rgba(0,0,0,.08);
  }
  .merged-access .merged-title,
  .merged-configoptions .merged-title {
    font-weight: 600;
    margin: 0 0 8px;
    font-size: 14px;
    line-height: 1.2;
  }

  .cred-row {
    display: flex; gap: 8px; align-items: center; margin: 6px 0;
    flex-wrap: wrap;
  }
  .cred-row label { min-width: 88px; margin: 0; font-weight: 500; }
  .cred-row input {
    flex: 1 1 260px; max-width: 420px;
    padding: 6px 8px; border: 1px solid #dcdcdc; border-radius: 6px;
    background: #fff;
  }
  .cred-row button {
    border: 1px solid #dcdcdc; background: #f8f8f8; border-radius: 6px;
    padding: 6px 10px; cursor: pointer;
  }
</style>

<script>
(function(){
  // ---- garante troca pra #domain se cloudflare vier ativa ----
  function fixActiveCloudflare(){
    try{
      var cloudA = document.querySelector('.panel-nav .nav-tabs a[href=\"#cloudflare-config\"]');
      if (!cloudA) return;
      var li = cloudA.closest('li');
      var wasActive = (cloudA && cloudA.classList.contains('active')) || (li && li.classList.contains('active'));
      if (li) li.style.display = 'none';
      cloudA.classList.remove('active');
      if (li) li.classList.remove('active');
      var cloudPane = document.querySelector('.tab-content > .tab-pane#cloudflare-config');
      if (cloudPane){
        cloudPane.classList.remove('active','in','show');
        cloudPane.style.display = 'none';
      }
      if (wasActive){
        var domainA = document.querySelector('.panel-nav .nav-tabs a[href=\"#domain\"]');
        var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
        if (domainA && typeof jQuery !== 'undefined' && typeof jQuery(domainA).tab === 'function'){
          jQuery(domainA).tab('show');
        } else {
          if (domainA){
            var domainLi = domainA.closest('li');
            if (domainLi) domainLi.classList.add('active');
            domainA.classList.add('active');
          }
          if (domainPane){
            document.querySelectorAll('.tab-content > .tab-pane.active').forEach(function(p){
              p.classList.remove('active','in','show');
              p.style.display = 'none';
            });
            domainPane.classList.add('active','in','show');
            domainPane.style.display = '';
          }
        }
      }
    }catch(e){ console.error('fixActiveCloudflare falhou', e); }
  }

  // ---- injeta bloco de credenciais (username/senha) em #domain ----
  function injectCreds(creds){
    try{
      var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
      if (!domainPane) return false;
      if (domainPane.querySelector('.merged-access')) return true; // evita duplicar

      var wrap = document.createElement('div');
      wrap.className = 'merged-access';

      var title = document.createElement('h4');
      title.className = 'merged-title';
      title.textContent = 'Acesso';
      wrap.appendChild(title);

      // Username
      var rowUser = document.createElement('div');
      rowUser.className = 'cred-row';
      rowUser.innerHTML = `
        <label>Usuário</label>
        <input type=\"text\" id=\"cred-username\" value=\"${(creds.username||'').replace(/\"/g,'&quot;')}\" readonly>
        <button type=\"button\" id=\"btn-copy-user\">Copiar</button>
      `;
      wrap.appendChild(rowUser);

      // Password (com toggle)
      var rowPass = document.createElement('div');
      rowPass.className = 'cred-row';
      rowPass.innerHTML = `
        <label>Senha</label>
        <input type=\"password\" id=\"cred-password\" value=\"${(creds.password||'').replace(/\"/g,'&quot;')}\" readonly>
        <button type=\"button\" id=\"btn-toggle-pass\">Mostrar</button>
        <button type=\"button\" id=\"btn-copy-pass\">Copiar</button>
      `;
      wrap.appendChild(rowPass);

      domainPane.appendChild(wrap);

      // handlers
      var $user = wrap.querySelector('#cred-username');
      var $pass = wrap.querySelector('#cred-password');
      var $btnCopyUser = wrap.querySelector('#btn-copy-user');
      var $btnCopyPass = wrap.querySelector('#btn-copy-pass');
      var $btnToggle   = wrap.querySelector('#btn-toggle-pass');

      if ($btnCopyUser) $btnCopyUser.addEventListener('click', function(){
        navigator.clipboard && navigator.clipboard.writeText($user.value).then(function(){}, function(){});
      });
      if ($btnCopyPass) $btnCopyPass.addEventListener('click', function(){
        navigator.clipboard && navigator.clipboard.writeText($pass.value).then(function(){}, function(){});
      });
      if ($btnToggle) $btnToggle.addEventListener('click', function(){
        if ($pass.type === 'password'){ $pass.type = 'text'; this.textContent = 'Ocultar'; }
        else { $pass.type = 'password'; this.textContent = 'Mostrar'; }
      });

      return true;
    }catch(e){
      console.error('injectCreds falhou:', e);
      return false;
    }
  }

  // ---- merge: move conteúdo de #configoptions para #domain ----
  function mergeConfig(){
    var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
    var cfgPane    = document.querySelector('.tab-content > .tab-pane#configoptions');
    if (!domainPane) return false;

    // cria o contêiner de config se ainda não existir
    var already = domainPane.querySelector('.merged-configoptions');
    if (!already){
      already = document.createElement('div');
      already.className = 'merged-configoptions';
      var title = document.createElement('h4');
      title.className = 'merged-title';
      title.textContent = 'Opções configuráveis';
      already.appendChild(title);
      domainPane.appendChild(already);
    }

    // se #configoptions existir, move o conteúdo para dentro
    if (cfgPane){
      while (cfgPane.firstChild){
        already.appendChild(cfgPane.firstChild);
      }
    }
    return true;
  }

  // ---- estratégia de espera ----
  function whenReady(cb){
    if (document.readyState === 'complete' || document.readyState === 'interactive'){
      cb();
    } else {
      document.addEventListener('DOMContentLoaded', cb, {once:true});
    }
  }
  function observeForPanes(cb){
    try{
      var target = document.querySelector('.tab-content') || document.body;
      if (!target || typeof MutationObserver === 'undefined') return;
      var done = false;
      var obs = new MutationObserver(function(){
        if (done) return;
        if (cb()){
          done = true; obs.disconnect();
        }
      });
      obs.observe(target, {childList:true, subtree:true});
    }catch(e){ console.warn('observer indisponível', e); }
  }
  function retries(fn, max, delay){
    var count = 0;
    var t = setInterval(function(){
      count++;
      if (fn()){ clearInterval(t); }
      else if (count >= max){ clearInterval(t); }
    }, delay);
  }

  // ---- boot ----
  var CREDS = {$credsJson};

  try { console.log('manage_abas_vps: payload', {$resultJson}); } catch(e){}

  whenReady(function(){
    fixActiveCloudflare();

    // injeta credenciais (mesmo que #configoptions ainda não exista)
    var credsOk = injectCreds(CREDS);
    if (!credsOk){
      observeForPanes(function(){ return injectCreds(CREDS); });
      retries(function(){ return injectCreds(CREDS); }, 15, 200);
    }

    // merge de configoptions
    var merged = mergeConfig();
    if (!merged){
      observeForPanes(mergeConfig);
      retries(mergeConfig, 15, 200);
    }
  });

})();
</script>
        ";
    }

    return "<script>console.log('não encontrou abas', {$resultJson});</script>";
}

?>
