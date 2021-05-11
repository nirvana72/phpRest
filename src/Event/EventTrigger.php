<?php
namespace PhpRest\Event;

use PhpRest\Application;

class EventTrigger
{
    public static function on($event, $params = [])
    {
        $app = Application::getInstance();
        $events = $app->getEvent($event);
        if ($events) {
            foreach ($events as $classPath) {
                $ctlClass = $app->get($classPath);
                call_user_func_array([$ctlClass, 'handle'], $params);
            }
        }
    }
}