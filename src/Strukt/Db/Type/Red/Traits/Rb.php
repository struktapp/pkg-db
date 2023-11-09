<?php

namespace Strukt\Db\Type\Red\Traits;

use RedBeanPHP\R;
use Symfony\Component\String\Inflector\EnglishInflector;

trait Rb{

    public function __construct(array $data = []){

        parent::__construct($data);
    }

	public function save(){

		foreach(get_object_vars($this) as $property=>$value)
        	if($property != 'bean')
            	$this->bean->$property = $value;

		R::store($this->bean);
	}

	public function toArray(){

		return $this->unbox()->export();
	}

	public function __get($name) {

    	$prop = str($name);
    	if($prop->equals("id"))
    		return $this->bean->id;

    	$prop = $prop->concat("_id")->yield();
    	if(property_exists($this, $prop))
            return sync($this->bean);

        $inflector = new EnglishInflector();
        $names = $inflector->singularize($name);
        $prop = reset($names);

        $own = str(ucfirst(str($prop)->toSnake()->yield()))->prepend("own")->yield();
        $beans = $this->unbox()->$own;
        if(empty($beans))
            return null;

        return arr($beans)->each(function($idx, $bean){

            return sync($bean);

        })->yield();
    }
}