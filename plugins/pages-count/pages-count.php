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
class PagesCountPlugin extends Plugin
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
            'onAdminAfterSave' => ['onAdminAfterSave', 0]
        ]);
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onAdminAfterSave(Event $e)
    {

        $this->savelog(count($this->grav['pages']->parentsRawRoutes(true)));
        
    }

    /**
     *  Saves data to the log file
     */
    protected function savelog($current)
    {

        $params = $this->config->get('plugins.pages-count');
        $filename = !empty($params['filename']) ? trim($params['filename']) : 'pages-count.txt';
        $folder = !empty($params['folder']) ? trim($params['folder']) : 'pages-count';
        $locator = $this->grav['locator'];
        $path = $locator->findResource('user://data', true);
        $fullFileName = $path . DS . $folder . DS . $filename;
        $file = File::instance($fullFileName);
        $pagesCount = 0;
        
        if (!file_exists($path . DS . $folder)) {
            mkdir($path . DS . $folder, 0755);
        }

        if (file_exists($fullFileName)) {
            $data = Yaml::parse($file->content());
            if (count($data) > 0 && $data > $current) {
                $pagesCount = $data + 1;
            } 
            else {
                $pagesCount = $current + 1;
            }
        } else {
            $pagesCount = $current + 1;
        }

        $file->save(Yaml::dump($pagesCount));


    }
}
