<?php

namespace Strukt\Db\Type\Pop;

use RedBeanPHP\R;

class SchemaManager{

	private $db;
	private $pdo;
	private $builder = null;
	private $table;
	private $columns = [];
	private $schema;
	private $whichDb;

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

	public function isDb(string $db){

		return $this->whichDb == trim($db);
	}

	public function getTable(){

		return $this->table;
	}

	public function tableExists(string $table){

		if($this->isDb("pop"))
			return $this->db->hasTable($table);

		return false;
	}

	public function createTable(string $table){

		$this->builder = $this->schema->create($table)->int("id", 16)->increment()->primary("id");

		return $this;
	}

	public function addColumn(string $column, string $type = "varchar", array $options = []){

		$size = $options["size"]??null;
		$precision = $options["precision"]??null;
		$attr = $options["attr"]??[];

		if(!is_array($attr))
			raise("Argument [attr] must be an array!");

		$this->builder->addColumn($column, $type, $size, $precision, $attr);

		return $this;
	}

	public function changeColumn(string $column, string $type = "varchar", array $options = []){

		$size = $options["size"]??null;
		$precision = $options["precision"]??null;
		$attr = $options["attr"]??[];

		if(!is_array($attr))
			raise("Argument [attr] must be an array!");

		$this->builder->modifyColumn($column, $column, $type, $size, $precision);

		return $this;
	}

	public function hasColumn(string $column){

		if(!empty($this->columns))
			return array_key_exists($column, $this->columns);

		return false;
	}

	public function tables(){

		if($this->isDb("pop"))
			return $this->db->getTables();

		return [];
	}

	public function fields(){

		return arr($this->columns)->each(fn($k, $v)=>preg_replace("/\(.*\)|\(|\)|\d+/", "", $v))->yield();
	}

	public function columns(){

		return $this->columns;
	}

	public function getSql(){

		return (string)$this->builder;
	}

	public function exec(){

		if(!is_null($this->builder))
			if($this->isDb("pop"))
				return $this->db->query($this->getSql());

		return R::exec($this->getSql());
	}
}