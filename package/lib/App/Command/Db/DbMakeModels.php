<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;

use Strukt\Db\FieldType;
use Strukt\Db\DbType;

/**
* db:make-models     Make models from db
*
* Usage:
*
*      db:make-models [<db_type>] [<table>]
*
* Arguments:
*
*      db_type   Either pop|rb this is RedBeanPHP|PopDb default is rb, options:(rb,rb0,rb1,rb2,pop,pop0)
*      table     Table name
*/
class DbMakeModels extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$db_type = $in->get("db_type");
		if(empty($db_type))
			$db_type = "rb";

		$db_types = DbType::$types;
		if(!array_key_exists($db_type, $db_types))
			raise(sprintf("Unavailable %s type!", $db_type));

		$model_type = $db_types[$db_type];

		$types = [];
		foreach(array_flip(FieldType::$types) as $native=>$type)
			$types[str($native)->toSnake()->yield()] = $type;

		$tables = [];
		$table = $in->get("table");
		if(empty($table))
			$tables = sm()->tables();

		if(empty($tables))
			$tables[] = str($table)->toSnake()->yield();

		$list = arr($tables)->each(function($key, $table) use($types, $model_type){

			$short_name = ucfirst(str($table)->toCamel()->yield());

			try{

				$columns = sm($table)->columns();

				// dd($columns);

				unset($columns["id"]);

				$properties = arr($columns)->each(function($name, $type) use($types, $model_type){

					preg_match_all("/^(\w+)/", $type, $matches);
					$matches = reset($matches);
					$match = reset($matches);

					$notes["Type"] = $types[$match];

					$type = str($type);
					if($type->startsWith("enum")){

						$list = $type->replace(["enum(",")","'",'"'],"")->split(",");
						$notes["List"] = implode(",", $list);
					}

					return array(

						"access"=>"public",
						"name"=>$name,
						"annotations"=>$notes
					);

				})->yield();

				$app_name = config("app.name");

				$class = array(

					"declaration"=>array(

						"namespace"=>$app_name,
						"name"=>$short_name,
						"extends"=>$model_type,
					),
					"properties"=>$properties
				);

				$g = generator($class, ["property_notes"=>true]);

				$app_path = sprintf("app/src/%s", $app_name);
				$filename = sprintf("%s.php", $short_name);
				$contents = sprintf("<?php\n%s", (string)$g);
				
				$success = fs($app_path)->touchWrite($filename, $contents);
				if(!$success)
					heap(color("light-red", sprintf("status:fail|model:%s - Was unable to create model %s!", 
						$short_name,
						$short_name)));
				else
					heap(sprintf("status:success|model:%s", $short_name));
			}
			catch(\Exception $e){

				heap(color("light-red", sprintf("status:fail|model:%s", $short_name, $e->getMessage())));
			}
		});

		$out->add(implode("\n", heap()->get()->yield()));
	}
}