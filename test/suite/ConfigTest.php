<?php

use PHPUnit\Framework\TestCase;
use Comhon\Object\Config\Config;
use Comhon\Exception\Config\ConfigFileNotFoundException;
use Comhon\Exception\Config\ConfigMalformedException;
use Comhon\Model\Restriction\RegexCollection;
use Test\Comhon\Data;
use Comhon\Model\Singleton\ModelManager;

class ConfigTest extends TestCase
{
	
	public function testNotFoundConfig()
	{
		$this->expectException(ConfigFileNotFoundException::class);
		Config::setLoadPath('./config/not-existing-config.json');
	}
	
	public function testMalformedConfig()
	{
		$this->expectException(ConfigMalformedException::class);
		
		Config::setLoadPath('./config/malformed-config.json');
		$config = Config::getInstance();
	}
	
	/**
	 * @depends testNotFoundConfig
	 * @depends testMalformedConfig
	 */
	public function testDatabaseFileNotFoundConfig()
	{
		$this->expectException(ConfigFileNotFoundException::class);
		
		Config::setLoadPath('./config/inconsistent-config.json');
		$config = Config::getInstance();
	}
	
	/**
	 * @depends testDatabaseFileNotFoundConfig
	 */
	public function testRegexFileNotFoundConfig()
	{
		Config::setLoadPath('./config/inconsistent-2-config.json');
		$config = Config::getInstance();
		$configPath = Config::getInstance()->getDirectory() . '/' . basename(Config::getLoadPath());
		$this->assertTrue(strpos($configPath, 'test/config/inconsistent-2-config.json') !== false);
		
		$this->expectException(ConfigFileNotFoundException::class);
		RegexCollection::getInstance();
	}
	
	/**
	 * @depends testDatabaseFileNotFoundConfig
	 */
	public function testSuccessConfigWithoutSql()
	{
		ModelManager::resetSingleton();
		Config::resetSingleton();
		Config::setLoadPath(__DIR__ . '/../config/config-without-sql.json');
		$config = Config::getInstance();
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlTable'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlDatabase'));
	}
	
	/**
	 * @depends testSuccessConfigWithoutSql
	 */
	public function testSuccessConfigWithSql()
	{
		ModelManager::resetSingleton();
		Config::resetSingleton();
		Config::setLoadPath(Data::$config);
		$config = Config::getInstance();
		$configPath = Config::getInstance()->getDirectory() . '/' . basename(Config::getLoadPath());
		$this->assertTrue(strpos($configPath, realpath(Data::$config)) !== false);
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlTable'));
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlDatabase'));
		
		RegexCollection::getInstance();
	}

}
