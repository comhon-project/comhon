<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class LoadModelSpeedTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		Config::getInstance();
		ModelManager::resetSingleton();
	}
	
	public static function tearDownAfterClass()
	{
		ModelManager::resetSingleton();
	}
	
	public function testloadModelSpeed()
	{
		$timeStart = microtime(true);
		
		for ($i = 0; $i < 100; $i++) {
			Config::resetSingleton();
			ModelManager::resetSingleton();
			
			ModelManager::getInstance()->getInstanceModel('Comhon\Config');
			ModelManager::getInstance()->getInstanceModel('Comhon\Config\DbOpt');
			ModelManager::getInstance()->getInstanceModel('Comhon\Config\Autoload');
			ModelManager::getInstance()->getInstanceModel('Comhon\File\JsonFile');
			ModelManager::getInstance()->getInstanceModel('Comhon\File\XmlFile');
			ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
			ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable');
			
			ModelManager::getInstance()->getInstanceModel('Test\Body\Man');
			ModelManager::getInstance()->getInstanceModel('Test\Body\Woman');
			ModelManager::getInstance()->getInstanceModel('Test\Body\Tatoo');
			ModelManager::getInstance()->getInstanceModel('Test\Body\Piercing');
			ModelManager::getInstance()->getInstanceModel('Test\Home');
			ModelManager::getInstance()->getInstanceModel('Test\House');
			ModelManager::getInstance()->getInstanceModel('Test\LocatedHouse');
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedFinal');
			ModelManager::getInstance()->getInstanceModel('Test\Person\Man');
			ModelManager::getInstance()->getInstanceModel('Test\Person\Woman');
			ModelManager::getInstance()->getInstanceModel('Test\Test');
			ModelManager::getInstance()->getInstanceModel('Test\Test\ObjectContainer');
			ModelManager::getInstance()->getInstanceModel('Test\Test\Object');
			ModelManager::getInstance()->getInstanceModel('Test\Test\ObjectTwo');
			ModelManager::getInstance()->getInstanceModel('Test\Test\ObjectRefParent');
			ModelManager::getInstance()->getInstanceModel('Test\TestDb');
			ModelManager::getInstance()->getInstanceModel('Test\TestDb\Object');
			ModelManager::getInstance()->getInstanceModel('Test\TestDb\ObjectWithId');
			ModelManager::getInstance()->getInstanceModel('Test\TestDb\ObjectWithIdAndMore');
			ModelManager::getInstance()->getInstanceModel('Test\TestDb\ObjectWithIdAndMoreMore');
			ModelManager::getInstance()->getInstanceModel('Test\TestMultiIncremental');
			ModelManager::getInstance()->getInstanceModel('Test\TestNoId');
			ModelManager::getInstance()->getInstanceModel('Test\Town');
		}
		
		$exectTime = microtime(true) - $timeStart;
		$errorMargin = 0.01;
		
		$this->assertContains(Config::getInstance()->getManifestFormat(), ['xml', 'json', 'yaml']);
		switch (Config::getInstance()->getManifestFormat()) {
			case 'xml':
				$averageTime = 0.524;
				break;
			case 'json':
				$averageTime = 0.348;
				break;
			case 'yaml':
				$averageTime = 1.009;
				break;
		}
		if (($exectTime - $averageTime) > $errorMargin) {
			echo PHP_EOL . PHP_EOL . "Loading model time :  " . PHP_EOL
				. "\033[31mWARNING!!!"
				. "\033[0m exec :" . round($exectTime, 4) . ", average :" . $averageTime . PHP_EOL . PHP_EOL;
		}
	}

}
