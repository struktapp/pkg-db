<?php

namespace Strukt\Db\Type\Pop\Contract;

/**
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Entity extends \Pop\Db\Record{

	/**
	 * @param array $row
	 */
	public function __construct(?array $row = null){

		$child = get_called_class();
		foreach(get_class_vars($child) as $property=>$value)
			if((new \ReflectionProperty($child, $property))->isPublic())
				unset($this->$property);

		parent::__construct($row);
	}

	/**
	 * @param array $column
	 * @param bool $commit
	 * 
	 * @return void
	 */
	public function save(?array $columns = null, bool $commit = true): void{

		parent::save($columns, $commit);
	}
}