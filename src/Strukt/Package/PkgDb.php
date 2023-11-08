<?php

namespace Strukt\Package;

class PkgDb implements \Strukt\Framework\Contract\Package{

	private $manifest;

	public function __construct(){

		$this->manifest = array(
			"cmd_name"=>"Db",
			"package"=>"pkg-db",
			"files"=>array(
				"lib/App/Command/Db/DbMakeModels.php",
		        "lib/App/Command/Db/ModelMake.php",
		        "lib/App/Command/Db/DbMakeFromModels.php",
		        "cfg/db.ini"
			)
		);
	}

	public function getSettings($type){

		$settings = array(
			"App:Cli"=>array(
				"providers"=>array(),
				"middlewares"=>array(),
				"commands"=>array(
					\App\Command\Db\DbMakeModels::class,
			        \App\Command\Db\ModelMake::class,
			        \App\Command\Db\DbMakeFromModels::class
				)
			),
			"App:Idx"=>array(
				"providers"=>array(),
				"middlewares"=>array()
			)
		);

		return $settings[$type];
	}

	public function getName(){

		return $this->manifest["package"];
	}

	public function getCmdName(){

		return $this->manifest["cmd_name"];
	}

	public function getFiles(){

		return $this->manifest["files"];
	}

	public function getModules(){

		return null;
	}

	/**
	* Use php's class_exists function to identify a class that indicated your package is installed
	*/
	public function isPublished(){

		//This will return false because SomeClass::class shouldn't exists
		return class_exists(\App\Command\Db\DbMakeFromModels::class);
	}

	public function getRequirements(){
		
		return null;
	}
}