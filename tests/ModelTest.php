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
CREATE TABLE Client (idClient INT, nom TEXT, idVille INT);
CREATE TABLE Ville (idVille INT, ville TEXT, population INT);
INSERT INTO Ville VALUES (1, 'PARIS', 1000000);
INSERT INTO Ville VALUES (2, 'LYON', 500000);
INSERT INTO Client VALUES (1, 'Dupont', 1);
INSERT INTO Client VALUES (2, 'Durand', 2);
INSERT INTO Client VALUES (3, 'Dumont', 3);
INSERT INTO Client VALUES (4, 'Dumas', NULL);
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

        $this->assertEquals('LYON', $g->getVille(2)->ville);
        $this->assertEquals(500000, $g->getVille(2)->population);
        $this->assertEquals('Dupont', $g->getClient(1)->nom);
        $this->assertEquals('PARIS', $g->getClient(1)->Ville__ville);
        $this->assertEquals(1, $g->getClient(1)->Ville__idVille);

        // testing getting rows for non-existing primary keys
        $this->assertEquals(FALSE, $g->getClient(5));
        $this->assertEquals(FALSE, $g->getVille(5));
        
        // asserting the MyORM\Client class is used
        $this->assertInstanceOf(\MyORM\Client::class, $g->getClient(2));
        
        $this->assertEquals('Dumas', $g->getClient(4)->nom);
        $this->assertEquals(NULL, $g->getClient(4)->idVille);
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Property 'notexistingproperty' does not exist in class 
     */
    public function testGatewayExceptionProperty()
    {
        $g = new TestGateway($this->_pdo);
        $g->getVille(2)->notexistingproperty;
    }



    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Foreign key row of 'Ville' with primary key '3' does not exist.
     */
    public function testGatewayExceptionFK()
    {
        $g = new TestGateway($this->_pdo);
        $g->getClient(3)->Ville__ville;
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
        parent::__construct($pdo, 'MyORM', ['Client', 'Ville'], ['Client' => ['Ville']]);
    }
}




namespace MyORM;

class Client extends \Nettools\ORM\ModelObject
{
    
}


?>
