<?php
namespace comhon\database;

use \PDO;
use \Exception;
use comhon\object\object\Config;
use comhon\object\object\Object;

class DatabaseController {
	
	const MYSQL = "mysql";
	const PGSQL = "pgsql";

	private static $sInstances = [];
	
	private static $sInsertReturns = [
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
	
	private static $sSupportedLastInsertId = [
		'mysql',
		//'cubrid',
		//'informix',
		//'sqlsrv',
	];
	
	private $mId;
	private $mDbHandle;
	private $mPreparedQueries = [];
	private $mPreparedQueriesParamCount = [];
	private $mIsSupportedLastInsertId;
	private $mInsertReturn;
	
	/**
	 * @param Object $pDbReference
	 * @throws Exception
	 */
	private function __construct($pDbReference) {
		if (!array_key_exists($pDbReference->getValue("DBMS"), self::$sInsertReturns)) {
			throw new \Exception("DBMS '{$pDbReference->getValue("DBMS")}' not supported yet");
		}
		$this->mId = $pDbReference->getIdValue('id');
		$lDataSourceName = sprintf('%s:dbname=%s;host=%s', $pDbReference->getValue("DBMS"), $pDbReference->getValue("name"), $pDbReference->getValue("host"));
		if ($pDbReference->hasValue("port")) {
			$lDataSourceName .= sprintf(';port=%s', $pDbReference->getValue("port"));
		}
		$this->mDbHandle = new PDO($lDataSourceName, $pDbReference->getValue("user"), $pDbReference->getValue("password"));
		$this->mIsSupportedLastInsertId = in_array($pDbReference->getValue("DBMS"), self::$sSupportedLastInsertId);
		$this->mInsertReturn = self::$sInsertReturns[$pDbReference->getValue("DBMS")];
		$this->_setDatabaseOptions();
	}
	
	/**
	 * @return boolean true if PDO pilote support function PDO::lastInsertId
	 */
	public function isSupportedLastInsertId() {
		return $this->mIsSupportedLastInsertId;
	}
	
	/**
	 * @return string|null keyword to use for returning value in insert query, null if returning is not supported
	 */
	public function getInsertReturn() {
		return $this->mInsertReturn;
	}
	
	private function  _setDatabaseOptions() {
		
		if (is_object(Config::getInstance()->getValue('database'))) {
			if (Config::getInstance()->getValue('database')->hasValue('charset')) {
				$lCharset  = Config::getInstance()->getValue('database')->getValue('charset');
			} else {
				trigger_error('Warning undefined charset database. By default charset is set to \'utf8\' ');
				$lCharset  = 'utf8';
			}
			if (Config::getInstance()->getValue('database')->hasValue('timezone')) {
				$lTimezone = Config::getInstance()->getValue('database')->getValue('timezone');
			} else {
				trigger_error('Warning undefined timezone database. By default charset is set to \'utf8\' ');
				$lTimezone = 'UTC';
			}
		} else {
			trigger_error('Warning undefined database options connections');
			$lCharset  = 'utf8';
			$lTimezone = 'UTC';
		}
		
		$lDate               = new \DateTime('now', new \DateTimeZone($lTimezone));
		$lTotalOffsetSeconds = $lDate->getOffset();
		$lOffsetOperator     = ($lTotalOffsetSeconds >= 0) ? '+' : '-';
		$lOffsetHours        = floor(abs($lTotalOffsetSeconds) / 3600);
		$lOffsetMinutes      = floor((abs($lTotalOffsetSeconds) % 3600) / 60);
		$lOffset             = $lOffsetOperator . $lOffsetHours . ':' . $lOffsetMinutes;
		
		$this->mDbHandle->exec("SET NAMES $lCharset;");
		$this->mDbHandle->exec("SET time_zone = '$lOffset';");

		// do not transform int to string (doesn't work)
		// $this->mDbHandle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		// $this->mDbHandle->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
	}

	/**
	 * @param integer $pId
	 * @return DatabaseController
	 */
	public static function getInstanceWithDataBaseId($pId) {
		$lReturn = null;
		if (array_key_exists($pId, self::$sInstances)) {
			$lReturn = self::$sInstances[$pId];
		}
		return $lReturn;
	}
	
	/**
	 * @param Object $pDbReference
	 * @return DatabaseController
	 */
	public static function getInstanceWithDataBaseObject(Object $pDbReference) {
		$lReturn = null;
		if (!$pDbReference->hasIdValue('id')) {
			throw new \Exception("malformed database reference");
		}
		$lId = $pDbReference->getIdValue("id");
		if (array_key_exists($lId, self::$sInstances)) {
			$lReturn = self::$sInstances[$lId];
		}else if ($pDbReference->hasValues(["DBMS", "host", "name", "user", "password"])) {
			$lReturn = new DatabaseController($pDbReference);
			self::$sInstances[$lId] = $lReturn;
		}else {
			throw new \Exception("malformed database reference");
		}
		return $lReturn;
	}
	
	/**
	 * prepare query
	 * @param string $pQuery
	 * @param array $pValues values to replace in the query
	 * @throws Exception
	 * @return PDOStatement
	 */
	private function _prepareQuery($pQuery, $pValues = []) {
		if (!array_key_exists($pQuery, $this->mPreparedQueries)) {
			$this->mPreparedQueries[$pQuery] = $this->mDbHandle->prepare($pQuery);
			$this->mPreparedQueriesParamCount[$pQuery] = count($pValues);
		}
		else if (count($pValues) !== $this->mPreparedQueriesParamCount[$pQuery]) {
			throw new Exception("prepareQuery query failed : query should have {$this->mPreparedQueriesParamCount[$lQueryId]} values, ".count($pValues).' given.');
		}
		$lPreparedQuery = $this->mPreparedQueries[$pQuery];
		for ($i = 0; $i < count($pValues); $i++) {
			if (is_null($pValues[$i])) {
				$lResult = $lPreparedQuery->bindValue($i+1, $pValues[$i], PDO::PARAM_NULL);
			}else {
				$lResult = $lPreparedQuery->bindValue($i+1, $pValues[$i]);
			}
			if ($lResult === false) {
				trigger_error("\nbindValue query failed :\n'".$lPreparedQuery->queryString."'\n");
				throw new Exception("\nbindValue query failed :\n'".$lPreparedQuery->queryString."'\n");
			}
		}
		return $lPreparedQuery;
	}
	
	/**
	 * execute the query that match with $pQueryId
	 * @param PDOStatement $pPDOStatement
	 * @throws Exception
	 */
	private function _doQuery($pPDOStatement) {
		if (!$pPDOStatement->execute()) {
			$lMessage = "\n\nexecution query failed :\n'"
					.$pPDOStatement->queryString
					."'\n\nPDO errorInfo : \n"
							.var_export($pPDOStatement->errorInfo(), true)
							."'\n";
			throw new Exception($lMessage);
		}
	}
	
	/**
	 * return the last insert id
	 */
	public function lastInsertId() {
		return $this->mDbHandle->lastInsertId();
	}
	
	/**
	 * prepare, execute and return result of query
	 * @param SelectQuery $pSelectQuery
	 * @param integer $pFetchStyle
	 * @throws Exception
	 * @return array
	 */
	public function executeSelectQuery(SelectQuery $pSelectQuery, $pFetchStyle = PDO::FETCH_ASSOC) {
		list($lQuery, $lValues) = $pSelectQuery->export();
		return $this->executeSimpleQuery($lQuery, $lValues)->fetchAll($pFetchStyle);
	}
	
	/**
	 * prepare, execute and return result of query
	 * @param string $pSelectQuery
	 * @param array $pValues
	 * @throws Exception
	 * @return PDOStatement
	 */
	public function executeSimpleQuery($pQuery, $pValues = []) {
		//var_dump("\n\n".vsprintf(str_replace('?', "%s", $pQuery), $pValues));
		$lPDOStatement = $this->_prepareQuery($pQuery, $pValues);
		$this->_doQuery($lPDOStatement);
		
		return $lPDOStatement;
	}
	
}