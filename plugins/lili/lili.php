<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Class LiliPlugin
 * @package Grav\Plugin
 */
class LiliPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if (!$this->isAdmin()) {
            return;
        }

        // Enable the main event we are interested in
        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0]
        ]);
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onPageContentRaw(Event $e)
    {
        // Get a variable from the plugin configuration
        //$text = $this->grav['config']->get('plugins.lili.text_var');

        // Get the current raw content
        //$content = $e['page']->getRawContent();

        // Prepend the output with the custom text and set back on the page
        //$e['page']->setRawContent($text . "\n\n" . $content);
        
        return $this->grav['page']->title();
    }
    
    /*
     *  Obtiene un compuesto LR + contador pages plugin + random
     * 
     */
    public static function generateRandomSku($length = 2) {
        
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        $grav = Grav::instance();
      
        $filename = 'pages-counter.txt';
        $folder = 'pages-counter';
        $locator = $grav['locator'];
        $data_path = $locator->findResource('user://data', true);
        $fullFileName = $data_path . DS . $folder . DS . $filename;
        $file = File::instance($fullFileName);

        $count = Yaml::parse($file->content());

        
        //$grav = Grav::instance();
        //$pages = $grav['pages']->parentsRawRoutes(true);
        //$count = count($pages)+1;
        
        
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return 'LR-'.$count .'-'. $randomString;
    }
    
}
