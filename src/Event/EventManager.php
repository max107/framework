<?php
/**
 * This file is part of the Aura Project for PHP.
 * @package Aura.Signal
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace Mindy\Event;

/**
 * Processes signals through to Handler objects.
 * @package Aura.Signal
 */
class EventManager
{
    /**
     * Indicates that the signal should not call more Handler instances.
     * @const string
     */
    const STOP = 'EVENTS_STOP';
    /**
     * An array of Handler instances that respond to class signals.
     * @var array
     */
    protected $handlers = [];
    /**
     * A prototype ResultCollection; this will be cloned by `send()` to retain
     * the Result objects from Handler instances.
     * @var ResultCollection
     */
    protected $resultCollection;
    /**
     * A ResultCollection from the last signal sent.
     * @var ResultCollection
     */
    protected $results;
    /**
     * Have the handlers for a signal been sorted by position?
     * @var array
     */
    protected $sorted = [];

    /**
     * Constructor.
     * @param array $config An array describing Handler params.
     */
    public function __construct(array $config = [])
    {
        $this->resultCollection = new ResultCollection;
        if (isset($config['events'])) {
            $this->setEvents($config['events']);
        }
        $this->results = clone $this->resultCollection;
    }

    /**
     * @param $events
     */
    public function setEvents($events)
    {
        foreach ($events as $handler) {
            list($sender, $signal, $callback) = $handler;
            if (isset($handler[3])) {
                $position = $handler[3];
            } else {
                $position = 5000;
            }
            $this->handler($sender, $signal, $callback, $position);
        }
    }

    /**
     * Adds a Handler to respond to a sender signal.
     * @param string|object $sender The class or object sender of the signal.
     * If a class, inheritance will be honored, and '*' will be interpreted
     * as "any class."
     * @param string $signal The name of the signal for that sender.
     * @param callback $callback The callback to execute when the signal is
     * received.
     * @param int $position The handler processing position; lower numbers are
     * processed first. Use this to force a handler to be used before or after
     * others.
     * @return void
     */
    public function handler($sender, $signal, $callback, $position = 5000)
    {
        $handler = new Handler($sender, $signal, $callback);
        $this->handlers[$signal][(int)$position][] = $handler;
        $this->sorted[$signal] = false;
    }

    /**
     * Gets Handler instances for the Manager.
     * @param string $signal Only get Handler instances for this signal; if
     * null, get all Handler instances.
     * @return array
     */
    public function getHandlers($signal = null)
    {
        if (!$signal) {
            return $this->handlers;
        }

        if (!isset($this->handlers[$signal])) {
            return;
        }

        if (!$this->sorted[$signal]) {
            ksort($this->handlers[$signal]);
            $this->sorted[$signal] = true;
        }

        return $this->handlers[$signal];
    }

    /**
     * Invokes the Handler objects for a sender and signal.
     * @param object $origin The object sending the signal. Note that this is
     * always an object, not a class name.
     * @param string $signal The name of the signal from that origin.
     * @param array $params Arguments to pass to the Handler callback.
     * @return ResultCollection The results from each of the Handler objects.
     */
    public function send($origin, $signal, array $params = [])
    {
        // clone a new result collection
        $this->results = clone $this->resultCollection;

        // now process the signal through the handlers and return the results
        $this->process($origin, $signal, $params);
        return $this->results;
    }

    /**
     * Invokes the Handler objects for a sender and signal.
     * @param object $origin The object sending the signal. Note that this is
     * always an object, not a class name.
     * @param string $signal The name of the signal from that origin.
     * @param array $args Arguments to pass to the Handler callback.
     */
    protected function process($origin, $signal, array $args)
    {
        // are there any handlers for this signal, regardless of sender?
        $list = $this->getHandlers($signal);
        if (!$list) {
            return;
        }

        // go through the handler positions for the signal
        foreach ($list as $position => $handlers) {

            // go through each handler in this position
            foreach ($handlers as $handler) {

                // try the handler
                $params = $handler($origin, $signal, $args);

                // if it executed, it returned the params for a Result object
                if ($params) {

                    // create a Result object
                    $result = new Result($params['origin'], $params['sender'], $params['signal'], $params['value']);

                    // allow a meta-handler to examine the Result object,
                    // but only if it wasn't sent from the Manager (this
                    // prevents infinite looping). use process() instead
                    // of send() to prevent resetting the $results prop.
                    if ($origin !== $this) {
                        $this->process($this, 'handler_result', [$result]);
                    }

                    // retain the result
                    $this->results->append($result);

                    // should we stop processing?
                    if ($result->value === static::STOP) {
                        // yes, leave the processing loop
                        return;
                    }
                }
            }
        }
    }

    /**
     * Returns the ResultCollection from the last signal processing.
     * @return ResultCollection
     */
    public function getResults()
    {
        return $this->results;
    }
}