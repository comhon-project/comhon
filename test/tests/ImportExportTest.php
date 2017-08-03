<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as Object;
use Comhon\Object\Object as FinalObject;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Object\ObjectArray;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);

$stdPublicInterfacer = new StdObjectInterfacer();
$stdPublicInterfacer->setPrivateContext(false);

$stdSerialInterfacer = new StdObjectInterfacer();
$stdSerialInterfacer->setPrivateContext(true);
$stdSerialInterfacer->setSerialContext(true);

$xmlPrivateInterfacer = new XMLInterfacer();
$xmlPrivateInterfacer->setPrivateContext(true);

$xmlPublicInterfacer= new XMLInterfacer();
$xmlPublicInterfacer->setPrivateContext(false);

$xmlSerialInterfacer = new XMLInterfacer();
$xmlSerialInterfacer->setPrivateContext(true);
$xmlSerialInterfacer->setSerialContext(true);

$flattenArrayPrivateInterfacer = new AssocArrayInterfacer();
$flattenArrayPrivateInterfacer->setPrivateContext(true);
$flattenArrayPrivateInterfacer->setFlattenValues(true);

$flattenArrayPublicInterfacer = new AssocArrayInterfacer();
$flattenArrayPublicInterfacer->setPrivateContext(false);
$flattenArrayPublicInterfacer->setFlattenValues(true);

$flattenArraySerialInterfacer = new AssocArrayInterfacer();
$flattenArraySerialInterfacer->setPrivateContext(true);
$flattenArraySerialInterfacer->setFlattenValues(true);
$flattenArraySerialInterfacer->setSerialContext(true);

$stdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$stdPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$stdSerialInterfacer->setMergeType(Interfacer::NO_MERGE);
$xmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$xmlPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$xmlSerialInterfacer->setMergeType(Interfacer::NO_MERGE);
$flattenArrayPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$flattenArrayPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$flattenArraySerialInterfacer->setMergeType(Interfacer::NO_MERGE);

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$objectTestDb = $dbTestModel->loadObject('[1,"1501774389"]');

$copiedObject = new FinalObject('testDb');
foreach ($objectTestDb->getValues() as $key => $value) {
	$copiedObject->setValue($key, $value);
}

$privateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}';
$publicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}';
$serializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}';
$serializedXML    = '<testDb xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="testDb\objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="testDb\objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="testDb\objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="testDb\objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="testDb\objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></testDb>';
$sqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}","lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}';

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

