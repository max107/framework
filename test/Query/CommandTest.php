<?php

namespace Mindy\Tests\Query;

use Mindy\Query\DataReader;
use PDO;

/**
 * @group db
 * @group mysql
 */
abstract class CommandTest extends DatabaseTestCase
{
    public function testConstruct()
    {
        $sql = 'SELECT * FROM customer';
        $this->assertEquals($sql, $this->getAdapter()->quoteSql($sql));
    }

    public function testPrepareCancel()
    {
        $db = $this->getDb(false);

        $command = $db->createCommand('SELECT * FROM customer');
        $this->assertEquals(null, $command->pdoStatement);
        $command->prepare();
        $this->assertNotEquals(null, $command->pdoStatement);
        $command->cancel();
        $this->assertEquals(null, $command->pdoStatement);
    }

    /**
     * @expectedException \Mindy\Query\Exception\Exception
     */
    public function testExecute()
    {
        $db = $this->getDb();

        $sql = 'INSERT INTO customer(email, name , address) VALUES (\'user4@example.com\', \'user4\', \'address4\')';
        $command = $db->createCommand($sql);
        $this->assertEquals(1, $command->execute());

        $sql = 'SELECT COUNT(*) FROM customer WHERE name =\'user4\'';
        $command = $db->createCommand($sql);
        $this->assertEquals(1, $command->queryScalar());

        $db->createCommand('bad SQL')->execute();
    }

    public function testQueryAll()
    {
        $connection = $this->getDb();
        $rows = $connection->createCommand('SELECT * FROM customer')->queryAll();
        $this->assertEquals(3, count($rows));
        $row = $rows[2];
        $this->assertEquals(3, $row['id']);
        $this->assertEquals('user3', $row['name']);

        $rows = $connection->createCommand('SELECT * FROM customer WHERE id=10')->queryAll();
        $this->assertEquals([], $rows);
    }

    public function testQuery()
    {
        $reader = $this->getDb()->createCommand('SELECT * FROM customer')->query();
        $this->assertTrue($reader instanceof DataReader);
    }

    public function testQueryOne()
    {
        $connection = $this->getDb();
        $row = $connection->createCommand('SELECT * FROM customer ORDER BY id')->queryOne();
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $command = $connection->createCommand('SELECT * FROM customer ORDER BY id');
        $command->prepare();
        $row = $command->queryOne();
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $command = $connection->createCommand('SELECT * FROM customer WHERE id=10');
        $this->assertFalse($command->queryOne());
    }

    public function testQueryColumn()
    {
        $connection = $this->getDb();
        $column = $connection->createCommand('SELECT * FROM customer')->queryColumn();
        $this->assertEquals(range(1, 3), $column);

        $command = $connection->createCommand('SELECT id FROM customer WHERE id=10');
        $this->assertEquals([], $command->queryColumn());
    }

    public function testQueryScalar()
    {
        $connection = $this->getDb();
        $this->assertEquals($connection->createCommand('SELECT * FROM customer ORDER BY id')->queryScalar(), 1);

        $command = $connection->createCommand('SELECT id FROM customer ORDER BY id');
        $command->prepare();
        $this->assertEquals(1, $command->queryScalar());

        $command = $connection->createCommand('SELECT id FROM customer WHERE id=10');
        $this->assertFalse($command->queryScalar());
    }

    public function testQueryBad()
    {
        $db = $this->getDb();
        $command = $db->createCommand('bad SQL');
        $this->expectException(\Mindy\Query\Exception\Exception::class);
        $command->query();
    }

    public function testFetchAssoc()
    {
        $connection = $this->getDb();
        $command = $connection->createCommand('SELECT * FROM customer');
        $result = $command->queryOne();
        $this->assertTrue(is_array($result) && isset($result['id']));
    }

    public function testFetchObj()
    {
        $connection = $this->getDb();
        $command = $connection->createCommand('SELECT * FROM customer');
        $command->fetchMode = \PDO::FETCH_OBJ;
        $result = $command->queryOne();
        $this->assertTrue(is_object($result));
    }

    public function testFetchNum()
    {
        $connection = $this->getDb();
        $command = $connection->createCommand('SELECT * FROM customer');
        $result = $command->queryOne(\PDO::FETCH_NUM);
        $this->assertTrue(is_array($result) && isset($result[0]));
    }

    public function testInsert()
    {
    }

    public function testUpdate()
    {
    }

    public function testDelete()
    {
    }

    public function testCreateTable()
    {
    }

    public function testRenameTable()
    {
    }

    public function testDropTable()
    {
    }

    public function testTruncateTable()
    {
    }

    public function testAddColumn()
    {
    }

    public function testDropColumn()
    {
    }

    public function testRenameColumn()
    {
    }

    public function testAlterColumn()
    {
    }

    public function testAddForeignKey()
    {
    }

    public function testDropForeignKey()
    {
    }

    public function testCreateIndex()
    {
    }

    public function testDropIndex()
    {
    }
}
