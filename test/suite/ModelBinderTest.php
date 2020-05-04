<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cli;
use Comhon\Utils\Utils;
use Comhon\Utils\Project\ModelBinder;

class ModelBinderTest extends TestCase
{
	const DATA_DIR = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'ModelBinder'.DIRECTORY_SEPARATOR;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		Cli::$STDIN = fopen(self::DATA_DIR.'stdin.txt', 'r');
		Cli::$STDOUT = fopen(self::DATA_DIR.'stdout_actual.txt', 'w');
		
		$manifestBinderPath_rd = Config::getInstance()->getManifestAutoloadList()->getValue('Binder');
		$manifestBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $manifestBinderPath_rd;
		$serializationBinderPath_rd = Config::getInstance()->getSerializationAutoloadList()->getValue('Binder');
		$serializationBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializationBinderPath_rd;
		
		Utils::copyDirectory(self::DATA_DIR.'manifest', $manifestBinderPath_ad);
		Utils::copyDirectory(self::DATA_DIR.'serialization', $serializationBinderPath_ad);
	}
	
	public static function  tearDownAfterClass() {
		fclose(Cli::$STDIN);
		fclose(Cli::$STDOUT);
		Cli::$STDIN = STDIN;
		Cli::$STDOUT = STDOUT;
		
		$manifestBinderPath_rd = Config::getInstance()->getManifestAutoloadList()->getValue('Binder');
		$manifestBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $manifestBinderPath_rd;
		Utils::deleteDirectory($manifestBinderPath_ad);
		mkdir($manifestBinderPath_ad);
		
		$serializationBinderPath_rd = Config::getInstance()->getSerializationAutoloadList()->getValue('Binder');
		$serializationBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializationBinderPath_rd;
		Utils::deleteDirectory($serializationBinderPath_ad);
		mkdir($serializationBinderPath_ad);
	}
	
	public function testBindModels() {
		$modelBinder = new ModelBinder(true);
		$modelBinder->bindModels('Binder', true);
		$expected = self::DATA_DIR.'stdout_expected.txt';
		$actual = self::DATA_DIR.'stdout_actual.txt';
		$this->assertFileEquals($expected, $actual);
	}
	
	/**
	 * depends testBindModels
	 */
	public function testGeneratedManifests() {
		
		$manifestBinderPath_rd = Config::getInstance()->getManifestAutoloadList()->getValue('Binder');
		$manifestBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $manifestBinderPath_rd;
		
		$files = Utils::scanDirectory($manifestBinderPath_ad);
		$this->assertCount(21, $files);
		
		$expectedMd5Files = Config::getInstance()->getManifestFormat() == 'json'
			? 'd781e123b4bbe270b087609ba37e3031'
			: '979a326fb0c87804f1f8828b243e135b';
		
		$actualMd5Files = '';
		foreach ($files as $file) {
			if (is_dir($file)) {
				continue;
			}
			$actualMd5Files = md5($actualMd5Files.md5_file($file));
		}
		$this->assertEquals($expectedMd5Files, $actualMd5Files);
	}
	
	/**
	 * depends testBindModels
	 */
	public function testGeneratedSerializationManifests() {
		
		$serializationBinderPath_rd = Config::getInstance()->getSerializationAutoloadList()->getValue('Binder');
		$serializationBinderPath_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializationBinderPath_rd;
		
		$files = Utils::scanDirectory($serializationBinderPath_ad);
		$this->assertCount(24, $files);
		
		$expectedMd5Files = Config::getInstance()->getManifestFormat() == 'json'
			? 'b6941a04a5d5b77ff346fda62d4dc3f4'
			: 'c4c6e896e2a53c4eae9d9cf36316231c';
		
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
