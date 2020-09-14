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
	 * @dataProvider ModelToSqlFileData
	 */
	public function testModelToSqlFile($update, $modelName, $recursive) {
		Cli::$STDIN = fopen(self::DATA_DIR.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(self::DATA_DIR.'stdout_actual.txt', 'w');
		$modelBinder = new ModelToSQL(true);
		$modelBinder->generateFiles(self::OUTPUT_PATH, $update, $modelName, $recursive);
		
		switch (Config::getInstance()->getManifestFormat()) {
			case 'json':
				$expectedStdFile = 'stdout_expected_json.txt';
				break;
			case 'xml':
				$expectedStdFile = 'stdout_expected_xml.txt';
				break;
			case 'yaml':
				$expectedStdFile = 'stdout_expected_yaml.txt';
				break;
			default:
				throw new \Exception('unrecognized manifest format');
		}
		
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
	
	public function ModelToSqlFileData() {
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
	
	
	
	/**
	 * @dataProvider ModelToSqlQueryData
	 */
	public function testModelToSqlQuery($update, $modelName, $recursive, $expectedPgsqlQueries, $expectedMysqlQueries) {
		$modelBinder = new ModelToSQL(false);
		$actualQueries = $modelBinder->generateQueries($update, $modelName, $recursive);
		
		switch (Config::getInstance()->getManifestFormat()) {
			case 'json':
			case 'yaml':
				$this->assertEquals($expectedPgsqlQueries, $actualQueries);
				break;
			case 'xml':
				$this->assertEquals($expectedMysqlQueries, $actualQueries);
				break;
			default:
				throw new \Exception('unrecognized manifest format');
		}
		
		
	}
	
	public function modelToSqlQueryData() {
		return [
			[
				false,
				'Test\House',
				false,
				[
					'2' => '
CREATE TABLE public.house (
    "id_serial" INT,
    "surface" FLOAT,
    "type" TEXT,
    "garden" BOOLEAN,
    "garage" BOOLEAN,
    "ghosts" TEXT,
    "address" INT,
    PRIMARY KEY ("id_serial")
);

ALTER TABLE public.house
    ADD FOREIGN KEY ("address") REFERENCES public.place("id");
'
				],
				[
					'1' => '
CREATE TABLE house (
    `id_serial` INT,
    `surface` DECIMAL(20,10),
    `type` TEXT,
    `garden` BOOLEAN,
    `garage` BOOLEAN,
    `ghosts` TEXT,
    `address` INT,
    PRIMARY KEY (`id_serial`)
);

ALTER TABLE house
    ADD FOREIGN KEY (`address`) REFERENCES place(`id`);
'
				]
			],
			[
				true,
				'Test\House',
				true,
				[
					'2' => '
ALTER TABLE public.house
    ADD "ghosts" TEXT,
    ADD "address" INT
;

ALTER TABLE public.house
    ADD FOREIGN KEY ("address") REFERENCES public.place("id");
'
				],
				[
					'1' => '
ALTER TABLE house
    ADD `ghosts` TEXT,
    ADD `address` INT
;

ALTER TABLE house
    ADD FOREIGN KEY (`address`) REFERENCES place(`id`);
'
				]
			]
		];
	}
	
}
