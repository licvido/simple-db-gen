<?php

/**
 * @generated 2017-01-08 02:45:55
 */

namespace DB;

use Nette;

class Context
{
	use Nette\SmartObject;

	/** @var Nette\Database\Context */
	private $db;


	/**
	 * @param Nette\Database\Context $db
	 */
	public function __construct(Nette\Database\Context $db)
	{
		$this->db = $db;
	}


	/**
	 * @param string
	 * @return Nette\Database\ResultSet
	 */
	public function query($sql, ...$params)
	{
		$this->db->query($sql, ...$params);
	}


	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		$this->db->beginTransaction();
	}


	/**
	 * @return void
	 */
	public function commit()
	{
		$this->db->commit();
	}


	/**
	 * @return void
	 */
	public function rollBack()
	{
		$this->db->rollBack();
	}

}
