<?php
$defaultCorePath = $modx->getOption('core_path').'components/modshopify/';
$modshopifyCorePath = $modx->getOption('modshopify.core_path',null,$defaultCorePath);

if (!function_exists('truncate')){
function truncate($string, $length, $stopanywhere=false) {
    //truncates a string to a certain char length, stopping on a word if not specified otherwise.
    if (strlen($string) > $length) {
        //limit hit!
        $string = substr($string,0,($length -3));
        if ($stopanywhere) {
            //stop anywhere
            $string .= '...';
        } else{
            //stop on a word.
            $string = substr($string,0,strrpos($string,' ')).'...';
        }
    }
    return $string;
}
}



$defaults = array(  
  'limit' => 50,
  'page' => 1,
  'published_status' => 'published',
  'vendor' => 'Bronson',
  'handle' => '',
  'product_type' => 'Supplements', 
  'collection_id' => '', 
  'fields' => 'id,images,title,body_html,options,variants,handle',
    
  'containerTpl' => 'modshopifyOuterTpl',
  'productTpl' => 'modshopifyProductTpl',
  'productImgTpl' => 'modshopifyProductImgTpl',
  'productVariantTpl' => 'modshopifyProductVariantTpl',
  
  'thumbsWidth' => 120,
  'thumbsHeight' => 218,
  'thumbsArgs' => '&zc=1'
);



$scriptProperties = array_merge($defaults,$scriptProperties);


$modx->log(modX::LOG_LEVEL_DEBUG
    , '[getProducts] Properties: '.print_r($scriptProperties, true));

$scriptProperties['thumbs_options'] = "";
if (!empty($scriptProperties['thumbsWidth'])) $scriptProperties['thumbs_options'] .= "&w=" . $scriptProperties['thumbsWidth'];
if (!empty($scriptProperties['thumbsHeight'])) $scriptProperties['thumbs_options'] .= "&h=" . $scriptProperties['thumbsHeight'];
$scriptProperties['thumbs_options'] .= $scriptProperties['thumbsArgs'];
unset($scriptProperties['thumbsWidth'], $scriptProperties['thumbsHeight'], $scriptProperties['thumbsArgs']);

$ms = $modx->getService('modshopify', 'ModShopify', $modshopifyCorePath . 'model/', $scriptProperties);

$output = array();
$shop = $ms->getShop();
if(empty($shop)) return;

$callParams = array();
if (!empty($scriptProperties['vendor'])) $callParams['vendor'] = $scriptProperties['vendor'];
if (!empty($scriptProperties['handle'])) $callParams['handle'] = $scriptProperties['handle'];
if (!empty($scriptProperties['product_type'])) $callParams['product_type'] = $scriptProperties['product_type'];
if (!empty($scriptProperties['limit'])) $callParams['limit'] = $scriptProperties['limit'];
if (!empty($scriptProperties['published_status'])) $callParams['published_status'] = $scriptProperties['published_status'];
if (!empty($scriptProperties['page'])) $callParams['page'] = $scriptProperties['page'];
if (!empty($scriptProperties['fields'])) $callParams['fields'] = $scriptProperties['fields'];
if (!empty($scriptProperties['collection_id'])) $callParams['collection_id'] = $scriptProperties['collection_id'];


$modx->log(modX::LOG_LEVEL_DEBUG
    , '[getProducts] Params: '.print_r($callParams, true));

$products = $ms->getProducts($callParams);

$modx->log(modX::LOG_LEVEL_DEBUG
    , '[getProducts] Result count : '.count($products));


if(empty($products)) return;

foreach ($products as $product) {
 
  $tmp = array();  
    
  if (!empty($product['images'])) {
    $image = $product['images'][0];
    $image['alt'] = trim($product['title'], "'\"");
    
    if (!empty($scriptProperties['thumbs_options'])) {
      $image['src'] = $modx->runSnippet("phpthumbof", array(
        'input' => $image['src'],
        'options' => $scriptProperties['thumbs_options']
      ));
    }
    $product['images'] = $ms->getChunk($scriptProperties['productImgTpl'], $image);
    
  } else {
    $image['src'] = '/assets/ediets/template/desktop/images/shop/product.png';
    $product['images'] = $ms->getChunk($scriptProperties['productImgTpl'], $image);
  } 
   
  $tmp['body_html'] = truncate($product['body_html'],70);

  $variant = $product['variants'][0];//get first variation
  $price = preg_replace('/{{.*}}/', $variant['price'], $shop['money_format']);
  $tmp['price'] = $price;
  
  $tmp['domain'] = $shop['domain'];
  
  $product = array_merge($product,$tmp);
 
  $modx->log(modX::LOG_LEVEL_DEBUG
    , '[getProducts] Product: '.print_r($product, true));
  
  $output[] = $ms->getChunk($scriptProperties['productTpl'], $product);
}
$output = implode($scriptProperties['productSeparator'], $output);

$output = $ms->getChunk($scriptProperties['containerTpl'], array(products => $output));

return $output;