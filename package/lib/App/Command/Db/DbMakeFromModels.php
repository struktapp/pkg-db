<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* db:make     Make db from models
*
* Usage:
*
*      db:make
*      db:make [<model>]
*
* Arguments:
*
*      model   name of model or table
*/
class DbMakeFromModels extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$app_name = config("app.name");
		$fs = fs(sprintf("%s/app/src/%s", env("root_dir"), $app_name));

		// $files = fs(sprintf("app/src/%s", $app_name))->ls();
		$files = $fs->ls();
		$models = arr($files)->each(fn($k, $v)=>str($v)->endsWith(".php")?trim($v, ".php"):null)->yield();

		$model = $in->get("model");
		if(!empty($model)){

			$model = ucfirst(str($model)->toSnake()->yield());
			if(in_array($model, $models))
				$models = [$model];
		}

		$sm = sm(); //Schema Manager

		$types = [];
		foreach(\Strukt\Db\FieldType::$types as $native=>$type)
			$types[str($native)->toSnake()->yield()] = $type;

		arr(array_filter($models))->each(function($k, $model) use($app_name, $sm, $types, $out){

			$table = str($model)->toSnake()->yield();
			if(!$sm->tableExists($table)){

				$sm->createTable($table);
				print_r($sm->getSql());
				$sm->exec();
			}

			$class = sprintf("%s\\%s", $app_name, $model);
			$notes = notes($class);
			unset($notes["methods"]);
			unset($notes["properties"]["bean"]);

			$notes["properties"] = array_filter($notes["properties"], function($key) use($class){

				return in_array($key, array_keys(get_class_vars($class)));

			}, ARRAY_FILTER_USE_KEY);

			arr($notes["properties"])->each(function($column, $note) use($table, $types, $out){

				$sm = sm($table);

				$otype = strtolower($note["Type"]["item"]);
				$type = $types[$otype];
				if(is_null($note))
					$note = [];

				if(array_key_exists("List", $note))
					$type = str($type)
						->concat("('")
						->concat(implode("','", $note["List"]["items"]))
						->concat("')")
						->yield();

				$options = [];
				if(str($otype)->toLower()->equals("string"))
					$options["size"] = 255;

				if(str($otype)->toLower()->equals("int") ||
					str($otype)->toLower()->equals("integer"))
					$options["size"] = 11;

				if(is_null($type))
					$type = "varchar";

				if(arr($note)->contains("Default"))
					if(str($note["Default"]["item"])->equals("now"))
						$options["default"] = "CURRENT_TIMESTAMP";

				try{

					$print = false;
					if(!$sm->hasColumn($column)){

						$print = true;
						$sm->addColumn($column, $type, $options);
					}
					
					if($sm->hasColumn($column)){

						$ftype = $type;
						if(str($otype)->equals("enum"))
							$ftype = $otype;

						$fields = $sm->fields();
						if(!str($fields[$column])->equals($ftype)){

							$print = true;
							$sm->changeColumn($column, $type, $options);
						}
					}

					if($print){

						print_r($sm->getSql());
						$sm->exec();

						heap(sprintf("status:success|table:%s|column:%s|type:%s", 
										$table, 
										$column, 
										$type));
					}
				}
				catch(\Exception $e){

					heap(color("light-red", sprintf("status:fail|table:%s|column:%s|type:%s - %s", 
									$table, 
									$column,
									$type, 
									$e->getMessage())));
				}
			});
		});

		$messages = heap()->get();
		if(!$messages->empty())
			$out->add(implode("\n", $messages->yield()));

		if($messages->empty())
			$out->add("Nothing was done!");
	}
}