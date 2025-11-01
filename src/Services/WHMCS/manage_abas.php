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

  /* Bloco fundido dentro de #domain */
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
  // ---- util: garante troca pra #domain se cloudflare vier ativa ----
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

  // ---- merge: move conteúdo de #additionalinfo para #domain ----
  function doMerge(){
    var domainPane = document.querySelector('.tab-content > .tab-pane#domain');
    var addPane    = document.querySelector('.tab-content > .tab-pane#additionalinfo');
    if (!domainPane || !addPane) return false;

    // evita duplicar
    if (domainPane.querySelector('.merged-additionalinfo')) return true;

    // se #additionalinfo não tem conteúdo útil, não faz nada
    var hasRealContent = Array.prototype.some.call(addPane.childNodes, function(n){
      return (n.nodeType === 1) || (n.nodeType === 3 && String(n.textContent||'').trim() !== '');
    });
    if (!hasRealContent) return true; // considera concluído para evitar loops

    var wrap = document.createElement('div');
    wrap.className = 'merged-additionalinfo';

    var title = document.createElement('h4');
    title.className = 'merged-title';
    title.textContent = 'Informações adicionais';
    wrap.appendChild(title);

    // move filhos (mantém estados de inputs)
    while (addPane.firstChild){
      wrap.appendChild(addPane.firstChild);
    }
    domainPane.appendChild(wrap);
    return true;
  }

  // ---- estratégia de espera: DOMContentLoaded + Observer + retries ----
  function whenReady(cb){
    if (document.readyState === 'complete' || document.readyState === 'interactive'){
      cb();
    } else {
      document.addEventListener('DOMContentLoaded', cb, {once:true});
    }
  }

  function tryMergeWithRetries(max, delay){
    var count = 0;
    var t = setInterval(function(){
      count++;
      if (doMerge()){
        clearInterval(t);
      } else if (count >= max){
        clearInterval(t);
      }
    }, delay);
  }

  function observeForPanes(){
    try{
      var target = document.querySelector('.tab-content') || document.body;
      if (!target || typeof MutationObserver === 'undefined') return;
      var done = false;
      var obs = new MutationObserver(function(){
        if (done) return;
        if (doMerge()){
          done = true;
          obs.disconnect();
        }
      });
      obs.observe(target, {childList:true, subtree:true});
    }catch(e){ console.warn('observer indisponível', e); }
  }

  try {
    console.log('manage_abas_vps: payload', {$resultJson});
  } catch(e){}

  whenReady(function(){
    // garante que a UI não fique presa na aba escondida
    fixActiveCloudflare();

    // tenta imediatamente
    if (!doMerge()){
      // observa carregamentos tardios
      observeForPanes();
      // e ainda tenta em retries (ex.: templates que hidratam em etapas)
      tryMergeWithRetries(15, 200); // 15 tentativas a cada 200ms (~3s)
    }
  });

})();
</script>
        ";
    }

    return "<script>console.log('não encontrou abas', {$resultJson});</script>";
}

?>
