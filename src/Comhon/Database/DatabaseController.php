<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

use Comhon\Object\Config\Config;
use Comhon\Object\ObjectUnique;

class DatabaseController {
	
	/** @var string */
	const MYSQL = 'mysql';
	
	/** @var string */
	const PGSQL = 'pgsql';

	/** @var string[] */
	private static $instances = [];
	
	/** @var string[] */
	private static $insertReturns = [
		//'cubrid' => null,
		//'dblib' => 'OUTPUT',
		//'firebird' => 'RETURNING',
		//'ibm'
		//'informix' => null,
		//'sqlsrv' => null,
		'mysql' => null,
		//'oci' => 'RETURNING',
		//'odbc'
		//'pgsql' => 'RETURNING',
		//'sqlite' => 'OUTPUT',
		//'4D'
	];
	
	/** @var string */
	private static $supportedLastInsertId = [
		'mysql',
		//'cubrid',
		//'informix',
		//'sqlsrv',
	];
	
	/**
	 * @var string
	 */
	private $id;
	
	/**
	 * @var \PDO 
	 */
	private $dbHandle;
	
	/**
	 * @var \PDOStatement[] all prepared queries already built (avoid to rebuild each time same query)
	 */
	private $preparedQueries = [];
	
	/**
	 * @var integer permit to known how many values need a prepared query
	 */
	private $preparedQueriesParamCount = [];
	
	/**
	 * @var boolean permit to know if \PDO pilote support function \PDO::lastInsertId
	 */
	private $isSupportedLastInsertId;
	
	/**
	 * @var string|null keyword used to return value in insert query (null if returning is not supported)
	 */
	private $insertReturn;
	
	/**
	 * @param \Comhon\Object\ObjectUnique $dbReference
	 * @throws \Exception
	 */
	private function __construct(ObjectUnique $dbReference) {
		if (!array_key_exists($dbReference->getValue('DBMS'), self::$insertReturns)) {
			throw new \Exception("DBMS '{$dbReference->getValue('DBMS')}' not supported yet");
		}
		$this->id = $dbReference->getValue('id');
		$dataSourceName = sprintf('%s:dbname=%s;host=%s', $dbReference->getValue('DBMS'), $dbReference->getValue('name'), $dbReference->getValue('host'));
		if ($dbReference->hasValue('port')) {
			$dataSourceName .= sprintf(';port=%s', $dbReference->getValue('port'));
		}
		$this->dbHandle = new \PDO($dataSourceName, $dbReference->getValue('user'), $dbReference->getValue('password'));
		$this->isSupportedLastInsertId = in_array($dbReference->getValue('DBMS'), self::$supportedLastInsertId);
		$this->insertReturn = self::$insertReturns[$dbReference->getValue('DBMS')];
		$this->_setDatabaseOptions();
	}
	
	/**
	 * permit to know if \PDO pilote support function \PDO::lastInsertId
	 * 
	 * @return boolean
	 */
	public function isSupportedLastInsertId() {
		return $this->isSupportedLastInsertId;
	}
	
	/**
	 * get keyword used to return value in insert query
	 * 
	 * @return string|null null if returning is not supported
	 */
	public function getInsertReturn() {
		return $this->insertReturn;
	}
	
	private function  _setDatabaseOptions() {
		$date               = new \DateTime('now', new \DateTimeZone(Config::getInstance()->getDataBaseTimezone()));
		$totalOffsetSeconds = $date->getOffset();
		$offsetOperator     = ($totalOffsetSeconds >= 0) ? '+' : '-';
		$offsetHours        = floor(abs($totalOffsetSeconds) / 3600);
		$offsetMinutes      = floor((abs($totalOffsetSeconds) % 3600) / 60);
		$offset             = $offsetOperator . $offsetHours . ':' . $offsetMinutes;
		
		$this->dbHandle->exec('SET NAMES '.Config::getInstance()->getDataBaseCharset().';');
		$this->dbHandle->exec("SET time_zone = '$offset';");

		// do not transform int to string (I fail to make it works)
		// $this->dbHandle->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		// $this->dbHandle->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
	}

