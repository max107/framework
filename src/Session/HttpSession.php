<?php

namespace Mindy\Session;

use Mindy\Base\Mindy;
use Mindy\Exception\Exception;
use Mindy\Http\CollectionAwareTrait;
use RuntimeException;
use SessionHandlerInterface;

class HttpSession
{
    use CollectionAwareTrait;
    
    /**
     * @var boolean whether the session should be automatically started when the session application component is initialized, defaults to true.
     */
    public $autoStart = true;
    /**
     * @var string
     */
    public $cacheLimiter = "public";
    /**
     * @var bool
     */
    protected $started;
    /**
     * @var bool
     */
    protected $closed;
    /**
     * @var CollectionInterface
     */
    protected $collection;
    /**
     * @var SessionHandlerInterface|null
     */
    protected $handler;

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else if (property_exists($this, $name)) {
            $this->{$name} = $value;
        } else {
            throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

        $this->collection = new SessionCollection();

        if ($this->handler === null) {
            register_shutdown_function([$this, 'close']);
        }

        session_cache_limiter($this->cacheLimiter);

        if ($this->autoStart) {
            $this->open();
        }
    }

    public function set($key, $value)
    {
        if (!$this->started) {
            $this->open();
        }
        $this->collection->set($key, $value);
        return $this;
    }

    /**
     * @param array $options
     * @see http://php.net/session.configuration
     */
    public function setIniOptions(array $options)
    {
        static $validOptions = [
            'cache_limiter', 'cookie_domain', 'cookie_httponly',
            'cookie_lifetime', 'cookie_path', 'cookie_secure',
            'entropy_file', 'entropy_length', 'gc_divisor',
            'gc_maxlifetime', 'gc_probability', 'hash_bits_per_character',
            'hash_function', 'name', 'referer_check',
            'serialize_handler', 'use_cookies',
            'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
            'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
            'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags',
        ];
        foreach ($options as $key => $value) {
            if (in_array($key, $validOptions)) {
                ini_set('session.'.$key, $value);
            }
        }
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method should be overriden to return true if custom session storage handler should be used.
     * If returning true, make sure the methods {@link openSession}, {@link closeSession}, {@link readSession},
     * {@link writeSession}, {@link destroySession}, and {@link gcSession} are overridden in child
     * class, because they will be used as the callback handlers.
     * The default implementation always return false.
     * @return boolean whether to use custom storage.
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Starts the session if it has not started yet.
     */
    public function open()
    {
        $this->start();
    }

    /**
     * @return $this
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            throw new RuntimeException('Failed to start the session: already started by PHP.');
        }
        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new RuntimeException(sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line));
        }
        // ok to try and start the session
        if (!session_start()) {
            throw new RuntimeException('Failed to start the session');
        }

        $this->started = true;
        $this->closed = false;

        return $this;
    }

    /**
     * Ends the current session and store session data.
     */
    public function close()
    {
        if ($this->getIsStarted()) {
            session_write_close();

            $this->started = false;
            $this->closed = true;
        }
    }

    /**
     * Frees all session variables and destroys all data registered to a session.
     */
    public function destroy()
    {
        if ($this->getIsStarted()) {
            session_unset();
            session_destroy();
            $this->started = false;
            $this->closed = true;
        }
    }

    /**
     * @return boolean whether the session has started
     */
    public function getIsStarted()
    {
        return $this->started;
    }

    /**
     * @return string the current session ID
     */
    public function getSessionID()
    {
        return session_id();
    }

    /**
     * @param string $value the session ID for the current session
     * @return string
     */
    public function setSessionID($value)
    {
        return session_id($value);
    }

    /**
     * Updates the current session id with a newly generated one .
     * Please refer to {@link http://php.net/session_regenerate_id} for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     * @since 1.1.8
     */
    public function regenerateID($deleteOldSession = false)
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * @return string the current session name
     */
    public function getSessionName()
    {
        return session_name();
    }

    /**
     * @param string $value the session name for the current session, must be an alphanumeric string, defaults to PHPSESSID
     */
    public function setSessionName($value)
    {
        session_name($value);
    }

    /**
     * @return string the current session save path, defaults to {@link http://php.net/session.save_path}.
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * @param string $value the current session save path
     * @throws Exception if the path is not a valid directory
     */
    public function setSavePath($value)
    {
        if (is_dir($value)) {
            session_save_path($value);
        } else {
            throw new Exception('HttpSession.savePath "' . $value . '" is not a valid directory.');
        }
    }

    /**
     * @return array the session cookie parameters.
     * @see http://us2.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams()
    {
        return session_get_cookie_params();
    }

    /**
     * Sets the session cookie parameters.
     * The effect of this method only lasts for the duration of the script.
     * Call this method before the session starts.
     * @param array $value cookie parameters, valid keys include: lifetime, path,
     * domain, secure, httponly. Note that httponly is all lowercase.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams($value)
    {
        $data = session_get_cookie_params();
        extract($data);
        extract($value);
        if (isset($httponly)) {
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        } else {
            session_set_cookie_params($lifetime, $path, $domain, $secure);
        }
    }

    /**
     * @return string how to use cookie to store session ID. Defaults to 'Allow'.
     */
    public function getCookieMode()
    {
        if (ini_get('session.use_cookies') === '0') {
            return 'none';
        } elseif (ini_get('session.use_only_cookies') === '0') {
            return 'allow';
        } else {
            return 'only';
        }
    }

    /**
     * @param string $value how to use cookie to store session ID. Valid values include 'none', 'allow' and 'only'.
     */
    public function setCookieMode($value)
    {
        if ($value === 'none') {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === 'allow') {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === 'only') {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            throw new Exception(Mindy::t('base', 'CHttpSession.cookieMode can only be "none", "allow" or "only".'));
        }
    }

    /**
     * @return float the probability (percentage) that the gc (garbage collection) process is started on every session initialization, defaults to 1 meaning 1% chance.
     */
    public function getGCProbability()
    {
        return (float)(ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * @param float $value the probability (percentage) that the gc (garbage collection) process is started on every session initialization.
     * @throws Exception if the value is beyond [0,100]
     */
    public function setGCProbability($value)
    {
        if ($value >= 0 && $value <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new Exception(Mindy::t('base', 'CHttpSession.gcProbability "{value}" is invalid. It must be a float between 0 and 100.', ['{value}' => $value]));
        }
    }
}