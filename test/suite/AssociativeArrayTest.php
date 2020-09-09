<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Object\Config\Config;
use Test\Comhon\Data;

class AssociativeArrayTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testAssociativeArray()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestAssociativeArray');
		$testAssociativeArray = $model->getObjectInstance();
		
		$names = $testAssociativeArray->initValue('names');
		$emails = $testAssociativeArray->initValue('emails');
		
		$names->setValue('first', 'john');
		$names->setValue('last', 'doe');
		
		$emails->pushValue('john@doe.fr');
		$emails->pushValue('john@doe.com');
		
		$stdInterfacer = new StdObjectInterfacer();
		$stdValue = $testAssociativeArray->export($stdInterfacer);
		$assocInterfacer = new AssocArrayInterfacer();
		$assocValue = $testAssociativeArray->export($assocInterfacer);
		$XmlInterfacer = new XMLInterfacer();
		$xmlValue = $testAssociativeArray->export($XmlInterfacer);
		$names->setValue('middle', 'john');
		
		$testAssociativeArray2 = $model->import(json_decode(json_encode($stdValue)), $stdInterfacer);
		$stdValue = $testAssociativeArray->export($stdInterfacer);
		$this->assertEquals('{"names":{"first":"john","last":"doe","middle":"john"},"emails":["john@doe.fr","john@doe.com"]}', json_encode($stdValue));
		$stdValue = $testAssociativeArray2->export($stdInterfacer);
		$this->assertEquals('{"names":{"first":"john","last":"doe"},"emails":["john@doe.fr","john@doe.com"]}', json_encode($stdValue));
		
		$testAssociativeArray2 = $model->import(json_decode(json_encode($assocValue), true), $assocInterfacer);
		$assocValue = $testAssociativeArray->export($assocInterfacer);
		$this->assertEquals('{"names":{"first":"john","last":"doe","middle":"john"},"emails":["john@doe.fr","john@doe.com"]}', json_encode($assocValue));
		$assocValue = $testAssociativeArray2->export($assocInterfacer);
		$this->assertEquals('{"names":{"first":"john","last":"doe"},"emails":["john@doe.fr","john@doe.com"]}', json_encode($assocValue));
		
		$testAssociativeArray2 = $model->import($xmlValue, $XmlInterfacer);
		$xmlValue = $testAssociativeArray->export($XmlInterfacer);
		$this->assertEquals('<root><names><name key-="first">john</name><name key-="last">doe</name><name key-="middle">john</name></names><emails><email>john@doe.fr</email><email>john@doe.com</email></emails></root>', $XmlInterfacer->toString($xmlValue));
		$xmlValue = $testAssociativeArray2->export($XmlInterfacer);
		$this->assertEquals('<root><names><name key-="first">john</name><name key-="last">doe</name></names><emails><email>john@doe.fr</email><email>john@doe.com</email></emails></root>', $XmlInterfacer->toString($xmlValue));
		
	}
	

}
