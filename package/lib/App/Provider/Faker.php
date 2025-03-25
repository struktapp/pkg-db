<?php

namespace App\Provider;

use Strukt\Ref;
use Strukt\Contract\ProviderInterface;

/**
* @Name(faker)
*/
class Faker implements ProviderInterface{

	private $faker;

	public function __construct(){
		
		$this->faker = new \Faker\Generator();
		$this->faker->addProvider(new \Faker\Provider\en_US\Person($this->faker));
		$this->faker->addProvider(new \Faker\Provider\en_US\Address($this->faker));
		$this->faker->addProvider(new \Faker\Provider\en_US\PhoneNumber($this->faker));
		$this->faker->addProvider(new \Faker\Provider\en_US\Company($this->faker));
		$this->faker->addProvider(new \Faker\Provider\Lorem($this->faker));
		$this->faker->addProvider(new \Faker\Provider\Internet($this->faker));
	}

	/**
	 * @return void
	 */
	public function register():void{

		$self = $this;
		event("provider.fake", function() use($self){

			return $self->faker;
		});
	}
}
