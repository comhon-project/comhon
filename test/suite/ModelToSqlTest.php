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
	
	/**
	 * @dataProvider ModelToSqlData
	 */
	public function testModelToSql($update, $modelName, $recursive) {
		Cli::$STDIN = fopen(self::DATA_DIR.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(self::DATA_DIR.'stdout_actual.txt', 'w');
		$modelBinder = new ModelToSQL(true);
		$modelBinder->generateFiles(self::OUTPUT_PATH, $update, $modelName, $recursive);
		
		$expectedStdFile = Config::getInstance()->getManifestFormat() == 'json'
			? 'stdout_expected_json.txt' : 'stdout_expected_xml.txt';
		$expected = self::DATA_DIR.$expectedStdFile;
		$actual = self::DATA_DIR.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
		
		$suffix = $update ? '-update' : '-create';
		$expectedDir = self::DATA_DIR.'output-conf-'.Config::getInstance()->getManifestFormat().$suffix;
		$this->assertFileExists(self::OUTPUT_PATH);
		$this->assertFileExists($expectedDir);
		$actualFiles = Utils::scanDirectory(self::OUTPUT_PATH);
		$expectedFiles = Utils::scanDirectory($expectedDir);
		$this->assertEquals(count($actualFiles), count($expectedFiles));
		
		for ($i = 0; $i < count($expectedFiles); $i++) {
			$actualFile = $actualFiles[$i];
			$expectedFile = $expectedFiles[$i];
			$this->assertEquals(basename($expectedFile), basename($actualFile));
			$this->assertFileEquals($expectedFile, $actualFile);
		}
	}
	
	public function modelToSqlData() {
		return [
			[
				false,
				'Test',
				true
			],
			[
				true,
				'Test',
				true
			]
		];
	}
	
}
