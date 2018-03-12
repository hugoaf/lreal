<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Class PagesCountPlugin
 * @package Grav\Plugin
 */
class PagesCounterPlugin extends Plugin
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
            'onAdminCreatePageFrontmatter' => ['onAdminCreatePageFrontmatter', 0]
        ]);
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onAdminCreatePageFrontmatter(Event $event)
    {
        
        $header = $event['header'];
                
        if (!isset($header['pages-counter-number'])) {

            $params = $this->config->get('plugins.pages-counter');
            $filename = !empty($params['filename']) ? trim($params['filename']) : 'pages-counter.txt';
            $folder = !empty($params['folder']) ? trim($params['folder']) : 'pages-counter';
            $locator = $this->grav['locator'];
            $data_path = $locator->findResource('user://data', true);
            $fullFileName = $data_path . DS . $folder . DS . $filename;
            if (!file_exists($data_path . DS . $folder)) {
                mkdir($data_path . DS . $folder, 0755);
            }
            $file = File::instance($fullFileName);

            $current_pages_counter_max = 1;
            if (file_exists($fullFileName)) {
                $current_pages_counter_max = Yaml::parse($file->content()) + 1;
            }

            // create frontmatter field
            $header['pages-counter-number'] = $current_pages_counter_max;
            $event['header'] = $header;

            // update counter in data folder
            $file->save(Yaml::dump($current_pages_counter_max));

            
        }
        
    }

    
}
