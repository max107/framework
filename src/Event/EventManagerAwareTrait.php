<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 22/09/16
 * Time: 00:53
 */

namespace Mindy\Event;

trait EventManagerAwareTrait
{
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @param EventManagerInterface $eventManager
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return EventManagerInterface
     */
    public function getEventManager() : EventManagerInterface
    {
        if ($this->eventManager === null) {
            $this->eventManager = new EventManager();
        }
        return $this->eventManager;
    }
}