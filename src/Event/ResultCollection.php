<?php
/**
 * This file is part of the Aura Project for PHP.
 * @package Aura.Signal
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Mindy\Event;

/**
 * Represents a collection of Result objects.
 * @package Aura.Signal
 */
class ResultCollection extends \ArrayObject
{
    /**
     * Returns the last Result in the collection.
     * @return Result
     */
    public function getLast()
    {
        $k = count($this);
        return $k > 0 ? $this[$k - 1] : null;
    }

    /**
     *
     * Tells if the ResultCollection was stopped during processing.
     *
     * @return bool
     *
     */
    public function isStopped()
    {
        $last = $this->getLast();
        return $last ? $last->value === EventManager::STOP : null;
    }
}