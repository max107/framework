<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 08/08/16
 * Time: 10:52
 */

declare(strict_types = 1);

namespace Mindy\Base\Traits;

/**
 * Class StatePersister
 * @package Mindy
 * @method string getRuntimePath()
 */
trait StatePersisterTrait
{
    /**
     * @var mixed
     */
    private $_globalState;
    /**
     * @var bool
     */
    private $_stateChanged;

    /**
     * @return string
     */
    protected function getStateFile() : string
    {
        return $this->getRuntimePath() . DIRECTORY_SEPARATOR . 'state.bin';
    }

    /**
     * Loads state data from persistent storage.
     * @return mixed state data. Null if no state data available.
     */
    public function loadStorage()
    {
        $stateFile = $this->getStateFile();
        if (!is_file($stateFile)) {
            file_put_contents($stateFile, '');
        }
        // TODO add cache
        if (($content = @file_get_contents($stateFile)) !== false) {
            return unserialize($content);
        } else {
            return null;
        }
    }

    /**
     * Saves application state in persistent storage.
     * @param mixed $state state data (must be serializable).
     */
    public function saveStorage($state)
    {
        file_put_contents($this->getStateFile(), serialize($state), LOCK_EX);
    }

    /**
     * Clears a global value.
     *
     * The value cleared will no longer be available in this request and the following requests.
     * @param string $key the name of the value to be cleared
     */
    public function clearGlobalState($key)
    {
        $this->setGlobalState($key, true, true);
    }

    /**
     * Loads the global state data from persistent storage.
     * @see getStatePersister
     */
    public function loadGlobalState()
    {
        if (($this->_globalState = $this->loadStorage()->load()) === null) {
            $this->_globalState = [];
        }
        $this->_stateChanged = false;
    }

    /**
     * Saves the global state data into persistent storage.
     * @see getStatePersister
     */
    public function saveGlobalState()
    {
        if ($this->_stateChanged) {
            $this->_stateChanged = false;
            $this->saveStorage($this->_globalState);
        }
    }

    /**
     * Returns a global value.
     *
     * A global value is one that is persistent across users sessions and requests.
     * @param string $key the name of the value to be returned
     * @param mixed $defaultValue the default value. If the named global value is not found, this will be returned instead.
     * @return mixed the named global value
     * @see setGlobalState
     */
    public function getGlobalState($key, $defaultValue = null)
    {
        if ($this->_globalState === null) {
            $this->loadGlobalState();
        }

        return isset($this->_globalState[$key]) ? $this->_globalState[$key] : $defaultValue;
    }

    /**
     * Sets a global value.
     *
     * A global value is one that is persistent across users sessions and requests.
     * Make sure that the value is serializable and unserializable.
     * @param string $key the name of the value to be saved
     * @param mixed $value the global value to be saved. It must be serializable.
     * @param mixed $defaultValue the default value. If the named global value is the same as this value, it will be cleared from the current storage.
     * @see getGlobalState
     */
    public function setGlobalState($key, $value, $defaultValue = null)
    {
        if ($this->_globalState === null) {
            $this->loadGlobalState();
        }

        $changed = $this->_stateChanged;
        if ($value === $defaultValue && isset($this->_globalState[$key])) {
            unset($this->_globalState[$key]);
            $this->_stateChanged = true;
        } elseif (!isset($this->_globalState[$key]) || $this->_globalState[$key] !== $value) {
            $this->_globalState[$key] = $value;
            $this->_stateChanged = true;
        }

        if ($this->_stateChanged !== $changed) {
            $this->saveGlobalState();
        }
    }
}