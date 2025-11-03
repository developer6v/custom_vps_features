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
    $status      = $result["products"]["product"][0]["status"] ?? '';

    if ((stripos($productname, 'VPS') !== false || stripos($productname, 'n8n') !== false )) {

        // Monte em pedaços (sem HEREDOC)
        $out  = '';
        $out .= '<style>
  /* Esconde as abas do menu */
  .panel-nav .nav-tabs li:has(> a[href="#email"]),
  .panel-nav .nav-tabs li:has(> a[href="#configoptions"]),
  .panel-nav .nav-tabs li:has(> a[href="#additionalinfo"]),
  .panel-nav .nav-tabs li:has(> a[href="#cloudflare-config"]) { display:none!important; }

  /* Esconde os conteúdos dessas abas */
  .tab-content > .tab-pane#email,
  .tab-content > .tab-pane#configoptions,
  .tab-content > .tab-pane#additionalinfo,
  .tab-content > .tab-pane#cloudflare-config { display:none!important; }
</style>';

        $out .= '<script>(function(){';
        $out .= '
  function fixActiveCloudflare(){
    try{
      var cloudA = document.querySelector(\'.panel-nav .nav-tabs a[href="#cloudflare-config"]\');
      if (!cloudA) return;
      var li = cloudA.closest(\'li\');
      var wasActive = (cloudA && cloudA.classList.contains(\'active\')) || (li && li.classList.contains(\'active\'));
      if (li) li.style.display = \'none\';
      if (cloudA) cloudA.classList.remove(\'active\');
      if (li) li.classList.remove(\'active\');

      var cloudPane = document.querySelector(\'.tab-content > .tab-pane#cloudflare-config\');
      if (cloudPane){
        cloudPane.classList.remove(\'active\',\'in\',\'show\');
        cloudPane.style.display = \'none\';
      }

      if (wasActive){
        var domainA = document.querySelector(\'.panel-nav .nav-tabs a[href="#domain"]\');
        var domainPane = document.querySelector(\'.tab-content > .tab-pane#domain\');
        if (domainA && typeof jQuery !== \'undefined\' && typeof jQuery(domainA).tab === \'function\'){
          jQuery(domainA).tab(\'show\');
        } else {
          if (domainA){
            var domainLi = domainA.closest(\'li\');
            if (domainLi) domainLi.classList.add(\'active\');
            domainA.classList.add(\'active\');
          }
          if (domainPane){
            document.querySelectorAll(\'.tab-content > .tab-pane.active\').forEach(function(p){
              p.classList.remove(\'active\',\'in\',\'show\');
              p.style.display = \'none\';
            });
            domainPane.classList.add(\'active\',\'in\',\'show\');
            domainPane.style.display = \'\';
          }
        }
      }
    }catch(e){ console.error(\'fixActiveCloudflare falhou\', e); }
  }

  function findInfoList(root){
    if (!root) return null;
    return root.querySelector(\'ul.list-info.list-info-50.list-info-bordered\')
        || root.querySelector(\'ul.list-info-bordered\')
        || root.querySelector(\'ul.list-info\');
  }

  function makeLi(title, value, dataKey){
    var li  = document.createElement(\'li\');
    if (dataKey) li.setAttribute(\'data-access\', dataKey);
    var s1 = document.createElement(\'span\'); s1.className = \'list-info-title\'; s1.textContent = title;
    var s2 = document.createElement(\'span\'); s2.className = \'list-info-text\';  s2.textContent = value;
    li.appendChild(s1); li.appendChild(s2);
    return li;
  }

  function mergeConfigLisIntoDomainUl(){
    var domainPane = document.querySelector(\'.tab-content > .tab-pane#domain\');
    if (!domainPane) return null;

    var ulDomain = findInfoList(domainPane);
    var cfgPane  = document.querySelector(\'.tab-content > .tab-pane#configoptions\');
    var ulCfg    = findInfoList(cfgPane);

    if (!ulDomain && !ulCfg) return null;

    if (!ulDomain && ulCfg){
      domainPane.appendChild(ulCfg);
      ulDomain = ulCfg;
      ulDomain.setAttribute(\'data-merged\', \'1\');
      return ulDomain;
    }

    if (ulDomain && ulCfg && !ulDomain.hasAttribute(\'data-merged\')){
      Array.from(ulCfg.children).forEach(function(li){
        if (li && li.tagName === \'LI\') ulDomain.appendChild(li);
      });
      ulDomain.setAttribute(\'data-merged\', \'1\');
    }
    return ulDomain || ulCfg || null;
  }

  function appendAccessRows(ul, user, pass){
    if (!ul) return false;
    if (!ul.querySelector(\'li[data-access="username"]\')) ul.appendChild(makeLi(\'Username\', user || \'\', \'username\'));
    if (!ul.querySelector(\'li[data-access="password"]\')) ul.appendChild(makeLi(\'Senha\',    pass || \'\', \'password\'));
    return true;
  }

  function doAll(user, pass){
    var ul = mergeConfigLisIntoDomainUl();
    if (!ul){
      var domainPane = document.querySelector(\'.tab-content > .tab-pane#domain\');
      ul = findInfoList(domainPane);
    }
    if (!ul) return false;
    appendAccessRows(ul, user, pass);
    return true;
  }

  function whenReady(cb){
    if (document.readyState === \'complete\' || document.readyState === \'interactive\') cb();
    else document.addEventListener(\'DOMContentLoaded\', cb, {once:true});
  }

  function retries(fn, max, delay){
    var count = 0;
    var t = setInterval(function(){
      count++;
      if (fn()) clearInterval(t);
      else if (count >= max) clearInterval(t);
    }, delay);
  }
';

        // injeta os valores JS de forma segura
        $out .= '  var U = ' . json_encode($username, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        $out .= '  var P = ' . json_encode($senha,    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';

        // continua o script
        $out .= '
  whenReady(function(){
    fixActiveCloudflare();
    if (!doAll(U, P)){
      retries(function(){ return doAll(U, P); }, 20, 100); // ~2s
    }
  });
})();</script>';

        return $out;
    }

    // fallback de debug
    return '<script>console.log("não encontrou abas", ' . $resultJson . ');</script>';
}
