<?php

use Strukt\Env;
use Strukt\Framework\Configuration;

require "bootstrap.php";

env("json_validation_err", true);
env("res_send_headers", true);
config("app.type","App:Idx");

useDb(str(config("package.auth.default"))->replace("-db","")->yield());

$kernel = new App\Kernel(new Configuration());
exit($kernel->run());
