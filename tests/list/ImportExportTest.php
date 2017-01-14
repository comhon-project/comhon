<?php

use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\api\ObjectService;
use comhon\object\object\SqlTable;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;
use comhon\object\model\Model;
use comhon\object\object\ObjectArray;
use comhon\object\model\ModelArray;

$time_start = microtime(true);

$lDbTestModel = InstanceModel::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,1501774389]');

$lCopiedObject = new Object('testDb');
foreach ($lObject->getValues() as $lKey => $lValue) {
	$lCopiedObject->setValue($lKey, $lValue);
}

$lPrivateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lPublicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lSerializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';
$lSerializedXML    = '<testDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreign_objects><lonely_foreign_object __inheritance__="objectWithIdAndMore">11</lonely_foreign_object><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>';
$lSqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';

/** ****************************** test stdObject ****************************** **/

if (json_encode($lCopiedObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lCopiedObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPrivateStdObject($lCopiedObject->toPrivateStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lDbTestModel->fromPublicStdObject($lCopiedObject->toPublicStdObject(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObject->toSerialStdObject()) !== $lSerializedObject) {
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObject->toSerialStdObject()));
}
if (json_encode($lDbTestModel->fromSerializedStdObject($lCopiedObject->toSerialStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPrivateStdObject($lCopiedObject->toPublicStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPublicStdObject($lCopiedObject->toPrivateStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

$lNewObject = new Object('testDb');
try {
	$lDbTestModel->fillObjectFromPrivateStdObject($lNewObject, $lCopiedObject->toPrivateStdObject());
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('instance with same id already exists');
}

$lNewObject = $lObject;
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPrivateStdObject($lNewObject, $lCopiedObject->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPublicStdObject($lNewObject, $lCopiedObject->toPublicStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromSerializedStdObject($lNewObject, $lCopiedObject->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPrivateStdObject($lCopiedObject->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPublicStdObject($lCopiedObject->toPrivateStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromSerializedStdObject($lCopiedObject->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/

if (json_encode($lDbTestModel->fromPrivateXml($lCopiedObject->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($lDbTestModel->fromPrivateXml($lCopiedObject->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()));
}
if (json_encode($lDbTestModel->fromPublicXml($lCopiedObject->toPublicXml(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (trim(str_replace("<?xml version=\"1.0\"?>", '', $lCopiedObject->toSerialXml()->asXML())) !== $lSerializedXML) {
	throw new \Exception('bad serial object value : '.str_replace("<?xml version=\"1.0\"?>\n", '', $lCopiedObject->toSerialXml()->asXML()));
}
if (json_encode($lDbTestModel->fromSerializedXml($lCopiedObject->toSerialXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPrivateXml($lCopiedObject->toPublicXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPublicXml($lCopiedObject->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPrivateXml($lNewObject, $lCopiedObject->toPrivateXml());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPublicXml($lNewObject, $lCopiedObject->toPublicXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromSerializedXml($lNewObject, $lCopiedObject->toSerialXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPrivateXml($lCopiedObject->toPrivateXml());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPublicXml($lCopiedObject->toPrivateXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromSerializedXml($lCopiedObject->toSerialXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (json_encode($lDbTestModel->fromPrivateFlattenedArray($lCopiedObject->toPrivateFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lDbTestModel->fromPublicFlattenedArray($lCopiedObject->toPublicFlattenedArray(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObject->toSqlDatabase()) !== $lSqlArray) {
	var_dump(json_encode($lCopiedObject->toSqlDatabase()));
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObject->toSqlDatabase()));
}
if (json_encode($lDbTestModel->fromSqlDatabase($lCopiedObject->toSqlDatabase(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPrivateFlattenedArray($lCopiedObject->toPublicFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->fromPublicFlattenedArray($lCopiedObject->toPrivateFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPrivateFlattenedArray($lNewObject, $lCopiedObject->toPrivateFlattenedArray());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectFromPublicFlattenedArray($lNewObject, $lCopiedObject->toPublicFlattenedArray());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObjectfromSqlDatabase($lNewObject, $lCopiedObject->toSqlDatabase());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPrivateStdObject($lCopiedObject->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromPublicStdObject($lCopiedObject->toPrivateStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->resetValues();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fromSerializedStdObject($lCopiedObject->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

$lStdObj = json_decode($lPrivateStdObject);
$lStdObj->id1 = "65498";
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObjectFromPrivateStdObject($lNewObject, $lStdObj);

if (!MainObjectCollection::getInstance()->hasObject('[65498,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$lXML = simplexml_load_string('<testDb default_value="default" id_1="1111" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreign_objects><lonely_foreign_object __inheritance__="objectWithIdAndMore">11</lonely_foreign_object><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>');
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObjectFromSerializedXml($lNewObject, $lXML);

if (!MainObjectCollection::getInstance()->hasObject('[1111,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$lArray = json_decode($lSqlArray, true);
$lArray['id_1'] = "1456";
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObjectfromSqlDatabase($lNewObject, $lArray);

if (!MainObjectCollection::getInstance()->hasObject('[1456,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

/** ******************************************************************************* **/
/**                                test object array                                **/
/** ******************************************************************************* **/

$lTestDb = $lDbTestModel->loadObject('["1",50]');
$lMainParentTestDb = $lTestDb->getValue('mainParentTestDb');
$lTestDbs = $lMainParentTestDb->getValue('childrenTestDb');

$lPrivateStdObject = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lPublicStdObject  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lSerializedObject = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true}]';
$lSerializedXML    = '<testDb><childTestDb default_value="default" id_1="1" id_2="23" date="2016-05-01T14:53:54+02:00" timestamp="2016-10-16T21:50:19+02:00" string="aaaa" integer="0" boolean="0" boolean2="1"><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="50" date="2016-10-16T20:21:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="bbbb" integer="1" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="101" date="2016-04-13T09:14:33+02:00" timestamp="2016-10-16T21:50:19+02:00" string="cccc" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreign_objects><lonely_foreign_object __inheritance__="objectWithIdAndMore">11</lonely_foreign_object><lonely_foreign_object_two>11</lonely_foreign_object_two></childTestDb><childTestDb default_value="default" id_1="2" id_2="50" date="2016-05-01T23:37:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="dddd" integer="3" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="2" id_2="102" date="2016-04-01T08:00:00+02:00" timestamp="2016-10-16T18:21:18+02:00" string="eeee" integer="4" boolean="0" boolean2="1"><object plop="plop10" plop2="plop20"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb></testDb>';
$lSqlArray         = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"bbbb","integer":1,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"cccc","integer":2,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"dddd","integer":3,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":"{\"plop\":\"plop10\",\"plop2\":\"plop20\"}","string":"eeee","integer":4,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true}]';

$lModelArrayDbTest = new ModelArray($lDbTestModel, 'childTestDb');
$lCopiedObjectArray = new ObjectArray($lModelArrayDbTest);
foreach ($lTestDbs->getValues() as $lObject) {
	$lCopiedObject = new Object('testDb');
	foreach ($lObject->getValues() as $lKey => $lValue) {
		$lCopiedObject->setValue($lKey, $lValue);
	}
	if ($lCopiedObject->getValue('id2') == 50) {
		$lObject1 = $lCopiedObject->getValue('objectsWithId');
		$lCopiedObject->deleteValue('objectsWithId');
		$lObject2 = $lCopiedObject->getValue('foreignObjects');
		$lCopiedObject->deleteValue('foreignObjects');
		$lObject3 = $lCopiedObject->getValue('mainParentTestDb');
		$lCopiedObject->deleteValue('mainParentTestDb');
		$lBoolean1 = $lCopiedObject->getValue('boolean');
		$lCopiedObject->deleteValue('boolean');
		$lBoolean2 = $lCopiedObject->getValue('boolean2');
		$lCopiedObject->deleteValue('boolean2');
		
		$lCopiedObject->setValue('mainParentTestDb', $lObject3);
		$lCopiedObject->setValue('objectsWithId', $lObject1);
		$lCopiedObject->setValue('foreignObjects', $lObject2);
		$lCopiedObject->setValue('boolean', $lBoolean1);
		$lCopiedObject->setValue('boolean2', $lBoolean2);
	}
	$lCopiedObjectArray->pushValue($lCopiedObject);
}

/** ****************************** test stdObject ****************************** **/

if (json_encode($lCopiedObjectArray->toPrivateStdObject()) !== $lPrivateStdObject) {
	var_dump(json_encode($lCopiedObjectArray->toPrivateStdObject()));
	throw new \Exception('bad private object value');
}
if (json_encode($lCopiedObjectArray->toPublicStdObject()) !== $lPublicStdObject) {
	var_dump(json_encode($lCopiedObjectArray->toPublicStdObject()));
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPrivateStdObject($lCopiedObjectArray->toPrivateStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	var_dump(json_encode($lModelArrayDbTest->fromPrivateStdObject($lCopiedObjectArray->toPrivateStdObject(), Model::NO_MERGE)->toPrivateStdObject()));
	throw new \Exception('bad private object value');
}
if (json_encode($lModelArrayDbTest->fromPublicStdObject($lCopiedObjectArray->toPublicStdObject(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObjectArray->toSerialStdObject()) !== $lSerializedObject) {
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObjectArray->toSerialStdObject()));
}
if (json_encode($lModelArrayDbTest->fromSerializedStdObject($lCopiedObjectArray->toSerialStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPrivateStdObject($lCopiedObjectArray->toPublicStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPublicStdObject($lCopiedObjectArray->toPrivateStdObject(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

function resetValues($pObjectArray) {
	foreach ($pObjectArray->getValues() as $lObject) {
		$lObject->resetValues();
		$lObject->setValue('defaultValue', 'plop');
	}
}

$lNewObject = $lTestDbs;
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPrivateStdObject($lNewObject, $lCopiedObjectArray->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPublicStdObject($lNewObject, $lCopiedObjectArray->toPublicStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromSerializedStdObject($lNewObject, $lCopiedObjectArray->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fromPrivateStdObject($lCopiedObjectArray->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fromPublicStdObject($lCopiedObjectArray->toPrivateStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fromSerializedStdObject($lCopiedObjectArray->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/

if (json_encode($lModelArrayDbTest->fromPrivateXml($lCopiedObjectArray->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($lModelArrayDbTest->fromPrivateXml($lCopiedObjectArray->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()));
}
if (json_encode($lModelArrayDbTest->fromPublicXml($lCopiedObjectArray->toPublicXml(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (trim(str_replace("<?xml version=\"1.0\"?>", '', $lCopiedObjectArray->toSerialXml()->asXML())) !== $lSerializedXML) {
	throw new \Exception('bad serial object value : '.str_replace("<?xml version=\"1.0\"?>\n", '', $lCopiedObjectArray->toSerialXml()->asXML()));
}
if (json_encode($lModelArrayDbTest->fromSerializedXml($lCopiedObjectArray->toSerialXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPrivateXml($lCopiedObjectArray->toPublicXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPublicXml($lCopiedObjectArray->toPrivateXml(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPrivateXml($lNewObject, $lCopiedObjectArray->toPrivateXml());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPublicXml($lNewObject, $lCopiedObjectArray->toPublicXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromSerializedXml($lNewObject, $lCopiedObjectArray->toSerialXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fromPrivateXml($lCopiedObjectArray->toPrivateXml());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fromPublicXml($lCopiedObjectArray->toPrivateXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fromSerializedXml($lCopiedObjectArray->toSerialXml());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (json_encode($lModelArrayDbTest->fromPrivateFlattenedArray($lCopiedObjectArray->toPrivateFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lModelArrayDbTest->fromPublicFlattenedArray($lCopiedObjectArray->toPublicFlattenedArray(), Model::NO_MERGE)->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObjectArray->toSqlDatabase()) !== $lSqlArray) {
	var_dump(json_encode($lCopiedObjectArray->toSqlDatabase()));
	var_dump($lSqlArray);
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObjectArray->toSqlDatabase()));
}
if (json_encode($lModelArrayDbTest->fromSqlDatabase($lCopiedObjectArray->toSqlDatabase(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPrivateFlattenedArray($lCopiedObjectArray->toPublicFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->fromPublicFlattenedArray($lCopiedObjectArray->toPrivateFlattenedArray(), Model::NO_MERGE)->toPrivateStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPrivateFlattenedArray($lNewObject, $lCopiedObjectArray->toPrivateFlattenedArray());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectFromPublicFlattenedArray($lNewObject, $lCopiedObjectArray->toPublicFlattenedArray());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObjectfromSqlDatabase($lNewObject, $lCopiedObjectArray->toSqlDatabase());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fromPrivateStdObject($lCopiedObjectArray->toPrivateStdObject());
if (json_encode($lNewObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fromPublicStdObject($lCopiedObjectArray->toPrivateStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fromSerializedStdObject($lCopiedObjectArray->toSerialStdObject());
if (json_encode($lNewObject->toPublicStdObject()) !== $lPublicStdObject) {
	throw new \Exception('bad serial object value');
}

/********************************** test composition export *************************************/

$lMainTestDb = MainObjectCollection::getInstance()->getObject(2, 'mainTestDb');
$lMainTestDb->loadValueIds('childrenTestDb');
if (!isset($lMainTestDb->toPrivateStdObject()->childrenTestDb)) {
	throw new \Exception('compostion must be exported');
}
if (isset($lMainTestDb->toSerialStdObject()->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
if (isset($lMainTestDb->toSerialXml()->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
$lArray = $lMainTestDb->toSqlDatabase();
if (isset($lArray['childrenTestDb'])) {
	throw new \Exception('compostion should not be exported');
}


$time_end = microtime(true);
var_dump('import export test exec time '.($time_end - $time_start));