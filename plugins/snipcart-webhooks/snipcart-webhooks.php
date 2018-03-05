<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
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
     * Enable only if url matches to the configuration.
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        // get the post raw body
        $json = file_get_contents('php://input');
        $this->snipcart_order = json_decode($json, true);

        if (!isset($this->snipcart_order["eventName"])) {
            return;
        }

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

        $route = $this->config->get('plugins.snipcart-webhooks.route');

        // performance check for route
        if (!($route && $route == $uri->path())) {
            return;
        }


        // create the snipcart-webhook page
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

        //$this->snipcart_order["eventName"] = 'order.completed';

        switch ($this->snipcart_order["eventName"]) {

            case 'shippingrates.fetch':
                return;
                break;

            case 'order.completed':

                $this->processOrderCompletedEvent();
                break;

            default:
                $this->returnBadRequest(array('reason' => 'Unsupported event'));
                break;
        }


        $result = Array();

        $twig->twig_vars['result'] = array_values($result);
    }

    protected function processOrderCompletedEvent()
    {

        $content = $this->snipcart_order['content'];
        $updated = array();

        // We must iterated on each item
        foreach ($content['items'] as $item) {
            // We update the item quantity
            $entry = $this->updateItemQuantity($item);

            if ($entry != null) {
                $updated[] = $entry;
            }
        }

        //$this->returnJson($updated);
    }

    private function updateItemQuantity($item)
    {

        $order_product_quantity = $item['quantity'];
        $order_product_url = '/';

        foreach ($item['customFields'] as $custom_field) {
            if ($custom_field['name'] === 'base_url') {
                $order_product_url = $custom_field['value'];
            }
            if ($custom_field['name'] === 'talla') {
                $order_product_talla = $custom_field['value'];
            }
        }

        $url = substr($order_product_url, 7); // se elimina el folder /pagina/
        //$productPage = $this->grav['pages']->find($url);
        // get the file of the page to modify
        $file = new \SplFileInfo($this->grav['pages']->find($url)->filePath());

        $page = new Page();
        $page->init($file);

        $header = new \Grav\Common\Page\Header((Array) $page->header());
        $variants = $header->get('variantes');

        $new_variants = $variants;
        foreach ($variants as $k => $var) {
            if ($var['talla'] === $order_product_talla) {
                $new_variants[$k]['cantidad'] = (string) ($var['cantidad'] - $order_product_quantity);
            }
        }

        $header->set('variantes', $new_variants);
        $page->header($header->items);
        $page->save();
    }

    protected function validateRequest($snipcart_order)
    {

        if (!isset($_SERVER['HTTP_X_SNIPCART_REQUESTTOKEN'])) {
            throw new \RuntimeException('Invalid request: no request token');
        }

        return true;
    }

    private function returnBadRequest($errors = array())
    {
        header('HTTP/1.1 400 Bad Request');
        //$this->returnJson(array('success' => false, 'errors' => $errors));
    }
}
