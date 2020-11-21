<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Utils\Cache;
use Comhon\Cache\FileSystemCacheHandler;
use Comhon\Exception\Cache\CacheException;
use Comhon\Cache\MemCachedHandler;

class CacheTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testConfigCacheHandler() {
		if (Config::getInstance()->getManifestFormat() == 'json') {
			$this->assertEquals('directory:../../../cache', Config::getInstance()->getCacheSettings());
			$this->assertInstanceOf(FileSystemCacheHandler::class, ModelManager::getInstance()->getCacheHandler());
			$this->assertEquals(
				dirname(__DIR__, 3).'/cache', 
				ModelManager::getInstance()->getCacheHandler()->getDirectory()
			);
		} elseif (Config::getInstance()->getManifestFormat() == 'xml') {
			$this->assertEquals('memcached:host=127.0.0.1;port=11211', Config::getInstance()->getCacheSettings());
			$this->assertInstanceOf(MemCachedHandler::class, ModelManager::getInstance()->getCacheHandler());
		} elseif (Config::getInstance()->getManifestFormat() == 'yaml') {
			$this->assertNull(Config::getInstance()->getCacheSettings());
			$this->assertNull(ModelManager::getInstance()->getCacheHandler());
		}
	}
	
	public function testInstanciateFileSystemCacheHandler()
	{
		$cacheDir = __DIR__.'/cache';
		$this->assertFileNotExists($cacheDir);
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
	
	/**
	 * 
	 * @dataProvider instanciateMemCachedHandlerData
	 */
	public function testInstanciateMemCachedHandler($server, $success)
	{
		if (!$success) {
			$this->expectException(CacheException::class);
		}
		new MemCachedHandler($server);
		if ($success) {
			$this->assertTrue(true);
		}
		
	}
	
	public function instanciateMemCachedHandlerData() {
		return [
			[
				'host=127.0.0.1;port=11211',
				true
			],
			[
				'',
				true
			],
			[
				'host=127.0.0.1,port=11211',
				false
			],
			[
				'host=127.0.0.1;port11211',
				false
			],
			[
				'host=127.0.0.1;host=11211',
				false
			]
		];
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
