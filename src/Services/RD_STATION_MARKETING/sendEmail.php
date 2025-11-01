<?php

use WHMCS\Database\Capsule;

function sendEmailRd(array $merge_fields)
{
    $RD_ENDPOINT = 'https://api.rd.services/platform/events?event_type=conversion';

    // === MAPA DE PORTAS POR OPÇÃO DO SELETOR (ajuste se quiser) ===
    // chaves normalizadas (minúsculas/hífens), ex.: "WindowsServer-2022" => "windowsserver-2022"
    $PORT_MAP_BY_OPTION = [
        'disabled'             => null,  // não enviar cf_porta
        'almalinux-8'          => 22,
        'ubuntu-22.04'         => 22,
        'ubuntu-24.04'         => 22,
        'debian-12'            => 22,
        'windowsserver-2022'   => 3389,  // RDP
        // ajuste se quiser usar porta do painel:
        'template-coolify'     => 22,    // ex.: 22 SSH (ou 3000/8000 para painel)
        'easypanel-template'   => 22,    // ex.: 22 SSH (ou 3000 para painel)
    ];

    // === helpers ===
    $warnings = [];
    $get = function ($k,$d=null) use ($merge_fields){ return array_key_exists($k,$merge_fields)?$merge_fields[$k]:$d; };
    $coalesce = function (...$vals){ foreach($vals as $v){ if(isset($v) && $v!=='') return $v; } return null; };
    $normalizeOsKey = function ($label) {
        $s = mb_strtolower(trim((string)$label));
        $s = preg_replace('/\s+/', '-', $s);
        return $s;
    };

    // === coleta campos básicos dos merge fields ===
    $name         = $coalesce($get('client_name'), trim($coalesce($get('client_first_name'),'').' '.$coalesce($get('client_last_name'),'')));
    $email        = $get('client_email');
    $user         = $get('service_username');
    $password     = $get('service_password');
    $domain       = $get('service_domain');
    $ip           = $coalesce($get('service_dedicated_ip'), $get('service_server_ip'));
    $productName  = $get('service_product_name');
    $nextDue      = $get('service_next_due_date');
    $billingCycle = $get('service_billing_cycle');

    // === pega o service_id para consultar os config options estruturados ===
    $serviceId = (int)$coalesce($get('service_id'), $get('serviceid'));
    $osLabel   = null;

    if ($serviceId > 0) {
        // Consulta estruturada (evita parsear texto)
        $apiRes = localAPI('GetClientsProducts', ['serviceid' => $serviceId, 'stats' => true]);
        if (is_array($apiRes) && ($apiRes['result'] ?? '') === 'success') {
            $prod = $apiRes['products']['product'][0] ?? null;
            if ($prod && isset($prod['configoptions']['configoption']) && is_array($prod['configoptions']['configoption'])) {
                foreach ($prod['configoptions']['configoption'] as $opt) {
                    $optName = trim((string)($opt['option'] ?? ''));
                    if (mb_strtolower($optName) === 'sistema operacional') {
                        $osLabel = trim((string)($opt['value'] ?? ''));
                        break;
                    }
                }
            }
        } else {
            $warnings[] = 'Falha ao obter configoptions via localAPI(GetClientsProducts).';
        }
    } else {
        $warnings[] = 'service_id ausente; não foi possível consultar configoptions.';
    }

    // fallback leve (se quiser manter): tenta achar em service_config_options (string) caso não tenha vindo via API
    if (!$osLabel) {
        $txt = (string)$get('service_config_options');
        if ($txt && preg_match('/Sistema\s+Operacional\s*:\s*([^,\r\n]+)/i', $txt, $m)) {
            $osLabel = trim($m[1]);
        }
    }

    // decide porta a partir do valor do dropdown
    $port = null;
    if ($osLabel) {
        $osKey = $normalizeOsKey($osLabel);
        if (array_key_exists($osKey, $PORT_MAP_BY_OPTION)) {
            $port = $PORT_MAP_BY_OPTION[$osKey]; // pode ser null para "disabled"
        } else {
            // regra de segurança: se contiver "windows" → 3389; senão → 22
            $low = mb_strtolower($osLabel);
            $port = (strpos($low,'windows') !== false) ? 3389 : 22;
            $warnings[] = 'SO não mapeado especificamente: '.$osLabel.' (apliquei regra geral '.($port ?? 'null').')';
        }
    } else {
        $warnings[] = 'cf_so não identificado em configoptions.';
    }

    // avisos se faltar algo importante (payload ainda será enviado)
    if (!$name)         $warnings[] = 'name ausente';
    if (!$email)        $warnings[] = 'email ausente';
    if (!$user)         $warnings[] = 'cf_user_vps ausente';
    if (!$password)     $warnings[] = 'cf_psswrd ausente';
    if (!$domain)       $warnings[] = 'cf_dominio_do_lead ausente';
    if (!$ip)           $warnings[] = 'cf_ipaddress ausente';
    if (!$productName)  $warnings[] = 'cf_vps ausente';
    if (!$nextDue)      $warnings[] = 'cf_proximo_vencimento ausente';
    if (!$billingCycle) $warnings[] = 'cf_ciclo ausente';
    if ($osLabel === 'Disabled') $warnings[] = 'SO=Disabled: não enviaremos cf_porta';

    // monta payload (conversion_identifier dentro do payload, como seu helper)
    $payload = array_filter([
        'conversion_identifier'   => 'vps_so',
        'email'                   => (string)$email,
        'name'                    => (string)$name,

        'cf_user_vps'             => (string)$user,
        'cf_psswrd'               => (string)$password,
        'cf_dominio_do_lead'      => (string)$domain,
        'cf_ipaddress'            => (string)$ip,

        'cf_vps'                  => (string)$productName,
        'cf_proximo_vencimento'   => (string)$nextDue,
        'cf_ciclo'                => (string)$billingCycle,
        'cf_so'                   => (string)$osLabel,
        // envia cf_porta apenas se definido (null cai fora)
        'cf_porta'                => ($port === null ? null : (string)$port),
    ], fn($v)=>$v!==null && $v!=='');

    $body = json_encode([
        'event_type'   => 'CONVERSION',
        'event_family' => 'CDP',
        'payload'      => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // token atual (mesmo fluxo do seu rd_send_conversion)
    $cfg = Capsule::table('sr_rds_station_config')->where('id', 1)->first();
    if (!$cfg) { logActivity('[RDStation] CFG_MISSING ao enviar vps_so'); return false; }
    $token = (string)($cfg->access_token ?? '');

    $do = function($t,$b) use ($RD_ENDPOINT){
        $ch = curl_init($RD_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'authorization: Bearer ' . $t
            ],
            CURLOPT_POSTFIELDS     => $b,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return [$code,$res,$err];
    };

    // envia
    [$code,$res,$err] = $do($token,$body);
    if ($code === 401 && function_exists('refreshToken')) {
        refreshToken();
        $cfg   = Capsule::table('sr_rds_station_config')->where('id', 1)->first();
        $token = (string)($cfg->access_token ?? '');
        [$code,$res,$err] = $do($token,$body);
    }

    if (!empty($warnings)) logActivity('[RDStation] vps_so avisos: '.implode(' | ',$warnings));
    if ($err) { logActivity('[RDStation] vps_so CURL error: '.$err); return false; }
    if ($code < 200 || $code >= 300) { logActivity('[RDStation] vps_so HTTP '.$code.' resp='.$res); return false; }

    logActivity('[RDStation] vps_so enviado com sucesso (HTTP '.$code.')');
    return true;
}
