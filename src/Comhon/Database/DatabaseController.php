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
use Comhon\Object\UniqueObject;
use Comhon\Exception\Database\NotSupportedDBMSException;
use Comhon\Exception\Database\QueryExecutionFailureException;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\UnexpectedModelException;
use Comhon\Exception\Database\IncompleteSqlDbInfosException;
use Comhon\Exception\Database\UnexpectedCountValuesQueryException;
use Comhon\Exception\Database\QueryBindingValueException;

class DatabaseController {
	
	/** @var string */
	const MYSQL = 'mysql';
	
	/** @var string */
	const PGSQL = 'pgsql';

	/** @var DatabaseController[] */
	private static $instances = [];
	
	/** @var string[] */
	private static $insertReturns = [
		//'cubrid' => null,
		//'dblib' => 'OUTPUT',
		//'firebird' => 'RETURNING',
		//'ibm'
		//'informix' => null,
		//'sqlsrv' => null,
		self::MYSQL => null,
		//'oci' => 'RETURNING',
		//'odbc'
		self::PGSQL => 'RETURNING',
		//'sqlite' => 'OUTPUT',
		//'4D'
	];
	
	/**
	 * escape characters to apply on columns when build query
	 * 
	 * @var array
	 */
	private static $escapeChars = [
			self::MYSQL => '`',
			self::PGSQL => '"'
	];
	
