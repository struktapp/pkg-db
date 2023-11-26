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

			$notes = notes(sprintf("%s\\%s", $app_name, $model));
			unset($notes["methods"]);
			unset($notes["properties"]["bean"]);

			arr($notes["properties"])->each(function($column, $note) use($table, $types, $out){

				$sm = sm($table);

				$otype = strtolower($note["Type"]["item"]);
				$type = $types[$otype];
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

						msg(sprintf("status:success|table:%s|column:%s|type:%s", 
										$table, 
										$column, 
										$type));
					}
				}
				catch(\Exception $e){

					msg(color("light-red", sprintf("status:fail|table:%s|column:%s|type:%s - %s", 
									$table, 
									$column,
									$type, 
									$e->getMessage())));
				}
			});
		});

		$messages = msg()->get();
		if(!$messages->empty())
			$out->add(implode("\n", $messages->yield()));

		if($messages->empty())
			$out->add("Nothing was done!");
	}
}