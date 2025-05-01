<?php

namespace Strukt\Traits\Query;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait Set{
	
	/**
	 * @param string $sql
	 * 
	 * @return static
	 */
	public function union(string $sql):static{

		$this->sql = $this->sql->concat(" UNION ")->concat($sql);

		return $this;
	}

	/**
	 * @param string $sql
	 * 
	 * @return static
	 */
	public function unionAll(string $sql):static{

		$this->sql = $this->sql->concat(" UNION ALL ")->concat($sql);

		return $this;
	}
}