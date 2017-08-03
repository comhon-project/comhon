<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

use Comhon\Exception\Database\NotSupportedDBMSException;

class SqlUtils {

	/** @var array reserved words for mySQL DBSM */
	private static $mySQL_ReservedWords = [
		'ACCESSIBLE'=>null,'ADD'=>null,'ALL'=>null,'ALTER'=>null,'ANALYZE'=>null,'AND'=>null,'AS'=>null,'ASC'=>null,'ASENSITIVE'=>null,'BEFORE'=>null,'BETWEEN'=>null,'BIGINT'=>null,'BINARY'=>null,'BLOB'=>null,'BOTH'=>null,'BY'=>null,'CALL'=>null,'CASCADE'=>null,'CASE'=>null,
		'CHANGE'=>null,'CHAR'=>null,'CHARACTER'=>null,'CHECK'=>null,'COLLATE'=>null,'COLUMN'=>null,'CONDITION'=>null,'CONSTRAINT'=>null,'CONTINUE'=>null,'CONVERT'=>null,'CREATE'=>null,'CROSS'=>null,'CURRENT_DATE'=>null,
		'CURRENT_TIME'=>null,'CURRENT_TIMESTAMP'=>null,'CURRENT_USER'=>null,'CURSOR'=>null,'DATABASE'=>null,'DATABASES'=>null,'DAY_HOUR'=>null,'DAY_MICROSECOND'=>null,'DAY_MINUTE'=>null,'DAY_SECOND'=>null,'DEC'=>null,'DECIMAL'=>null,
		'DECLARE'=>null,'DEFAULT'=>null,'DELAYED'=>null,'DELETE'=>null,'DESC'=>null,'DESCRIBE'=>null,'DETERMINISTIC'=>null,'DISTINCT'=>null,'DISTINCTROW'=>null,'DIV'=>null,'DOUBLE'=>null,'DROP'=>null,
		'DUAL'=>null,'EACH'=>null,'ELSE'=>null,'ELSEIF'=>null,'ENCLOSED'=>null,'ESCAPED'=>null,'EXISTS'=>null,'EXIT'=>null,'EXPLAIN'=>null,'FALSE'=>null,'FETCH'=>null,'FLOAT'=>null,'FLOAT4'=>null,'FLOAT8'=>null,'FOR'=>null,
		'FORCE'=>null,'FOREIGN'=>null,'FROM'=>null,'FULLTEXT'=>null,'GENERATED'=>null,'GET'=>null,'GRANT'=>null,'GROUP'=>null,'HAVING'=>null,'HIGH_PRIORITY'=>null,'HOUR_MICROSECOND'=>null,'HOUR_MINUTE'=>null,'HOUR_SECOND'=>null,
		'IF'=>null,'IGNORE'=>null,'IN'=>null,'INDEX'=>null,'INFILE'=>null,'INNER'=>null,'INOUT'=>null,'INSENSITIVE'=>null,'INSERT'=>null,'INT'=>null,'INT1'=>null,'INT2'=>null,'INT3'=>null,'INT4'=>null,'INT8'=>null,'INTEGER'=>null,'INTERVAL'=>null,'INTO'=>null,
		'IO_AFTER_GTIDS'=>null,'IO_BEFORE_GTIDS'=>null,'IS'=>null,'ITERATE'=>null,'JOIN'=>null,'KEY'=>null,'KEYS'=>null,'KILL'=>null,'LEADING'=>null,'LEAVE'=>null,'LEFT'=>null,'LIKE'=>null,'LIMIT'=>null,'LINEAR'=>null,'LINES'=>null,'LOAD'=>null,'LOCALTIME'=>null,'LOCALTIMESTAMP'=>null,'LOCK'=>null,
		'LONG'=>null,'LONGBLOB'=>null,'LONGTEXT'=>null,'LOOP'=>null,'LOW_PRIORITY'=>null,'MASTER_BIND'=>null,'MASTER_SSL_VERIFY_SERVER_CERT'=>null,'MATCH'=>null,'MAXVALUE'=>null,'MEDIUMBLOB'=>null,'MEDIUMINT'=>null,
		'MEDIUMTEXT'=>null,'MIDDLEINT'=>null,'MINUTE_MICROSECOND'=>null,'MINUTE_SECOND'=>null,'MOD'=>null,'MODIFIES'=>null,'NATURAL'=>null,'NOT'=>null,'NO_WRITE_TO_BINLOG'=>null,'NULL'=>null,'NUMERIC'=>null,'ON'=>null,'OPTIMIZE'=>null,
		'OPTIMIZER_COSTS'=>null,'OPTION'=>null,'OPTIONALLY'=>null,'OR'=>null,'ORDER'=>null,'OUT'=>null,'OUTER'=>null,'OUTFILE'=>null,'PARTITION'=>null,'PRECISION'=>null,'PRIMARY'=>null,'PROCEDURE'=>null,'PURGE'=>null,'RANGE'=>null,'READ'=>null,
		'READS'=>null,'READ_WRITE'=>null,'REAL'=>null,'REFERENCES'=>null,'REGEXP'=>null,'RELEASE'=>null,'RENAME'=>null,'REPEAT'=>null,'REPLACE'=>null,'REQUIRE'=>null,'RESIGNAL'=>null,'RESTRICT'=>null,'RETURN'=>null,'REVOKE'=>null,'RIGHT'=>null,'RLIKE'=>null,
		'SCHEMA'=>null,'SCHEMAS'=>null,'SECOND_MICROSECOND'=>null,'SELECT'=>null,'SENSITIVE'=>null,'SEPARATOR'=>null,'SET'=>null,'SHOW'=>null,'SIGNAL'=>null,'SMALLINT'=>null,'SPATIAL'=>null,'SPECIFIC'=>null,'SQL'=>null,'SQLEXCEPTION'=>null,'SQLSTATE'=>null,
		'SQLWARNING'=>null,'SQL_BIG_RESULT'=>null,'SQL_CALC_FOUND_ROWS'=>null,'SQL_SMALL_RESULT'=>null,'SSL'=>null,'STARTING'=>null,'STORED'=>null,'STRAIGHT_JOIN'=>null,'TABLE'=>null,'TERMINATED'=>null,'THEN'=>null,
		'TINYBLOB'=>null,'TINYINT'=>null,'TINYTEXT'=>null,'TO'=>null,'TRAILING'=>null,'TRIGGER'=>null,'TRUE'=>null,'UNDO'=>null,'UNION'=>null,'UNIQUE'=>null,'UNLOCK'=>null,'UNSIGNED'=>null,'UPDATE'=>null,'USAGE'=>null,'USE'=>null,
		'USING'=>null,'UTC_DATE'=>null,'UTC_TIME'=>null,'UTC_TIMESTAMP'=>null,'VALUES'=>null,'VARBINARY'=>null,'VARCHAR'=>null,'VARCHARACTER'=>null,'VARYING'=>null,'VIRTUAL'=>null,'WHEN'=>null,'WHERE'=>null,'WHILE'=>null,'WITH'=>null,'WRITE'=>null,'XOR'=>null,'YEAR_MONTH'=>null
	];
	
