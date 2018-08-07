<?php
use PHPUnit\Framework\TestCase;
use Comhon\Object\Config\Config;
use Comhon\Exception\ConfigFileNotFoundException;
use Comhon\Exception\ConfigMalformedException;
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
	public function testSuccessConfig()
	{
		ModelManager::resetSingleton();
		Config::resetSingleton();
		Config::setLoadPath(Data::$config);
		$config = Config::getInstance();
		$configPath = Config::getInstance()->getDirectory() . '/' . basename(Config::getLoadPath());
		$this->assertTrue(strpos($configPath, realpath(Data::$config)) !== false);
		
		RegexCollection::getInstance();
		
		$plop = new stdClass();
		$plop->plop = 'plop';
		
		$plop2 = new stdClass();
		$plop2->plop = 'plop';
		$plop2 = $plop;
		$this->assertSame($plop2, $plop);
	}

}
