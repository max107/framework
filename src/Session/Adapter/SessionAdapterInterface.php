<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 20:07
 */

declare(strict_types = 1);

namespace Mindy\Session\Adapter;

use Countable;
use SessionHandlerInterface;

interface SessionAdapterInterface extends Countable, SessionHandlerInterface
{
    /**
     * @return bool
     */
    public function isStarted() : bool;

    /**
     * @return SessionAdapterInterface
     */
    public function start() : SessionAdapterInterface;

    /**
     * @return mixed
     */
    public function set($name, $value) : bool;

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null);

    /**
     * @return array
     */
    public function all() : array;

    /**
     * @param string $id
     * @return bool
     */
    public function setId(string $id) : bool;

    /**
     * @return string
     */
    public function getId() : string;

    /**
     * @param string $name
     * @return bool
     */
    public function setName(string $name) : bool;

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * @param bool $removeOld
     * @return bool
     */
    public function regenerateID(bool $removeOld = false) : bool;

    /**
     * @return bool
     */
    public function clear() : bool;
}