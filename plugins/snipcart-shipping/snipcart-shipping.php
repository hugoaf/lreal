<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Page\Page;
use Grav\Common\Page\Types;
use RocketTheme\Toolbox\Event\Event;

class SnipcartshippingPlugin extends Plugin
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
        $types->scanTemplates('plugins://snipcart-shipping/templates');
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
        
        // get the post raw body
        $json = file_get_contents('php://input');
        $this->snipcart_order = json_decode($json, true);
        
        // verify if web have a post data with an eventName from snipcart and if that event name is about shipping rates
        if ( !isset($this->snipcart_order["eventName"]) || $this->snipcart_order["eventName"] !== "shippingrates.fetch") {
            return;
        }
    
        $this->validateRequest();

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
        
        
    }


    /**
     * Build results.
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


        // create the snipcart-shipping page
        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . '/pages/snipcart-shipping.md'));

        // override the template is set in the config
        $template_override = $this->config->get('plugins.snipcart-shipping.template');
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
        $rates = $this->config->get('plugins.snipcart-shipping.shippingrates');
        $order_total_weight = (float) $this->snipcart_order['content']['totalWeight'];
        
        // removing the out of weight range shipping rates
        foreach ($rates as $k => $rate) {
            if ( $order_total_weight < (float) $rate['min_weight'] || $order_total_weight > (float) $rate['max_weight']) {
                unset($rates[$k]);
            }
        }
        
        $order_currency = $this->snipcart_order['content']['currency'];
                
        if ($order_currency === 'usd') {
            $tc = $this->grav['page']->find('/configuracion')->header()->tipo_de_cambio;
            foreach ($rates as $k => $rate) {
                $rates[$k]['cost'] = $rate['cost'] / $tc;
            }            
        }
        
        $twig->twig_vars['rates'] = array_values($rates);
        
    }

    protected function validateRequest()
    {
        if (!isset($_SERVER['HTTP_X_SNIPCART_REQUESTTOKEN'])) {
            throw new \RuntimeException('Invalid request: no request token');
        }        
       

        return true;
    }

}