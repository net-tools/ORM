<?php


namespace Nettools\ORM\Tests;



class ModelObjectTest extends \PHPUnit\Framework\TestCase
{
	
    public function testObject()
    {
		$o = new \Nettools\ORM\ModelObject(
                (object)[
                    'prop1'     => 'my prop 1',
                    'prop2'     => NULL,
                    'prop3'     => 12
                ]
            );
        
        
        $this->assertEquals('my prop 1', $o->prop1);
        $this->assertEquals(NULL, $o->prop2);
        $this->assertEquals(12, $o->prop3);
    }
    

    
    public function testCopyObject()
    {
		$o1 = new \Nettools\ORM\ModelObject(
                (object)[
                    'prop1'     => 'my prop 1'
                ]
            );
        
		$o2 = (object)[
                    'prop2'     => NULL,
                    'prop3'     => 12
                ];
        
        $o1->copyFrom($o2);        
        $this->assertEquals('my prop 1', $o1->prop1);
        $this->assertEquals(NULL, $o1->prop2);
        $this->assertEquals(12, $o1->prop3);
        
        
		$o3 = (object)['prop4' => 'empty'];
        $o1->copyFrom($o3, 'prefix_');
        $this->assertEquals('empty', $o1->prefix_prop4);
    }
    

    
    /**
     * @expectedException \Exception
     */
    public function testObjectException()
    {
		$o = new \Nettools\ORM\ModelObject((object)[]);
        $o->prop;
    }
    
    
	
}



?>
