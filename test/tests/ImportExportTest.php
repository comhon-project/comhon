<?php

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\_final\Object as FinalObject;
use comhon\object\collection\MainObjectCollection;
use comhon\object\ObjectArray;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\AssocArrayInterfacer;
use comhon\interfacer\Interfacer;

$time_start = microtime(true);

$lStdPrivateInterfacer = new StdObjectInterfacer();
$lStdPrivateInterfacer->setPrivateContext(true);

$lStdPublicInterfacer = new StdObjectInterfacer();
$lStdPublicInterfacer->setPrivateContext(false);

$lStdSerialInterfacer = new StdObjectInterfacer();
$lStdSerialInterfacer->setPrivateContext(true);
$lStdSerialInterfacer->setSerialContext(true);

$lXmlPrivateInterfacer = new XMLInterfacer();
$lXmlPrivateInterfacer->setPrivateContext(true);

$lXmlPublicInterfacer= new XMLInterfacer();
$lXmlPublicInterfacer->setPrivateContext(false);

$lXmlSerialInterfacer = new XMLInterfacer();
$lXmlSerialInterfacer->setPrivateContext(true);
$lXmlSerialInterfacer->setSerialContext(true);

$lFlattenArrayPrivateInterfacer = new AssocArrayInterfacer();
$lFlattenArrayPrivateInterfacer->setPrivateContext(true);
$lFlattenArrayPrivateInterfacer->setFlattenValues(true);

$lFlattenArrayPublicInterfacer = new AssocArrayInterfacer();
$lFlattenArrayPublicInterfacer->setPrivateContext(false);
$lFlattenArrayPublicInterfacer->setFlattenValues(true);

$lFlattenArraySerialInterfacer = new AssocArrayInterfacer();
$lFlattenArraySerialInterfacer->setPrivateContext(true);
$lFlattenArraySerialInterfacer->setFlattenValues(true);
$lFlattenArraySerialInterfacer->setSerialContext(true);

$lStdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lStdPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$lStdSerialInterfacer->setMergeType(Interfacer::NO_MERGE);
$lXmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lXmlPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$lXmlSerialInterfacer->setMergeType(Interfacer::NO_MERGE);
$lFlattenArrayPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lFlattenArrayPublicInterfacer->setMergeType(Interfacer::NO_MERGE);
$lFlattenArraySerialInterfacer->setMergeType(Interfacer::NO_MERGE);

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,"1501774389"]');

$lCopiedObject = new FinalObject('testDb');
foreach ($lObject->getValues() as $lKey => $lValue) {
	$lCopiedObject->setValue($lKey, $lValue);
}

$lPrivateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lPublicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lSerializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';
$lSerializedXML    = '<testDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>';
$lSqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';

// same objects than previous objects except "childrenTestDb" at the end
$lPrivateStdObjectEnd = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lPublicStdObjectEnd  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';

// same objects than previous objects except "childrenTestDb" removed
$lPrivateStdObjectWithoutAggregation = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';
$lPublicStdObjectWithoutAggregation  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}';


/** ****************************** test stdObject ****************************** **/

