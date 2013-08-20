<?php
$defaultCorePath = $modx->getOption('core_path').'components/modshopify/';
$modshopifyCorePath = $modx->getOption('modshopify.core_path',null,$defaultCorePath);

$tpl = $modx->getOption('tpl', $scriptProperties);
$domain = $modx->getOption('domain', $scriptProperties);
$path = $modx->getOption('path', $scriptProperties);
$return_url = $modx->getOption('return_url', $scriptProperties);

$modx->log(modX::LOG_LEVEL_DEBUG
    , '[Multipass] called on page '. $modx->resource->id . ' with the following parameters: '
    .print_r($scriptProperties, true));


$ms = $modx->getService('modshopify', 'ModShopify', $modshopifyCorePath . 'model/', $scriptProperties);

$shop_domain = isset($domain) ? $domain : $this->modx->getOption('modshopify.shop_domain', $ms->config);
$path = isset($path) ? $path : '/';
$return_to = isset($return_url) ? (string) $return_url : 'https://'.$shop_domain.$path;
$token = $ms->getMultipassLogin($return_to);

$modx->log(modX::LOG_LEVEL_DEBUG
    , '[Multipass] Token generated: ' . $token);


$link = 'https://'.$shop_domain.'/account/login/multipass/'.$token;
$output = $ms->getChunk($tpl, array("multipasslink" => $link));

$modx->log(modX::LOG_LEVEL_DEBUG
    , '[Multipass] Output: ' . $output);

return $output;