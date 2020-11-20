<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cache;
use Comhon\Cache\FileSystemCacheHandler;
use Comhon\Exception\Cache\CacheException;

class CacheTest extends TestCase
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
				dirname(__DIR__, 3).'/cache', 
				ModelManager::getInstance()->getCacheHandler()->getDirectory()
			);
		}
	}
	
	public function testInstanciateFileSystemCacheHandler()
	{
		$cacheDir = __DIR__.'/cache';
		new FileSystemCacheHandler($cacheDir);
		$this->assertFileExists($cacheDir);
		rmdir($cacheDir);
		$this->assertFileNotExists($cacheDir);
	}
	
	public function testFileSystemCacheHandlerGetOutsidePath()
	{
		$cacheHandler = new FileSystemCacheHandler(__DIR__);
		
		$this->expectException(CacheException::class);
		$this->expectExceptionCode(108);
		$this->expectExceptionMessage("invalid key '../plop', path is outside cache directory");
		$cacheHandler->getPath('../plop');
	}
	
	public function testResetCache() {
		// without cache settings just need to test if reset cache return false 
		if (is_null(Config::getInstance()->getCacheSettings())) {
			$this->assertFalse(Cache::reset());
			return;
		}
		$cacheHandler = ModelManager::getInstance()->getCacheHandler();
		if ($cacheHandler instanceof FileSystemCacheHandler) {
			$this->assertEquals(
				dirname(__DIR__, 3).'/cache/config',
				$cacheHandler->getPath($cacheHandler->getConfigKey())
			);
			$this->assertEquals(
				dirname(__DIR__, 3).'/cache/model/Comhon-SqlTable',
				$cacheHandler->getPath($cacheHandler->getModelKey('Comhon\SqlTable'))
			);
		}
		$this->assertTrue($cacheHandler->hasValue($cacheHandler->getConfigKey()));
		$this->assertNotNull($cacheHandler->getValue($cacheHandler->getConfigKey()));
		$this->assertTrue($cacheHandler->hasValue($cacheHandler->getModelKey('Comhon\SqlTable')));
		$this->assertNotNull($cacheHandler->getValue($cacheHandler->getModelKey('Comhon\SqlTable')));
		
		$this->assertTrue(Cache::reset());
		
		$this->assertFalse($cacheHandler->hasValue($cacheHandler->getConfigKey()));
		$this->assertNull($cacheHandler->getValue($cacheHandler->getConfigKey()));
		$this->assertFalse($cacheHandler->hasValue($cacheHandler->getModelKey('Comhon\SqlTable')));
		$this->assertNull($cacheHandler->getValue($cacheHandler->getModelKey('Comhon\SqlTable')));
		$this->assertFalse($cacheHandler->hasValue($cacheHandler->getModelPrefixKey()));
	}
}
