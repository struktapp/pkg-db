<?php

namespace Strukt\Db\Type\Red\Contract;

use RedBeanPHP\R;

abstract class Entity extends \RedBeanPHP\SimpleModel{

    use \Strukt\Db\Type\Red\Traits\Rb;
}