<?php

namespace Strukt\Db\Type\Red;

use RedBeanPHP\R;

class Connection{

	public function __construct(){

		$app_name = config("app.name");
		if(!is_null($app_name) && !defined("REDBEAN_MODEL_PREFIX"))
			define('REDBEAN_MODEL_PREFIX', sprintf('\\%s\\', $app_name));

		$dsn = sprintf('%s:host=%s;dbname=%s', config("db.type"), config("db.host"), config("db.name"));
		$username = config("db.username");
		$password = config("db.password");
		$file = config("db.file");

		if(!R::testConnection()){

			if(!is_null($file)){

				$path = sprintf("sqlite:%s/%s", env("root_dir"), $file);
				R::setup(sprintf("sqlite:%s", $path));
			}

			if(is_null($file))
				R::setup($dsn, $username, $password);
		}
	}

	public function getPdo(){

		return R::getDatabaseAdapter()->getDatabase()->getPdo();
	}
}