if (!compareJson(json_encode($lCopiedObject->export($lStdPrivateInterfacer)), $lPrivateStdObject)) {
	var_dump(json_encode($lCopiedObject->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value');
}
if (json_encode($lCopiedObject->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	var_dump(json_encode($lDbTestModel->import($lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lStdPublicInterfacer), $lStdPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObject->export($lStdSerialInterfacer)) !== $lSerializedObject) {
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObject->export($lStdSerialInterfacer)));
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lStdSerialInterfacer), $lStdSerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lStdPublicInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lStdPrivateInterfacer), $lStdPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

$lNewObject = new FinalObject('testDb');
try {
	$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('instance with same id already exists');
}

$lNewObject = $lObject;
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	var_dump(json_encode($lNewObject->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lStdPublicInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	var_dump(json_encode($lNewObject->export($lStdPublicInterfacer)));
	throw new \Exception('bad serial object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdPrivateInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/

if (json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	var_dump(json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value : '.json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)));
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPublicInterfacer), $lXmlPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareXML($lXmlSerialInterfacer->toString($lCopiedObject->export($lXmlSerialInterfacer)), $lSerializedXML)) {
	throw new \Exception('bad serial object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lXmlSerialInterfacer), $lXmlSerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPublicInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lXmlPublicInterfacer), $lXmlPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lXmlSerialInterfacer), $lXmlSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lXmlPrivateInterfacer), $lXmlPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lXmlSerialInterfacer), $lXmlSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (json_encode($lDbTestModel->import($lCopiedObject->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lCopiedObject->export($lFlattenArraySerialInterfacer)) !== $lSqlArray) {
	var_dump(json_encode($lCopiedObject->export($lFlattenArraySerialInterfacer)));
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObject->export($lFlattenArraySerialInterfacer)));
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lFlattenArraySerialInterfacer), $lFlattenArraySerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lDbTestModel->import($lCopiedObject->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lDbTestModel->fillObject($lNewObject, $lCopiedObject->export($lFlattenArraySerialInterfacer), $lFlattenArraySerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdPrivateInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
$lNewObject->reset();
$lNewObject->setValue('defaultValue', 'plop');
$lNewObject->fillObject($lCopiedObject->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

$lStdObj = json_decode($lPrivateStdObject);
$lStdObj->id1 = 65498;
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObject($lNewObject, $lStdObj, $lStdPrivateInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[65498,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$lXML = simplexml_load_string('<testDb default_value="default" id_1="1111" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>');
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObject($lNewObject, $lXML, $lXmlSerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1111,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

$lArray = json_decode($lSqlArray, true);
$lArray['id_1'] = 1456;
$lNewObject = $lDbTestModel->getObjectInstance();
$lDbTestModel->fillObject($lNewObject, $lArray, $lFlattenArraySerialInterfacer);

if (!MainObjectCollection::getInstance()->hasObject('[1456,"1501774389"]', 'testDb')) {
	throw new \Exception('object not added');
}

/** ******************************************************************************* **/
/**                                test object array                                **/
/** ******************************************************************************* **/

$lTestDb = $lDbTestModel->loadObject('[1,"50"]');
$lMainParentTestDb = $lTestDb->getValue('mainParentTestDb');

/** @var ObjectArray $lTestDbs */
$lTestDbs = $lMainParentTestDb->getValue('childrenTestDb');

$lPrivateStdObject = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lPublicStdObject  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lSerializedObject = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"object_with_id":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"main_test_id":1,"objects_with_id":[],"foreign_objects":[],"boolean":false,"boolean2":true}]';
$lSerializedXML    = '<testDb><childTestDb default_value="default" id_1="1" id_2="23" date="2016-05-01T14:53:54+02:00" timestamp="2016-10-16T21:50:19+02:00" string="aaaa" integer="0" boolean="0" boolean2="1"><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="50" date="2016-10-16T20:21:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="bbbb" integer="1" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="101" date="2016-04-13T09:14:33+02:00" timestamp="2016-10-16T21:50:19+02:00" string="cccc" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></childTestDb><childTestDb default_value="default" id_1="2" id_2="50" date="2016-05-01T23:37:18+02:00" timestamp="2016-10-16T21:50:19+02:00" string="dddd" integer="3" boolean="0" boolean2="1"><object plop="plop" plop2="plop2222"/><object_with_id plop="plop" plop2="plop2222"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb><childTestDb default_value="default" id_1="2" id_2="102" date="2016-04-01T08:00:00+02:00" timestamp="2016-10-16T18:21:18+02:00" string="eeee" integer="4" boolean="0" boolean2="1"><object plop="plop10" plop2="plop20"/><main_test_id>1</main_test_id><objects_with_id/><foreign_objects/></childTestDb></testDb>';
$lSqlArray         = '[{"default_value":"default","id_1":1,"id_2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"bbbb","integer":1,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"cccc","integer":2,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2222\"}","string":"dddd","integer":3,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true},{"default_value":"default","id_1":2,"id_2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":"{\"plop\":\"plop10\",\"plop2\":\"plop20\"}","string":"eeee","integer":4,"main_test_id":1,"objects_with_id":"[]","foreign_objects":"[]","boolean":false,"boolean2":true}]';

// same objects than previous objects except "childrenTestDb" at the end
$lPrivateStdObjectEnd = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lPublicStdObjectEnd  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';

// same objects than previous objects except "childrenTestDb" removed
$lPrivateStdObjectWithoutAggregation = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"bbbb","integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"string":"dddd","integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"string":"eeee","integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';
$lPublicStdObjectWithoutAggregation  = '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"50","date":"2016-10-16T20:21:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":1,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"50","date":"2016-05-01T23:37:18+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2222"},"objectWithId":{"plop":"plop","plop2":"plop2222"},"integer":3,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":2,"id2":"102","date":"2016-04-01T08:00:00+02:00","timestamp":"2016-10-16T18:21:18+02:00","object":{"plop":"plop10","plop2":"plop20"},"integer":4,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]';


$lCopiedObjectArray = new ObjectArray($lDbTestModel, true, 'childTestDb');
$lModelArrayDbTest = $lCopiedObjectArray->getModel();

foreach ($lTestDbs->getValues() as $lObject) {
	$lCopiedObject = new FinalObject('testDb');
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

if (!compareJson(json_encode($lCopiedObjectArray->export($lStdPrivateInterfacer)), $lPrivateStdObject)) {
	var_dump(json_encode($lCopiedObjectArray->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value');
}
if (!compareJson(json_encode($lCopiedObjectArray->export($lStdPublicInterfacer)), $lPublicStdObject)) {
	var_dump(json_encode($lCopiedObjectArray->export($lStdPublicInterfacer)));
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	var_dump(json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)));
	throw new \Exception('bad private object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdPublicInterfacer), $lStdPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($lCopiedObjectArray->export($lStdSerialInterfacer)), $lSerializedObject)) {
	var_dump(json_encode($lCopiedObjectArray->export($lStdSerialInterfacer)));
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObjectArray->export($lStdSerialInterfacer)));
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdSerialInterfacer), $lStdSerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdPublicInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

function resetValues($pObjectArray) {
	foreach ($pObjectArray->getValues() as $lObject) {
		$lId = $lObject->getId();
		$lObject->reset();
		$lObject->setId($lId, false);
	}
}

/** @var ObjectArray $lNewObject */
$lNewObject = $lTestDbs;
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	var_dump(json_encode($lNewObject->export($lStdPrivateInterfacer)));
	var_dump($lPrivateStdObjectEnd);
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lStdPublicInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	var_dump(json_encode($lNewObject->export($lStdPublicInterfacer)));
	var_dump($lPublicStdObjectWithoutAggregation);
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test xml ****************************** **/

if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)));
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlPublicInterfacer), $lXmlPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareXML($lXmlSerialInterfacer->toString($lCopiedObjectArray->export($lXmlSerialInterfacer)), $lSerializedXML)) {
	throw new \Exception('bad serial object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlSerialInterfacer), $lXmlSerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlPublicInterfacer), $lXmlPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lXmlPublicInterfacer), $lXmlPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lXmlSerialInterfacer), $lXmlSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lXmlPrivateInterfacer), $lXmlPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lXmlSerialInterfacer), $lXmlSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

/** ****************************** test flattened array ****************************** **/

if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicInterfacer)->export($lStdPublicInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (!compareJson(json_encode($lCopiedObjectArray->export($lFlattenArraySerialInterfacer)), $lSqlArray)) {
	var_dump(json_encode($lCopiedObjectArray->export($lFlattenArraySerialInterfacer)));
	throw new \Exception('bad serial object value : '.json_encode($lCopiedObjectArray->export($lFlattenArraySerialInterfacer)));
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lFlattenArraySerialInterfacer), $lFlattenArraySerialInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}
if (json_encode($lModelArrayDbTest->import($lCopiedObjectArray->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPublicInterfacer)->export($lStdPrivateInterfacer)) !== $lPublicStdObject) {
	throw new \Exception('bad public object value');
}

resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lModelArrayDbTest->fillObject($lNewObject, $lCopiedObjectArray->export($lFlattenArraySerialInterfacer), $lFlattenArraySerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (json_encode($lNewObject->export($lStdPrivateInterfacer)) !== $lPrivateStdObjectEnd) {
	throw new \Exception('bad private object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdPrivateInterfacer), $lStdPublicInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectEnd) {
	throw new \Exception('bad public object value');
}
resetValues($lNewObject);
$lNewObject->fillObject($lCopiedObjectArray->export($lStdSerialInterfacer), $lStdSerialInterfacer);
if (json_encode($lNewObject->export($lStdPublicInterfacer)) !== $lPublicStdObjectWithoutAggregation) {
	throw new \Exception('bad serial object value');
}

/********************************** test aggregation export *************************************/

$lMainTestDb = MainObjectCollection::getInstance()->getObject(2, 'mainTestDb');
$lMainTestDb->loadValueIds('childrenTestDb');
if (!isset($lMainTestDb->export($lStdPrivateInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion must be exported');
}
if (isset($lMainTestDb->export($lStdSerialInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
if (isset($lMainTestDb->export($lXmlSerialInterfacer)->childrenTestDb)) {
	throw new \Exception('compostion should not be exported');
}
$lArray = $lMainTestDb->export($lFlattenArraySerialInterfacer);
if (isset($lArray['childrenTestDb'])) {
	throw new \Exception('compostion should not be exported');
}


/********************************** test foreign property with private id export *************************************/

$lStdPrivateInterfacer->setMergeType(Interfacer::MERGE);
$lStdPublicInterfacer->setMergeType(Interfacer::MERGE);
$lStdSerialInterfacer->setMergeType(Interfacer::MERGE);
$lXmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
$lXmlPublicInterfacer->setMergeType(Interfacer::MERGE);
$lXmlSerialInterfacer->setMergeType(Interfacer::MERGE);
$lFlattenArrayPrivateInterfacer->setMergeType(Interfacer::MERGE);
$lFlattenArrayPublicInterfacer->setMergeType(Interfacer::MERGE);
$lFlattenArraySerialInterfacer->setMergeType(Interfacer::MERGE);

$lTestPrivateIdModel = ModelManager::getInstance()->getInstanceModel('testPrivateId');
$lTestPrivateId = $lTestPrivateIdModel->getObjectInstance();
$lTestPrivateId->setValue('id', "1");
$lTestPrivateId->setValue('name', 'test 1');
$lObjs = $lTestPrivateId->initValue('objectValues');
$lObj1 = $lObjs->getModel()->getModel()->getObjectInstance();
$lObj1->setValue('id1', 1);
$lObj1->setValue('id2', 2);
$lObj1->setValue('propertyOne', 'azeaze1');
$lObjs->pushValue($lObj1);
//--------------
$lObj2 = $lObjs->getModel()->getModel()->getObjectInstance();
$lObj2->setId(json_encode([10, 20]));
$lObj2->setValue('propertyOne', 'azeaze10');
$lObjs->pushValue($lObj2);
//--------------
$lObj3 = $lObjs->getModel()->getModel()->getObjectInstance();
$lObj3->setId(json_encode([100, 200]));
$lObj3->setValue('propertyOne', 'azeaze100');
$lObjs->pushValue($lObj3);
//--------------
$lTestPrivateId->setValue('foreignObjectValue', $lObj1);
$lForeignObjs = $lTestPrivateId->initValue('foreignObjectValues');
$lForeignObjs->pushValue($lObj2);
$lForeignObjs->pushValue($lObj3);
//--------------
$lTestPrivateId2 = $lTestPrivateIdModel->getObjectInstance();
$lTestPrivateId2->setValue('id', "2");
$lTestPrivateId2->setValue('name', 'test 3');
$lTestPrivateId3 = $lTestPrivateIdModel->getObjectInstance();
$lTestPrivateId3->setValue('id', "3");
$lTestPrivateId3->setValue('name', 'test 3');
$lTestPrivateId->setValue('foreignTestPrivateId', $lTestPrivateId2);
$lForeignMainObjs = $lTestPrivateId->initValue('foreignTestPrivateIds');
$lForeignMainObjs->pushValue($lTestPrivateId3);
$lForeignMainObjs->pushValue($lTestPrivateId);

$lPrivateStdObject = '{"id":"1","name":"test 1","objectValues":[{"id1":1,"id2":2,"propertyOne":"azeaze1"},{"id1":10,"id2":20,"propertyOne":"azeaze10"},{"id1":100,"id2":200,"propertyOne":"azeaze100"}],"foreignObjectValue":"[1,2]","foreignObjectValues":["[10,20]","[100,200]"],"foreignTestPrivateId":"2","foreignTestPrivateIds":["3","1"]}';
if (json_encode($lTestPrivateId->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($lTestPrivateId->export($lStdPrivateInterfacer)));
}
if (json_encode($lTestPrivateId->export($lStdSerialInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad serial object value : '.json_encode($lTestPrivateId->export($lStdPrivateInterfacer)));
}
if (json_encode($lTestPrivateId->export($lStdPublicInterfacer)) !== '{"name":"test 1","objectValues":[{"id2":2,"propertyOne":"azeaze1"},{"id2":20,"propertyOne":"azeaze10"},{"id2":200,"propertyOne":"azeaze100"}]}') {
	throw new \Exception('bad public object value : '.json_encode($lTestPrivateId->export($lStdPublicInterfacer)));
}
if (json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)) !== $lPrivateStdObject) {
	throw new \Exception('bad private object value : '.json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lStdPrivateInterfacer), $lStdPrivateInterfacer)->export($lStdPrivateInterfacer)));
}
if (!compareJson(json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lStdPrivateInterfacer), $lStdPublicInterfacer)->export($lStdPrivateInterfacer)), '{"name":"test 1","objectValues":[{"id2":2,"propertyOne":"azeaze1"},{"id2":20,"propertyOne":"azeaze10"},{"id2":200,"propertyOne":"azeaze100"}]}')) {
	throw new \Exception('bad public object value : '.json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lStdPrivateInterfacer), $lStdPublicInterfacer)->export($lStdPrivateInterfacer)));
}


$lPrivateFlattenedArray = '{"id":"1","name":"test 1","objectValues":"[{\"id1\":1,\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id1\":10,\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id1\":100,\"id2\":200,\"propertyOne\":\"azeaze100\"}]","foreignObjectValue":"[1,2]","foreignObjectValues":"[\"[10,20]\",\"[100,200]\"]","foreignTestPrivateId":"2","foreignTestPrivateIds":"[\"3\",\"1\"]"}';
if (json_encode($lTestPrivateId->export($lFlattenArrayPrivateInterfacer)) !== $lPrivateFlattenedArray) {
	throw new \Exception('bad private object value : '.json_encode($lTestPrivateId->export($lFlattenArrayPrivateInterfacer)));
}
if (json_encode($lTestPrivateId->export($lFlattenArrayPublicInterfacer)) !== '{"name":"test 1","objectValues":"[{\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id2\":200,\"propertyOne\":\"azeaze100\"}]"}') {
	throw new \Exception('bad public object value : '.json_encode($lTestPrivateId->export($lFlattenArrayPublicInterfacer)));
}
if (!compareJson(json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer)->export($lFlattenArrayPrivateInterfacer)), $lPrivateFlattenedArray)) {
	throw new \Exception('bad private object value : '.json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer)->export($lFlattenArrayPrivateInterfacer)));
}
if (!compareJson(json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPublicInterfacer)->export($lFlattenArrayPrivateInterfacer)), '{"name":"test 1","objectValues":"[{\"id2\":2,\"propertyOne\":\"azeaze1\"},{\"id2\":20,\"propertyOne\":\"azeaze10\"},{\"id2\":200,\"propertyOne\":\"azeaze100\"}]"}')) {
	throw new \Exception('bad public object value : '.json_encode($lTestPrivateIdModel->import($lTestPrivateId->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPublicInterfacer)->export($lFlattenArrayPrivateInterfacer)));
}


