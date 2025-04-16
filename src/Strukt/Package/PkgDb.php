<?php

namespace Strukt\Package;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class PkgDb implements \Strukt\Framework\Contract\Package{

	private $manifest;

	public function __construct(){

		$this->manifest = array(
			"cmd_name"=>"Db",
			"package"=>"pkg-db",
			"files"=>array(
				"bin/seed",
				"bin/seed-acl",
				"xhttp.dbx",
				"cfg/db.ini",
				"xcli.dbx",
				"app/src/App/User.sgf",
				"db/data/permission.json",
				"db/data/user.json",
				"db/data/role.json",
				"db/data/admin/user.json",
				"db/data/admin/role_permission.json",
				"lib/App/Command/Db/DbMakeFromModels.php",
				"lib/App/Command/Db/DbWipe.php",
				"lib/App/Command/Db/ModelMake.php",
				"lib/App/Command/Db/DbSeedFile.php",
				"lib/App/Command/Db/DbSeedSet.php",
				"lib/App/Command/Db/DbSql.php",
				"lib/App/Command/Db/DbMakeModels.php",
				"lib/App/Provider/Facet.php",
				"lib/App/Provider/Faker.php"
			)
		);
	}


	/**
	 * @return void
	 */
	public function preInstall():void{
		
		//
	}

	/**
	 * @param string $type
	 * 
	 * @return array
	 */
	public function getSettings(string $type):array{

		$settings = array(
			"App:Cli"=>array(
				"providers"=>array(
					\App\Provider\Facet::class,
					\App\Provider\Faker::class
				),
				"middlewares"=>array(),
				"commands"=>array(
					\App\Command\Db\DbMakeModels::class,
			        \App\Command\Db\ModelMake::class,
			        \App\Command\Db\DbMakeFromModels::class,
			        \App\Command\Db\DbSeedFile::class,
			        \App\Command\Db\DbSeedSet::class,
			        \App\Command\Db\DbWipe::class,
			        \App\Command\Db\DbSql::class
				)
			),
			"App:Idx"=>array(
				"providers"=>array(
					\App\Provider\Facet::class
				),
				"middlewares"=>array()
			)
		);

		return $settings[$type];
	}

	/**
	 * @return string
	 */
	public function getName():string{

		return $this->manifest["package"];
	}

	/**
	 * @return string
	 */
	public function getCmdName():string{

		return $this->manifest["cmd_name"];
	}

	/**
	 * @return array|null
	 */
	public function getFiles():array|null{

		return $this->manifest["files"];
	}

	/**
	 * @return array|null
	 */
	public function getModules():array|null{

		return null;
	}

	/**
	* Use php's class_exists function to identify a class that indicated your package is installed
	* 
	* @return bool
	*/
	public function isPublished():bool{

		//This will return false because SomeClass::class shouldn't exists
		return class_exists(\App\Command\Db\DbMakeFromModels::class);
	}

	/**
	 * @return array|null
	 */
	public function getRequirements():array|null{
		
		return null;
	}

	/**
	 * @return void
	 */
	public function postInstall():void{

		chmod('./xcli.dbx', 0755);
		chmod('./xhttp.dbx', 0755);
	}
}