<?php

use WHMCS\Database\Capsule;

function refreshtokenvps() {
    $config = Capsule::table("sr_rds_station_config")->where("id", 1)->first();
    if (!$config) return false;

    $payload = [
        "client_id" => (string) ($config->client_id ?? ''),
        "client_secret" => (string) ($config->client_secret ?? ''),
        "refresh_token" => (string) ($config->refresh_token ?? '')
    ];

    $ch = curl_init("https://api.rd.services/auth/token");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['accept: application/json','content-type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 20
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $json = json_decode($res, true);
    if (!is_array($json) || empty($json['access_token'])) return false;

    $update = ["access_token" => $json["access_token"]];
    if (!empty($json["refresh_token"])) $update["refresh_token"] = $json["refresh_token"];
    Capsule::table("sr_rds_station_config")->where("id", 1)->update($update);
    logActivity("refresh token: " . $json["refresh_token"]);
    return $json["access_token"];
}

?>