<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Db\Type\Pop\Seeder;

/**
* db:seed     Seed database tables
*
* Usage:
*
*      db:seed [<direction>] [<table>] [--filter <filter>]
*
* Arguments:
*
*      direction   Either up|down
*      table       Table name
*
* Options:
*
*      --filter -f  Folder name
*/
class DbSeed extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$direction = $in->get("direction");
		if(empty($direction))
			$direction = "up";

		if(!in_array($direction, ["up", "down"]))
			raise("Allowed directions up|down!");

		$table = $in->get("table");
		if(empty($table))
			$table = "*";

		$path = str("db/data/");
		$filter = $in->get("filter");
		if(!empty($filter))
			$path = \Strukt\Fs::ds($path->concat(sprintf("%s/%s.json", $filter, $table))->yield());

		$seeder = new Seeder($path);
		$seeder->$direction();

		$out->add("Database successfully seeded!");
	}
}