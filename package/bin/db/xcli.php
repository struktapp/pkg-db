<?php

use Strukt\Framework\Shell;
use Strukt\Framework\Configuration;

$loader = require "bootstrap.php";

env("json_validation_err", true);
env("res_send_headers", false);
config("app.type","App:Cli");
useDb(str(config("package.auth.default"))->replace("-db","")->yield());

$kernel = new App\Kernel(new Configuration());
$app = $kernel->getApp();
if(!is_null($app)){

	$app->init();
	$kernel->getRouter()->init();
}

alias("sh", "shell:exec");
alias("routes", "route:ls");
alias("packages", "package:ls");
alias("system", "sys:ls");
alias("run", "app:exec");
alias("cache", "cache:reset");
alias("sql", "db:sql");
alias("seed", "db:seeds");
$argv = arr($_SERVER["argv"])->each(function($_, $name){

	$alias = alias($name);
	if(str($alias??"")->equals("db:sql") || str($name)->equals("db:sql")){

		reg("env")->remove("cli_app_name");
		env("cli_app_name","");
	}

	if(notnull($alias))
		return $alias;

	return $name;
});

$sh = new Shell();
exit($sh->run($argv->yield()));