$lPrivateXml = '<testPrivateId id="1" name="test 1"><objectValues><objectValue id1="1" id2="2" propertyOne="azeaze1"/><objectValue id1="10" id2="20" propertyOne="azeaze10"/><objectValue id1="100" id2="200" propertyOne="azeaze100"/></objectValues><foreignObjectValue>[1,2]</foreignObjectValue><foreignObjectValues><foreignObjectValue>[10,20]</foreignObjectValue><foreignObjectValue>[100,200]</foreignObjectValue></foreignObjectValues><foreignTestPrivateId>2</foreignTestPrivateId><foreignTestPrivateIds><foreignTestPrivateId>3</foreignTestPrivateId><foreignTestPrivateId>1</foreignTestPrivateId></foreignTestPrivateIds></testPrivateId>';
if (!compareXML($lXmlPrivateInterfacer->toString($lTestPrivateId->export($lXmlPrivateInterfacer)), $lPrivateXml)) {
	throw new \Exception('bad private object value');
}
if (!compareXML($lXmlPublicInterfacer->toString($lTestPrivateId->export($lXmlPublicInterfacer)), '<testPrivateId name="test 1"><objectValues><objectValue id2="2" propertyOne="azeaze1"/><objectValue id2="20" propertyOne="azeaze10"/><objectValue id2="200" propertyOne="azeaze100"/></objectValues></testPrivateId>')) {
	throw new \Exception('bad public object value');
}
if (!compareXML($lXmlPrivateInterfacer->toString($lTestPrivateIdModel->import($lTestPrivateId->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer)->export($lXmlPrivateInterfacer)), $lPrivateXml)) {
	throw new \Exception('bad private object value');
}
if (!compareXML($lXmlPrivateInterfacer->toString($lTestPrivateIdModel->import($lTestPrivateId->export($lXmlPrivateInterfacer), $lXmlPublicInterfacer)->export($lXmlPrivateInterfacer)), '<testPrivateId name="test 1"><objectValues><objectValue id2="2" propertyOne="azeaze1"/><objectValue id2="20" propertyOne="azeaze10"/><objectValue id2="200" propertyOne="azeaze100"/></objectValues></testPrivateId>')) {
	throw new \Exception('bad public object value');
}

