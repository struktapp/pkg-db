<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Db\Type\Pop\Seeder;

/**
* db:feed     Seed with JSON file
*
* Usage:
*
*      db:feed <path>
*
* Arguments:
*
*      path   Path to file
*/
class DbSeedFile extends \Strukt\Console\Command{

	/**
	* @todo update to pkg-db package
	*/
	public function execute(Input $in, Output $out){

		$path = $in->get("path");

		// dd($path);
		$seeder = new Seeder($path);
		$seeder->useHashFn(fn($password)=>bcry($password)->encode());
		$seeder->up();

		$out->add("Database successfully seeded!");
	}
}