<?php

use WHMCS\Config\Setting;

function manage_status_clientarea(): string
{
    // Monta URL absoluta do endpoint, já com ?serviceid=
    $baseUrl      = rtrim(Setting::getValue('SystemURL'), '/');
    $endpointBase = $baseUrl . '/modules/addons/custom_vps_features/src/Controllers/validate_service.php?serviceid=';

    $out  = '';
    $out .= '<style>
  .label-success{display:none;}
  .label-setup{background:#ffa64d;color:#2b2b2b;}
</style>';

    // usa json_encode para inserir string JS com segurança
    $out .= '<script>
console.log("chegou aqui");
(function(){\'use strict\';
  const endpointBase = ' . json_encode($endpointBase) . ';

  function getServiceIdFromHref(href){
    try{
      const a = document.createElement("a");
      a.href = href;
      let qs = a.search;
      if(!qs && href.indexOf("?") !== -1){ qs = "?" + href.split("?")[1]; }
      const params = new URLSearchParams(qs || "");
      return params.get("id") || params.get("serviceid") || null;
    }catch(e){ return null; }
  }

  function applySetupStyleIfNeeded(node){
    if(!node) return;

    const statusWrapper = node.querySelector(".list-group-item-status");
    if(!statusWrapper) return;

    const activeBadge = statusWrapper.querySelector(
      ".label.label-success, .label-success, .label[title=\\"Ativo\\"], .status-active, .label-success[title]"
    );
    if(!activeBadge) return;

    const href = node.getAttribute("data-href") || "";
    const serviceId = getServiceIdFromHref(href);
    if(!serviceId) return;

    const url = endpointBase + encodeURIComponent(serviceId);

    fetch(url, { credentials: "same-origin" })
      .then(res => { if(!res.ok) throw new Error("HTTP " + res.status); return res.json(); })
      .then(json => {
        if(json && json.setup === true){
          activeBadge.textContent = "Fazendo Setup";
          activeBadge.setAttribute("title", "Fazendo Setup");
          activeBadge.classList.remove("label-success");
          activeBadge.classList.add("label-setup");
        } else {
          activeBadge.style.display = "block";
        }
      })
      .catch(() => { activeBadge.style.display = "block"; });
  }

  function handleServiceList(){
    const items = document.querySelectorAll(".list-group-item-content[data-href*=\\\"productdetails\\\"]");
    items.forEach(applySetupStyleIfNeeded);
  }

  function handleSingleServicePage(){
    const isProductDetails = /clientarea\\.php/i.test(window.location.href)
      && /action=productdetails/i.test(window.location.search);
    if(!isProductDetails) return;

    const params = new URLSearchParams(window.location.search);
    const currentId = params.get("id") || params.get("serviceid");
    if(!currentId) return;

    const statusWrapper = document.querySelector(".list-group-item-status, .service-status, .widget-status");
    if(!statusWrapper) return;

    const fakeNode = document.createElement("div");
    fakeNode.setAttribute("data-href", "clientarea.php?action=productdetails&id=" + currentId);
    fakeNode.appendChild(statusWrapper.cloneNode(true));

    applySetupStyleIfNeeded(fakeNode);

    const changedBadge = fakeNode.querySelector(".label-setup");
    if(changedBadge){
      const realBadge = statusWrapper.querySelector(".label, .status, .label-success, .label[title=\\"Ativo\\"]");
      if(realBadge){
        realBadge.textContent = "Fazendo Setup";
        realBadge.setAttribute("title", "Fazendo Setup");
        realBadge.classList.remove("label-success");
        realBadge.classList.add("label-setup");
      }
    }
  }

  function init(){ handleServiceList(); handleSingleServicePage(); }
  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", init);
  }else{
    init();
  }
})();</script>';

    return $out;
}
