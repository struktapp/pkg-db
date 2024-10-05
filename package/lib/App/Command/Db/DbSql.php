<?php

namespace App\Command\Db;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* db:sql    Truncate database
*
* Usage:
*
*      db:sql <sql>
*
* Arguments:
*
*      sql   Sql Statement
*/
class DbSql extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$sql = $in->get("sql");

		try{

			$rs = pdo()->execQuery($sql);

			$sql = str($sql)->toLower();
			if($sql->startsWith("select")  || 
				$sql->startsWith("show")   ||
				$sql->startsWith("pragma") ||
				$sql->startsWith("analyze"))
					$out->add(json($rs)->pp());
		}
		catch(\Exception $e){

			$out->add(sprintf("success:false|message:%s", $e->getMessage()));
		}
	}
}