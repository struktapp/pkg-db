<?php

namespace Strukt\Db\Type\Pop;

use Pop\Db\Db;
use Pop\Db\Record;
use Pop\Db\Adapter\Sqlite as PopDbSqliteAdapter;
use Pop\Db\Adapter\AbstractAdapter as PopDbAbstractAdapter;
use Strukt\Fs;

/**
* @author Moderator <pitsolu@gmail.com>
*/
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

			$path = env("root_dir");
			if(env("phar"))
				$path = dirname(env("root_dir"));

			$path = str(sprintf("sqlite:%s/%s", $path, $file))->replace("phar://","")->yield();

			$this->adapter = Db::sqliteConnect([

				"database"=>$file
			]);
		}

		Record::setDb($this->adapter);
	}

	/**
	 * @return Pop\Db\Adapter\Sqlite|Pop\Db\Adapter\AbstractAdapter
	 */
	public function getAdapter():PopDbSqliteAdapter|PopDbAbstractAdapter{

		return $this->adapter;
	}

	/**
	 * @return mixed
	 */
	public function getPdo():mixed{

		return $this->adapter->getConnection();
	}
}