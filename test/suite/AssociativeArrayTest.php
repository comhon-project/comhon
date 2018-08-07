<?php
use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\NotDefinedModelException;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class AssociativeArrayTest extends TestCase
{
	
	public function testAssociativeArray()
	{
		$hasThrownEx = false;
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
		$this->assertEquals('<root><names><first>john</first><last>doe</last><middle>john</middle></names><emails><email>john@doe.fr</email><email>john@doe.com</email></emails></root>', $XmlInterfacer->toString($xmlValue));
		$xmlValue = $testAssociativeArray2->export($XmlInterfacer);
		$this->assertEquals('<root><names><first>john</first><last>doe</last></names><emails><email>john@doe.fr</email><email>john@doe.com</email></emails></root>', $XmlInterfacer->toString($xmlValue));
		
	}
	

}
