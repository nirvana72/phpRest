<?php
namespace PhpRest\Event;

use PhpRest\Application;

class EventTrigger
{
    public static function on(string $event, $params = [])
    {
        $app = Application::getInstance();
        $events = $app->getEvent($event);
        if ($events) {
            $ary['event'] = $event;
            $ary['params'] = $params;
            foreach ($events as $classPath) {
                $ctlClass = $app->get($classPath);
                call_user_func_array([$ctlClass, 'handle'], $ary);
            }
        }
    }
}