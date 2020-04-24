<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cli;
use Comhon\Utils\Project\ModelSqlSerializer;
use Comhon\Object\ComhonObject;
use Comhon\Utils\Utils;

class ModelSqlSerializerTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		$separator = DIRECTORY_SEPARATOR;
		Cli::$STDIN = fopen(__DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(__DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdout_actual.txt', 'w');
	}
	
	public static function  tearDownAfterClass() {
		fclose(Cli::$STDIN);
		fclose(Cli::$STDOUT);
		Cli::$STDIN = STDIN;
		Cli::$STDOUT = STDOUT;
		
		$sqlTablePath = Config::getInstance()->getSerializationSqlTablePath();
		Utils::deleteDirectory($sqlTablePath.'/sql_abstract');
		Utils::deleteDirectory($sqlTablePath.'/sql_body_man');
		Utils::deleteDirectory($sqlTablePath.'/sql_body_woman');
		Utils::deleteDirectory($sqlTablePath.'/sql_child_test_db');
		Utils::deleteDirectory($sqlTablePath.'/sql_db_constraint');
		Utils::deleteDirectory($sqlTablePath.'/sql_home');
		Utils::deleteDirectory($sqlTablePath.'/sql_house');
		Utils::deleteDirectory($sqlTablePath.'/sql_main_test_db');
		Utils::deleteDirectory($sqlTablePath.'/sql_person');
		Utils::deleteDirectory($sqlTablePath.'/sql_place');
		Utils::deleteDirectory($sqlTablePath.'/sql_test_db');
		Utils::deleteDirectory($sqlTablePath.'/sql_test_multi_incremental');
		Utils::deleteDirectory($sqlTablePath.'/sql_town');
		Utils::deleteDirectory($sqlTablePath.'/sql_test_no_id');
		
		$serializationSqlPath_rd = Config::getInstance()->getSerializationAutoloadList()->getValue('Sql');
		$serializationSqlPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializationSqlPath_rd;
		Utils::deleteDirectory($serializationSqlPath_ad);
		mkdir($serializationSqlPath_ad);
	}
	
	public function testRegisterSerializations() {
		$sqlDatabase = new ComhonObject('Comhon\SqlDatabase');
		$sqlDatabase->setId('test');
		$sqlDatabase->setValue('DBMS', 'mysql');
		$modelSqlSerialzer = new ModelSqlSerializer();
		$modelSqlSerialzer->registerSerializations($sqlDatabase, 'iso', 'Sql', true);
		$separator = DIRECTORY_SEPARATOR;
		$expected = __DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdout_expected.txt';
		$actual = __DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
	}
}
