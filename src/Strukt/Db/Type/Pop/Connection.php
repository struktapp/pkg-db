<?php

namespace Strukt\Db\Type\Pop;

use Pop\Db\Db;
use Pop\Db\Record;
use Strukt\Fs;

class Connection{

	private $adapter = null;

	public function __construct(){

		$database = config('db.name');
	    $username = config('db.username');
	    $password = config('db.password');
	    $host     = config('db.host');
	    $type     = config('db.type');
	    $file     = config("db.file");

		if(!is_null($database))
			$this->adapter = Db::connect('pdo', [

			    'database' => $database,
			    'username' => $username,
			    'password' => $password,
			    'host'     => $host,
			    'type'     => $type
			]);

		if(!is_null($file)){

			if(phar()->active())
				$file = str(Fs::ds(sprintf("%s/%s", dirname(phar()->adapt()), $file)))->replace("phar://", "")->yield();

			$this->adapter = Db::sqliteConnect([

				"database"=>$file
			]);
		}

		Record::setDb($this->adapter);
	}

	public function getAdapter(){

		return $this->adapter;
	}

	public function getPdo(){

		return $this->adapter->getConnection();
	}
}