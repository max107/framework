<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 19:55
 */

declare(strict_types = 1);

namespace Mindy\Session\Handler;

use Countable;
use Exception;
use RuntimeException;
use SessionHandler;

abstract class AbstractSessionHandler extends SessionHandler implements Countable, SessionHandlerInterface
{
    /**
     * @var bool
     */
    protected $started = false;

    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @param array $config
     */
    protected function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param array $options
     * @throws Exception
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
            'serialize_handler', 'use_cookies', 'save_path', 'save_handler',
            'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
            'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
            'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags',
        ];
        foreach ($options as $key => $value) {
            if (in_array($key, $validOptions)) {
                ini_set('session.' . $key, $value);
            } else {
                throw new Exception('Unknown ini property: ' . $key);
            }
        }
    }

    /**
     * @return SessionHandlerInterface
     */
    public function start() : SessionHandlerInterface
    {
        if ($this->started) {
            return $this;
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

        return $this;
    }

    public function isStarted() : bool
    {
        return $this->getId() !== '';
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function set($key, $value) : bool
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $defaultValue;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        return $_SESSION;
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
        return count($_SESSION);
    }

    /**
     * Frees all session variables and destroys all data registered to a session.
     */
    public function destroy($session_id)
    {
        if ($this->isStarted()) {
            session_unset();
            session_destroy();
            $this->started = false;
            return true;
        }

        return false;
    }

    /**
     * Updates the current session id with a newly generated one
     * @param bool $removeOld
     * @return bool
     */
    public function regenerateID(bool $removeOld = false) : bool
    {
        return session_regenerate_id($removeOld);
    }

    /**
     * @param string $value the session ID for the current session
     * @return bool
     */
    public function setId(string $value) : bool
    {
        session_id($value);
        return true;
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return session_id();
    }

    /**
     * @return string the current session name
     */
    public function getName() : string
    {
        return session_name();
    }

    /**
     * @param string $name the session name for the current session, must be an alphanumeric string, defaults to PHPSESSID
     * @return bool
     */
    public function setName(string $name) : bool
    {
        session_name($name);
        return true;
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
     * @throws Exception
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
            throw new Exception('Session.cookieMode can only be "none", "allow" or "only".');
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
            throw new Exception('Session.gcProbability "' . $value . '" is invalid. It must be a float between 0 and 100.');
        }
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        if (isset($_SESSION)) {
            foreach (array_keys($_SESSION) as $key) {
                unset($_SESSION[$key]);
            }
        }
        return true;
    }
}