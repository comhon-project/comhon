<?php
namespace objectManagerLib\database;

use \PDO;
use \Exception;

class DatabaseController {
	
	const MYSQL = "mysql";
	const PGSQL = "pgsql";

	private static $sInstances = array();
	
	private $mId;
	private $mDbHandle;
	private $mPreparedQueries;
	
	/**
	 * @param Object $pDbReference
	 * @throws Exception
	 */
	private function __construct($pDbReference) {
		$this->mId = $pDbReference->getValue("id");
		$lDataSourceName = sprintf('%s:dbname=%s;host=%s', $pDbReference->getValue("DBMS"), $pDbReference->getValue("name"), $pDbReference->getValue("host"));
		if ($pDbReference->hasValue("port")) {
			$lDataSourceName .= sprintf(';port=%s', $pDbReference->getValue("port"));
		}
		$this->mDbHandle = new PDO($lDataSourceName, $pDbReference->getValue("user"), $pDbReference->getValue("password"), array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		$this->mPreparedQueries = array();
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
	public static function getInstanceWithDataBaseObject($pDbReference) {
		$lReturn = null;
		$lId = $pDbReference->getvalue("id");
		if (array_key_exists($lId, self::$sInstances)) {
			$lReturn = self::$sInstances[$lId];
		}else if ($pDbReference->hasValues(array("id", "DBMS", "host", "name", "user", "password"))) {
			$lReturn = new DatabaseController($pDbReference);
			self::$sInstances[$lId] = $lReturn;
		}else {
			throw new \Exception("malformed database reference");
		}
		return $lReturn;
	}
	
	/**
	 * prepare query
	 * @param unknown $pQuery
	 * @param unknown $pValues values to replace in the query
	 * @throws Exception
	 * @return string md5 of query
	 */
	public function prepareQuery($pQuery, $pValues = array()) {
		$lQueryId = md5($pQuery);
		if (!array_key_exists($lQueryId, $this->mPreparedQueries)) {
			$this->mPreparedQueries[$lQueryId] = $this->mDbHandle->prepare($pQuery);
		}
		
		for ($i = 0; $i < count($pValues); $i++) {
			if (is_null($pValues[$i])) {
				$lResult = $this->mPreparedQueries[$lQueryId]->bindValue($i+1, $pValues[$i], PDO::PARAM_NULL);
			}else {
				$lResult = $this->mPreparedQueries[$lQueryId]->bindValue($i+1, $pValues[$i]);
			}
			if ($lResult === false) {
				trigger_error("\nbindValue query failed :\n'".$this->mPreparedQueries[$lQueryId]->queryString."'\n");
				throw new Exception("\nbindValue query failed :\n'".$this->mPreparedQueries[$lQueryId]->queryString."'\n");
			}
		}
		return $lQueryId;
	}
	
	/**
	 * execute the query $pQuery
	 * Warning! prepareQuery() must be called before execute this function.
	 * @param string $pQuery
	 * @return boolean true if success, false otherwise
	 */
	public function doQuery($pQuery) {
		return $this->doQueryWithId(md5($pQuery));
	}
	
	/**
	 * execute the query that match with $pQueryId
	 * Warning! prepareQuery() must be called before execute this function.
	 * @param string $pQueryId md5 of query string
	 * @throws Exception
	 * @return boolean true if success, false otherwise
	 */
	public function doQueryWithId($pQueryId) {
		$lResult= false;
		if (array_key_exists($pQueryId, $this->mPreparedQueries)) {
			$lResult = $this->mPreparedQueries[$pQueryId]->execute();
			if ($lResult === false) {
				$lMessage = "\n\nexecution query failed :\n'"
						.$this->mPreparedQueries[$pQueryId]->queryString
						."'\n\nPDO errorInfo : \n"
								.var_export($this->mPreparedQueries[$pQueryId]->errorInfo(), true)
								."'\n";
				throw new Exception($lMessage);
			}
		}
		return $lResult;
	}
	
	/**
	 * fetch row retrieve after executing doQuery() 
	 * Warning! prepareQuery() must be called before execute this function.
	 * @param string $pQuery
	 * @return array
	 */
	public function fetchAll($pQuery) {
		return $this->fetchAllWithId(md5($pQuery));
	}
	
	/**
	 * fetch row retrieve after executing doQuery()
	 * Warning! prepareQuery() must be called before execute this function.
	 * @param string $pQueryId md5 of query
	 * @return array
	 */
	public function fetchAllWithId($pQueryId) {
		$lResult = null;
	
		if (array_key_exists($pQueryId, $this->mPreparedQueries)) {
			$lResult = $this->mPreparedQueries[$pQueryId]->fetchAll();
		}
		return $lResult;
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
	 * @throws Exception
	 * @return array
	 */
	public function executeQuery($pSelectQuery) {
		list($lQuery, $lValues) = $pSelectQuery->export();
		try {
			//var_dump("\n\n".vsprintf(str_replace('?', "%s", $lQuery), $lValues));
			$lQueryId = $this->prepareQuery($lQuery, $lValues);
			$this->doQueryWithId($lQueryId);
			$lResult = $this->fetchAllWithId($lQueryId);
		} catch (Exception $e) {
			trigger_error(var_export($e->getMessage(), true));
			throw new Exception($e->getMessage());
		}
		return $lResult;
	}
	
}