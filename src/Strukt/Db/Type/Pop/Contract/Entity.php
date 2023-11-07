<?php

namespace Strukt\Db\Type\Pop\Contract;

abstract class Entity extends \Pop\Db\Record{

	public function __construct(array $row = null){

		$child = get_called_class();
		foreach(get_class_vars($child) as $property=>$value)
			if((new \ReflectionProperty($child, $property))->isPublic())
				unset($this->$property);

		parent::__construct($row);
	}
}