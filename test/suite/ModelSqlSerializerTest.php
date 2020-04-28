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
		Utils::deleteDirectory($sqlTablePath.'/sql_home');
		Utils::deleteDirectory($sqlTablePath.'/sql_house');
		Utils::deleteDirectory($sqlTablePath.'/sql_main_test_db');
		Utils::deleteDirectory($sqlTablePath.'/sql_person');
		Utils::deleteDirectory($sqlTablePath.'/sql_place');
		Utils::deleteDirectory($sqlTablePath.'/sql_test_db');
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
		$modelSqlSerialzer = new ModelSqlSerializer(true);
		$modelSqlSerialzer->registerSerializations($sqlDatabase, 'snake', 'Sql', true);
		$separator = DIRECTORY_SEPARATOR;
		$expected = __DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdout_expected.txt';
		$actual = __DIR__.$separator.'data'.$separator.'ModelSqlSerializer'.$separator.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
	}
	
	/**
	 * @depends testRegisterSerializations
	 */
	public function testGeneratedSerializationManifests() {
		$serializationSqlPath_rd = Config::getInstance()->getSerializationAutoloadList()->getValue('Sql');
		$serializationSqlPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializationSqlPath_rd;
		
		$files = Utils::scanDirectory($serializationSqlPath_ad);
		$this->assertCount(34, $files);
		
		$expectedMd5Files = Config::getInstance()->getManifestFormat() == 'json'
			? '7c4dd582e49c04b89b960ec704d07881'
			: '02c17a23e1b7580f6c50709d267ef75f';
		
		$actualMd5Files = '';
		foreach ($files as $file) {
			if (is_dir($file)) {
				continue;
			}
			$actualMd5Files = md5($actualMd5Files.md5_file($file));
		}
		$this->assertEquals($expectedMd5Files, $actualMd5Files);
	}
}
