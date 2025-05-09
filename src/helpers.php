<?php

use Strukt\Type\Str;
use Strukt\Contract\SqlInterface;
use Strukt\Db\Type\Red\Connection as RedDb;
use Strukt\Db\Type\Pop\Connection as PopDb;
use Strukt\Db\Type\Pop\SchemaManager;
use Pop\Db\Record as PopDbRecord;
use Pop\Db\Sql\Schema as PopDbSchema;
use Pop\Db\Adapter\Pdo as PopDbPdo;
use Pop\Db\Adapter\AbstractAdapter as PopDbAbstractAdapter;
use RedBeanPHP\OODBBean as Bean;
use RedBeanPHP\SimpleModelInterface;
use RedBeanPHP\R;
use Faker\Generator;


helper("pkg-db");

if(helper_add("sync")){

	/**
	* Sync bean and model for RedDb only
	* 
	*  sync($rs) // recordset
	*  sync($row)
	* 
	* @param array|\RedBeanPHP\OODBBean $bean
	* 
	* @return \RedBeanPHP\SimpleModelInterface|
	*         \RedBeanPHP\OODBBean|array
	*/
	function sync(array|Bean $bean):array|Bean|SimpleModelInterface{

		if(is_array($bean))
			return arr($bean)->each(fn($k, $v)=>sync($v))->yield();

		$model = $bean->box();
		$model->id = $bean->id;
		foreach(get_object_vars($model) as $property=>$value)
        	if($property != 'bean')
            	$model->$property = $bean->$property;

        return $model;
	};
}

if(helper_add("rb")){
	/**
	 * @param ?string $model_name
	 * @param ?int $id
	 * 
	 * @return \RedBeanPHP\R|
	 *         \RedBeanPHP\OODBBean|
	 *         \RedBeanPHP\SimpleModelInterface|array
	 */
	function rb(?string $model_name = null, ?int $id = null):R|Bean|SimpleModelInterface|array{

		if(!is_null($model_name) && is_null($id)) 
			return R::getRedBean()->dispense(str($model_name)->toLower()->yield())->box();

        if(!is_null($id) && !is_null($model_name)) 
        	return sync(R::load($model_name, $id));

		return new R;
	}
}

if(helper_add("schema")){

	/**
	 * @return Pop\Db\Sql\Schema
	 */
	function schema():PopDbSchema{

		try{

			if(reg("db.which") == "pop")
				return db()->createSchema();
		}
		catch(Exception $e){

			$db = config("db*");
			config("db.database", $db["name"]);

			return new PopDbSchema(new PopDbPdo(config("db*")));
		}
	}
}


if(helper_add("makeModel")){

	/**
	 * Make model for PopDb only
	 * 
	 * @param string $model
	 * 
	 * @return \Pop\Db\Record
	 */
	function makeModel(string $model):PopDbRecord{

		$class = ucfirst(str($model)->toCamel()->yield());

		if(!class_exists($class))
			eval("class $class extends \Pop\Db\Record{}");

		return new $class;
	}
}

if(helper_add("popdb")){

	/**
	 * @param string $model_name
	 * @param int $id
	 * 
	 * @return \Pop\Db\Record|
	 *         \Pop\Db\Adapter\AbstractAdapter
	 */
	function popdb(?string $model_name = null, ?int $id = null):PopDbRecord|PopDbAbstractAdapter{

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

		return PopDbRecord::getDb();
	}
}

if(helper_add("useDb")){

	/**
	 * useDb("red")
	 * useDb("rb")
	 * useDb("pop")
	 * 
	 * @param string $db
	 * 
	 * @return bool
	 */
	function useDb(string $db = "rb"):bool{

		$db = trim($db);
		reg("db.which", $db);
		if(!in_array($db, ["pop", "rb", "red"]))
			return false;

		if(in_array($db, ["rb", "red"]))
			new RedDb();

		if($db == "pop")
			new PopDb();

		return true;
	}
}

