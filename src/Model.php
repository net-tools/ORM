<?php


namespace Nettools\ORM;



/**
 * Class for ORM layer
 */
class Model {
	
	protected $_pdo;
    protected $_pdoStatementCache = [];
    protected $_userns;
    protected $_foreignKeysMap = [];
		
		
    
    /**
     * Prepare a PDOStatement for a table
     * 
     * @param string $table
     * @param string $keyname Primary key ; if not defined, will be set with 'id$table'
     */
    protected function _registerTable($table, $keyname = NULL)
    {
        if ( is_null($keyname) )
            $keyname = "id{$table}";
        
        $this->_pdoStatementCache[$table] = $this->_pdo->prepare("SELECT * FROM `$table` WHERE $keyname = ? LIMIT 1");
    }
    
    
    
    /**
     * Get a PDOStatement for a table
     *
     * @param string $table
     * @return \PDOStatement
     * @throws \Exception
     */
    protected function _getPDOStatement($table)
    {
        $pdost = $this->_pdoStatementCache[$table];
        if ( !$pdost )
            throw new \Exception("PDOStatement for table '$table' has not been prepared.");
        
        return $pdost;
    }
    
    
		
	/**
	 * Constructor
     * 
     * @param \PDO $pdo
     * @param string $userns User namespace to look into for user-side ORM objects 
     * @param string[] Tables to register with the ORM
     * @param array Map of foreign keys for tables (associative array table_name => ['fktable1', 'fktable2'])
	 */
	public function __construct(\PDO $pdo, $userns = NULL, array $tables, array $foreignKeys = [])
	{
		$this->_pdo = $pdo;
        $this->_userns = $userns;
        
        
        // registering ORM tables
        foreach ( $tables as $table )
            $this->_registerTable($table);
        
        
        // setting foreign keys
        $this->setForeignKeysMap($foreignKeys);
	}
    
    
    
    /**
     * Sets foreign keys map
     *
     * @param array Map of foreign keys for tables (associative array table_name => ['fktable1', 'fktable2'])
     */
    public function setForeignKeysMap(array $foreignKeys)
    {
        $this->_foreignKeysMap = $foreignKeys;    
    }
    
    
    
    /**
     * Get a table row identified by its primary key
     * 
     * @param string $table
     * @param string|int $id
     * @return \stdClass|FALSE
     * @throws \Exception
     */
    protected function _getTableRow($table, $id)
    {
        // get PDOStatement
        $pdo_st = $this->_getPDOStatement($table);

        // exec prepared request with parameter
        if ( !$pdo_st->execute([$id]) )
            throw new \Exception("SQL error during request on table '$table'.");
        
        // returning row or FALSE if not found
        return $pdo_st->fetch(\PDO::FETCH_OBJ);
    }
    
    
    
    /**
     * Get a row which is a foreign key for a given ModelObject.
     * 
     * Foreign row columns are set as properties of $obj prefixed with foreign table. For example,
     * if a Town table is a foreign key of a Client table, the $client object will have properties
     * such as 'Town__idTown', 'Town__geolocation', 'Town__population'.
     *
     * @param \Nettools\ORM\ModelObject $obj Object with a foreign key reference 
     * @param string $fktable Table referenced by foreign key
     * @param string $fkid Primary key value of row to return from $table and store inside $obj
     * @return \Nettools\ORM\ModelObject Returns $obj (for chaining calls)
     * @throws \Exception
     */
    protected function _getForeignKey(ModelObject $obj, $fktable, $fkid)
    {
        if ( $fkid )
        {
            // get foreign key row
            $fkobj = $this->_getTableRow($fktable, $fkid);
            if ( !$fkobj )
                throw new \Exception("Foreign key row of '$fktable' with primary key '$fkid' does not exist."); 

            $obj->copyFrom($fkobj, "{$fktable}__");
            return $obj;
        }
    }
    
    
    
    /**
     * Get a table row identified by its primary key and return an ORM layer object
     * 
     * @param string $table
     * @param string|int $id
     * @return \Nettools\ORM\ModelObject|FALSE
     * @throws \Exception
     */
    protected function _getObjectRow($table, $id)
    {
        $o = $this->_getTableRow($table, $id);
        if ( $o )
        {
            // does an object exist user-side for $table ?
            if ( $this->_userns )
            {
                $userclass = '\\' . trim($this->_userns, '\\') . '\\' . $table;
                if ( class_exists($userclass) )
                    return new $userclass($o);
            }

            
            // if we arrive here, there's no userside $table or the user-side namespace is not defined
            return new ModelObject($o);
        }
        else
            return false;
    }
    
    
    
    /**
     * Magic accessor for fetching a table row.
     *
     * Method calls should be 'getXXX' where XXX is the table name. Foreign keys registered in the constructor
     * are resolved (foreign key properties should be named 'idXXXX' with XXXX being the foreign key table name).
     *
     * @param string $m
     * @param array $args
     * @return \Nettools\ORM\ModelObject|FALSE
     */
    public function __call($m, $args)
    {
        // if method call starts with 'get', we are looking for a table registered named along with string following 'get' prefix
        if ( strpos($m, 'get') === 0 )
        {
            $table = substr($m, 3);
            if ( array_key_exists($table, $this->_pdoStatementCache) )
            {
                // fetch ORM object
                $o = $this->_getObjectRow($table, $args[0]);
                
                // if row exists
                if ( $o )
                    // handle foreign keys
                    if ( $this->_foreignKeysMap[$table] )
                        foreach ( $this->_foreignKeysMap[$table] as $fk )
                            $this->_getForeignKey($o, $fk, $o->{'id' . $fk});
                
                return $o;
            }
            else
                throw new \Exception("Table '$table' has not been registered ; 'get$table' call failed.");
        }
        else
            throw new \Exception("Method '$m' does not exist.");
    }
}


?>