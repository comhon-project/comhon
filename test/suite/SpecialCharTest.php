<?php
use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class SpecialCharTest extends TestCase
{
	
	public function testSpecialCharXML()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test');
		$test = $model->getObjectInstance();
		
		$XmlInterfacer = new XMLInterfacer();
		$test->setValue('stringValue', '<>&"');
		$xmlNode = $test->export($XmlInterfacer);
		$xmlString = $XmlInterfacer->toString($xmlNode);
		$this->assertEquals('<root stringValue="&lt;&gt;&amp;&quot;" floatValue="1.5" booleanValue="1" indexValue="0" percentageValue="1" dateValue="2016-11-13T20:04:05+00:00"/>', $xmlString);
		
		$test2 = $model->import($xmlNode, $XmlInterfacer);
		$this->assertEquals('<>&"', $test2->getValue('stringValue'));
		$xmlString= $XmlInterfacer->toString($test->export($XmlInterfacer));
		$this->assertEquals('<root stringValue="&lt;&gt;&amp;&quot;" floatValue="1.5" booleanValue="1" indexValue="0" percentageValue="1" dateValue="2016-11-13T20:04:05+00:00"/>',$xmlString);
		
	}
	

}
