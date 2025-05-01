<?php

namespace Strukt\Traits\Query;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait Aggregate{

	public function limit(int $limit):static{

		$this->sql = $this->sql->concat(sprintf(" LIMIT %d", $limit));

		return $this;
	}

	/**
	 * @param int $page
	 * @param int $perPage
	 * 
	 * @return static
	 */
	public function page(int $page, int $perPage=10):static{

		list($offset, $limit) = array_values(page($page, $perPage));

		$this->sql = $this->sql->concat(sprintf(" LIMIT %d, %d", $offset, $limit));

		return $this;
	}

	/**
	 * @param string $columns
	 * @param string $order
	 * 
	 * @return static
	 */
	public function orderBy(string $columns, string $order = "DESC"):static{

		$this->sql = $this->sql->concat(sprintf(" ORDER BY %s %s", $columns, $order));

		return $this;
	}

	/**
	 * @param string $columns
	 * 
	 * @return static
	 */
	public function groupBy(string $columns):static{

		$this->sql = $this->sql->concat(sprintf(" GROUP BY %s", $columns));

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