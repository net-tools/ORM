<?php


namespace Nettools\ORM\Tests;


class ModelTest extends \PHPUnit\Framework\TestCase
{
    protected $_db;
    protected $_pdo;



    public function setUp()
    {
        $this->_db = '/tmp/' . __CLASS__ . uniqid() . '.sqlite3';
        $this->_pdo = new \PDO('sqlite:' . $this->_db);
        $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$sql = <<<SQL
CREATE TABLE Client (idClient INT, name TEXT, idTown INT);
CREATE TABLE Town (idTown INT, town TEXT, population INT, bigcity INT);
INSERT INTO Town VALUES (0, 'NO MAN S LAND', 0, 0);
INSERT INTO Town VALUES (1, 'PARIS', 1000000, 1);
INSERT INTO Town VALUES (2, 'LYON', 500000, 1);
INSERT INTO Client VALUES (1, 'Dupont', 1);
INSERT INTO Client VALUES (2, 'Durand', 2);
INSERT INTO Client VALUES (3, 'Dumont', 3);
INSERT INTO Client VALUES (4, 'Dumas', NULL);
INSERT INTO Client VALUES (5, 'Dumat', 0);
SQL;

        $this->_pdo->exec($sql);
    }



    public function tearDown()
    {
        $this->_pdo = NULL;
        unlink($this->_db);
    }



    public function testGateway()
    {
        $g = new TestGateway($this->_pdo);

        $this->assertEquals('LYON', $g->getTown(2)->town);
        $this->assertEquals(500000, $g->getTown(2)->population);
        $this->assertEquals('Dupont', $g->getClient(1)->name);
        $this->assertEquals('PARIS', $g->getClient(1)->Town__town);
        $this->assertEquals(1, $g->getClient(1)->Town__idTown);

        // testing getting rows for non-existing primary keys
        $this->assertEquals(FALSE, $g->getClient(999));
        $this->assertEquals(FALSE, $g->getTown(999));
        
        // asserting the MyORM\Client class is used
        $this->assertInstanceOf(\MyORM\Client::class, $g->getClient(2));
        
        $this->assertEquals('Dumas', $g->getClient(4)->name);
        $this->assertEquals(NULL, $g->getClient(4)->idTown);
        
        // asserting that a foreign key with value 0 is fetched (difference between 0 and NULL)
        $this->assertEquals('NO MAN S LAND', $g->getClient(5)->Town__town);
		
		// testing select
		$select = $g->selectTown(["bigcity" => 1]);
		$this->assertEquals('array', gettype($select));
		$this->assertInstanceOf(\Nettools\ORM\ModelObject::class, $select[0]);
		$this->assertEquals('PARIS', $select[0]->town);
		$this->assertEquals('LYON', $select[1]->town);
				
		// testing select no where
		$select = $g->selectTown([]);
		$this->assertEquals('array', gettype($select));
		$this->assertEquals(3, count($select));
				
		// test query
		$rows = $g->query('SELECT * FROM Client, Town WHERE Client.idTown = Town.idTown AND Client.idClient = ?', [1]);
		$this->assertEquals('array', gettype($rows));
		$this->assertEquals(1, count($rows));
		$this->assertInstanceOf(\Nettools\ORM\ModelObject::class, $rows[0]);
		$this->assertEquals('PARIS', $rows[0]->town);
		$this->assertEquals('Dupont', $rows[0]->name);
		$this->assertEquals(1, $rows[0]->bigcity);
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Property 'notexistingproperty' does not exist in class 
     */
    public function testGatewayExceptionProperty()
    {
        $g = new TestGateway($this->_pdo);
        $g->getTown(2)->notexistingproperty;
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Foreign key row of 'Town' with primary key '3' does not exist.
     */
    public function testGatewayExceptionFK()
    {
        $g = new TestGateway($this->_pdo);
        $g->getClient(3)->Town__town;
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Table 'Invoice' has not been registered ; 'getInvoice' call failed.
     */
    public function testGatewayInexistantTable()
    {
        $g = new TestGateway($this->_pdo);
        $g->getInvoice(2);
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Method 'nomethod' does not exist.
     */
    public function testGatewayInexistantMethod()
    {
        $g = new TestGateway($this->_pdo);
        $g->nomethod();
    }
}



class TestGateway extends \Nettools\ORM\Model
{
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo, 'MyORM', ['Client', 'Town'], ['Client' => ['Town']]);
    }
}




namespace MyORM;

class Client extends \Nettools\ORM\ModelObject
{
    
}


?>
