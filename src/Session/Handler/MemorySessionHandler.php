<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:53
 */

declare(strict_types = 1);

namespace Mindy\Session\Handler;

class MemorySessionHandler implements SessionHandlerInterface
{
    /**
     * @var bool
     */
    protected $started = false;
    /**
     * @var array
     */
    private $_data = [];
    /**
     * @var string
     */
    private $_id;
    /**
     * @var string
     */
    private $_name;

    /**
     * @return bool
     */
    public function isStarted() : bool
    {
        return $this->started;
    }

    /**
     * @return SessionHandlerInterface
     */
    public function start() : SessionHandlerInterface
    {
        if ($this->isStarted()) {
            return $this;
        }

        $this->started = true;
        $this->closed = false;

        return $this;
    }

    /**
     * @return mixed
     */
    public function set($name, $value) : bool
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $this->_data[$name] = $value;
        return true;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : $defaultValue;
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
    public function count() : int
    {
        return count($this->_data);
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        $this->_data = [];
        return true;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return hash('sha256', uniqid('ss_mock_', true));
    }

    /**
     * @param string $id
     * @return bool
     */
    public function setId(string $id) : bool
    {
        $this->_id = $id;
        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function setName(string $name) : bool
    {
        $this->_name = $name;
        return true;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * @param bool $removeOld
     * @return mixed
     */
    public function regenerateID(bool $removeOld = false) : bool
    {
        return true;
    }

    /**
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close()
    {
        return true;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id)
    {
        return true;
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id)
    {
        return serialize($this->_data);
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data)
    {
        d($session_data);
    }
}