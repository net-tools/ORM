<?php


namespace Nettools\ORM;



/**
 * Default object for ORM layer 
 */
class ModelObject {
		
    /**
     * @var \stdClass Underlying object storing properties
     */
    protected $_o;
    
    
    
	/**
	 * Constructor
     * 
     * @param \stdClass $from Object litteral with column values
	 */
	public function __construct(\stdClass $from)
	{
		$this->_o = $from;
	}
    
    
    
    /**
     * Take values from an object litteral and store them inside current object, possibly with a prefix before property names
     *
     * @param \stdClass $from
     * @param string $prefix
     * @return \Nettools\ORM\ModelObject Returns $this (for chaining calls)
     */
    public function copyFrom(\stdClass $from, $prefix = '')
    {
        foreach ( $from as $k => $v )
            $this->_o->{$prefix . $k} = $v;
        
        return $this;
    }
	
	
	
    /**
     * Accessor
     *
     * @param string $k
     * @return mixed
     * @throws \Exception
     */
	public function get($k)
	{
        if ( !property_exists($this->_o, $k) )
            throw new \Exception("Property '$k' does not exist in class '" . get_class($this) ."'");
        
        return $this->_o->{$k};
	}
    
    
    
    /**
     * Magic accessor
     *
     * @param string $k
     * @return mixed
     * @throws \Exception
     */
    public function __get($k)
    {
		return $this->get($k);
    }
}


?>