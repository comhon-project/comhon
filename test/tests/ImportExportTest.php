<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as FinalObject;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Object\ComhonArray;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Model\ModelArray;

$time_start = microtime(true);

function testExportPrivateIdPublicContext($comhonObject, $publicInterfacer, $exceptionProperties) {
	$throw = true;
	try {
		$comhonObject->export($publicInterfacer);
	} catch (ExportException $e) {
		if (strpos($e->getMessage(), 'Cannot interface foreign value with private id in public context') === false) {
			throw new \Exception('bad exception message : ' . $e->getMessage());
		}
		if ($e->getStringifiedProperties() !== $exceptionProperties) {
			throw new \Exception('bad exception properties : ' . $e->getStringifiedProperties() . " != $exceptionProperties");
		}
		$throw = false;
	}
	if ($throw) {
		throw new \Exception('export foreign value with private id should failed');
	}
}

function testImportPrivateIdPublicContext($model, $interfacedPrivateObject, $publicInterfacer, $exceptionProperties) {
	try {
		$throw = true;
		$model->import($interfacedPrivateObject, $publicInterfacer);
	} catch (ImportException $e) {
		if (strpos($e->getMessage(), 'Cannot interface foreign value with private id in public context') === false) {
			throw new \Exception('bad exception message : ' . $e->getMessage());
		}
		if ($e->getStringifiedProperties() !== $exceptionProperties) {
			throw new \Exception('bad exception properties : ' . $e->getStringifiedProperties() . " != $exceptionProperties");
		}
		$throw = false;
	}
	if ($throw) {
		throw new \Exception('import foreign value with private id should failed');
	}
}

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);
$stdPrivateInterfacer->setVerifyReferences(false);

$stdPublicInterfacer = new StdObjectInterfacer();
$stdPublicInterfacer->setPrivateContext(false);
$stdPublicInterfacer->setVerifyReferences(false);

$stdSerialInterfacer = new StdObjectInterfacer();
$stdSerialInterfacer->setPrivateContext(true);
$stdSerialInterfacer->setSerialContext(true);
$stdSerialInterfacer->setVerifyReferences(false);

$xmlPrivateInterfacer = new XMLInterfacer();
$xmlPrivateInterfacer->setPrivateContext(true);
$xmlPrivateInterfacer->setVerifyReferences(false);

$xmlPublicInterfacer= new XMLInterfacer();
$xmlPublicInterfacer->setPrivateContext(false);
$xmlPublicInterfacer->setVerifyReferences(false);

$xmlSerialInterfacer = new XMLInterfacer();
$xmlSerialInterfacer->setPrivateContext(true);
$xmlSerialInterfacer->setSerialContext(true);
$xmlSerialInterfacer->setVerifyReferences(false);

$flattenArrayPrivateInterfacer = new AssocArrayInterfacer();
$flattenArrayPrivateInterfacer->setPrivateContext(true);
$flattenArrayPrivateInterfacer->setFlattenValues(true);
$flattenArrayPrivateInterfacer->setVerifyReferences(false);

$flattenArrayPublicInterfacer = new AssocArrayInterfacer();
$flattenArrayPublicInterfacer->setPrivateContext(false);
$flattenArrayPublicInterfacer->setFlattenValues(true);
$flattenArrayPublicInterfacer->setVerifyReferences(false);

$flattenArraySerialInterfacer = new AssocArrayInterfacer();
$flattenArraySerialInterfacer->setPrivateContext(true);
$flattenArraySerialInterfacer->setFlattenValues(true);
$flattenArraySerialInterfacer->setSerialContext(true);
$flattenArraySerialInterfacer->setVerifyReferences(false);

$stdPrivateInterfacer->setMergeType(Interfacer::OVERWRITE);
$stdPublicInterfacer->setMergeType(Interfacer::OVERWRITE);
$stdSerialInterfacer->setMergeType(Interfacer::OVERWRITE);
$xmlPrivateInterfacer->setMergeType(Interfacer::OVERWRITE);
$xmlPublicInterfacer->setMergeType(Interfacer::OVERWRITE);
$xmlSerialInterfacer->setMergeType(Interfacer::OVERWRITE);
$flattenArrayPrivateInterfacer->setMergeType(Interfacer::OVERWRITE);
$flattenArrayPublicInterfacer->setMergeType(Interfacer::OVERWRITE);
$flattenArraySerialInterfacer->setMergeType(Interfacer::OVERWRITE);

