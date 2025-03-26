<?php

use Strukt\Db\Type\Pop\SchemaManager;
use Strukt\Db\Type\Pop\Connection as PopDb;
use Strukt\Db\Type\Red\Connection as RedDb;
use Pop\Db\Sql\Schema as PopDbSchema;
use Pop\Db\Adapter\Pdo as PopDbPdo;
use Pop\Db\Record as PopDbRecord;
use Pop\Db\Adapter\AbstractAdapter as PopDbAbstractAdapter;
use RedBeanPHP\OODBBean as Bean;
use RedBeanPHP\R;
use Faker\Generator;

helper("pkg-db");

if(helper_add("rb")){

	/**
	* Sync bean and model
	* 
	* @param array|\RedBeanPHP\OODBBean $bean
	*/
	function sync(array|Bean $bean):array|Bean{

		if(is_array($bean))
			return arr($bean)->each(fn($k, $v)=>sync($v))->yield();

		$model = $bean->box();
		$model->id = $bean->id;
		foreach(get_object_vars($model) as $property=>$value)
        	if($property != 'bean')
            	$model->$property = $bean->$property;

        return $model;
	};

	/**
	 * @param string $model_name
	 * @param int $id
	 * 
	 * @return \RedBeanPHP\R
	 */
	function rb(string $model_name = null, int $id = null):R{

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

if(helper_add("popdb")){

	/**
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

	/**
	 * @param string $model_name
	 * @param int $id
	 * 
	 * @return \Pop\Db\Record|\Pop\Db\Adapter\AbstractAdapter
	 */
	function popdb(string $model_name = null, int $id = null):PopDbRecord|PopDbAbstractAdapter{

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
	 * @param string $db
	 * 
	 * @return bool
	 */
	function useDb(string $db = "rb"):bool{

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
}

if(helper_add("switchDb")){

	/**
	 * @return string|null
	 */
	function switchDb():string|null{

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

	/**
	 * @param string $model_name
	 * @param string $id
	 * 
	 * @return \RedBeanPHP\R|\Pop\Db\Record|\Pop\Db\Adapter\AbstractAdapter
	 */
	function db(string $model_name = null, int $id = null):R|PopDbRecord|PopDbAbstractAdapter{

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
	 * PopDb Schema Manager
	 * 
	 * @param string $table
	 * 
	 * @return Strukt\Db\Type\Pop\SchemaManager
	 */
	function sm(string $table = null):SchemaManager{

		return new SchemaManager($table);
	}
}

if(helper_add("pdo")){

	counter(0, ".strukt-trx");

	/**
	 * @return object
	 */
	function pdo():object{

		$db = str(reg("db.which"));

		if($db->equals("pop"))
			$pdo = db()->getConnection();

		if($db->equals("rb"))
			$pdo = db()->getDatabaseAdapter()->getDatabase()->getPdo();

		// $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
		// $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
		// $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		$is_sqlite = reg("config")->exists("db.file");
		if(negate($is_sqlite))
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return new class($pdo, $is_sqlite){

			private $pdo;

			/**
			 * @param \Pdo $pdo
			 * @param bool $is_sqlite
			 */
			public function __construct(\Pdo $pdo, bool $is_sqlite){

				$this->pdo = $pdo;
				$this->is_sqlite = $is_sqlite;
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

				if(is_null($params)){

					if(negate($this->is_sqlite))
						return $this->pdo->query($sql, \PDO::FETCH_ASSOC)->fetchAll();

					if($this->is_sqlite){

						$rs = [];
						$res = $this->pdo->query($sql);//Sqlite3Result
						while($row = $res->fetchArray(SQLITE3_ASSOC))
							$rs[] = $row;

						return $rs;
					}
				}

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
	 * @param string $table
	 * @param array $data
	 * 
	 * @return array
	 */
	function seed(string $table, array $data = []):array{

		$sql = db()->createSql();

        $sql->insert($table)->values($data);

        return db()->query($sql);
	}
}

if(helper_add("faker")){

	/**
	 * @param string $var
	 * 
	 * @return \Faker\Generator
	 */
	function fake(string $var = null):FakerGenerator{

		$fake = event("provider.fake")->exec();

		if(!is_null($var))
			return $fake->$var;

		return $fake;
	}
}

if(helper_add("select")){

	/**
	 * @param string $fields
	 */
	function select(string $fields):object{


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

		/**
		 * @param string $sql
		 * @param callable $ops
		 */
		return new class($sql, $ops){

			private $sql;
			private $ops;
			public $prep;

			/**
			 * @param string $sql
			 * @param callable $ops
			 */
			public function __construct(string $sql, callable $ops){

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
			public function from(string $tables):object{

				// $self = $this;
				$this->sql = $this->sql->concat(str(" FROM ")->concat($tables));

				// return new class($self, $this->sql){
				return new class($this, $this->sql){

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, string &$sql){

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
					 * @param string $name
					 * @param array $args
					 * 
					 * @return object
					 */
					public function __call(string $name, array $args):object{

						if(arr(["orWhere", "andWhere", "where"])->has($name) && 
							!$this->sql->contains(" WHERE "))
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
			public function where(string $condition):object{

				// $self = $this;
				$this->sql = $this->sql->concat(" WHERE ")->concat($condition);
				if(str($condition)->contains("?"))
					$this->prep = true;

				// return new class($self, $this->sql){
				return new class($this, $this->sql){

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, string &$sql){

						$this->sql = &$sql;
						$this->self = $self;
					}

					/**
					 * @param string $condition
					 * 
					 * @return static
					 */
					public function andWhere(string $condition):static{

						$this->sql = $this->sql->concat(" AND ")->concat($condition);
						if(str($condition)->contains("?"))
							$this->self->prep = true;

						return $this;
					}

					/**
					 * @param string $condition
					 */
					public function orWhere(string $condition):static{

						$this->sql = $this->sql->concat(" OR ")->concat($condition);
						if(str($condition)->contains("?"))
							$this->self->prep = true;

						return $this;
					}

					/**
					 * @param string $name
					 * @param array $args
					 */
					public function __call(string $name, array $args){

						return $this->self->$name(...$args);
					}

					public function __toString(){

						return $this->sql->yield();
					}
				};
			}

			/**
			 * @param int $page
			 * @param int $perPage
			 * 
			 * @return static
			 */
			public function page(int $page, int $perPage=10):static{

				$offset = ($page - 1) * $perPage;

				$this->sql = $this->sql->concat(sprintf(" LIMIT %d, %d", $offset, $perPage));

				return $this;
			}

			/**
			 * @param string $columns
			 * 
			 * @return static
			 */
			public function groupBy(string $columns):static{

				$this->sql = $this->sql->concat(sprintf(" GROUP BY %s", $columns));

				return $this;
			}

			/**
			 * @param string $columns
			 * @param string $order
			 * 
			 * @return static
			 */
			public function orderBy(string $columns, string $order = "DESC"):static{

				$this->sql = $this->sql->concat(sprintf(" ORDER BY %s %s", $columns, $order));

				return $this;
			}

			/**
			 * @param string $sql
			 * 
			 * @return static
			 */
			public function union(string $sql):static{

				$this->sql = $this->sql->concat(" UNION ")->concat($sql);

				return $this;
			}

			/**
			 * @param string $sql
			 * 
			 * @return static
			 */
			public function unionAll(string $sql):static{

				$this->sql = $this->sql->concat(" UNION ALL ")->concat($sql);

				return $this;
			}

			/**
			 * @return bool
			 */
			public function isPrep():bool{

				return $this->prep;
			}

			public function __toString(){

				return $this->sql->yield();
			}
		};
	}	
}

if(helper_add("modify")){

	/**
	 * @param string $table
	 */
	function modify(string $table){

		return new class($table){

			private $sql;

			/**
			 * @param string $table
			 */
			public function __construct(string $table){

				$this->sql = str("UPDATE ")->concat($table);
			}

			/**
			 * @param string $modify
			 * 
			 * @return object
			 */
			public function set(string $modify):object{

				$separator = preg_match("/\sSET\s/", $this->sql->yield());

				$this->sql = $this->sql->concat($separator?", ":" SET ")->concat($modify);

				return new class($this, $this->sql){

					private $sql;
					private $parent;

					/**
					 * @param object $self
					 * @param string $sql
					 */
					public function __construct(object $self, string $sql){

						$this->parent = $parent;
						$this->sql = $sql;
					}

					/**
					 * @param string $condition
					 * 
					 * @return static 
					 */
					public function where(string $condition):static{

						$where = " WHERE ";
						if($this->sql->contains("WHERE"))
							$where = " AND ";

						$this->sql = $this->sql->concat($where)->concat($condition);

						return $this;
					}

					/**
					 * @param string $condition
					 * 
					 * @return static
					 */
					public function andWhere(string $condition):static{

						return $this->where($condition);
					}

					/**
					 * @param string $condition
					 * 
					 * @return static
					 */
					public function orWhere(string $condition){

						$where = " WHERE ";
						if($this->sql->contains("WHERE"))
							$where = " OR ";

						$this->sql = $this->sql->concat($where)->concat($condition);

						return $this;
					}

					/**
					 * @return string
					 */
					public function yield():string{

						return $this->sql->yield();
					}

					/**
					 * @param string $name
					 * @param array $args
					 */
					public function __call(string $name, array $args){

						return $this->parent->$name(...$args);
					}
				};
			}

			/**
			 * @param string $modify
			 * 
			 * @return object
			 */
			public function addSet(string $modify):object{

				return $this->set($modify);
			}

			/**
			 * @return string
			 */
			public function yield(){

				return $this->sql->yield();
			}
		};
	}
}

if(helper_add("resultset")){

	/**
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

				$type = str($type);
				$rs = arr($this->rs)->each(function($k, $row)use($field, $type){

					if($type->equals("date"))
						$row[$field] = @when($row[$field])->when();

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
	 * @param string $bl
	 * @param int $count
	 * @param int $start_at
	 * 
	 * @return array|\RedBeanPHP\OODBBean
	 */
	function last(string $tbl, int $count = 10, int $start_at = 1):array|Bean{

		$page = page($start_at, $count);

		extract($page);

		$rs = db()->find($tbl, sprintf("order by id desc limit %d, %d", $offset, $limit));

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