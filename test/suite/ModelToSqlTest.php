<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cli;
use Comhon\Utils\Utils;
use Comhon\Utils\Project\ModelBinder;
use Comhon\Utils\Project\ModelToSQL;

class ModelToSqlTest extends TestCase
{
	const DATA_DIR = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'ModelToSql'.DIRECTORY_SEPARATOR;
	const OUTPUT_PATH = __DIR__.'/output';
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		Cli::$STDIN = fopen(self::DATA_DIR.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(self::DATA_DIR.'stdout_actual.txt', 'w');
	}
	
	public static function  tearDownAfterClass() {
		fclose(Cli::$STDIN);
		fclose(Cli::$STDOUT);
		Cli::$STDIN = STDIN;
		Cli::$STDOUT = STDOUT;
		
		if (file_exists(self::OUTPUT_PATH)) {
			Utils::deleteDirectory(self::OUTPUT_PATH);
		}
	}
	
	public function testModelToSql() {
		$modelBinder = new ModelToSQL(true);
		$modelBinder->transform(self::OUTPUT_PATH, 'Test', true);
		$expectedFile = Config::getInstance()->getManifestFormat() == 'json' 
			? 'stdout_expected_json.txt' : 'stdout_expected_xml.txt';
		$expected = self::DATA_DIR.$expectedFile;
		$actual = self::DATA_DIR.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
	}
	
	/**
	 * depends testModelToSql
	 */
	public function testGeneratedSqlFiles() {
		
		$this->assertFileExists(self::OUTPUT_PATH);
		$files = Utils::scanDirectory(self::OUTPUT_PATH);
		$this->assertCount(2, $files);
		
		$expectedMd5Files = Config::getInstance()->getManifestFormat() == 'json'
			? 'e5adaeb37744f5eda174fe74af5aed2d'
			: '3baaff397bb360a67bc0e2c26eb5720d';
		
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