	/** @var array reserved words for postgreSQL DBSM */
	private static $postgreSQL_ReservedWords = [
		'ALL'=>null,'ANALYSE'=>null,'ANALYZE'=>null,'AND'=>null,'ANY'=>null,'ARRAY'=>null,'AS'=>null,'ASC'=>null,'ASYMMETRIC'=>null,'BOTH'=>null,
		'CASE'=>null,'CAST'=>null,'CHECK'=>null,'COLLATE'=>null,'COLUMN'=>null,'CONSTRAINT'=>null,'CREATE'=>null,'CURRENT_CATALOG'=>null,'CURRENT_DATE'=>null,
		'CURRENT_ROLE'=>null,'CURRENT_TIME'=>null,'CURRENT_TIMESTAMP'=>null,'CURRENT_USER'=>null,'DEFAULT'=>null,'DEFERRABLE'=>null,'DESC'=>null,'DISTINCT'=>null,
		'DO'=>null,'ELSE'=>null,'END'=>null,'EXCEPT'=>null,'FALSE'=>null,'FETCH'=>null,'FOR'=>null,'FOREIGN'=>null,'FROM'=>null,'GRANT'=>null,
		'GROUP'=>null,'HAVING'=>null,'IN'=>null,'INITIALLY'=>null,'INTERSECT'=>null,'INTO'=>null,'LEADING'=>null,'LIMIT'=>null,'LOCALTIME'=>null,
		'LOCALTIMESTAMP'=>null,'NOT'=>null,'NULL'=>null,'OFFSET'=>null,'ON'=>null,'ONLY'=>null,'OR'=>null,'ORDER'=>null,'PLACING'=>null,'PRIMARY'=>null,
		'REFERENCES'=>null,'RETURNING'=>null,'SELECT'=>null,'SESSION_USER'=>null,'SOME'=>null,'SYMMETRIC'=>null,'TABLE'=>null,'THEN'=>null,'TO'=>null,
		'TRAILING'=>null,'TRUE'=>null,'UNION'=>null,'UNIQUE'=>null,'USER'=>null,'USING'=>null,'VARIADIC'=>null,'WHEN'=>null,'WHERE'=>null,'WINDOW'=>null,'WITH'=>null
	];
	
	/**
	 * verify if specified word is a reserved word in specified database management system
	 * 
	 * a reserved word is a word that need to be escaped in queries.
	 * 
	 * @param string $DBMS
	 * @param string $word
	 * @throws \Exception
	 * @return boolean
	 */
	public static function isReservedWorld($DBMS, $word) {
		switch ($DBMS) {
			case 'mysql': return array_key_exists(strtoupper($word), self::$mySQL_ReservedWords);      break;
			case 'pgsql': return array_key_exists(strtoupper($word), self::$postgreSQL_ReservedWords); break;
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
			default: throw new NotSupportedDBMSException($DBMS);
		}
	}
	
	/**
	 * verify if specified word is a reserved word in mySQL
	 * 
	 * @param string $word
	 * @return boolean
	 */
	public static function isMySQLReservedWorld($word) {
		return array_key_exists(strtoupper($word), self::$mySQL_ReservedWords);
	}
	
	/**
	 * verify if specified word is a reserved word in postgreSQL
	 * 
	 * @param string $word
	 * @return boolean
	 */
	public static function isPostgreSQLReservedWorld($word) {
		return array_key_exists(strtoupper($word), self::$postgreSQL_ReservedWords);
	}
}