/** ************************************** test node/attribute xml ********************************************* **/

$lTestXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$lTestXml = $lTestXmlModel->loadObject('plop2');

if (!compareXML($lXmlPrivateInterfacer->toString($lTestXml->export($lXmlPrivateInterfacer)), '<testXml textAttribute="attribute"><name>plop2</name><textNode>node</textNode><objectValue id="1" propertyOne="plop1" propertyTwo="plop11"/><objectValues><objectValue id="2" propertyOne="plop2" propertyTwo="plop22"/><objectValue id="3" propertyOne="plop3" propertyTwo="plop33"/></objectValues><objectContainer><foreignObjectValue>3</foreignObjectValue><objectValueTwo id="1" propertyTwoOne="2plop1"/><person id="1" firstName="Bernard" lastName="Dupond"><birthPlace>2</birthPlace><children><child id="5" __inheritance__="man"/><child id="6" __inheritance__="man"/></children></person></objectContainer><foreignObjectValues><foreignObjectValue>1</foreignObjectValue><foreignObjectValue>2</foreignObjectValue></foreignObjectValues></testXml>')) {
	var_dump($lXmlPrivateInterfacer->toString($lTestXml->export($lXmlPrivateInterfacer)));
	throw new Exception('bad value');
}

/** ************************************** test null values ********************************************* **/

