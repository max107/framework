<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:52
 */

namespace Mindy\Session\Adapter;

use Exception;
use PDO;

class PdoSessionAdapter extends NativeSessionAdapter
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @param PDO $pdo
     */
    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        $statement = $this->pdo->prepare("SELECT * FROM session WHERE expire>=:expire AND id=:id");
        $statement->execute([':expire' => time(), ':id' => $session_id]);
        $session = $statement->fetchObject();
        return $session ? $session->data : null;
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

        $statement = $this->pdo->prepare("SELECT * FROM session WHERE id>=:id");
        $statement->execute([':id' => time()]);
        $session = $statement->fetchObject();

        if (!$session) {
            $stmt = $this->pdo->prepare("INSERT INTO session (id, data, expire) VALUES (:id, :data, :expire)");
            if ($stmt->execute([':id' => $session_id, ':data' => $session_data, ':expire' => $expire]) === false) {
                throw new Exception("Failed to create session");
            }
        } else {
            $stmt = $this->pdo->prepare("UPDATE session SET data=:data, expire=:expire WHERE id=:id");
            if ($stmt->execute([':data' => $session_data, ':expire' => $expire, ':id' => $session_id]) === false) {
                throw new Exception("Failed to update session");
            }
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
        $stmt = $this->pdo->prepare("DELETE FROM session WHERE id=:id");
        return $stmt->execute([':id' => $session_id]);
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxlifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gc($maxlifetime)
    {
        $stmt = $this->pdo->prepare("DELETE FROM session WHERE expire<=:expire");
        return $stmt->execute([':expire' => time()]);
    }
}