$newObject = new FinalObject('testDb');
try {
	$dbTestModel->fillObject($newObject, $copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
	$throw = true;
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('instance with same id already exists');
}

$newObject = $objectTestDb;
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdPublicInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
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
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlPublicInterfacer), $xmlPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($xmlPrivateInterfacer), $xmlPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
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
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$dbTestModel->fillObject($newObject, $copiedObject->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (json_encode($newObject->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad public object value');
}
$newObject->reset();
$newObject->setValue('defaultValue', 'plop');
$newObject->fill($copiedObject->export($stdSerialInterfacer), $stdSerialInterfacer);
if (json_encode($newObject->export($stdPublicInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad serial object value');
}

$stdObj = json_decode($privateStdObject);
$stdObj->id1 = 65498;
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $stdObj, $stdPrivateInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[65498,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$XML = simplexml_load_string('<testDb default_value="default" id_1="1111" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="testDb\objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="testDb\objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="testDb\objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="testDb\objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="testDb\objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>');
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $XML, $xmlSerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1111,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$array = json_decode($sqlArray, true);
$array['id_1'] = 1456;
$newObject = $dbTestModel->getObjectInstance();
$dbTestModel->fillObject($newObject, $array, $flattenArraySerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1456,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

/** ******************************************************************************* **/
/**                                test object array                                **/
/** ******************************************************************************* **/

$testDb = $dbTestModel->loadObject('[1,"50"]');
$mainParentTestDb = $testDb->getValue('mainParentTestDb');

/** @var ObjectArray $testDbs */
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
$testDbs->setValues($orderedTestDbs);

$privateStdObject = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"objectWithId":null,"string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","mainParentTestDb":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"objectWithId":null,"string":"eeee","integer":4,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}]';
$publicStdObject  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"objectWithId":null,"integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","mainParentTestDb":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"objectWithId":null,"integer":4,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}]';
$serializedObject = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"object_with_id":null,"string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","main_test_id":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"object_with_id":null,"string":"eeee","integer":4,"objects_with_id":[],"foreign_objects":[],"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}]';
$serializedXML    = '<testDb xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><childTestDb default_value="default" id_1="1" id_2="23" date="2016-05-01T14:53:54+02:00" timestamp="2016-10-16T21:50:19+02:00" string="aaaa" integer="0" boolean="0" boolean2="1"><object xsi:nil="true"/><object_with_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="1" id_2="50" date="2016-10-16T20:21:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="bbbb" integer="1" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="101" date="2016-04-13T09:14:33+02:00" timestamp="2016-10-16T21:50:19+02:00" string="cccc" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="testDb\objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="testDb\objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="testDb\objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="testDb\objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="testDb\objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="testDb\objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb><childTestDb default_value="default" id_1="2" id_2="50" date="2016-05-01T23:37:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="dddd" integer="3" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="2" id_2="102" date="2016-04-01T08:00:00+02:00" timestamp="2016-10-16T18:21:18+02:00" string="eeee" integer="4" boolean="0" boolean2="1"><main_test_id>1</main_test_id><object plop="plop10" plop2="plop20"/><object_with_id xsi:nil="true"/><objects_with_id/><foreign_objects/><lonely_foreign_object xsi:nil="true"/><lonely_foreign_object_two xsi:nil="true"/><man_body_json_id xsi:nil="true"/><woman_xml_id xsi:nil="true"/></childTestDb></testDb>';
$sqlArray         = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":null,"object_with_id":null,"string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"bbbb","integer":1,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"cccc","integer":2,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"testDb\\\\\\\\objectWithIdAndMore\"}","lonely_foreign_object_two":"11","man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"dddd","integer":3,"lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","main_test_id":1,"date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":"{\"plop\":\"plop10\",\"plop2\":\"plop20\"}","object_with_id":null,"string":"eeee","integer":4,"objects_with_id":"[]","foreign_objects":"[]","lonely_foreign_object":null,"lonely_foreign_object_two":null,"man_body_json_id":null,"woman_xml_id":null,"boolean":false,"boolean2":true}]';

$copiedObjectArray = new ObjectArray($dbTestModel, true, 'childTestDb');
$modelArrayDbTest = $copiedObjectArray->getModel();

foreach ($testDbs as $objectTestDb) {
	$copiedObject = new FinalObject('testDb');
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
	$copiedObjectArray->pushValue($copiedObject);
}

/** ****************************** test stdObject ****************************** **/

if (!compareJson(json_encode($copiedObjectArray->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($copiedObjectArray->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($stdPublicInterfacer), $stdPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedObjectArray->export($stdSerialInterfacer)), $serializedObject)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($stdSerialInterfacer), $stdSerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($stdPublicInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($stdPrivateInterfacer), $stdPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

function resetValues(ObjectArray $objectArray) {
	foreach ($objectArray as $object) {
		$id = $object->getId();
		$object->reset();
		$object->setId($id, false);
	}
}

/** @var ObjectArray $newObject */
$newObject = $testDbs;
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($stdPublicInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value : '.json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)));
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlPublicInterfacer), $xmlPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlSerialInterfacer->toString($copiedObjectArray->export($xmlSerialInterfacer)), $serializedXML)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlSerialInterfacer), $xmlSerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlPublicInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($xmlPrivateInterfacer), $xmlPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($xmlPublicInterfacer), $xmlPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($xmlPrivateInterfacer), $xmlPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($xmlSerialInterfacer), $xmlSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer)->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($copiedObjectArray->export($flattenArraySerialInterfacer)), $sqlArray)) {
	throw new \Exception('bad serial object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer)->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($flattenArrayPublicInterfacer), $flattenArrayPrivateInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($modelArrayDbTest->import($copiedObjectArray->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer)->export($stdPrivateInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}

resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$modelArrayDbTest->fillObject($newObject, $copiedObjectArray->export($flattenArraySerialInterfacer), $flattenArraySerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($newObject->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad private object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdPrivateInterfacer), $stdPublicInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad public object value');
}
resetValues($newObject);
$newObject->fill($copiedObjectArray->export($stdSerialInterfacer), $stdSerialInterfacer);
if (!compareJson(json_encode($newObject->export($stdPublicInterfacer)), $publicStdObject)) {
	throw new \Exception('bad serial object value');
}

/********************************** test aggregation export *************************************/

$mainTestDb = MainObjectCollection::getInstance()->getObject(2, 'mainTestDb');
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

$testPrivateIdModel = ModelManager::getInstance()->getInstanceModel('testPrivateId');
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
if (json_encode($testPrivateId->export($stdSerialInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad serial object value : '.json_encode($testPrivateId->export($stdPrivateInterfacer)));
}
if (json_encode($testPrivateId->export($stdPublicInterfacer)) !== '{"name":"test 1","objectValues":[{"id2":2,"propertyOne":"azeaze1"},{"id2":20,"propertyOne":"azeaze10"},{"id2":200,"propertyOne":"azeaze100"}]}') {
	throw new \Exception('bad public object value : '.json_encode($testPrivateId->export($stdPublicInterfacer)));
}
if (json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($stdPrivateInterfacer)));
}
if (!compareJson(json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPublicInterfacer)->export($stdPrivateInterfacer)), '{"name":"test 1","objectValues":[{"id1":null,"id2":2,"propertyOne":"azeaze1"},{"id1":null,"id2":20,"propertyOne":"azeaze10"},{"id1":null,"id2":200,"propertyOne":"azeaze100"}]}')) {
	throw new \Exception('bad public object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($stdPrivateInterfacer), $stdPublicInterfacer)->export($stdPrivateInterfacer)));
}


$privateFlattenedArray = '{"id":"1","name":"test 1","objectValues":"[{\"id1\":1,\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id1\":10,\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id1\":100,\"id2\":200,\"propertyOne\":\"azeaze100\"}]","foreignObjectValue":"[1,2]","foreignObjectValues":"[\"[10,20]\",\"[100,200]\"]","foreignTestPrivateId":"2","foreignTestPrivateIds":"[\"3\",\"1\"]"}';
if (json_encode($testPrivateId->export($flattenArrayPrivateInterfacer)) !== $privateFlattenedArray) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateId->export($flattenArrayPrivateInterfacer)));
}
if (json_encode($testPrivateId->export($flattenArrayPublicInterfacer)) !== '{"name":"test 1","objectValues":"[{\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id2\":200,\"propertyOne\":\"azeaze100\"}]"}') {
	throw new \Exception('bad public object value : '.json_encode($testPrivateId->export($flattenArrayPublicInterfacer)));
}
if (!compareJson(json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($flattenArrayPrivateInterfacer)), $privateFlattenedArray)) {
	throw new \Exception('bad private object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer)->export($flattenArrayPrivateInterfacer)));
}
if (!compareJson(json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer)->export($flattenArrayPrivateInterfacer)), '{"name":"test 1","objectValues":"[{\"id1\":null,\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id1\":null,\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id1\":null,\"id2\":200,\"propertyOne\":\"azeaze100\"}]"}')) {
	throw new \Exception('bad public object value : '.json_encode($testPrivateIdModel->import($testPrivateId->export($flattenArrayPrivateInterfacer), $flattenArrayPublicInterfacer)->export($flattenArrayPrivateInterfacer)));
}


