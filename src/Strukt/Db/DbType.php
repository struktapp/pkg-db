<?php

namespace Strukt\Db;

class DbType{

	public static $types = array(

		"rb0"  => "\RedBeanPHP\SimpleModel", 
		"rb1"  => "\RedBeanPHP\TypedModel", 
		"rb2"  => "\Strukt\Db\Type\Red\Contract\EntityTyped", 
		"rb"   => "\Strukt\Db\Type\Red\Contract\Entity", 
		"pop0" => "\Pop\Db\Record",
		"pop"  => "\Strukt\Db\Type\Pop\Contract\Entity"		
	);
}