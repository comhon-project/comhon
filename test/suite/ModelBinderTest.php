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
		$this->assertCount(28, $files);
		
		switch (Config::getInstance()->getManifestFormat()) {
			case 'json':
				$expectedMd5Files = '10474249b9cd53fd5d7e9261d81efdf8';
				break;
			case 'xml':
				$expectedMd5Files = '875519b8d976cd17985c5c4e6a647449';
				break;
			case 'yaml':
				$expectedMd5Files = '76c5776a15a95b05df2081d308cd30c8';
				break;
			default:
				throw new \Exception('unrecognized manifest format');
		}
		
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
		$this->assertCount(31, $files);
		
		switch (Config::getInstance()->getManifestFormat()) {
			case 'json':
				$expectedMd5Files = '0dacfc4b64b54485e16ffb6756704bec';
				break;
			case 'xml':
				$expectedMd5Files = '7f487a00b38e3f59885a7cf7c3133691';
				break;
			case 'yaml':
				$expectedMd5Files = 'cb8a123d7a4e64375bf4685890d0b997';
				break;
			default:
				throw new \Exception('unrecognized manifest format');
		}
		
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
