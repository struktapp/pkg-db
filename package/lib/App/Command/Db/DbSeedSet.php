<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Db\Type\Pop\Seeder;

/**
* db:seeds   Seed database tables with JSON fileset - folder (seed)
*
* Usage:
*
*      db:seeds <set> [<direction>]
*
* Arguments:
*
*      set         Folder with JSON set
*      direction   optional: Either up|down. Default:up
*/
class DbSeedSet extends \Strukt\Console\Command{

	/**
	* @todo update to pkg-db package
	*/
	public function execute(Input $in, Output $out){

		$direction = $in->get("direction");
		if(empty($direction))
			$direction = "up";

		if(!in_array($direction, ["up", "down"]))
			raise("Allowed directions up|down!");

		$path = str(phar("db/data/")->adapt());
		$filter = $in->get("set");
		$path = \Strukt\Fs::ds($path->concat(sprintf("%s/", $filter))->yield());

		$seeder = new Seeder($path, $filter);
		$seeder->useHashFn(hashfn());
		$seeder->$direction();

		$out->add("Database successfully seeded!");
	}
}