$privateXml = '<testPrivateId id="1" name="test 1"><objectValues><objectValue id1="1" id2="2" propertyOne="azeaze1"/><objectValue id1="10" id2="20" propertyOne="azeaze10"/><objectValue id1="100" id2="200" propertyOne="azeaze100"/></objectValues><foreignObjectValue>[1,2]</foreignObjectValue><foreignObjectValues><foreignObjectValue>[10,20]</foreignObjectValue><foreignObjectValue>[100,200]</foreignObjectValue></foreignObjectValues><foreignTestPrivateId>2</foreignTestPrivateId><foreignTestPrivateIds><foreignTestPrivateId>3</foreignTestPrivateId><foreignTestPrivateId>1</foreignTestPrivateId></foreignTestPrivateIds></testPrivateId>';
if (!compareXML($xmlPrivateInterfacer->toString($testPrivateId->export($xmlPrivateInterfacer)), $privateXml)) {
	throw new \Exception('bad private object value');
}
if (!compareXML($xmlPublicInterfacer->toString($testPrivateId->export($xmlPublicInterfacer)), '<testPrivateId name="test 1"><objectValues><objectValue id2="2" propertyOne="azeaze1"/><objectValue id2="20" propertyOne="azeaze10"/><objectValue id2="200" propertyOne="azeaze100"/></objectValues></testPrivateId>')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($testPrivateIdModel->import($testPrivateId->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($xmlPrivateInterfacer)), $privateXml)) {
	throw new \Exception('bad private object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($testPrivateIdModel->import($testPrivateId->export($xmlPrivateInterfacer), $xmlPublicInterfacer)->export($xmlPrivateInterfacer)), '<testPrivateId name="test 1"><objectValues><objectValue id1="xsi:nil" id2="2" propertyOne="azeaze1"/><objectValue id1="xsi:nil" id2="20" propertyOne="azeaze10"/><objectValue id1="xsi:nil" id2="200" propertyOne="azeaze100"/></objectValues></testPrivateId>')) {
	throw new \Exception('bad public object value');
}

/** ************************************** test node/attribute xml ********************************************* **/

$testXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$testXml = $testXmlModel->loadObject('plop2');

if (!compareXML($xmlPrivateInterfacer->toString($testXml->export($xmlPrivateInterfacer)), '<testXml textAttribute="attribute"><name>plop2</name><textNode>node</textNode><objectValue id="1" propertyOne="plop1" propertyTwo="plop11"/><objectValues><objectValue id="2" propertyOne="plop2" propertyTwo="plop22"/><objectValue id="3" propertyOne="plop3" propertyTwo="plop33"/></objectValues><objectContainer><foreignObjectValue>3</foreignObjectValue><objectValueTwo id="1" propertyTwoOne="2plop1"/><person id="1" firstName="Bernard" lastName="Dupond"><birthPlace>2</birthPlace><children><child id="5" __inheritance__="man"/><child id="6" __inheritance__="man"/></children></person></objectContainer><foreignObjectValues><foreignObjectValue>1</foreignObjectValue><foreignObjectValue>2</foreignObjectValue></foreignObjectValues></testXml>')) {
	throw new \Exception('bad value');
}

$testXml1 = $testXmlModel->getObjectInstance();
$testXml1->setValue('name', null);
$testXml1->setValue('textNode', '');
$domNode1 = $testXml1->export($xmlPrivateInterfacer);
$xml1     = $xmlPrivateInterfacer->toString($domNode1);

if (!compareXML($xml1, '<testXml xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><name xsi:nil="true"/><textNode></textNode></testXml>')) {
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
$objectTestDb->setValue('childrenTestDb', null);
$objectTestDb->setValue('objectsWithId', null);

if (!compareJson($stdPrivateInterfacer->toString($stdPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"childrenTestDb":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":null,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":null}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($xmlPrivateInterfacer->export($objectTestDb)), '<testDb xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><childrenTestDb xsi:nil="true"/><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects xsi:nil="true"/><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId xsi:nil="true"/></testDb>')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($flattenArrayPrivateInterfacer->toString($flattenArrayPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"childrenTestDb":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":null,"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":null}')) {
	throw new \Exception('bad public object value');
}


$objectsWithId = $objectTestDb->initValue('objectsWithId');
$objectWithId = $objectTestDb->getProperty('objectWithId')->getModel()->getObjectInstance();
$objectsWithId->pushValue($objectWithId);
$objectsWithId->pushValue(null);
$objectsWithId->pushValue($objectWithId);

$foreignObjects = $objectTestDb->initValue('foreignObjects');
$objectWithId = $objectTestDb->getProperty('objectWithId')->getModel()->getObjectInstance();
$objectWithId->setId('12');
$foreignObjects->pushValue(null);
$foreignObjects->pushValue($objectWithId);

if (!compareJson($stdPrivateInterfacer->toString($stdPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"childrenTestDb":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":[null,"12"],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":[[],null,[]]}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($xmlPrivateInterfacer->export($objectTestDb)), '<testDb xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><childrenTestDb xsi:nil="true"/><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects><foreignObject xsi:nil="true"/><foreignObject>12</foreignObject></foreignObjects><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId><objectWithId/><objectWithId xsi:nil="true"/><objectWithId/></objectsWithId></testDb>')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($flattenArrayPrivateInterfacer->toString($flattenArrayPrivateInterfacer->export($objectTestDb)), '{"defaultValue":null,"childrenTestDb":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":"[null,\"12\"]","lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":"[[],null,[]]"}')) {
	throw new \Exception('bad public object value');
}
if (!compareJson($stdPrivateInterfacer->toString($dbTestModel->import($objectTestDb->export($xmlPrivateInterfacer), $xmlPrivateInterfacer)->export($stdPrivateInterfacer)), '{"defaultValue":null,"childrenTestDb":null,"id1":null,"id2":null,"date":null,"timestamp":null,"object":null,"objectWithId":null,"string":null,"integer":null,"mainParentTestDb":null,"foreignObjects":[null,"12"],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"notSerializedValue":null,"notSerializedForeignObject":null,"boolean":null,"boolean2":null,"objectsWithId":[[],null,[]]}')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($dbTestModel->import($objectTestDb->export($stdPrivateInterfacer), $stdPrivateInterfacer)->export($xmlPrivateInterfacer)), '<testDb xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="xsi:nil" id1="xsi:nil" id2="xsi:nil" date="xsi:nil" timestamp="xsi:nil" string="xsi:nil" integer="xsi:nil" notSerializedValue="xsi:nil" boolean="xsi:nil" boolean2="xsi:nil"><childrenTestDb xsi:nil="true"/><object xsi:nil="true"/><objectWithId xsi:nil="true"/><mainParentTestDb xsi:nil="true"/><foreignObjects><foreignObject xsi:nil="true"/><foreignObject>12</foreignObject></foreignObjects><lonelyForeignObject xsi:nil="true"/><lonelyForeignObjectTwo xsi:nil="true"/><manBodyJson xsi:nil="true"/><womanXml xsi:nil="true"/><notSerializedForeignObject xsi:nil="true"/><objectsWithId><objectWithId/><objectWithId xsi:nil="true"/><objectWithId/></objectsWithId></testDb>')) {
	throw new \Exception('bad public object value');
}

$time_end = microtime(true);
var_dump('import export test exec time '.($time_end - $time_start));