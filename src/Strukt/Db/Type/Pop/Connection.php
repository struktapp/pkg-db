<?php

namespace Strukt\Db\Type\Pop;

use Pop\Db\Db;
use Pop\Db\Record;

class Connection{

	private $adapter = null;

	public function __construct(){

		$this->adapter = Db::connect('pdo', [

		    'database' => config('db.name'),
		    'username' => config('db.username'),
		    'password' => config('db.password'),
		    'host'     => config('db.host'),
		    'type'     => config('db.type')
		]);

		Record::setDb($this->adapter);
	}

	public function getAdapter(){

		return $this->adapter;
	}

	public function getPdo(){

		return $this->adapter->getConnection();
	}
}