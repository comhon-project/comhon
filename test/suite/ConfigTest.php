<?php
use PHPUnit\Framework\TestCase;
use Comhon\Object\Config\Config;
use Comhon\Exception\ConfigFileNotFoundException;
use Comhon\Exception\ConfigMalformedException;
use Comhon\Model\Restriction\RegexCollection;

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
		Config::resetSingleton();
		Config::setLoadPath('./config/inconsistent-2-config.json');
		$config = Config::getInstance();
		$this->assertTrue(strpos(Config::getLoadPath(), 'test/config/inconsistent-2-config.json') !== false);
		
		$this->expectException(ConfigFileNotFoundException::class);
		RegexCollection::getInstance();
	}
	
	/**
	 * @depends testDatabaseFileNotFoundConfig
	 */
	public function testSuccessConfig()
	{
		Config::resetSingleton();
		Config::setLoadPath('./config/config.json');
		$config = Config::getInstance();
		$this->assertTrue(strpos(Config::getLoadPath(), 'test/config/config.json') !== false);
		
		RegexCollection::getInstance();
	}

}
