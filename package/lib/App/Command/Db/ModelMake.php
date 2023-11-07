<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;

use Strukt\Db\FieldType;
use Strukt\Db\DbType;

/**
* model:make     Make model
*
* Usage:
*
*      model:make  [--type <rb>] <args...>
*
* Arguments:
*
*      args     Class arguments
*
* Options:
*
*      --type -t   DB type - optional default rb
*
*                rb0  - RedBeanPHP\SimpleModel
*                rb1  - RedBeanPHP\TypedModel 
*                rb2  - Strukt\Db\Contract\EntityTyped
*                rb   - Strukt\Db\Contract\Entity
*                pop0 - Pop\Db\Record
*                pop  - Strukt\Db\Type\Pop\Contract\Entity
*/
class ModelMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$argv = $in->getAll();
		$argv = array_flip($argv);
		$type = $in->get("type");

		if(!empty($type)){

			unset($argv["--type"]);
			unset($argv[$type]);
		}

		$types = DbType::$types;
		if(empty($type))
			$type = "rb";

		if(!array_key_exists($type, $types))
			raise(sprintf("Unavailable %s type!", $type));

		$model_type = $types[$type];

		$argv = array_flip($argv);
		$class = array_shift($argv);
		if(!preg_match("/^class:[A-Za-z0-9_]+$/", $class))
			raise("Invalid class specification!");

		list($_, $class_name) = explode(":", $class);
		$class_name = ucfirst(str($class_name)->toCamel());

		$class = array(

			"declaration"=>array(

				"namespace"=>config("app.name"),
				"name"=>$class_name,
				"extends"=>$model_type
			)
		);

		$properties = arr($argv)->each(function($key, &$val){

			$type = "string";
			$field = $val;
			if(preg_match("/^\w+:\w+$/", $val))
				list($field, $type) = explode(":", $val);
			
			return array(

				"access"=>"public",
				"name"=>$field,
				"annotations"=>array(

					"Type"=>$type,
				)
			);
		});

		$class["properties"] = $properties->yield();

		$g = generator($class, ["property_notes"=>true]);

		$app_path = sprintf("app/src/%s", config("app.name"));
		$filename = sprintf("%s.php", $class_name);
		$contents = sprintf("<?php\n%s", (string)$g);
		
		$success = fs($app_path)->touchWrite($filename, $contents);
		if(!$success)
			raise("Was unable to created model!");

		$out->add("Model created successfully.");
	}
}