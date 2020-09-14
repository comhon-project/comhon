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
	public static function setUpBeforeClass()
	{
		Config::resetSingleton();
		ModelManager::resetSingleton();
	}
	
	public function testNotFoundConfig()
	{
		$this->expectException(ConfigFileNotFoundException::class);
		Config::setLoadPath('./config/not-existing-config.json');
	}
	
	public function testMalformedConfig()
	{
		$this->expectException(ConfigMalformedException::class);
		
		Config::setLoadPath('./config/malformed-config.json');
		Config::getInstance();
	}
	
	/**
	 * @depends testNotFoundConfig
	 * @depends testMalformedConfig
	 */
	public function testDatabaseFileNotFoundConfig()
	{
		$this->expectException(ConfigFileNotFoundException::class);
		
		Config::setLoadPath('./config/inconsistent-config.json');
		Config::getInstance();
	}
	
	/**
	 * @depends testDatabaseFileNotFoundConfig
	 */
	public function testRegexFileNotFoundConfig()
	{
		Config::setLoadPath('./config/inconsistent-2-config.json');
		$config = Config::getInstance();
		$configPath = $config->getDirectory() . '/' . basename(Config::getLoadPath());
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
		Config::getInstance();
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
		$configPath = $config->getDirectory() . '/' . basename(Config::getLoadPath());
		$this->assertTrue(strpos($configPath, realpath(Data::$config)) !== false);
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlTable'));
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Comhon\SqlDatabase'));
		$this->assertTrue(in_array($config->getManifestFormat(), ['xml', 'json', 'yaml']));
		
		$expected = '{
    "manifest_format": "json",
    "database": {
        "charset": "utf8",
        "timezone": "UTC"
    },
    "date_time_format": "c",
    "autoload": {
        "manifest": {
            "Test": "..\/manifests\/test\/manifest",
            "Sql": "..\/manifests\/sql\/manifest",
            "Binder": "..\/manifests\/binder\/manifest"
        },
        "serialization": {
            "Test": "..\/manifests\/test\/serialization_pgsql",
            "Sql": "..\/manifests\/sql\/serialization",
            "Binder": "..\/manifests\/binder\/serialization"
        },
        "options": {
            "Test": "..\/manifests\/test\/options"
        }
    },
    "regex_list": ".\/regex.json",
    "sql_table": ".\/table",
    "sql_database": ".\/database",
    "request_collection_limit": 20
}
';
		if ($config->getManifestFormat() == 'xml') {
			$expected = str_replace(['"json"', 'serialization_pgsql'], ['"xml"', 'serialization'], $expected);
		} elseif ($config->getManifestFormat() == 'yaml') {
			$expected = str_replace('"json"', '"yaml"', $expected);
		}
		$this->assertEquals($expected, $config->__toString());
		
		RegexCollection::getInstance();
	}

}