$lObject = $lDbTestModel->getObjectInstance();
$lObject->setValue('id1', null);
$lObject->setValue('id2', null);
$lObject->setValue('date', null);
$lObject->setValue('timestamp', null);
$lObject->setValue('object', null);
$lObject->setValue('objectWithId', null);
$lObject->setValue('string', null);
$lObject->setValue('integer', null);
$lObject->setValue('mainParentTestDb', null);
$lObject->setValue('objectsWithId', null);
$lObject->setValue('foreignObjects', null);
$lObject->setValue('lonelyForeignObject', null);
$lObject->setValue('lonelyForeignObjectTwo', null);
$lObject->setValue('defaultValue', null);
$lObject->setValue('manBodyJson', null);
$lObject->setValue('womanXml', null);
$lObject->setValue('notSerializedValue', null);
$lObject->setValue('notSerializedForeignObject', null);
$lObject->setValue('boolean', null);
$lObject->setValue('boolean2', null);
$lObject->setValue('childrenTestDb', null);

if ($lStdPrivateInterfacer->toString($lStdPrivateInterfacer->export($lObject)) !== '{}') {
	throw new \Exception('bad public object value');
}
if ($lXmlPrivateInterfacer->toString($lXmlPrivateInterfacer->export($lObject)) !== '<testDb/>') {
	throw new \Exception('bad public object value');
}
if ($lFlattenArrayPrivateInterfacer->toString($lFlattenArrayPrivateInterfacer->export($lObject)) !== '[]') {
	throw new \Exception('bad public object value');
}

$time_end = microtime(true);
var_dump('import export test exec time '.($time_end - $time_start));