	/**
	 * get existing instance of DatabaseController according specified id
	 * 
	 * @param string $id
	 * @return DatabaseController|null
	 */
	public static function getInstanceWithDataBaseId($id) {
		$return = null;
		if (array_key_exists($id, self::$instances)) {
			$return = self::$instances[$id];
		}
		return $return;
	}
	
	/**
	 * get existing or new  instance of DatabaseController according specified database reference
	 * 
	 * @param \Comhon\Object\ObjectUnique $dbReference
	 * @return DatabaseController
	 */
	public static function getInstanceWithDataBaseObject(ObjectUnique $dbReference) {
		$return = null;
		if (!$dbReference->hasValue('id')) {
			throw new \Exception('malformed database reference');
		}
		$id = $dbReference->getValue('id');
		if (array_key_exists($id, self::$instances)) {
			$return = self::$instances[$id];
		}else if ($dbReference->hasValues(['id', 'DBMS', 'host', 'name', 'user', 'password'])) {
			$return = new DatabaseController($dbReference);
			self::$instances[$id] = $return;
		}else {
			throw new \Exception('malformed database reference');
		}
		return $return;
	}
	
	/**
	 * prepare query
	 * 
	 * @param string $query
	 * @param array $values values to replace in the query
	 * @throws \Exception
	 * @return \PDOStatement
	 */
	private function _prepareQuery($query, $values = []) {
		if (!array_key_exists($query, $this->preparedQueries)) {
			$this->preparedQueries[$query] = $this->dbHandle->prepare($query);
			$this->preparedQueriesParamCount[$query] = count($values);
		}
		else if (count($values) !== $this->preparedQueriesParamCount[$query]) {
			throw new \Exception("prepareQuery query failed : query should have {$this->preparedQueriesParamCount[$query]} values, ".count($values).' given.');
		}
		$preparedQuery = $this->preparedQueries[$query];
		for ($i = 0; $i < count($values); $i++) {
			if (is_null($values[$i])) {
				$result = $preparedQuery->bindValue($i+1, $values[$i], \PDO::PARAM_NULL);
			} else if (is_bool($values[$i])) {
				$result = $preparedQuery->bindValue($i+1, $values[$i], \PDO::PARAM_BOOL);
			} else {
				$result = $preparedQuery->bindValue($i+1, $values[$i]);
			}
			if ($result === false) {
				trigger_error("\nbindValue query failed :\n'".$preparedQuery->queryString."'\n");
				throw new \Exception("\nbindValue query failed :\n'".$preparedQuery->queryString."'\n");
			}
		}
		return $preparedQuery;
	}
	
	/**
	 * execute the query
	 * 
	 * @param \PDOStatement $PDOStatement
	 * @throws \Exception
	 */
	private function _doQuery($PDOStatement) {
		if (!$PDOStatement->execute()) {
			$message = "\n\nexecution query failed :\n'"
					.$PDOStatement->queryString
					."'\n\nPDO errorInfo : \n"
							.var_export($PDOStatement->errorInfo(), true)
							."'\n";
			throw new \Exception($message);
		}
	}
	
	/**
	 * get the last insert id
	 * 
	 * @return string
	 */
	public function lastInsertId() {
		return $this->dbHandle->lastInsertId();
	}
	
	/**
	 * prepare, execute and return result of query
	 * @param SelectQuery $selectQuery
	 * @param integer $fetchStyle
	 * @throws \Exception
	 * @return array all selected rows
	 */
	public function executeSelectQuery(SelectQuery $selectQuery, $fetchStyle = \PDO::FETCH_ASSOC) {
		list($query, $values) = $selectQuery->export();
		//var_dump("\n\n".vsprintf(str_replace('?', "%s", $query), $values));
		return $this->executeSimpleQuery($query, $values)->fetchAll($fetchStyle);
	}
	
	/**
	 * prepare, execute and return result of query
	 * @param string $selectQuery
	 * @param array $values values that need to be binded
	 * @throws \Exception
	 * @return \PDOStatement
	 */
	public function executeSimpleQuery($query, $values = []) {
		//var_dump("\n\n".vsprintf(str_replace('?', "%s", $query), $values));
		$PDOStatement = $this->_prepareQuery($query, $values);
		$this->_doQuery($PDOStatement);
		
		return $PDOStatement;
	}
	
}