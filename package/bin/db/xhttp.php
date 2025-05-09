<?php

use Strukt\Http\Request;
use Strukt\Framework\Configuration;
use App\Middleware\XSession;
use App\XHttp;

require "bootstrap.php";

env("json_validation_err", true);
env("res_send_headers", false);
config("app.type","App:Cli");
useDb("pop");

XSession::withOptions(array(

	"username"=>"admin",
	"allow"=>[

		"strukt:auth",
		// "user_all"
	]
));

$xhttp = new XHttp(new Configuration, Request::createFromGlobals());
exit($xhttp->run($_SERVER["argv"]));