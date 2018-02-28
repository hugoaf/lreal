<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Page\Page;
use Grav\Common\Page\Types;
use RocketTheme\Toolbox\Event\Event;

class SnipcartWebhooksPlugin extends Plugin
{
    
    /**
     * @var array snipcart raw post body https://docs.snipcart.com/webhooks/shipping
     */    
    protected $snipcart_order;
    
    /**
     * @var float
     */    
    protected $tipo_de_cambio;


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        
        
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onGetPageTemplates' => ['onGetPageTemplates', 0],
        ];
    }

    /**
     * Add page template types. (for Admin plugin)
     */
    public function onGetPageTemplates(Event $event)
    {
        /** @var Types $types */
        $types = $event->types;
        $types->scanTemplates('plugins://snipcart-webhooks/templates');
    }


    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Enable search only if url matches to the configuration.
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
        
        // get the post raw body
        $json = file_get_contents('php://input');
        $this->snipcart_order = json_decode($json, true);

        
        if ( !isset($this->snipcart_order["eventName"]) || !$this->validateRequest($this->snipcart_order) ) {
            return;
        }
        
    }


    /**
     * Build page.
     */
    public function onPagesInitialized()
    {

        $page = $this->grav['page'];
       
        /** @var Uri $uri */
        $uri = $this->grav['uri'];
       
        $route = $this->config->get('plugins.snipcart-shipping.route');

        // performance check for route
        if (!($route && $route == $uri->path())) {
            return;
        }
        
        
        // create the snipcart-webhooks page
        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . '/pages/snipcart-webhooks.md'));

        
        // override the template is set in the config
        $template_override = $this->config->get('plugins.snipcart-webhooks.template');
        if ($template_override) {
            $page->template($template_override);
        }

        // fix RuntimeException: Cannot override frozen service "page" issue
        unset($this->grav['page']);

        $this->grav['page'] = $page;

    }


    /**
     * Set needed variables to display the results.
     */
    public function onTwigSiteVariables()
    {
        $twig = $this->grav['twig'];
        
       // 
                
        if ($this->snipcart_order["eventName"] == "shippingrates.fetch" ){

            $rates = $this->config->get('plugins.snipcart-webhooks.shippingrates');
            
            
            // pendiente: if no rates return an error to snipcart " no shipping configured "
            
            $default_total_weight = 1000; // establecer este valor en el plugin
            
            $total_weight = $this->snipcart_order['content']['totalWeight']?  $this->snipcart_order['content']['totalWeight'] : $default_total_weight;
            
            

            // se remueven los shipping rates que no coinciden con el peso
            foreach ($rates as $k => $rate) {
                //print_r ($rate['cost']);
                if ( $total_weight < $rate['min_weight'] || $total_weight > $rate['max_weight']) {
                    unset($rates[$k]);
                }
            }

            $currency = $this->snipcart_order['content']['currency'];
            if ($currency === 'usd') {
                $tc = $this->grav['page']->find('/configuracion')->header()->tipo_de_cambio;
                foreach ($rates as $k => $rate) {
                    $rates[$k]['cost'] = $rate['cost'] / $tc;
                }            
            }            

            $twig->twig_vars['rates'] = array_values($rates);
            
            
        }
        
        

        
        
    }

    protected function validateRequest($snipcart_order)
    {
        
        if (!isset($_SERVER['HTTP_X_SNIPCART_REQUESTTOKEN'])) {
            throw new \RuntimeException('Invalid request: no request token');
        }
        /*
        $requestToken = $snipcart_order['content']['token'];
        $url = 'https://app.snipcart.com/api/requestvalidation/' . $requestToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_USERPWD, 'NmEyNjAzZTktYjI3Ny00ODVmLWIxNjgtZjNhZmFlNWUzYjAwNjM2NDkyMjk0MTg5MjI3OTc4' . ':');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch);

        if (empty($response) || $status['http_code'] != 200) {
            throw new \RuntimeException('Invalid request: no response');
        }

        $response = @json_decode($response);
        if (!$response) {
            throw new \RuntimeException('Invalid request: response not json');
        }
        if ($response->token !== $requestToken) {
            throw new \RuntimeException('Invalid request: invalid token');
        }*/
        return true;
    }

}