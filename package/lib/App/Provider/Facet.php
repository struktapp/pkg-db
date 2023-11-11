<?php

namespace App\Provider;

use Strukt\Ref;
use Strukt\Contract\ProviderInterface;

/**
* @Name(facet)
*/
class Facet implements ProviderInterface{

	use \Strukt\Traits\FacetHelper;

	public function __construct(){

		//
	}

	public function register(){

		$self = $this;
		event("provider.core", function(string $alias_ns, array $args = null) use($self){

			if(!reg()->exists("nr"))
				raise("[nr|Name Registry] does not exists!");

			if(!$this->isQualifiedAlias($alias_ns)){

				$model = db(str($alias_ns)->toSnake()->toLower()->yield());
				if(!is_null($args))
					$model->__construct(...$args);
				
				return $model;
			}

			if(!is_null($args))
				return Ref::create($self->getNamespace($alias_ns))
							->makeArgs($args)
							->getInstance();

			return Ref::create($self->getNamespace($alias_ns))
					->noMake()
					->getInstance();
		});
	}
}

