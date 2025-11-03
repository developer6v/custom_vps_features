<?php

use WHMCS\Config\Setting;

function manage_status_clientarea() {

    // Monta URL absoluta do endpoint, já com ?serviceid=
    $baseUrl = rtrim(Setting::getValue('SystemURL'), '/');
    $endpointBase = $baseUrl . '/modules/addons/custom_vps_features/src/Controllers/validate_service.php?serviceid=';

    $out = "
<style>
  .label-success {
    display: none;
  }
  .label-setup {
    background: #ffa64d; /* laranja suave */
    color: #2b2b2b;
  }
</style>

<script>
    console.log('chegou aqui');
(function () {
  'use strict';

  function getServiceIdFromHref(href) {
    try {
      var a = document.createElement('a');
      a.href = href;

      var qs = a.search;
      if (!qs && href.indexOf('?') !== -1) {
        qs = '?' + href.split('?')[1];
      }

      var params = new URLSearchParams(qs || '');
      return params.get('id') || params.get('serviceid') || null;
    } catch (e) {
      return null;
    }
  }

  function applySetupStyleIfNeeded(node) {
    if (!node) return;

    var statusWrapper = node.querySelector('.list-group-item-status');
    if (!statusWrapper) return;

    // cobre temas comuns (ajuste se necessário)
    var activeBadge = statusWrapper.querySelector(
      '.label.label-success, .label-success, .label[title="Ativo"], .status-active, .label-success[title]'
    );
    if (!activeBadge) return;

    var href = node.getAttribute('data-href') || '';
    var serviceId = getServiceIdFromHref(href);
    if (!serviceId) return;

    // IMPORTANTE: aqui agora tem ?serviceid=
    var url = '{$endpointBase}' + encodeURIComponent(serviceId);

    fetch(url, { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (json) {
        // esperado: { setup: true } ou { setup: false }
        if (json && json.setup === true) {
          activeBadge.textContent = 'Fazendo Setup';
          activeBadge.setAttribute('title', 'Fazendo Setup');
          activeBadge.classList.remove('label-success');
          activeBadge.classList.add('label-setup');
        } else {
          activeBadge.style.display = 'block';
        }
      })
      .catch(function () { 
        activeBadge.style.display = 'block';


       });
  }

  // Lista de serviços
  function handleServiceList() {
    var items = document.querySelectorAll(
      '.list-group-item-content[data-href*="productdetails"]'
    );
    items.forEach(applySetupStyleIfNeeded);
  }

  // Página de detalhes do serviço
  function handleSingleServicePage() {
    var isProductDetails = /clientarea\\.php/i.test(window.location.href)
      && /action=productdetails/i.test(window.location.search);

    if (!isProductDetails) return;

    var params = new URLSearchParams(window.location.search);
    var currentId = params.get('id') || params.get('serviceid');
    if (!currentId) return;

    var statusWrapper = document.querySelector(
      '.list-group-item-status, .service-status, .widget-status'
    );
    if (!statusWrapper) return;

    var fakeNode = document.createElement('div');
    fakeNode.setAttribute(
      'data-href',
      'clientarea.php?action=productdetails&id=' + currentId
    );
    fakeNode.appendChild(statusWrapper.cloneNode(true));

    // roda a mesma lógica no fake
    applySetupStyleIfNeeded(fakeNode);

    // se mudou no fake, aplica no real
    var changedBadge = fakeNode.querySelector('.label-setup');
    if (changedBadge) {
      var realBadge = statusWrapper.querySelector(
        '.label, .status, .label-success, .label[title="Ativo"]'
      );
      if (realBadge) {
        realBadge.textContent = 'Fazendo Setup';
        realBadge.setAttribute('title', 'Fazendo Setup');
        realBadge.classList.remove('label-success');
        realBadge.classList.add('label-setup');
      }
    }
  }

  function init() {
    handleServiceList();
    handleSingleServicePage();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
";

    return $out;
}
