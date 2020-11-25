<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Model\ModelArray;
use Comhon\Interfacer\Interfacer;

class InterfacerTest extends TestCase
{
	const DATA_DIR = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'Interfacer'.DIRECTORY_SEPARATOR;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		date_default_timezone_set('Europe/Berlin');
	}
	
	public static function  tearDownAfterClass()
	{
		date_default_timezone_set('UTC');
	}
	
	public function testMlformedData()
	{
		$data = "foo\n  bar";
		$interfacer = new XMLInterfacer();
		$this->assertNull($interfacer->fromString($data));
		
		$interfacer = new StdObjectInterfacer('json');
		$this->assertNull($interfacer->fromString($data));
		
		$interfacer = new AssocArrayInterfacer('yaml');
		$this->assertNull($interfacer->fromString($data));
	}
	
	/**
	 *
	 * @dataProvider mediaTypeData
	 */
	public function testMediaType($format, $mediaType, $class)
	{
		$interfacer = new $class($format);
		$this->assertEquals($mediaType, $interfacer->getMediaType());
		$interfacer = new $class($mediaType);
		$this->assertEquals($mediaType, $interfacer->getMediaType());
		$interfacer = Interfacer::getInstance($format, $class === AssocArrayInterfacer::class);
		$this->assertEquals($mediaType, $interfacer->getMediaType());
		$this->assertInstanceOf($class, $interfacer);
	}
	
	
	public function mediaTypeData() {
		return [
			['json', 'application/json', AssocArrayInterfacer::class],
			['json', 'application/json', StdObjectInterfacer::class],
			['yaml', 'application/x-yaml', AssocArrayInterfacer::class],
			['yaml', 'application/x-yaml', StdObjectInterfacer::class],
			['xml', 'application/xml', XMLInterfacer::class]
		];
	}
	
	public function testPreferences()
	{
		$interfacer = new StdObjectInterfacer();
		$this->assertEquals('application/json', $interfacer->getMediaType());
		$this->assertEquals(false, $interfacer->isPrivateContext());
		$this->assertEquals(false, $interfacer->isSerialContext());
		$this->assertEquals('Europe/Berlin', $interfacer->getDateTimeZone()->getName());
		$this->assertEquals('c', $interfacer->getDateTimeFormat());
		$this->assertEquals(false, $interfacer->hasToExportOnlyUpdatedValues());
		$this->assertEquals(null, $interfacer->getPropertiesFilter());
		$this->assertEquals(false, $interfacer->hasToFlattenValues());
		$this->assertEquals(true, $interfacer->hasToFlagValuesAsUpdated());
		$this->assertEquals(true, $interfacer->hasToFlagObjectAsLoaded());
		$this->assertEquals(Interfacer::MERGE, $interfacer->getMergeType());
		
		$interfacer->setPreferences([
			Interfacer::PRIVATE_CONTEXT        => true,
			Interfacer::SERIAL_CONTEXT         => true,
			Interfacer::DATE_TIME_ZONE         => 'Pacific/Tahiti',
			Interfacer::DATE_TIME_FORMAT       => 'Y-m-d H:i:s',
			Interfacer::ONLY_UPDATED_VALUES    => true,
			Interfacer::PROPERTIES_FILTERS     => ['haha', 'hoho'],
			Interfacer::FLATTEN_VALUES         => true,
			Interfacer::FLAG_VALUES_AS_UPDATED => false,
			Interfacer::FLAG_OBJECT_AS_LOADED  => false,
			Interfacer::MERGE_TYPE             => Interfacer::OVERWRITE
		]);
		
		$this->assertEquals(true, $interfacer->isPrivateContext());
		$this->assertEquals(true, $interfacer->isSerialContext());
		$this->assertEquals('Pacific/Tahiti', $interfacer->getDateTimeZone()->getName());
		$this->assertEquals('Y-m-d H:i:s', $interfacer->getDateTimeFormat());
		$this->assertEquals(true, $interfacer->hasToExportOnlyUpdatedValues());
		$this->assertEquals('["haha","hoho"]', json_encode($interfacer->getPropertiesFilter()));
		$this->assertEquals(true, $interfacer->hasToFlattenValues());
		$this->assertEquals(false, $interfacer->hasToFlagValuesAsUpdated());
		$this->assertEquals(false, $interfacer->hasToFlagObjectAsLoaded());
		$this->assertEquals(Interfacer::OVERWRITE, $interfacer->getMergeType());
	}
	
	/**
	 *
	 * @dataProvider flattenData
	 */
	public function testFlatten($interfacer, $flatten, $notFlatten)
	{
		$node = $interfacer->createNode('root');
		$createdNode = $interfacer->createNode('object');
		$interfacer->setValue($createdNode, 'value', 'prop');
		$interfacer->setValue($createdNode, 'value_node', 'prop_node', true);
		$interfacer->setValue($node, $createdNode, 'object');
		$interfacer->setValue($node, 'root_value', 'root_prop');
		
		$nodeArray = $interfacer->createArrayNode('array');
		$interfacer->addValue($nodeArray, 'value1', 'element');
		$interfacer->addValue($nodeArray, 'value2', 'element');
		
		$createdNode = $interfacer->createNode('element');
		$interfacer->setValue($createdNode, 123, 'object_element_prop');
		$interfacer->setValue($createdNode, 123, 'object_element_node', true);
		$interfacer->addValue($nodeArray, $createdNode);
		
		$interfacer->setValue($node, $nodeArray, 'array');
		$interfacer->flattenNode($node, 'array');
		
		$this->assertEquals($flatten, $interfacer->toString($node));
		$interfacer->unflattenNode($node, 'array');
		$this->assertEquals($notFlatten, $interfacer->toString($node));
		$interfacer->flattenNode($node, 'array');
		$this->assertEquals($flatten, $interfacer->toString($node));
	}
	
	public function flattenData() {
		return [
			[
				new StdObjectInterfacer(),
				'{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}',
				'{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}'
			],
			[
				new AssocArrayInterfacer(),
				'{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}',
				'{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}'
			],
			[
				new XMLInterfacer(),
				'<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>',
				'<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array><element>value1</element><element>value2</element><element object_element_prop="123"><object_element_node>123</object_element_node></element></array></root>'
			]
		];
	}
	
	/**
	 * 
	 * @dataProvider jsonData
	 */
	public function testInterOperability($json, $expectedYaml, $expectedXml, $isArray)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
		if ($isArray) {
			$model = new ModelArray($model, false, $model->getShortName());
		}
		$jsonAssocInterfacer = new AssocArrayInterfacer('json');
		$yamlStdInterfacer = new StdObjectInterfacer('yaml');
		$xmlInterfacer = new XMLInterfacer();
		
		$object = $model->import($jsonAssocInterfacer->fromString($json), $jsonAssocInterfacer);
		
		$yaml = $yamlStdInterfacer->toString($yamlStdInterfacer->export($object));
		$this->assertEquals($expectedYaml, $yaml);
		$object2 = $model->import($yamlStdInterfacer->fromString($yaml), $yamlStdInterfacer);
		
		$xml = $xmlInterfacer->toString($xmlInterfacer->export($object2));
		$object3 = $model->import($xmlInterfacer->fromString($xml), $xmlInterfacer);
		
		$newJson = $jsonAssocInterfacer->toString($jsonAssocInterfacer->export($object3));
		$this->assertEquals($json, $newJson);
	}
	
	public function jsonData() {
		return [
			[
				'{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"null","plop2":"true"},"objectWithId":{"plop":"plop","plop2":"~"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}',
				file_get_contents(self::DATA_DIR.'object.yaml'),
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="null" plop2="true"/><objectWithId plop="plop" plop2="~"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" inheritance-="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" inheritance-="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" inheritance-="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" inheritance-="Test\TestDb\ObjectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></root>',
				false
			],
			[
				'[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"objectWithId":null,"integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","inheritance-":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"objectWithId":null,"integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}]',
				file_get_contents(self::DATA_DIR.'array.yaml'),
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><TestDb defaultValue="default" id1="1" id2="23" date="2016-05-01T14:53:54+02:00" timestamp="2016-10-16T21:50:19+02:00" integer="0" boolean="0" boolean2="1"><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb>1</mainParentTestDb><objectsWithId/><foreignObjects/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb><TestDb defaultValue="default" id1="1" id2="50" date="2016-10-16T20:21:18+02:00" timestamp="2016-10-16T21:50:19+02:00" integer="1" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><objectWithId plop="plop" plop2="plop2222"/><mainParentTestDb>1</mainParentTestDb><objectsWithId/><foreignObjects/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb><TestDb defaultValue="default" id1="1" id2="101" date="2016-04-13T09:14:33+02:00" timestamp="2016-10-16T21:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId/><foreignObjects/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb><TestDb defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" inheritance-="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" inheritance-="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" inheritance-="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" inheritance-="Test\TestDb\ObjectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" inheritance-="Test\TestDb\ObjectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb><TestDb defaultValue="default" id1="2" id2="50" date="2016-05-01T23:37:18+02:00" timestamp="2016-10-16T21:50:19+02:00" integer="3" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><objectWithId plop="plop" plop2="plop2222"/><mainParentTestDb>1</mainParentTestDb><objectsWithId/><foreignObjects/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb><TestDb defaultValue="default" id1="2" id2="102" date="2016-04-01T08:00:00+02:00" timestamp="2016-10-16T18:21:18+02:00" integer="4" boolean="0" boolean2="1"><object plop="plop10" plop2="plop20"/><objectWithId xsi:nil="true"/><mainParentTestDb>1</mainParentTestDb><objectsWithId/><foreignObjects/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/></TestDb></root>',
				true
			]
		];
	}
	
}