if(helper_add("switchDb")){

	/**
	 * Change db between pop-db|red-db
	 * 
	 * @return string|null
	 */
	function switchDb():string|null{

		if(reg()->exists("db.which")){

			$db = reg("db.which");
			reg()->remove("db.which");
		}

		if(!isset($db))
			$db = "rb";
		elseif(str($db)->equals("rb") || str($db)->equals("red"))
			$db = "pop";
		elseif(str($db)->equals("pop"))
			$db = "rb";

		if(useDb($db))
			return $db;

		return null;
	}
}

if(helper_add("db")){

	/**
	 * $user = db("user"); // empty model
	 * $user = db("user", 1) // find user by id:1
	 * 
	 * @param string $model_name
	 * @param string $id
	 * 
	 * @return \RedBeanPHP\R|
	 * 		   \RedBeanPHP\SimpleModelInterface|
	 *         \Pop\Db\Record|
	 *         \Pop\Db\Adapter\AbstractAdapter
	 */
	function db(?string $model_name = null, ?int $id = null):R|SimpleModelInterface|
															PopDbRecord|PopDbAbstractAdapter{

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

	/**
	 * usage: sm($table_name)
	 * 
	 * PopDb Schema Manager
	 * 
	 * @param string $table
	 * 
	 * @return Strukt\Db\Type\Pop\SchemaManager
	 */
	function sm(?string $table = null):SchemaManager{

		return new SchemaManager($table);
	}
}

if(helper_add("pdo")){

	counter(0, ".strukt-trx");

	/**
	 * $pdo = pdo()
	 * 
	 * // Transaction
	 * $pdo->begin()
	 * $pdo->commit()
	 * $pdo->rollback()
	 * $pdo->transact(callable $callback):array|null
	 * 
	 * // Querying
	 * $pdo->execPreQuery(string $sql, array $params):array
	 * $pdo->execQuery(string $sql, ?array $params):array|null
	 * $pdo->execPrep(string $sql, ?array $params):array|null
	 * $pdo->qMarks(array $params):string // create question marks string for list of params
	 * 
	 * // Pdo
	 * $pdo->getDb():\Pdo
	 * 
	 * @return object
	 */
	function pdo():object{

		$db = str(reg("db.which"));

		if($db->equals("pop"))
			$pdo = db()->getConnection();

		if($db->equals("rb") || $db->equals("red"))
			$pdo = db()->getDatabaseAdapter()->getDatabase()->getPdo();

		// $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
		// $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
		// $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		$is_sqlite = reg("config")->exists("db.file");
		if(negate($is_sqlite))
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return new class($pdo){

			private $pdo;

			/**
			 * @param \Pdo $pdo
			 */
			public function __construct(\Pdo $pdo){

				$this->pdo = $pdo;
			}

			/**
			 * @return bool
			 */
			public function begin(){
				
				return db()->beginTransaction(); //success
			}

			/**
			 * @return bool
			 */
			public function commit(){

				return db()->commit();
			}

			/**
			 * @return void
			 */
			public function rollback():void{
	
				db()->rollback();
			}

			/**
			 * @param string $sql
			 * @param array $params
			 * 
			 * @return array
			 */
			public function execPreQuery(string $sql, array $params):array{
				
				$options = [];
				if(arr($params)->isMap())
					$options = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY];
						
				$stmt = $this->pdo->prepare($sql, $options);
				$stmt->execute($params);

				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			}

			/**
			 * @param string $sql
			 * @param array $params
			 * 
			 * @return array|null
			 */
			public function execQuery(string $sql, array $params = null):array|null{

				if(is_null($params))
					return $this->pdo->query($sql, \PDO::FETCH_ASSOC)->fetchAll();
				

				return $this->execPreQuery($sql, $params);
			}

			/**
			 * @param array $params
			 * 
			 * @return string
			 */
			public function qMarks(array $params):string{

				$qMarks = str_repeat('?,', count($params) - 1) . '?';

				return $qMarks;
			}

			/**
			 * @param string $sql
			 * @param array $params
			 * 
			 * @return array|null
			 */
			public function execPrep(string $sql, array $params = null):array|null{

				$params =  array_values(arr($params)->level());

				return $this->execPreQuery($sql, $params);
			}

			/**
			 * @return \Pdo
			 */
			public function getDb():\Pdo{

				return $this->pdo;
			}

			/**
			* Copied from R::transaction for similar features
			* 
			* @param callable $callback
			* 
			* @return array|null
			*/
			public function transact(callable $callback):array|null{

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

	/**
	 * seed(string $table, array $data)
	 * 
	 * @param string $table
	 * @param array $data
	 * 
	 * @return \Pop\Db\Adapter\AbstractAdapter
	 */
	function seed(string $table, array $data = []):PopDbAbstractAdapter{

		$sql = db()->createSql();

        $sql->insert($table)->values($data);

        return db()->query($sql);
	}
}

if(helper_add("fake")){

	/**
	 * @param ?string $var
	 * 
	 * @return \Faker\Generator
	 */
	function fake(?string $var = null):FakerGenerator{

		$fake = event("provider.fake")->exec();

		if(!is_null($var))
			return $fake->$var;

		return $fake;
	}
}

if(helper_add("select")){

	/**
	 * SQL
	 *	└── select(string $fields)
	 *	    ├── addSelect(string $fields)
	 *	    ├── from(string $tables)
	 *	    │	 └── leftjoin(string $join)
	 *	    ├── groupBy(string $columns)
	 *	    ├── limit(int $limit)
	 *	    ├── orderBy(string $columns, string $order = "DESC")
	 *	    ├── page(int $page, int $perPage=10)
	 *	    ├── union(string $sql)
	 *	    ├── unionAll(string $sql)
	 *	    └── where(string $condition)
	 *	        ├── andWhere(string $condition)
	 *	        └── orWhere(string $condition)
	 * 
	 * @param string $fields
	 * 
	 * @return Strukt\Contract\SqlInterface|string
	 */
	function select(string $fields):SqlInterface|string{

		$ops = fn(string $fields)=>arr(str($fields)->split(","))->each(function($k, $field){
				
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

		/**
		 * @param string $sql
		 * @param callable $ops
		 */
		return new class($sql, $ops) implements SqlInterface{

			use Strukt\Traits\Query\Set;
			use Strukt\Traits\Query\Aggregate;

			private $sql;
			private $ops;
			public $prep;

			/**
			 * @param string $sql
			 * @param callable $ops
			 */
			public function __construct(Str $sql, callable $ops){

				$this->sql = $sql;
				$this->ops = $ops;
				$this->prep = false;
			}

			/**
			 * @param string $fields
			 */
			public function addSelect(string $fields):static{

				$ops = $this->ops;
				$this->sql = $this->sql->concat(" , ")->concat(implode(", ", $ops($fields)->yield()));

				return $this;
			}

			/**
			 * @param string $tables
			 * 
			 * @return object
			 */
			public function from(string $tables):SqlInterface{

				$this->sql = $this->sql->concat(str(" FROM ")->concat($tables));
				return new class($this, $this->sql) implements SqlInterface{

					private $sql;
					private $self;

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, Str &$sql){

						$this->sql = &$sql;
						$this->self = $self;
					}

					/**
					 * @param string $join
					 * 
					 * @return static 
					 */
					public function leftjoin(string $join):static{

						$this->sql = $this->sql->concat(" LEFT JOIN ")->concat($join);

						return $this;
					}

					/**
					 * Call parent SQL class
					 * 
					 * @param string $name
					 * @param array $args
					 * 
					 * @return \Strukt\Contract\SqlInterface
					 */
					public function __call(string $name, array $args):SqlInterface|bool{

						if(arr([
							"orWhere", 
							"andWhere", 
							"where"])->has($name) && !$this->sql->contains(" WHERE "))
								return $this->self->where(...$args);

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			/**
			 * @param string $condition
			 * 
			 * @return object
			 */
			public function where(string $condition):SqlInterface{

				$this->sql = $this->sql->concat(" WHERE ")->concat($condition);
				if(str($condition)->contains("?"))
					$this->prep = true;

				return new class($this, $this->sql) implements SqlInterface{

					use Strukt\Traits\Query\Predicate, Strukt\Traits\Query\Aggregate {
		        		Strukt\Traits\Query\Aggregate::isPrep insteadof Strukt\Traits\Query\Predicate;
		        		Strukt\Traits\Query\Aggregate::yield insteadof Strukt\Traits\Query\Predicate;
		    		}

					private $self;
					private $sql;
					public $prep;

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, Str &$sql){

						$this->prep = false;
						$this->sql = &$sql;
						$this->self = $self;
					}

					/**
					 * Call parent SQL class
					 * 
					 * @param string $name
					 * @param array $args
					 * 
					 * @return \Strukt\Contract\SqlInterface|bool
					 */
					public function __call(string $name, array $args):SqlInterface|bool{

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			public function __toString(){

				return $this->sql->yield();
			}
		};
	}	
}

if(helper_add("modify")){

	/**
	 * SQL
	 *	└──modify(string $table)
	 *		 ├── addSet(string $modify)
	 *		 ├── set(string $modify)
	 *		 │	  ├── andWhere(string $condition)
	 *		 │	  ├── orWhere(string $condition)
	 *		 │	  ├── where(string $condition)
	 *		 │	  └── yield():string
	 *		 └── yield():string
	 * 
	 * @param string $table
	 * 
	 * @return Strukt\Contract\SqlInterface|string
	 */
	function modify(string $table):SqlInterface|string{

		return new class($table) implements SqlInterface{

			private $sql;
			public $prep;

			/**
			 * @param string $table
			 */
			public function __construct(string $table){

				$this->sql = str("UPDATE ")->concat($table);
				$this->prep = false;
			}

			/**
			 * @param string $modify
			 * 
			 * @return object
			 */
			public function set(string $modify):SqlInterface{

				$separator = preg_match("/\sSET\s/", $this->sql->yield());

				$this->sql = $this->sql->concat($separator?", ":" SET ")->concat($modify);

				return new class($this, $this->sql) implements SqlInterface{

					use Strukt\Traits\Query\Predicate;

					private $sql;
					private $self;
					private $prep;

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, Str $sql){

						$this->self = $self;
						$this->sql = $sql;
					}

					/**
					 * @param string $name
					 * @param array $args
					 * 
					 * @return \Strukt\Contract\SqlInterface
					 */
					public function __call(string $name, array $args):SqlInterface|bool{

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			/**
			 * @param string $modify
			 * 
			 * @return object
			 */
			public function addSet(string $modify):SqlInterface|string{

				return $this->set($modify);
			}

			/**
			 * @return string
			 */
			public function yield(){

				return $this->sql->yield();
			}

			public function __toString(){

				return $this->sql->yield();
			}
		};
	}
}


if(helper_add("sql")){

	/**
	 * Use SQL aggregation functions
	 * 
	 * @param $sql
	 * 
	 * @return Strukt\Contract\SqlInterface|string
	 */
	function sql(string $sql = ""):SqlInterface|string{

		return new class($sql) implements SqlInterface{

			use Strukt\Traits\Query\Aggregate;

			private $sql;
			public $prep;

			public function __construct(string $sql){

				$this->prep = false;
				$this->sql = str($sql);
			}

			public function __toString(){

				return trim($this->sql->yield());
			}
		};
	}
}

if(helper_add("commit")){

	/**
	 * $user_id = commit("user", ["usename"=>"pitsolu","password"=>hashfn()("p@55w0rd")]);// add
	 * $success = commit("user", ["password"=>hashfn()("*p@55w0rd$")], $user_id); // update
	 * 
	 * @param string $model_name
	 * @param array $data
	 * @param ?int id
	 * 
	 * @return \RedBeanPHP\SimpleModelInterface|
	 * 			\Pop\Db\Record|bool|null
	 */
	function commit(string $model_name, array $data, ?int $id = null):PopDbRecord|
																		SimpleModelInterface|
																		bool|null{

		$mode = str(is_null($id)?"new":"update");

		try{

			$snake = str($model_name)->toSnake()->yield();

			$db = str(reg("db.which"));
			if($mode->equals("new")){

				$camel = ucfirst(str($model_name)->toCamel()->yield());
				if($db->equals("pop"))
					$model = core($camel, $data);

				if(negate($db->equals("pop")))
					$model = core($camel);
			}

			if($mode->equals("update"))
				$model = db($snake, $id);

			if(negate($mode->equals("new") && $db->equals("pop")))
				arr($data)->each(fn($key, $val)=>$model->$key = $val);
			
			$model->save();
			if($mode->equals("new"))
				return $model->id;

			if($mode->equals("update"))
				return true;
		}
		catch(\Exception $e){

			cmd("service.logger")->error($e);

			if($mode->equals("new"))
				return null;

			if($mode->equals("update"))
				return false;
		}
	}
}

if(helper_add("resultset")){

	/**
	 * // normalize example string "created_at:date" or "created_at:humanize"
	 * resultset(mixed $sql, array $filter)->normalize(string $field)->yield()
	 * resultset(mixed $sql, array $filter)->yield()
	 * resultset(mixed $sql)->yield()
	 * 
	 * @param mixed $sql
	 * @param array $filter
	 */
	function resultset(mixed $sql, array $filter = []):object{

		return new class($sql, $filter){

			private $rs;

			/**
			 * @param mixed $sql
			 * @param array $filter
			 */
			public function __construct(mixed $sql, array $filter){

				if($sql->isPrep())
					$this->rs = pdo()->execPrep((string)$sql, $filter);

				if(negate($sql->isPrep()))
					$this->rs = pdo()->execQuery((string)$sql, $filter);
			}

			/**
			 * Normalizing database fields
			 *  - date
			 * 
			 * @param string $field
			 * 
			 * @return static
			 */
			public function normalize(string $field):static{

				list($field, $type) = str($field)->split(":");
				$rs = arr($this->rs)->each(function($k, $row)use($field, $type){

					$row[$field] = cmd(sprintf("format.%s", $type), [$row[$field]]);

					return $row;
				});

				$this->rs = $rs->yield();

				return $this;
			}

			/**
			 * @return array
			 */
			public function yield(){

				return $this->rs;
			}
		};
	}
}

if(helper_add("filter")){

	/**
	 * $filter = filter(["name", "descr"], "abc"); // ['%name%'=>'abc', '%descr%'=>'abc']
	 * $filter = filter(["name"], "abc", false); // ['name'=>'%abc%']
	 * 
	 * @param array $fields
	 * @param string $filter
	 * @param bool $like
	 * 
	 * @return array
	 */
	function filter(array $fields, string $filter, bool $like=true):array{

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

if(helper_add("last")){

	/**
	 * last("user") // find last single user
	 * last("user", 2) // find last 2 users
	 * last("user", 1, 10)
	 * 
	 * @param string $bl
	 * @param int $count
	 * @param int $start_at
	 * 
	 * @return array|\RedBeanPHP\OODBBean
	 */
	function last(string $tbl, int $count = 1, int $start_at = 1):array|Bean{

		$rs = db()->find($tbl, sql()->orderBy("id", order:"DESC")->page($count, $start_at));

		return sync($rs);
	}
}

if(helper_add("page")){

	/**
	 * @param int $page
	 * @param int $perPage
	 * 
	 * @return array
	 */
	function page(int $page=1, int $perPage=10):array{

		$offset = ($page - 1) * $perPage;

		return ["offset"=>$offset, "limit"=>$perPage];
	}
}