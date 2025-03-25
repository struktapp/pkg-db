<?php

namespace Strukt\Db\Type\Red;

use RedBeanPHP\R;
use Psr\Log\LoggerInterface;

 /** 
 * @author Moderator <pitsolu@gmail.com>
 */
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

				$path = env("root_dir");
				if(env("phar"))
					$path = dirname(env("root_dir"));

				$path = str(sprintf("sqlite:%s/%s", $path, $file))->replace("phar://","")->yield();

				R::setup($path);
			}

			if(is_null($file))
				R::setup($dsn, $username, $password);
		}
	}

	/**
	 * @return \Pdo
	 */
	public function getPdo():\Pdo{

		return R::getDatabaseAdapter()->getDatabase()->getPdo();
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 * 
	 * @return void
	 */
	public function useLogger(LoggerInterface $logger):void{

		R::getDatabaseAdapter()->getDatabase()->setLogger(new class($logger) implements \RedBeanPHP\Logger{

		  	private $logger;

		  	/**
			 * @param \Psr\Log\LoggerInterface $logger
			 */
		  	public function __construct(LoggerInterface $logger) {

		  	 	$this->logger = $logger->withName('sql'); 
		  	}
		  	
		  	/**
		  	 * @return void
		  	 */
		  	public function log():void{ 

		  		$this->logger->debug(...func_get_args()); 
		  	}
		});
	}
}