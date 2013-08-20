<?php

class ModShopify {

  public $modx = null;
  public $config = array();
  public $chunks = array();
  
  /* @var shopifyClient $sc */
  
  public function __construct(modX $modx, $config) {
    $this->modx = &$modx;
    $basePath = $this->modx->getOption('modshopify.core_path',$config,$this->modx->getOption('core_path').'components/modshopify/');
    $assetsUrl = $this->modx->getOption('modshopify.assets_url',$config,$this->modx->getOption('assets_url').'components/modshopify/');
    $assetsPath = $this->modx->getOption('modshopify.assets_path',$config,$this->modx->getOption('assets_path').'components/modshopify/');
    $this->config = array_merge(array(
      'base_bath' => $basePath,
      'core_path' => $basePath,
      'model_path' => $basePath.'model/',
      'processors_path' => $basePath.'processors/',
      'elements_path' => $basePath.'elements/',
      'assets_path' => $assetsPath,
      'js_url' => $assetsUrl.'js/',
      'css_url' => $assetsUrl.'css/',
      'assets_url' => $assetsUrl,
      'connector_url' => $assetsUrl.'connector.php',
    ),$config);
    
    $shop_domain = $this->modx->getOption('modshopify.shop_domain', $config);
    $token = $this->modx->getOption('modshopify.auth_token', $config);
    $api_key = $this->modx->getOption('modshopify.api_key', $config);
    $secret = $this->modx->getOption('modshopify.auth_secret', $config);
    $multipass_secret = $this->modx->getOption('modshopify.multipass_secret',$config);
    
    require_once 'shopify.class.php';
    $this->sc = new shopifyClient($shop_domain, $token, $api_key , $secret, true);
    
    require_once 'multipass.class.php';
    $this->mp = new shopifyMultipass($multipass_secret);
  }
  
  public function getProducts($callParams) {
    $method = 'GET';
    $path = '/admin/products.json';

    try {
      return $this->sc->call($method, $path, $callParams);
    } catch (Exception $e) {
      return null;
    }
  }
  
  public function getShop() {
    $method = 'GET';
    $path = '/admin/shop.json';
    try {
      return $this->sc->call($method, $path);
    } catch (Exception $e) {
      return null;
    }
  }
  
  
    
  public function getMultipassLogin($return_to) {
    $method = 'GET';
    $path = 'account/login/multipass/';
    
    $user = $this->modx->getUser('web');
    $profile = $user->getOne('Profile');
    $customer_data_hash["email"]=$profile ? $profile->get('email') : '';
    ### Store the current time in ISO8601 format.
    ### The token will only be valid for a small timeframe around this timestamp.
    $datetime = new DateTime();
    $customer_data_hash["created_at"] = $datetime->format(DateTime::ISO8601);;
    $customer_data_hash["return_to"] = $return_to;
    $token = $this->mp->generate_token($customer_data_hash);
    
    
    return $token;
  }
  
  
 /**
  * Gets a Chunk and caches it; also falls back to file-based templates
  * for easier debugging.
  *
  * @author Shaun McCormick
  * @access public
  * @param string $name The name of the Chunk
  * @param array $properties The properties for the Chunk
  * @return string The processed content of the Chunk
  */
  public function getChunk($name,$properties = array()) {
    $chunk = null;
    if (!isset($this->chunks[$name])) {
      $chunk = $this->modx->getObject('modChunk',array('name' => $name),true);
      if (empty($chunk)) {
          $chunk = $this->_getTplChunk($name);
          if ($chunk == false) return false;
      }
      $this->chunks[$name] = $chunk->getContent();
    } else {
      $o = $this->chunks[$name];
      $chunk = $this->modx->newObject('modChunk');
      $chunk->setContent($o);
    }
    $chunk->setCacheable(false);
    return $chunk->process($properties);
  }

 /**
  * Returns a modChunk object from a template file.
  *
  * @author Shaun McCormick
  * @access private
  * @param string $name The name of the Chunk. Will parse to name.chunk.tpl
  * @param string $postFix The postfix to append to the name
  * @return modChunk/boolean Returns the modChunk object if found, otherwise
  * false.
  */
  private function _getTplChunk($name,$postFix = '.tpl') {
    $chunk = false;
    $name = preg_replace('/(.*)Tpl$/', '$1', $name);
    $f = $this->config['elements_path'].'chunks/'.$name.$postFix;
    if (file_exists($f)) {
        $o = file_get_contents($f);
        /* @var modChunk $chunk */
        $chunk = $this->modx->newObject('modChunk');
        $chunk->set('name',$name);
        $chunk->setContent($o);
    }
    return $chunk;
  }
  
}
