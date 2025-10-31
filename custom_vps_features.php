<?php

include_once __DIR__ . '/hooks.php';
include_once __DIR__ . '/src/Views/config.php';
include_once __DIR__ . '/src/Config/assets.php';

function custom_vps_features_config() { 
    return array(
        'name' => 'Custom VPS Features',
        'description' => 'Módulo responsável por customizações no painel de VPS no WHMCS.',
        'version' => '1.0',
        'author' => 'Sourei',
        'fields' => array()
    );
}

function custom_vps_features_activate() {
    return array('status' => 'success', 'description' => 'Módulo ativado com sucesso!');
}

function custom_vps_features_deactivate() {
    return array('status' => 'success', 'description' => 'Módulo desativado com sucesso!');
}

function custom_vps_features_output() {
    echo cvf_assets();
    echo cvf_config();
}



?>