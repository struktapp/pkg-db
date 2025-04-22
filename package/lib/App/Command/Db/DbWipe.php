<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* db:wipe    Truncate database
*
* Usage:
*
*      db:wipe [<table>]
*
* Arguments:
*
*      table   name of model or table
*/
class DbWipe extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$tables = [];
		$table = $in->get("table");
		if(empty($table))
			$tables = sm()->tables();

		if(empty($tables))
			$tables[] = $table;

		try{

			arr($tables)->each(function($k, $table){

				$dbfile = config("db.file");
				if(notnull($dbfile))//SQLite
					pdo()->execQuery(sprintf("DELETE FROM %s", $table));

				if(is_null($dbfile))//Other DBs
					db()->query(schema()->truncate($table)->render());

				print_r(sprintf("success:true|table:%s\n", $table));
			});
		}
		catch(\Exception $e){

			$out->add(sprintf("success:false|message:%s", $e->getMessage()));
		}
	}
}