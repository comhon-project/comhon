<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class ManifestV2Test extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testManifestV2()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Manifest_V_2\Inherited_V_2');
		$this->assertEquals(['id', 'name', 'inherited'], $model->getPropertiesNames());
	}

}
