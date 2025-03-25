<?php

namespace Strukt\Db\Type\Pop;

use RedBeanPHP\R;

/**
 * PopDb Schema Manager
 * 
 * @author Moderator <pitsolu@gmail.com>
 */
class SchemaManager{

	private $db;
	private $pdo;
	private $builder = null;
	private $table;
	private $columns = [];
	private $schema;
	private $whichDb;

	/**
	 * @param string $table
	 */
	public function __construct(string $table = null){

		if(reg()->exists("db.which"))
			$this->whichDb = reg("db.which");

		$this->db = db();
		$this->schema = schema();

		if(!is_null($table)){

			$this->builder = $this->schema->createIfNotExists($table);
			if($this->tableExists($table))
				$this->builder = $this->schema->alter($table);

			$info = $this->builder->getInfo();
			$this->columns = arr($info["columns"])->each(fn($k,$v)=>$v["type"])->yield();

			if($this->isDb("pop"))
				if(!$this->db->hasTable($table))
					raise(sprintf("table:%s does not exists!", $table));
		}

		$this->table = $table;
	}

	/**
	 * @param string $db
	 * 
	 * @return bool
	 */
	public function isDb(string $db):bool{

		return $this->whichDb == trim($db);
	}

	/**
	 * @return string
	 */
	public function getTable():string{

		return $this->table;
	}

	/**
	 * @param string $table
	 * 
	 * @return bool
	 */
	public function tableExists(string $table):bool{

		if($this->isDb("pop"))
			return $this->db->hasTable($table);

		return false;
	}

	/**
	 * @param string $table
	 * 
	 * @return static
	 */
	public function createTable(string $table):static{

		$this->builder = $this->schema->create($table)->int("id", 16)->increment()->primary("id");

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $type
	 * @param array $options
	 * 
	 * @return static
	 */
	public function addColumn(string $column, string $type = "varchar", array $options = []){

		$size = $options["size"]??null;
		$precision = $options["precision"]??null;
		$attr = $options["attr"]??[];

		if(!is_array($attr))
			raise("Argument [attr] must be an array!");

		$this->builder->addColumn($column, $type, $size, $precision, $attr);
		if($options["default"]??false)
			$this->builder->defaultIs($options["default"]);

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $type
	 * @param array $options
	 * 
	 * @return static
	 */
	public function changeColumn(string $column, string $type = "varchar", array $options = []){

		$size = $options["size"]??null;
		$precision = $options["precision"]??null;
		$attr = $options["attr"]??[];

		if(!is_array($attr))
			raise("Argument [attr] must be an array!");

		$this->builder->modifyColumn($column, $column, $type, $size, $precision);
		if($options["default"]??false)
			$this->builder->defaultIs($options["default"]);

		return $this;
	}

	/**
	 * @param string $column
	 * 
	 * @return bool
	 */
	public function hasColumn(string $column):bool{

		if(!empty($this->columns))
			return array_key_exists($column, $this->columns);

		return false;
	}

	/**
	 * @return array
	 */
	public function tables():array{

		if($this->isDb("pop"))
			return $this->db->getTables();

		return [];
	}

	/**
	 * @return array
	 */ 
	public function fields(){

		return arr($this->columns)->each(fn($k, $v)=>preg_replace("/\(.*\)|\(|\)|\d+/", "", $v))->yield();
	}

	/**
	 * @return array
	 */
	public function columns(){

		return $this->columns;
	}

	/**
	 * @return string
	 */
	public function getSql(){

		return (string)$this->builder;
	}

	/**
	 * @return int
	 */
	public function exec():int{

		if(!is_null($this->builder))
			if($this->isDb("pop"))
				return $this->db->query($this->getSql());

		return R::exec($this->getSql());
	}
}