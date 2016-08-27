<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:07
 */

namespace Mindy\Session;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Flash implements IteratorAggregate, Countable
{
    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';

    /**
     * @var array
     */
    private $_data = [];

    /**
     * @param $status
     * @param $message
     */
    public function add($status, $message)
    {
        $this->_data[] = ['status' => $status, 'message' => $message];
    }

    /**
     * @param $message
     */
    public function success($message)
    {
        $this->add(self::SUCCESS, $message);
    }

    /**
     * @param $message
     */
    public function error($message)
    {
        $this->add(self::ERROR, $message);
    }

    /**
     * @param $message
     */
    public function info($message)
    {
        $this->add(self::INFO, $message);
    }

    /**
     * @param $message
     */
    public function warning($message)
    {
        $this->add(self::WARNING, $message);
    }

    /**
     * @return array
     */
    public function all() : array
    {
        $data = $this->_data;
        $this->_data = [];
        return $data;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->_data);
    }
}