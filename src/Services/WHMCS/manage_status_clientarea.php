<?php

function manage_status_clientarea() {


    $endpointBase = 'modules/addons/custom_vps_features/src/Controllers/validate_service.php';

    $out = <<<HTML
<style>

    .label-setup {
        background: #ffa64d;
        color: #2b2b2b;
    }
</style>

<script>
(function () {
   
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

        var activeBadge = statusWrapper.querySelector(
            '.label.label-success, .label-success, .label[title="Ativo"], .status-active, .label-success[title]'
        );
        if (!activeBadge) return;

        var href = node.getAttribute('data-href') || '';
        var serviceId = getServiceIdFromHref(href);
        if (!serviceId) return;

        var url = '{$endpointBase}' + encodeURIComponent(serviceId);

        fetch(url, {
            credentials: 'same-origin' 
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (json) {
            // esperamos algo tipo: { setup: true } ou { setup: false }
            if (json && json.setup === true) {
                // troca texto e título
                activeBadge.textContent = 'Fazendo Setup';
                activeBadge.setAttribute('title', 'Fazendo Setup');

                // troca classe visual (verde -> laranja suave)
                activeBadge.classList.remove('label-success');
                activeBadge.classList.add('label-setup');
            }
        })
        .catch(function (err) {
            // silencioso: se falhar, só não troca nada
            // console.warn('setupStatus fetch failed', err);
        });
    }

    /**
     * handleServiceList()
     * Itera todos os cards/list items de serviço na área do cliente.
     * Exemplo de item:
     *
     * <div class="list-group-item-content"
     *      data-href="clientarea.php?action=productdetails&id=5107">
     *      ...
     * </div>
     */
    function handleServiceList() {
        var items = document.querySelectorAll(
            '.list-group-item-content[data-href*="productdetails"]'
        );

        items.forEach(function (item) {
            applySetupStyleIfNeeded(item);
        });
    }

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
        // roda a lógica normal em cima do fakeNode
        applySetupStyleIfNeeded(fakeNode);

        // se o fakeNode resultou em um badge .label-setup,
        // aplicamos de volta no DOM real
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

    /**
     * init()
     * Roda tanto na listagem quanto em uma página individual de produto.
     */
    function init() {
        handleServiceList();
        handleSingleServicePage();
    }

    // garante execução após o DOM existir
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
HTML;

    return $out;
}

?>
