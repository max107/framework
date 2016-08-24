<?php
/**
 * This file is part of the Aura Project for PHP.
 * @package Aura.Signal
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Mindy\Event;

use Closure;

/**
 * A signal Handler definition.
 * @package Aura.Signal
 */
class Handler
{
    /**
     * Handle signals from this sender. This can be an object, in which case
     * the Handler will match only signals from that object, or a class name,
     * in which case the Handler will match signals from any instance of that
     * class.
     * @var object|string
     */
    protected $sender;

    /**
     * Handle this signal from the sender.
     * @var string
     */
    protected $signal;

    /**
     *
     * Use this callback to handle the signal.
     *
     * @var callback
     *
     */
    protected $callback;

    /**
     * Constructor.
     * @param object|string $sender Handle signals from this sender.
     * @param string $signal Handle this specific signal.
     * @param callback $callback Use this callback to handle the signal.
     */
    public function __construct($sender, $signal, $callback)
    {
        $this->sender = $sender;
        $this->signal = $signal;
        $this->callback = $callback;
    }

    /**
     * Make properties available as magic read-only.
     * @param string $key The property name.
     * @return mixed
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     * Execute the handler: if the originating object matches the required
     * sender, and the signal matches the required signal, then run the
     * callback and return the results.
     * @param object $origin The originating object that sent the signal.
     * @param string $signal The signal sent by the originating object.
     * @param array $args Arguments for the callback, which will be invoked
     * if the sender and signal match this Handler.
     * @return null|array An array of parameters suitable for creating a Result
     * object, or null if the origin and signal did not match this Handler.
     */
    public function __invoke($origin, $signal, array $args)
    {
        // match sender on a specific object, or on a class?
        if (is_object($this->sender)) {
            // match on a specific object
            $matchSender = $this->sender === $origin;
            $callback = $this->callback;
        } else {
            if (
                is_a($origin, $this->sender) &&
                ($this->callback instanceof Closure) === false &&
                $this->callback[1] == $signal
            ) {
                $callback = [$origin, $signal];
            } else {
                $callback = $this->callback;
            }
            // match on a class
            $matchSender = $this->sender == '*' || $origin instanceof $this->sender;
        }

        // match on a signal
        $marchSignal = $this->signal == '*' || $this->signal == $signal;

        // do the sender and signal match?
        if ($matchSender && $marchSignal) {
            // yes, return an array of params with the callback return value
            return [
                'origin' => $origin,
                'sender' => $this->sender,
                'signal' => $this->signal,
                'value' => call_user_func_array($callback, $args)
            ];
        }

        return null;
    }
}