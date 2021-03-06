<?php

namespace DB;

use Nette;



class Generator
{
	use Nette\SmartObject;

	/**
	 * @var Nette\Database\Context
	 */
	private $db;

	/**
	 * @var Nette\PhpGenerator\PhpFile
	 */
	private $file;

	/**
	 * @var string
	 */
	private $wordSeparator = '_';

	/**
	 * @var string
	 */
	private $namespaceSeparator = '__';



	/**
	 * @param Nette\Database\Context $db
	 */
	public function __construct(Nette\Database\Context $db)
	{
		$this->db = $db;
	}

	/**
	 * @return void
	 */
	public function generate()
	{
		$tables = $this->getTables();

		$class = $this->createClass();
		$this->addConstructMethod($class);
		$this->addQueryMethod($class);
		$this->addTransactionMethods($class);
		$this->addGetMethods($class);

		foreach ($tables as $table) {
			$method = $class->addMethod($this->toMethodName($table));
			$method->setVisibility('public');
			$method->addComment('@return Nette\Database\Table\Selection');
			$method->addBody("return \$this->db->table('{$table}');");
		}

		echo "<pre>";
		echo htmlspecialchars($this->file);
		echo "</pre>";
		die;
	}



	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 * @return void
	 */
	private function addConstructMethod(Nette\PhpGenerator\ClassType $class)
	{
		// public function __construct(Nette\Database\Context $db) { ... }
		$method = $class->addMethod('__construct');
		$method->addComment('@param Nette\\Database\\Context $db');
		$method->addBody('$this->db = $db;');

		$param = $method->addParameter('db');
		$param->setTypeHint('Nette\\Database\\Context');
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 * @return void
	 */
	private function addQueryMethod(Nette\PhpGenerator\ClassType $class)
	{
		// public function query($sql, ...$params) { ... }
		$method = $class->addMethod('query');
		$method->addComment('@param string');
		$method->addComment('@return Nette\Database\ResultSet');
		$method->addBody('$this->db->query($sql, ...$params);');
		$method->addParameter('sql, ...$params');
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 * @return void
	 */
	private function addTransactionMethods(Nette\PhpGenerator\ClassType $class)
	{
		// public function beginTransaction() { ... }
		$beginTransaction = $class->addMethod('beginTransaction');
		$beginTransaction->addComment('@return void');
		$beginTransaction->addBody('$this->db->beginTransaction();');

		// public function commit() { ... }
		$commit = $class->addMethod('commit');
		$commit->addComment('@return void');
		$commit->addBody('$this->db->commit();');

		// public function rollBack() { ... }
		$rollBack = $class->addMethod('rollBack');
		$rollBack->addComment('@return void');
		$rollBack->addBody('$this->db->rollBack();');
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 * @return void
	 */
	private function addGetMethods(Nette\PhpGenerator\ClassType $class)
	{
		// public function getInsertId() { ... }
		$getInsertId = $class->addMethod('getInsertId');
		$getInsertId->addComment('@param string|NULL $name');
		$getInsertId->addComment('@return string');
		$getInsertId->addBody('return $this->db->getInsertId($name);');
		$param = $getInsertId->addParameter('name');
		$param->setOptional(TRUE);

		// public function getConnection() { ... }
		$getConnection = $class->addMethod('getConnection');
		$getConnection->addComment('@return Nette\\Database\\Connection');
		$getConnection->addBody('return $this->db->getConnection();');

		// public function getStructure() { ... }
		$getStructure = $class->addMethod('getStructure');
		$getStructure->addComment('@return Nette\\Database\\IStructure');
		$getStructure->addBody('return $this->db->getStructure();');

		// public function getStructure() { ... }
		$getConventions = $class->addMethod('getConventions');
		$getConventions->addComment('@return Nette\\Database\\IConventions');
		$getConventions->addBody('return $this->db->getConventions();');
	}

	/**
	 * @return Nette\PhpGenerator\ClassType
	 */
	private function createClass()
	{
		// class DB\Generator { ... }
		$this->file = new Nette\PhpGenerator\PhpFile();
		$this->file->addComment('@generated ' . date('Y-m-d H:i:s'));

		$namespace = $this->file->addNamespace('DB');
		$namespace->addUse('Nette');

		$class = $namespace->addClass('Context');
		$class->addTrait('Nette\\SmartObject');

		$property = $class->addProperty('db');
		$property->setComment('@var Nette\\Database\\Context');
		$property->setVisibility('private');

		return $class;
	}

	/**
	 * @return array
	 */
	private function getTables()
	{
		$tables = [];

		foreach ($this->db->getStructure()->getTables() as $table) {
			$tables[] = $table['name'];
		}

		return $tables;
	}

	/**
	 * @param string $tableName
	 * @return string
	 */
	private function toMethodName($tableName)
	{
		$nameParts = [];
		$tableName = trim(strtolower($tableName));
		$namespaces = explode($this->namespaceSeparator, $tableName);

		foreach ($namespaces as $namespace) {
			$words = explode($this->wordSeparator, $namespace);

			if (count($words) === 1) {
				$nameParts[] = $namespace;
			} else {
				$part = NULL;

				foreach ($words as $index => $word) {
					if ($index === 0) {
						$part = $word;
					} else {
						$part .= ucfirst($word);
					}
				}

				$nameParts[] = $part;
			}
		}

		$name = join('_', $nameParts);
		return $name;
	}

	/**
	 * @param string $tableName
	 * @return string
	 */
	private function toClassName($tableName)
	{
		$nameParts = ['DB'];
		$tableName = trim(strtolower($tableName));
		$namespaces = explode($this->namespaceSeparator, $tableName);

		foreach ($namespaces as $namespace) {
			$words = explode($this->wordSeparator, $namespace);

			if (count($words) === 1) {
				$nameParts[] = ucfirst($namespace);
			} else {
				$part = NULL;

				foreach ($words as $index => $word) {
					$part .= ucfirst($word);
				}

				$nameParts[] = $part;
			}
		}

		$name = join('\\', $nameParts);
		return $name;
	}

}