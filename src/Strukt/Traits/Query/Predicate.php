<?php

namespace Strukt\Traits\Query;

use Strukt\Contract\SqlInterface;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait Predicate{

	/**
	 * @param string $condition
	 * 
	 * @return static
	 */
	public function where(string $condition):static{
				
		$this->sql = $this->sql->concat(" WHERE ")->concat($condition);
		if(str($condition)->contains("?"))
			$this->prep = true;

		return $this;
	}

	/**
	 * @param string $condition
	 * 
	 * @return static
	 */
	public function andWhere(string $condition):static{

		$this->sql = $this->sql->concat(" AND ")->concat($condition);
		if(str($condition)->contains("?"))
			$this->self->prep = true;

		return $this;
	}

	/**
	 * @param string $condition
	 * 
	 * @return static
	 */
	public function orWhere(string $condition):static{

		$this->sql = $this->sql->concat(" OR ")->concat($condition);
		if(str($condition)->contains("?"))
			$this->self->prep = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isPrep():bool{

		return $this->prep;
	}

	/**
	 * @return string
	 */
	public function yield():string{

		return $this->sql->yield();
	}
}