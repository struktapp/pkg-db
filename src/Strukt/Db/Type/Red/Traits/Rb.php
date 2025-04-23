<?php

namespace Strukt\Db\Type\Red\Traits;

use RedBeanPHP\R;
use RedBeanPHP\SimpleModelInterface;

 /** 
 * @author Moderator <pitsolu@gmail.com>
 */
trait Rb{

    /**
     * @var ...$args
     */
    public function __construct(...$args){

        if(!empty($args)){

            $props = array_keys($this->getProperties());
            foreach($props as $idx=>$prop)
                $this->$prop = $args[$idx]??$args[$prop]??null; 
        }
    }

    /**
     * @return array
     */
    private function getProperties():array{

        $props = get_object_vars($this);
        unset($props["bean"]);

        return $props;
    }

    /**
     * @return int
     */
    public function save():int{

        foreach(get_object_vars($this) as $property=>$value)
            if($property != 'bean')
                $this->bean->$property = $value;

        return R::store($this->bean);
    }

    /**
     * @return array
     */
    public function toArray():array{

        return $this->unbox()?->export()??$this->getProperties();
    }

    /**
     * @param string $name
     */
    public function __get($name):array|string|SimpleModelInterface{

        $prop = str($name);
        if($prop->equals("id"))
            return $this->bean->id;

        $prop = $prop->concat("_id")->yield();
        if(property_exists($this, $prop))
            return sync($this->bean->$name);

        $prop = singular($name);

        $owner = str(get_called_class())
                    ->replace(str(config("app.name"))
                        ->concat("\\")
                        ->yield(), "")
                    ->toLower()
                    ->yield();

        $relation = sprintf("%s_%s", $owner, $prop);
        if(db()->getToolBox()->getWriter()->tableExists($relation)){

            $own_relation = str($relation)->toCamel()->prepend("own")->yield();
            $beans = $this->bean->unbox()->$own_relation;

            return arr($beans)->each(function($idx, $bean) use($prop){

                return sync($bean)->$prop;

            })->yield();
        }

        $own = str(ucfirst(str($prop)->toSnake()->yield()))->prepend("own")->yield();
        $beans = $this->unbox()->$own;
        if(empty($beans))
            return null;

        return arr($beans)->each(function($idx, $bean){

            return sync($bean);

        })->yield();
    }
}