$dbTestModel = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
$objectTestDb = $dbTestModel->loadObject('[1,"1501774389"]', null, true);

$copiedObject = new FinalObject('Test\TestDb');
foreach ($objectTestDb->getValues() as $key => $value) {
	$copiedObject->setValue($key, $value);
}

$privateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}';
$publicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}';
$serializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}';
$serializedXML    = '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></root>';
$sqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}","lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}';

/** ****************************** test stdObject ****************************** **/

if (!compareJson(json_encode($copiedObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (json_encode($copiedObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($stdPublicInterfacer), $stdPublicInterfacer)->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedObject->export($stdSerialInterfacer)), $serializedObject)) {
	throw new \Exception('bad serial object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($stdPublicInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($stdPrivateInterfacer), $stdPublicInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}

$newObject = new FinalObject('Test\TestDb');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);

$newObject = $objectTestDb;
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdPublicInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/

if (json_encode($dbTestModel->import($copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($dbTestModel->import($copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)));
}
if (json_encode($dbTestModel->import($copiedObject->export($xmlPublicInterfacer), $xmlPublicInterfacer)->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlSerialInterfacer->toString($copiedObject->export($xmlSerialInterfacer)), $serializedXML)) {
	throw new \Exception('bad serial object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($xmlSerialInterfacer), $xmlSerialInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($xmlPublicInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($xmlPrivateInterfacer), $xmlPublicInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlPublicInterfacer), $xmlPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($xmlPrivateInterfacer), $xmlPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (json_encode($dbTestModel->import($copiedObject->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer)->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedObject->export($flattenArraySerialInterfacer)), $sqlArray)) {
	throw new \Exception('bad serial object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($flattenArrayPublicInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($dbTestModel->import($copiedObject->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer)->export($stdPrivateInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setIsLoaded(true);
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}

$stdObj = json_decode($privateStdObject);
$stdObj->id1 = 65498;
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $stdObj, $stdPrivateInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[65498,"1501774389"]', 'Test\TestDb')) {
	throw new \Exception('object not added');
}

$XML = simplexml_load_string('<root default_value="default" id_1="1111" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></root>');
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $XML, $xmlSerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1111,"1501774389"]', 'Test\TestDb')) {
	throw new \Exception('object not added');
}

$array = json_decode($sqlArray, true);
$array['id_1'] = 1456;
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $array, $flattenArraySerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1456,"1501774389"]', 'Test\TestDb')) {
	throw new \Exception('object not added');
}

/** ******************************************************************************* **/
/**                                test object array                                **/
/** ******************************************************************************* **/

$testDb = $dbTestModel->loadObject('[1,"50"]');
$mainParentTestDb = $testDb->getValue('mainParentTestDb');

/** @var ComhonArray $testDbs */
$testDbs = $mainParentTestDb->getValue('childrenTestDb');
$orderedTestDbs = [];
foreach ($testDbs as $testDb) {
	switch ($testDb->getId()) {
		case '[1,"23"]': $orderedTestDbs[0] = $testDb; break;
		case '[1,"50"]': $orderedTestDbs[1] = $testDb; break;
		case '[1,"101"]': $orderedTestDbs[2] = $testDb; break;
		case '[1,"1501774389"]': $orderedTestDbs[3] = $testDb; break;
		case '[2,"50"]': $orderedTestDbs[4] = $testDb; break;
		case '[2,"102"]': $orderedTestDbs[5] = $testDb; break;
			
	}
}
ksort($orderedTestDbs);
$testDbs->reset();
$testDbs->setIsLoaded(true);
foreach ($orderedTestDbs as $orderedTestDb) {
	$testDbs->pushValue($orderedTestDb);
}