	/** @var string */
	private static $supportedLastInsertId = [
		self::MYSQL,
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
	 * escape character to apply on columns when build query
	 *
	 * @var string
	 */
	private $escapeChar;
	
	/**
	 * @param \Comhon\Object\UniqueObject $dbReference
	 * @throws \Exception
	 */
	private function __construct(UniqueObject $dbReference) {
		if (!array_key_exists($dbReference->getValue('DBMS'), self::$insertReturns)) {
			throw new NotSupportedDBMSException($dbReference->getValue('DBMS'));
		}
		$this->id = $dbReference->getValue('id');
		$dataSourceName = sprintf('%s:dbname=%s;host=%s', $dbReference->getValue('DBMS'), $dbReference->getValue('name'), $dbReference->getValue('host'));
		if ($dbReference->hasValue('port')) {
			$dataSourceName .= sprintf(';port=%s', $dbReference->getValue('port'));
		}
		$this->dbHandle = new \PDO($dataSourceName, $dbReference->getValue('user'), $dbReference->getValue('password'));
		$this->isSupportedLastInsertId = in_array($dbReference->getValue('DBMS'), self::$supportedLastInsertId);
		$this->insertReturn = self::$insertReturns[$dbReference->getValue('DBMS')];
		$this->escapeChar = self::$escapeChars[$dbReference->getValue('DBMS')];
		$this->_setDatabaseOptions($dbReference);
	}
	
	/**
	 * 
	 * @return \PDO
	 */
	public function getPDO() {
		return $this->dbHandle;
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
	
	/**
	 * get escape character to apply on columns when build query
	 *
	 * @return string|null null if returning is not supported
	 */
	public function getEscapeChar() {
		return $this->escapeChar;
	}
	
	/**
	 * set database options
	 * 
	 * @param UniqueObject $dbReference
	 * @throws \Exception
	 */
	private function _setDatabaseOptions(UniqueObject $dbReference) {
		switch ($dbReference->getValue('DBMS')) {
			case self::MYSQL:
				$this->_setDatabaseOptionsMySql();
				break;
			case self::PGSQL:
				$this->_setDatabaseOptionsPgSql();
				break;
			//case 'cubrid':
			//case 'dblib':
			//case 'firebird':
			//case 'ibm':
			//case 'informix':
			//case 'sqlsrv':
			//case 'oci':
			//case 'odbc':
			//case 'sqlite':
			//case '4D':
			default:
				throw new NotSupportedDBMSException($dbReference->getValue('DBMS'));
		}
	}
	
	/**
	 * get offset from time zone defined in config
	 * 
	 * @return string
	 */
	private function _getTimeZoneOffset() {
		$date               = new \DateTime('now', new \DateTimeZone(Config::getInstance()->getDataBaseTimezone()));
		$totalOffsetSeconds = $date->getOffset();
		$offsetOperator     = ($totalOffsetSeconds >= 0) ? '+' : '-';
		$offsetHours        = floor(abs($totalOffsetSeconds) / 3600);
		$offsetMinutes      = floor((abs($totalOffsetSeconds) % 3600) / 60);
		return $offsetOperator . $offsetHours . ':' . $offsetMinutes;
	}
	
	/**
	 * set database options
	 * 
	 * @throws \Exception
	 */
	private function _setDatabaseOptionsMySql() {
		if ($this->dbHandle->exec('SET NAMES \''.Config::getInstance()->getDataBaseCharset().'\';') === false) {
			throw new QueryExecutionFailureException('SET NAMES \''.Config::getInstance()->getDataBaseCharset().'\';');
		}
		if ($this->dbHandle->exec("SET time_zone = '{$this->_getTimeZoneOffset()}';") === false) {
			throw new QueryExecutionFailureException("SET time_zone = '{$this->_getTimeZoneOffset()}';");
		}
		
		// do not transform int to string (I fail to make it works)
		// $this->dbHandle->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		// $this->dbHandle->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
	}
	
	/**
	 * set database options
	 * 
	 * @throws \Exception
	 */
	private function _setDatabaseOptionsPgSql() {
		if ($this->dbHandle->exec('SET NAMES \''.Config::getInstance()->getDataBaseCharset().'\';') === false) {
			throw new QueryExecutionFailureException('SET NAMES \''.Config::getInstance()->getDataBaseCharset().'\';');
		}
		if ($this->dbHandle->exec("SET time zone  '{$this->_getTimeZoneOffset()}';") === false) {
			throw new QueryExecutionFailureException("SET time zone  '{$this->_getTimeZoneOffset()}';");
		}
	}

	/**
	 * get existing instance of DatabaseController according specified id
	 * 
	 * @param string $id
	 * @return DatabaseController|null
	 */
	public static function getInstanceWithDataBaseId($id) {
		return array_key_exists($id, self::$instances)
			? self::$instances[$id]
			: self::getInstanceWithDataBaseObject(ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->loadObject($id));
	}
	
	/**
	 * get existing or new  instance of DatabaseController according specified database reference
	 * 
	 * @param \Comhon\Object\UniqueObject $dbReference
	 * @return DatabaseController
	 */
	public static function getInstanceWithDataBaseObject(UniqueObject $dbReference) {
		$databaseController = null;
		if ($dbReference->getModel() !== ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')) {
			throw new UnexpectedModelException(ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase'), $dbReference->getModel());
		}
		if (!$dbReference->hasValue('id')) {
			throw new IncompleteSqlDbInfosException();
		}
		$id = $dbReference->getValue('id');
		if (array_key_exists($id, self::$instances)) {
			$databaseController = self::$instances[$id];
		}else if ($dbReference->hasValues(['DBMS', 'host', 'name', 'user', 'password'])) {
			$databaseController = new DatabaseController($dbReference);
			self::$instances[$id] = $databaseController;
		}else {
			throw new IncompleteSqlDbInfosException();
		}
		return $databaseController;
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
			throw new UnexpectedCountValuesQueryException($query, $this->preparedQueriesParamCount[$query], count($values));
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
				throw new QueryBindingValueException($preparedQuery);
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
			throw new QueryExecutionFailureException($PDOStatement);
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
	public function select(SelectQuery $selectQuery, $fetchStyle = \PDO::FETCH_ASSOC) {
		list($query, $values) = $selectQuery->export();
		// var_dump("\n\n".vsprintf(str_replace('?', "%s", $query), $values));
		return $this->execute($query, $values)->fetchAll($fetchStyle);
	}
	
	/**
	 * prepare, execute and return results count of query. ignore offset and limit settings
	 * @param SelectQuery $selectQuery
	 * @param integer $fetchStyle
	 * @throws \Exception
	 * @return array all selected rows
	 */
	public function count(SelectQuery $selectQuery, $fetchStyle = \PDO::FETCH_ASSOC) {
		list($query, $values) = $selectQuery->exportCount();
		//var_dump("\n\n".vsprintf(str_replace('?', "%s", $query), $values));
		$row = $this->execute($query, $values)->fetch($fetchStyle);
		return (integer) $row[SelectQuery::COL_COUNT];
	}
	
	/**
	 * prepare, execute and return result of query
	 * @param string $selectQuery
	 * @param array $values values that need to be binded
	 * @throws \Exception
	 * @return \PDOStatement
	 */
	public function execute($query, $values = []) {
		// var_dump("\n\n".vsprintf(str_replace('?', "%s", $query), $values));
		$PDOStatement = $this->_prepareQuery($query, $values);
		$this->_doQuery($PDOStatement);
		
		return $PDOStatement;
	}
	
}