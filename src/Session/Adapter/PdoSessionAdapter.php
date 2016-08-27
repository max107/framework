<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:52
 */

namespace Mindy\Session\Adapter;

use Mindy\Base\Mindy;
use PDO;

class PdoSessionAdapter extends NativeSessionAdapter
{
    public $key;
    public $encrypt = false;
    /**
     * @var PDO
     */
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        parent::__construct();

        $this->pdo = $pdo;
        $this->key = Mindy::getVersion();
    }

    /**
     * @return bool|void
     */
    public function close()
    {
        return true;
    }

    /**
     * Session open handler.
     * Do not call this method directly.
     * @param string $save_path session save path
     * @param string $session_id session name
     * @return boolean whether session is opened successfully
     */
    public function open($save_path, $session_id)
    {
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @return string the session data
     */
    public function read($session_id)
    {
        $statement = $this->pdo->query("SELECT * FROM session WHERE expire>=:expire AND id=:id");
        $statement->bindParam(':expire', time(), PDO::PARAM_INT);
        $statement->bindParam(':id', time(), PDO::PARAM_STR);
        $object = $statement->fetchObject(PDO::FETCH_OBJ);
        return $object->data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @param string $session_data session data
     * @throws Exception
     * @return boolean whether session write is successful
     */
    public function write($session_id, $session_data)
    {
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        $expire = time() + (int)ini_get('session.gc_maxlifetime');

        $session = Session::objects()->get(['id' => $session_id]);
        if ($session === null) {
            $session = new Session([
                'id' => $session_id,
                'data' => $session_data,
                'expire' => $expire,
            ]);
            if ($session->save() === false) {
                throw new Exception("Can't create session");
            }
        } else {
            Session::objects()->filter(['id' => $session_id])->update([
                'data' => $session_data,
                'expire' => $expire
            ]);
        }
        return true;
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroy($session_id)
    {
        Session::objects()->filter(['id' => $session_id])->delete();
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxlifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gc($maxlifetime)
    {
        Session::objects()->filter(['expire__lt' => time()])->delete();
        return true;
    }
}