$privateStdObject = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"objectWithId":null,"string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","mainParentTestDb":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"objectWithId":null,"string":"eeee","integer":4,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}]';
$publicStdObject  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"objectWithId":null,"integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","mainParentTestDb":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"objectWithId":null,"integer":4,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}]';
$serializedObject = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"object_with_id":null,"string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","main_test_id":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"object_with_id":null,"string":"eeee","integer":4,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}]';
$serializedXML    = '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><childTestDb default_value="default" id_1="1" id_2="23" date="2016-05-01T14:53:54+02:00" timestamp="2016-10-16T21:50:19+02:00" string="aaaa" integer="0" boolean="0" boolean2="1"><object xsi:nil="true"/><object_with_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="1" id_2="50" date="2016-10-16T20:21:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="bbbb" integer="1" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="101" date="2016-04-13T09:14:33+02:00" timestamp="2016-10-16T21:50:19+02:00" string="cccc" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="2" id_2="50" date="2016-05-01T23:37:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="dddd" integer="3" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="2" id_2="102" date="2016-04-01T08:00:00+02:00" timestamp="2016-10-16T18:21:18+02:00" string="eeee" integer="4" boolean="0" boolean2="1"><main_test_id>1</main_test_id><object plop="plop10" plop2="plop20"/><object_with_id xsi:nil="true"/><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb></root>';
$sqlArray         = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"object_with_id":null,"string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"bbbb","integer":1,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"cccc","integer":2,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}","lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"dddd","integer":3,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","main_test_id":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":"{\"plop\":\"plop10\",\"plop2\":\"plop20\"}","object_with_id":null,"string":"eeee","integer":4,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}]';

$copiedComhonArray = new ComhonArray(
	new ModelArray($dbTestModel, false, 'childTestDb', [], [], true, true), 
	true
);
$modelArrayDbTest = $copiedComhonArray->getModel();

foreach ($testDbs as $objectTestDb) {
	$copiedObject = new FinalObject('Test\TestDb');
	foreach ($objectTestDb->getValues() as $key => $value) {
		$copiedObject->setValue($key, $value);
	}
	if ($copiedObject->getValue('id2') == 50) {
		$object1 = $copiedObject->getValue('objectsWithId');
		$copiedObject->unsetValue('objectsWithId');
		$object2 = $copiedObject->getValue('foreignObjects');
		$copiedObject->unsetValue('foreignObjects');
		$object3 = $copiedObject->getValue('mainParentTestDb');
		$copiedObject->unsetValue('mainParentTestDb');
		$boolean1 = $copiedObject->getValue('boolean');
		$copiedObject->unsetValue('boolean');
		$boolean2 = $copiedObject->getValue('boolean2');
		$copiedObject->unsetValue('boolean2');
		
		$copiedObject->setValue('mainParentTestDb', $object3);
		$copiedObject->setValue('objectsWithId', $object1);
		$copiedObject->setValue('foreignObjects', $object2);
		$copiedObject->setValue('boolean', $boolean1);
		$copiedObject->setValue('boolean2', $boolean2);
	}
	$copiedComhonArray->pushValue($copiedObject);
}

/** ****************************** test stdObject ****************************** **/

