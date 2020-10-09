<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cache;
use Comhon\Cache\FileSystemCacheHandler;

class ResetCacheTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testInstanciateCacheHandler() {
		if (is_null(Config::getInstance()->getCacheSettings())) {
			$this->assertNull(ModelManager::getInstance()->getCacheHandler());
		} else {
			$this->assertEquals('directory:../../../cache', Config::getInstance()->getCacheSettings());
			$this->assertInstanceOf(FileSystemCacheHandler::class, ModelManager::getInstance()->getCacheHandler());
			$this->assertEquals(
				dirname(__DIR__).'/config/../../../cache', 
				ModelManager::getInstance()->getCacheHandler()->getDirectory()
			);
		}
	}
	
	public function testResetCache() {
		// without cache settings just need to test if reset cache return false 
		if (is_null(Config::getInstance()->getCacheSettings())) {
			$this->assertFalse(Cache::reset());
			return;
		}
		$this->assertEquals(
			dirname(__DIR__).'/config/../../../cache/config', 
			ModelManager::getInstance()->getCacheHandler()->getConfigKey()
		);
		$this->assertEquals(
			dirname(__DIR__).'/config/../../../cache/sqlTable_sqlDatabase', 
			ModelManager::getInstance()->getCacheHandler()->getSqlTableModelKey()
		);
		$this->assertFileExists(ModelManager::getInstance()->getCacheHandler()->getConfigKey());
		$this->assertFileExists(ModelManager::getInstance()->getCacheHandler()->getSqlTableModelKey());
		
		$this->assertTrue(Cache::reset());
		
		$this->assertFileNotExists(ModelManager::getInstance()->getCacheHandler()->getConfigKey());
		$this->assertFileNotExists(ModelManager::getInstance()->getCacheHandler()->getSqlTableModelKey());
	}
}
