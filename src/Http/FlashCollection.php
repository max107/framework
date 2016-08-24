<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 19:21
 */

namespace Mindy\Http;

class FlashCollection implements CollectionInterface
{
    const KEY = 'flash';

    const SUCCESS = 'success';
    const WARNING = 'warning';
    const ERROR = 'error';
    const INFO = 'info';

    /**
     * @var SessionCollection
     */
    private $_session;

    /**
     * FlashCollection constructor.
     * @param $session
     */
    public function __construct($session)
    {
        $this->_session = $session;
    }

    /**
     * @param $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->_session = $session;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function getSession()
    {
        return $this->_session;
    }

    /**
     * @return array
     */
    public function all()
    {
        $data = $this->getSession()->get(self::KEY, []);
        $this->clear();
        return $data;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getSession()->get(self::KEY));
    }

    /**
     * @param $key
     * @param null $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        return $this->getSession()->get($key, $defaultValue);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        $data = $this->getSession()->get(self::KEY, []);
        $data[] = ['class' => $key, 'value' => $value];
        $this->getSession()->set(self::KEY, $data);
        return $this;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function merge(array $data)
    {
        return array_merge($this->getSession()->get(self::KEY, []), $data);
    }

    /**
     * @void
     */
    public function clear()
    {
        $this->getSession()->remove(self::KEY);
        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function remove($key)
    {

    }

    public function success($message)
    {
        $this->set(self::SUCCESS, $message);
        return $this;
    }

    public function error($message)
    {
        $this->set(self::ERROR, $message);
        return $this;
    }

    public function info($message)
    {
        $this->set(self::INFO, $message);
        return $this;
    }

    public function warning($message)
    {
        $this->set(self::WARNING, $message);
        return $this;
    }
}