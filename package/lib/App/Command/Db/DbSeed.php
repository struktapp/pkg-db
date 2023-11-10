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
*      db:seed [<direction>] [<table>]
*
* Arguments:
*
*      direction   Either up|down
*      table       Table name
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

		$seeder = new Seeder(\Strukt\Fs::ds(sprintf("db/data/%s.json", $table)));
		$seeder->$direction();

		$out->add("Database successfully seeded!");
	}
}