if (!compareJson(json_encode($copiedComhonArray->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($copiedComhonArray->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($stdPublicInterfacer), $stdPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedComhonArray->export($stdSerialInterfacer)), $serializedObject)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($stdSerialInterfacer), $stdSerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($stdPublicInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($stdPrivateInterfacer), $stdPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

function resetValues(ComhonArray $objectArray) {
	foreach ($objectArray as $object) {
		$id = $object->getId();
		$object->reset();
		$object->setIsLoaded(true);
		$object->setId($id, false);
	}
}

/** @var ComhonArray $newObject */
$newObject = $testDbs;
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($stdPublicInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value : '.json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)));
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlPublicInterfacer), $xmlPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlSerialInterfacer->toString($copiedComhonArray->export($xmlSerialInterfacer)), $serializedXML)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlSerialInterfacer), $xmlSerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlPublicInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($xmlPrivateInterfacer), $xmlPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($xmlPublicInterfacer), $xmlPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($xmlPrivateInterfacer), $xmlPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedComhonArray->export($flattenArraySerialInterfacer)), $sqlArray)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($flattenArrayPublicInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedComhonArray->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedComhonArray->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedComhonArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/********************************** test aggregation export *************************************/

$mainTestDb = MainObjectCollection::getInstance()->getObject(2, 'Test\MainTestDb');
$mainTestDb->initValue('childrenTestDb', false);
$mainTestDb->loadAggregationIds('childrenTestDb');
if (!isset($mainTestDb->export($stdPrivateInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion must be exported');
}
if (isset($mainTestDb->export($stdSerialInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
if (isset($mainTestDb->export($xmlSerialInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
$array = $mainTestDb->export($flattenArraySerialInterfacer);
if (isset($array['childrenTestDb'])) {
	throw new \Exception('compostion should not be exported');
}


/********************************** test foreign property with private id export *************************************/

$stdPrivateInterfacer->setMergeType(Interfacer::MERGE);
$stdPublicInterfacer->setMergeType(Interfacer::MERGE);
$stdSerialInterfacer->setMergeType(Interfacer::MERGE);
$xmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
$xmlPublicInterfacer->setMergeType(Interfacer::MERGE);
$xmlSerialInterfacer->setMergeType(Interfacer::MERGE);
$flattenArrayPrivateInterfacer->setMergeType(Interfacer::MERGE);
$flattenArrayPublicInterfacer->setMergeType(Interfacer::MERGE);
$flattenArraySerialInterfacer->setMergeType(Interfacer::MERGE);

$testPrivateIdModel = ModelManager::getInstance()->getInstanceModel('Test\TestPrivateId');
$testPrivateId = $testPrivateIdModel->getObjectInstance();
$testPrivateId->setValue('id', "1");
$testPrivateId->setValue('name', 'test 1');
$objs = $testPrivateId->initValue('objectValues');
$obj1 = $objs->getModel()->getModel()->getObjectInstance();
$obj1->setValue('id1', 1);
$obj1->setValue('id2', 2);
$obj1->setValue('propertyOne', 'azeaze1');
$objs->pushValue($obj1);
//--------------
$obj2 = $objs->getModel()->getModel()->getObjectInstance();
$obj2->setId(json_encode([10, 20]));
$obj2->setValue('propertyOne', 'azeaze10');
$objs->pushValue($obj2);
//--------------
$obj3 = $objs->getModel()->getModel()->getObjectInstance();
$obj3->setId(json_encode([100, 200]));
$obj3->setValue('propertyOne', 'azeaze100');
$objs->pushValue($obj3);
//--------------
$testPrivateId->setValue('foreignObjectValue', $obj1);
$foreignObjs = $testPrivateId->initValue('foreignObjectValues');
$foreignObjs->pushValue($obj2);
$foreignObjs->pushValue($obj3);
//--------------
$testPrivateId2 = $testPrivateIdModel->getObjectInstance();
$testPrivateId2->setValue('id', "2");
$testPrivateId2->setValue('name', 'test 3');
$testPrivateId3 = $testPrivateIdModel->getObjectInstance();
$testPrivateId3->setValue('id', "3");
$testPrivateId3->setValue('name', 'test 3');
$testPrivateId->setValue('foreignTestPrivateId', $testPrivateId2);
$foreignMainObjs = $testPrivateId->initValue('foreignTestPrivateIds');
$foreignMainObjs->pushValue($testPrivateId3);
$foreignMainObjs->pushValue($testPrivateId);

$privateStdObject = '{"id":"1","name":"test 1","objectValues":[{"id1":1,"id2":2,"propertyOne":"azeaze1"},{"id1":10,"id2":20,"propertyOne":"azeaze10"},{"id1":100,"id2":200,"propertyOne":"azeaze100"}],"foreignObjectValue":"[1,2]","foreignObjectValues":["[10,20]","[100,200]"],"foreignTestPrivateId":"2","foreignTestPrivateIds":["3","1"]}';
if (json_encode($testPrivateId->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateId->export($stdPrivateInterfacer)));
}

$serialStdObject = '{"id":"1","name":"test 1","object_values":[{"id1":1,"id2":2,"propertyOne":"azeaze1"},{"id1":10,"id2":20,"propertyOne":"azeaze10"},{"id1":100,"id2":200,"propertyOne":"azeaze100"}],"foreign_object_value":"[1,2]","foreign_object_values":["[10,20]","[100,200]"],"foreign_test_private_id":"2","foreign_test_private_ids":["3","1"]}';
if (json_encode($testPrivateId->export($stdSerialInterfacer)) !== $serialStdObject) {
	throw new \Exception('bad serial object value : '.json_encode($testPrivateId->export($stdSerialInterfacer)));
}

testExportPrivateIdPublicContext($testPrivateId, $stdPublicInterfacer, '.foreignObjectValue');

if (json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)));
}

testImportPrivateIdPublicContext($testPrivateIdModel, $testPrivateId->export($stdPrivateInterfacer), $stdPublicInterfacer, '.foreignObjectValue');

$privateFlattenedArray = '{"id":"1","name":"test 1","objectValues":"[{\"id1\":1,\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id1\":10,\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id1\":100,\"id2\":200,\"propertyOne\":\"azeaze100\"}]","foreignObjectValue":"[1,2]","foreignObjectValues":"[\"[10,20]\",\"[100,200]\"]","foreignTestPrivateId":"2","foreignTestPrivateIds":"[\"3\",\"1\"]"}';
if (json_encode($testPrivateId->export($flattenArrayPrivateInterfacer)) !== $privateFlattenedArray) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateId->export($flattenArrayPrivateInterfacer)));
}

testExportPrivateIdPublicContext($testPrivateId, $flattenArrayPublicInterfacer, '.foreignObjectValue');

if (!compareJson(json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($flattenArrayPrivateInterfacer)), $privateFlattenedArray)) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($flattenArrayPrivateInterfacer)));
}

