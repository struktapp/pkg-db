<?php

use RedBeanPHP\R;
use Pop\Db\Record;
use Strukt\Db\Type\Pop\SchemaManager;
use Strukt\Db\Type\Pop\Connection as PopDb;
use Strukt\Db\Type\Red\Connection as RedDb;
use Pop\Db\Sql\Schema as PopSchema;
use Pop\Db\Adapter\Pdo as PopPdo;
use RedBeanPHP\OODBBean as Bean;

helper("pkg-db");

if(helper_add("rb")){

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

if(helper_add("schema")){

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

if(helper_add("popdb")){

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

if(helper_add("useDb")){

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

if(helper_add("db")){

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

if(helper_add("sm")){

	function sm(string $table = null){

		return new SchemaManager($table);//PopDb Schema Manager
	}
}

if(helper_add("pdo")){

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
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return new class($pdo){

			private $pdo;

			public function __construct($pdo){

				$this->pdo = $pdo;
			}

			public function begin(){
				
				return db()->beginTransaction(); //success
			}

			public function commit(){

				return db()->commit();
			}

			public function rollback(){
	
				db()->rollback();
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

			public function qMarks(array $params){

				$qMarks = str_repeat('?,', count($params) - 1) . '?';

				return $qMarks;
			}

			public function execPrep(string $sql, array $params = null){

				$params =  array_values(arr($params)->level());

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

if(helper_add("seed")){

	function seed(string $table, array $data = []){

		$sql = db()->createSql();

        $sql->insert($table)->values($data);

        return db()->query($sql);
	}
}

if(helper_add("faker")){

	function fake(string $var = null){

		$fake = event("provider.fake")->exec();

		if(!is_null($var))
			return $fake->$var;

		return $fake;
	}
}

if(helper_add("select")){

	function select(string $fields){


		$ops=fn(string $fields)=>arr(str($fields)->split(","))->each(function($k, $field){
				
			$field = str(trim($field));
			if($field->startsWith("date(")){

				$field = $field->replace(["date(",")"],"");
				list($alias, $column) = $field->split(".");
				$field = $field->prepend("DATE_FORMAT(")
							->concat(',"%Y-%m-%d") as ')
							->concat($column);
			}

			if($field->startsWith("datetime(")){

				$field = $field->replace(["datetime(",")"],"");
				list($alias, $column) = $field->split(".");
				$field = $field->prepend("DATE_FORMAT(")
							->concat(',"%Y-%m-%d %H:%i:%s") as ')
							->concat($column);
			}

			if($field->startsWith("money(")){

				$field = $field->replace(["money(",")"],"");
				list($alias, $column) = $field->split(".");
				$field = $field->prepend("FORMAT(")
							->concat(',2) as ')
							->concat($column);
			}
			
			return $field;
		});

		$sql = str(" SELECT ")->concat(implode(", ", $ops($fields)->yield()));

		return new class($sql, $ops){

			private $sql;
			private $ops;
			private $prep;

			public function __construct($sql, callable $ops){

				$this->sql = $sql;
				$this->ops = $ops;
				$this->prep = false;
			}

			public function addSelect(string $fields){

				$ops = $this->ops;
				$this->sql = $this->sql->concat(" , ")->concat(implode(", ", $ops($fields)->yield()));

				return $this;
			}

			public function from(string $tables){

				$self = $this;
				$this->sql = $this->sql->concat(str(" FROM ")->concat($tables));
				return new class($self, $this->sql, $this->prep){

					public function __construct($self, &$sql, &$prep){

						$this->sql = &$sql;
						$this->self = $self;
						$this->prep = &$prep;
					}

					public function leftjoin(string $join){

						$this->sql = $this->sql->concat(" LEFT JOIN ")->concat($join);

						return $this;
					}

					public function __call(string $name, array $args){

						if(arr(["orWhere", "andWhere", "where"])->has($name) && !$this->sql->contains(" WHERE "))
							return $this->self->where(...$args);

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			public function where(string $condition){

				$self = $this;
				$this->sql = $this->sql->concat(" WHERE ")->concat($condition);
				return new class($self, $this->sql, $this->prep){

					public function __construct($self, &$sql, &$prep){

						$this->sql = &$sql;
						$this->self = $self;
						$this->prep = &$prep;
					}

					public function andWhere(string $condition){

						$this->sql = $this->sql->concat(" AND ")->concat($condition);
						if(str($condition)->contains("?"))
							$this->prep = true;

						return $this;
					}

					public function orWhere(string $condition){

						$this->sql = $this->sql->concat(" OR ")->concat($condition);
						if(str($condition)->contains("?"))
							$this->prep = true;

						return $this;
					}

					public function __call(string $name, array $args){

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			public function page(int $page, int $perPage=10){

				$offset = ($page - 1) * $perPage;

				$this->sql = $this->sql->concat(sprintf(" LIMIT %d, %d", $offset, $perPage));

				return $this;
			}

			public function groupBy(string $columns){

				$this->sql = $this->sql->concat(sprintf(" GROUP BY %s", $columns));

				return $this;
			}

			public function orderBy(string $columns, string $order = "DESC"){

				$this->sql = $this->sql->concat(sprintf(" ORDER BY %s %s", $columns, $order));

				return $this;
			}

			public function union(string $sql){

				$this->sql = $this->sql->concat(" UNION ")->concat($sql);

				return $this;
			}

			public function unionAll(string $sql){

				$this->sql = $this->sql->concat(" UNION ALL ")->concat($sql);

				return $this;
			}

			public function isPrep(){

				return $this->prep;
			}

			public function __toString(){

				return $this->sql->yield();
			}
		};
	}	
}

if(helper_add("modify")){

	function modify(string $table){

		return new class($table){

			private $sql;

			public function __construct(string $table){

				$this->sql = str("UPDATE ")->concat($table);
			}

			public function set(string $modify){

				$separator = preg_match("/\sSET\s/", $this->sql->yield());

				$this->sql = $this->sql->concat($separator?", ":" SET ")->concat($modify);

				return new class($this->sql, $this){

					private $sql;
					private $parent;

					public function __construct($sql, $parent){

						$this->parent = $parent;
						$this->sql = $sql;
					}

					public function where(string $condition){

						$where = " WHERE ";
						if($this->sql->contains("WHERE"))
							$where = " AND ";

						$this->sql = $this->sql->concat($where)->concat($condition);

						return $this;
					}

					public function andWhere(string $condition){

						return $this->where($condition);
					}

					public function orWhere(string $condition){

						$where = " WHERE ";
						if($this->sql->contains("WHERE"))
							$where = " OR ";

						$this->sql = $this->sql->concat($where)->concat($condition);

						return $this;
					}

					public function yield(){

						return $this->sql->yield();
					}

					public function __call(string $name, array $args){

						return $this->parent->$name(...$args);
					}
				};
			}

			public function addSet(string $modify){

				return $this->set($modify);
			}

			public function yield(){

				return $this->sql->yield();
			}
		};
	}
}

if(helper_add("resultset")){

	function resultset(string $sql, array $filter = []){

		return new class($sql, $filter){

			private $rs;

			public function __construct(string $sql, array $filter){

				$this->rs = pdo()->execQuery($sql, $filter);
			}

			public function normalize(string $field){

				list($field, $type) = str($field)->split(":");

				$type = str($type);
				$rs = arr($this->rs)->each(function($k, $row)use($field, $type){

					if($type->equals("date"))
						$row[$field] = @when($row[$field])->when();

					return $row;
				});

				$this->rs = $rs->yield();

				return $this;
			}

			public function yield(){

				return $this->rs;
			}
		};
	}
}

if(helper_add("filter")){

	function filter(array $fields, string $filter, bool $like=true){

		$flike = fn($v)=>$v;
		if($like)
			$flike = fn($v)=>sprintf("%%%s%%", $v);

		$ffilter=fn($filter)=>$filter;
		if(!$like)
			$ffilter = fn($filter)=>sprintf("%%%s%%", $filter);

		if(!empty($filter))
			return arr($fields)->each(fn($k,$v)=>[$flike($v)=>$ffilter($filter)])->level();

		return [];
	}
}