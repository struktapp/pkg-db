<?php

use RedBeanPHP\R;
use Pop\Db\Record;
use Strukt\Db\Type\Pop\SchemaManager;
use Strukt\Db\Type\Pop\Connection as PopDb;
use Strukt\Db\Type\Red\Connection as RedDb;
use Pop\Db\Sql\Schema as PopSchema;
use Pop\Db\Adapter\Pdo as PopPdo;
use RedBeanPHP\OODBBean as Bean;

if(!function_exists("rb")){

	/**
	* Sync bean and model
	*/
	function sync(array|Bean $bean){

		if(is_array($bean))
			return arr($bean)->each(fn($k, $v)=>sync($v))->yield();

		$model = $bean->box();
		$model->id = $bean->id;
		foreach(get_object_vars($model) as $property=>$value)
        	if($property != 'bean')
            	$model->$property = $bean->$property;

        return $model;
	};

	function rb(string $model_name = null, int $id = null){

		if(!is_null($model_name) && is_null($id)) 
			return R::getRedBean()->dispense(str($model_name)->toLower()->yield())->box();

        if(!is_null($id) && !is_null($model_name)) 
        	return sync(R::load($model_name, $id));

		return new R;
	}
}

if(!function_exists("schema")){

	function schema(){

		try{

			if(reg("db.which") == "pop")
				return db()->createSchema();
		}
		catch(Exception $e){

			$db = config("db*");
			config("db.database", $db["name"]);

			return new PopSchema(new PopPdo(config("db*")));
		}
	}
}

if(!function_exists("popdb")){

	function makeModel(string $model){

		$class = ucfirst(str($model)->toCamel()->yield());

		if(!class_exists($class))
			eval("class $class extends \Pop\Db\Record{}");

		return new $class;
	}

	function popdb(string $model_name = null, int $id = null){

		if(!is_null($model_name)){

			$app_name = config("app.name");
			$model = str($app_name)->concat("\\")
						->concat(ucfirst(str($model_name)
									->toCamel()
										->yield()))
							->yield();

			if(!is_null($id))
				return $model::findById($id);

			return new $model([]);
		}

		return Record::getDb();
	}
}

if(!function_exists("useDb")){

	function useDb(string $db = "rb"){

		$db = trim($db);
		reg("db.which", $db);
		if(!in_array($db, ["pop", "rb"]))
			return false;

		if($db == "rb")
			new RedDb();

		if($db == "pop")
			new PopDb();

		return true;
	}

	function switchDb(){

		if(reg()->exists("db.which")){

			$db = reg("db.which");
			reg()->remove("db.which");
		}

		if(!isset($db))
			$db = "rb";
		elseif(str($db)->equals("rb"))
			$db = "pop";
		elseif(str($db)->equals("pop"))
			$db = "rb";

		if(useDb($db))
			return $db;

		return null;
	}
}

if(!function_exists("db")){

	function db(string $model_name = null, int $id = null){

		if(!is_null($model_name) && is_null($id))
			if(str($model_name)->endsWith("*"))
				return str(config("app.name"))
						->concat("\\")
						->concat(ucfirst(str(trim($model_name, "*"))
							->toSnake()
							->yield()))
								->yield();

		if(!is_null($model_name) && is_null($id)){

			if(reg("db.which") == "pop")
				return popdb($model_name);

			return rb($model_name);			
		}

		if(!is_null($model_name) && !is_null($id)){

			if(reg("db.which") == "pop")
				return popdb($model_name, $id);

			return rb($model_name, $id);
		}

		if(is_null($model_name) && is_null($id)){

			if(reg("db.which") == "pop")
				return popdb();

			return rb();
		}
	}
}

if(!function_exists("sm")){

	function sm(string $table = null){

		return new SchemaManager($table);//PopDb Schema Manager
	}
}

if(!function_exists("pdo")){

	counter(0, ".strukt-trx");

	function pdo(){

		$db = str(reg("db.which"));

		if($db->equals("pop"))
			$pdo = db()->getConnection();

		if($db->equals("rb"))
			$pdo = db()->getDatabaseAdapter()->getDatabase()->getPdo();

		// $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
		// $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
		// $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		// $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return new class($pdo){

			private $pdo;

			public function __construct($pdo){

				$this->counter = counters(".strukt-trx");
				$this->pdo = $pdo;
			}

			public function begin(){

				$success = null;
				if($this->counter->equals(0))
					$success = $this->pdo->beginTransaction();

				$this->counter->up();

				return $success;
			}

			public function commit(){

				$this->counter->down();

				if($this->counter->equals(0))
					return $this->pdo->commit();
			}

			public function rollback(\Exception $exception = null){

				$this->counter->down();

				if($this->counter->equals(0))
					return $this->pdo->rollBack();

				if(!$this->counter->equals(0)){

					if(is_null($exception))
						$exception = new \Exception("Rollback occured!");

					throw $exception;
				}
			}

			public function execPreQuery(string $sql, array $params){
				
				$options = [];
				if(arr($params)->isMap())
					$options = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY];
						
				$stmt = $this->pdo->prepare($sql, $options);
				$stmt->execute($params);

				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			}

			public function execQuery(string $sql, array $params = null){

				if(is_null($params))
					return $this->pdo->query($sql, \PDO::FETCH_ASSOC)->fetchAll();

				return $this->execPreQuery($sql, $params);
			}

			public function getDb(){

				return $this->pdo;
			}

			/**
			* Copied from R::transaction for similar features
			*/
			public function transact(callable $callback){

				$result = null;
				
				try {

					$this->begin();
					$result = call_user_func($callback); //maintain 5.2 compatibility
					$this->commit();
				} 
				catch(Exception $e){

					$this->rollback();
			
					throw $e;
				}

				return $result;
			}
		};
	}
}

if(!function_exists("seed")){

	function seed(string $table, array $data = []){

		$sql = db()->createSql();

        $sql->insert($table)->values($data);

        db()->query($sql);
	}
}

if(!function_exists("faker")){

	function fake(string $var = null){

		$fake = event("provider.fake")->exec();

		if(!is_null($var))
			return $fake->$var;

		return $fake;
	}

	function faker(int $count, string $table, array $columns){

		$faker = event("provider.faker")->exec();

		$faker->table($table)->columns($columns)->rowQuantity($count);

		return $faker;
	}
}