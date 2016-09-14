<?php

namespace Mindy\Query;

/**
 * Class PDO
 * @package Mindy\Query
 */
class PDO extends \PDO
{
    /**
     * @var array Database drivers that support savepoints.
     */
    protected $savepointTransactions = ["pgsql", "mysql"];

    /**
     * @var int The current transaction level.
     */
    protected $transLevel = 0;

    protected function nestable()
    {
        return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME), $this->savepointTransactions);
    }

    public function beginTransaction()
    {
        if (!$this->nestable() || $this->transLevel == 0) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL{$this->transLevel}");
        }

        $this->transLevel++;
    }

    public function commit()
    {
        $this->transLevel--;

        if (!$this->nestable() || $this->transLevel == 0) {
            parent::commit();
        } else {
            $this->exec("RELEASE SAVEPOINT LEVEL{$this->transLevel}");
        }
    }

    public function rollBack()
    {
        $this->transLevel--;

        if (!$this->nestable() || $this->transLevel == 0) {
            parent::rollBack();
        } else {
            $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transLevel}");
        }
    }
}
