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
</style>

<script>
(function(){
  function fixActiveCloudflare(){
    try{
      var cloudA = document.querySelector('.panel-nav .nav-tabs a[href=\"#cloudflare-config\"]');
      if (!cloudA) return;
      var li = cloudA.closest('li');
      var wasActive = (cloudA && cloudA.classList.contains('active')) || (li && li.classList.contains('active'));

      if (li) li.style.display = 'none';
      if (cloudA) cloudA.classList.remove('active');
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

  /* Localiza a UL da lista de informações no container informado */
  function findInfoList(root){
    if (!root) return null;
    return root.querySelector('ul.list-info.list-info-50.list-info-bordered')
        || root.querySelector('ul.list-info-bordered')
        || root.querySelector('ul.list-info');
  }

  /* Cria um <li> padrão da lista: <li><span class='list-info-title'>t</span><span class='list-info-text'>v</span></li> */
  function makeLi(title, value, dataKey){
    var li  = document.createElement('li');
    if (dataKey) li.setAttribute('data-access', dataKey);

    var s1 = document.createElement('span');
    s1.className = 'list-info-title';
    s1.textContent = title;

    var s2 = document.createElement('span');
    s2.className = 'list-info-text';
    s2.textContent = value;

    li.appendChild(s1);
    li.appendChild(s2);
    return li;
  }

  /* Move a UL de #configoptions para #domain (se existir) e retorna a UL final que ficará em #domain */
  function ensureListInDomain(){
    var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
    if (!domainPane) return null;

    // 1) já existe UL em #domain?
    var ulDomain = findInfoList(domainPane);
    if (ulDomain) return ulDomain;

    // 2) pega UL de #configoptions (se existir) e move para #domain
    var cfgPane = document.querySelector('.tab-content > .tab-pane#configoptions');
    var ulCfg   = findInfoList(cfgPane);
    if (ulCfg){
      domainPane.appendChild(ulCfg); // move a UL inteira, com seus LIs
      return ulCfg;
    }

    // 3) fallback: cria uma UL com as classes do seu tema
    var ul = document.createElement('ul');
    ul.className = 'list-info list-info-50 list-info-bordered';
    domainPane.appendChild(ul);
    return ul;
  }

  /* Adiciona os LIs de Username/Senha na UL, sem duplicar */
  function appendAccessRows(ul, user, pass){
    if (!ul) return false;

    // evita duplicar
    var hasUser  = ul.querySelector('li[data-access=\"username\"]');
    var hasPass  = ul.querySelector('li[data-access=\"password\"]');

    if (!hasUser){
      ul.appendChild(makeLi('Username', user || '', 'username'));
    }
    if (!hasPass){
      ul.appendChild(makeLi('Senha', pass || '', 'password'));
    }
    return true;
  }

  // ---- merge principal ----
  function doMergeAndAccess(user, pass){
    var ul = ensureListInDomain();
    if (!ul) return false;
    appendAccessRows(ul, user, pass);
    return true;
  }

  function whenReady(cb){
    if (document.readyState === 'complete' || document.readyState === 'interactive'){
      cb();
    } else {
      document.addEventListener('DOMContentLoaded', cb, {once:true});
    }
  }

  function retries(fn, max, delay){
    var count = 0;
    var t = setInterval(function(){
      count++;
      if (fn()){
        clearInterval(t);
      } else if (count >= max){
        clearInterval(t);
      }
    }, delay);
  }

  var U = " . json_encode($username, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . ";
  var P = " . json_encode($senha,    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . ";

  try { console.log('manage_abas_vps: payload', {$resultJson}); } catch(e){}

  whenReady(function(){
    fixActiveCloudflare();

    // tenta imediatamente
    if (!doMergeAndAccess(U, P)){
      // pequeno retry em caso de hidratação tardia
      retries(function(){ return doMergeAndAccess(U, P); }, 20, 100); // ~2s
    }
  });
})();
</script>
        ";
    }

    return "<script>console.log('não encontrou abas', {$resultJson});</script>";
}

?>