testImportPrivateIdPublicContext($testPrivateIdModel, $testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer, '.foreignObjectValue');

$privateXml = '<root id="1" name="test 1"><objectValues><objectValue id1="1" id2="2" propertyOne="azeaze1"/><objectValue id1="10" id2="20" propertyOne="azeaze10"/><objectValue id1="100" id2="200" propertyOne="azeaze100"/></objectValues><foreignObjectValue>[1,2]</foreignObjectValue><foreignObjectValues><foreignObjectValue>[10,20]</foreignObjectValue><foreignObjectValue>[100,200]</foreignObjectValue></foreignObjectValues><foreignTestPrivateId>2</foreignTestPrivateId><foreignTestPrivateIds><foreignTestPrivateId>3</foreignTestPrivateId><foreignTestPrivateId>1</foreignTestPrivateId></foreignTestPrivateIds></root>';
if (!compareXML($xmlPrivateInterfacer->toString($testPrivateId->export($xmlPrivateInterfacer)), $privateXml)) {
	throw new \Exception('bad private object value');
}

testExportPrivateIdPublicContext($testPrivateId, $xmlPublicInterfacer, '.foreignObjectValue');

if (!compareXML($xmlPrivateInterfacer->toString($testPrivateIdModel->import($testPrivateId->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($xmlPrivateInterfacer)), $privateXml)) {
	throw new \Exception('bad private object value');
}

testImportPrivateIdPublicContext($testPrivateIdModel, $testPrivateId->export($xmlPrivateInterfacer), $xmlPublicInterfacer, '.foreignObjectValue');

/** ************************************** test node/attribute xml ********************************************* **/

$testXmlModel = ModelManager::getInstance()->getInstanceModel('Test\TestXml');
$testXml = $testXmlModel->loadObject('plop2');

if (!compareXML($xmlPrivateInterfacer->toString($testXml->export($xmlPrivateInterfacer)), '<root textAttribute="attribute"><name>plop2</name><textNode>node</textNode><objectValue id="1" propertyOne="plop1" propertyTwo="plop11"/><objectValues><objectValue id="2" propertyOne="plop2" propertyTwo="plop22"/><objectValue id="3" propertyOne="plop3" propertyTwo="plop33"/></objectValues><objectContainer><foreignObjectValue>3</foreignObjectValue><objectValueTwo id="1" propertyTwoOne="2plop1"/><person id="1" firstName="Bernard" lastName="Dupond"><birthPlace>2</birthPlace><children><child id="5" __inheritance__="Test\Person\Man"/><child id="6" __inheritance__="Test\Person\Man"/></children></person></objectContainer><foreignObjectValues><foreignObjectValue>1</foreignObjectValue><foreignObjectValue>2</foreignObjectValue></foreignObjectValues></root>')) {
	throw new \Exception('bad value');
}

$testXml1 = $testXmlModel->getObjectInstance();
$testXml1->setValue('name', null);
$testXml1->setValue('textNode', '');
$domNode1 = $testXml1->export($xmlPrivateInterfacer);
$xml1     = $xmlPrivateInterfacer->toString($domNode1);

if (!compareXML($xml1, '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><name xsi:nil="true"/><textNode></textNode></root>')) {
	throw new \Exception('bad value');
}

$testXml2 = $testXmlModel->getObjectInstance();
$testXml2->fill($domNode1, $xmlPrivateInterfacer);

if (!$testXml2->hasValue('name') || $testXml2->getValue('name') !== null) {
	throw new \Exception('bad value');
}
if ($testXml2->getValue('textNode') !== '') {
	throw new \Exception('bad value');
}
if ($xmlPrivateInterfacer->toString($testXml2->export($xmlPrivateInterfacer))!== $xml1) {
	throw new \Exception('bad value');
}

/** ************************************** test null values ********************************************* **/

$objectTestDb = $dbTestModel->getObjectInstance();
$objectTestDb->setValue('id1', null);
$objectTestDb->setValue('id2', null);
$objectTestDb->setValue('date', null);
$objectTestDb->setValue('timestamp', null);
$objectTestDb->setValue('object', null);
$objectTestDb->setValue('objectWithId', null);
$objectTestDb->setValue('string', null);
$objectTestDb->setValue('integer', null);
$objectTestDb->setValue('mainParentTestDb', null);
$objectTestDb->setValue('foreignObjects', null);
$objectTestDb->setValue('lonelyForeignObject', null);
$objectTestDb->setValue('lonelyForeignObjectTwo', null);
$objectTestDb->setValue('defaultValue', null);
$objectTestDb->setValue('manBodyJson', null);
$objectTestDb->setValue('womanXml', null);
$objectTestDb->setValue('notSerializedValue', null);
$objectTestDb->setValue('notSerializedForeignObject', null);
$objectTestDb->setValue('boolean', null);
$objectTestDb->setValue('boolean2', null);
$objectTestDb->unsetValue('childrenTestDb');
$objectTestDb->setValue('objectsWithId', null);

if (!compareJson($stdPrivateInterfacer->toString($stdPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":null,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":null}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($xmlPrivateInterfacer->export($objectTestDb)), '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects xsi:nil="true"/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId xsi:nil="true"/></root>')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($flattenArrayPrivateInterfacer->toString($flattenArrayPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":null,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":null}')) {
	throw new \Exception('bad public object value');
}


$objectsWithId = $objectTestDb->initValue('objectsWithId');
$objectWithId = $objectTestDb->getModel()->getProperty('objectWithId')->getModel()->getObjectInstance();
$objectsWithId->pushValue($objectWithId);
$objectsWithId->pushValue(null);
$objectsWithId->pushValue($objectWithId);

$foreignObjects = $objectTestDb->initValue('foreignObjects');
$objectWithId = $objectTestDb->getModel()->getProperty('objectWithId')->getModel()->getObjectInstance();
$objectWithId->setId('12');
$foreignObjects->pushValue(null);
$foreignObjects->pushValue($objectWithId);

if (!compareJson($stdPrivateInterfacer->toString($stdPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":[null,"12"],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":[[],null,[]]}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($xmlPrivateInterfacer->export($objectTestDb)), '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects><foreignObject xsi:nil="true"/><foreignObject>12</foreignObject></foreignObjects><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId><objectWithId/><objectWithId xsi:nil="true"/><objectWithId/></objectsWithId></root>')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($flattenArrayPrivateInterfacer->toString($flattenArrayPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":"[null,\"12\"]","lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":"[[],null,[]]"}')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($stdPrivateInterfacer->toString($dbTestModel->import($objectTestDb->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), '{"defaultValue":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":[null,"12"],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":[[],null,[]]}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($dbTestModel->import($objectTestDb->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($xmlPrivateInterfacer)), '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects><foreignObject xsi:nil="true"/><foreignObject>12</foreignObject></foreignObjects><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId><objectWithId/><objectWithId xsi:nil="true"/><objectWithId/></objectsWithId></root>')) {
	throw new \Exception('bad public object value');
}

$time_end = microtime(true);
var_dump('import export test exec time '.($time_end - $time_start));