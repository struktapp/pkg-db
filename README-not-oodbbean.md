## Return own model instead of OODBBean

```php
//https://bit.ly/40l4xAL
class Model extends \RedBeanPHP\SimpleModel {
    public static $config = array(
    	'table' => null
    );
    
    public function __construct($bean = null) {
        if(is_null($bean)) $this->bean = \R::dispense(self::getTable());
        elseif(is_int($bean)) $this->bean = \R::load(self::getTable(),$bean);
        else $this->bean = $bean;
    }
    
    public function save() {
        \R::store($this->bean);
    }
    
    public static function findOne($sql=null,$bindings=array()) {
        $instance = new self(\R::findOne(self::getTable(),$sql,$bindings));
        return $instance;
    }
    
    public static function find($sql=null,$bindings=array()) {
        $return = [];
        $result = \R::find(self::getTable(),$sql,$bindings);
        
        foreach($result as $item) $return[] = new self($item);
        
        return $return;
    }
    
    public static function findAll($sql=null,$bindings=array()) {
        $return = [];
        $result = \R::findAll(self::getTable(),$sql,$bindings);
        
        foreach($result as $item) $return[] = new self($item);
        
        return $return;
    }
    
    private static function getTable() {
        if(is_null(static::$config['table'])) {
            $reflection = new \ReflectionClass(get_called_class());
            return strtolower($reflection->getShortName());
        }
        
        return static::$config['table'];
    }
}
```