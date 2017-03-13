<?php

use comhon\model\singleton\ModelManager;
use comhon\object\serialization\file\XmlFile;
use comhon\object\collection\MainObjectCollection;

$time_start = microtime(true);

$lPrivateUpdatedValues = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"string":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false,"childrenTestDb":false}';
$lPublicUpdatedValues  = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false,"childrenTestDb":false}';

$lPrivateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$lPrivateXml       = '<testDb defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreignObjects><lonelyForeignObject __inheritance__="objectWithIdAndMore">11</lonelyForeignObject><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></testDb>';
$lPrivateFlattened = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$lPublicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$lPublicXml        = '<testDb defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" __inheritance__="objectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreignObjects><lonelyForeignObject __inheritance__="objectWithIdAndMore">11</lonelyForeignObject><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></testDb>';
$lPublicFlattened  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$lSerializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';
$lSerializedXML    = '<testDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject __inheritance__="objectWithIdAndMoreMore">1</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">1</foreignObject><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject __inheritance__="objectWithIdAndMore">11</foreignObject></foreign_objects><lonely_foreign_object __inheritance__="objectWithIdAndMore">11</lonely_foreign_object><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>';
$lSqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,1501774389]');

if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
$lObject = $lDbTestModel->loadObject('[1,1501774389]', null, true);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toPrivateStdObject()) !== $lPrivateStdObject) {
	throw new Exception('bad object Values');
}

/** ----------------------------- import/export stdObject --------------------------------- **/
// -- private
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
$lObject->fromPrivateStdObject($lObject->toPrivateStdObject());
if (json_encode($lObject->getUpdatedValues()) !== $lPrivateUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (json_encode($lObject->toPrivateStdObject(null, true)) !== $lPrivateStdObject) {
	throw new Exception('bad object Values');
}
$lObject->fromStdObject($lObject->toPrivateStdObject(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toPrivateStdObject(null, true)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- public
$lObject->fromPublicStdObject($lObject->toPublicStdObject());
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (json_encode($lObject->toPublicStdObject(null, true)) !== $lPublicStdObject) {
	throw new Exception('bad object Values');
}
$lObject->fromStdObject($lObject->toPublicStdObject(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toPublicStdObject(null, true)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fromSerializedStdObject($lObject->toSerialStdObject());
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toSerialStdObject()) !== $lSerializedObject) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->toSerialStdObject(null, true)) !== '{"id_1":1,"id_2":"1501774389"}') {
	throw new Exception('bad object Values');
}

/** ----------------------------- import/export xml --------------------------------- **/
// -- private
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
$lObject->fromPrivateXml($lObject->toPrivateXml());
if (json_encode($lObject->getUpdatedValues()) !== $lPrivateUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPrivateXml(null, true)->asXML())) !== $lPrivateXml) {
	var_dump(trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPrivateXml(null, true)->asXML())));
	throw new Exception('bad object Values');
}
$lObject->fromXml($lObject->toPrivateXml(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPrivateXml(null, true)->asXML())) !== '<testDb id1="1" id2="1501774389"/>') {
	var_dump(trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPrivateXml(null, true)->asXML())));
	throw new Exception('bad object Values');
}

// -- public
$lObject->fromPublicXml($lObject->toPublicXml());
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPublicXml(null, true)->asXML())) !== $lPublicXml) {
	throw new Exception('bad object Values');
}
$lObject->fromXml($lObject->toPublicXml(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPublicXml(null, true)->asXML())) !== '<testDb id1="1" id2="1501774389"/>') {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fromSerializedXml($lObject->toSerialXml());
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toSerialXml()->asXML())) !== $lSerializedXML) {
	throw new Exception('bad object Values');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toSerialXml(null, true)->asXML())) !== '<testDb id_1="1" id_2="1501774389"/>') {
	throw new Exception('bad object Values');
}

/** ----------------------------- import/export flattened array --------------------------------- **/
// -- private
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
$lObject->fromPrivateFlattenedArray($lObject->toPrivateFlattenedArray());
if (json_encode($lObject->getUpdatedValues()) !== $lPrivateUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (json_encode($lObject->toPrivateFlattenedArray(null, true)) !== $lPrivateFlattened) {
	var_dump(json_encode($lObject->toPrivateFlattenedArray(null, true)));
	throw new Exception('bad object Values');
}
$lObject->fromFlattenedArray($lObject->toPrivateFlattenedArray(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toPrivateFlattenedArray(null, true)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- public
$lObject->fromPublicFlattenedArray($lObject->toPublicFlattenedArray());
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (json_encode($lObject->toPublicFlattenedArray(null, true)) !== $lPublicFlattened) {
	throw new Exception('bad object Values');
}
$lObject->fromFlattenedArray($lObject->toPublicFlattenedArray(), true, false, null, true, false);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toPublicFlattenedArray(null, true)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fromSqlDatabase($lObject->toSqlDatabase());
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->toSqlDatabase()) !== $lSqlArray) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->toSqlDatabase(null, true)) !== '{"id_1":1,"id_2":"1501774389"}') {
	throw new Exception('bad object Values');
}

/** ----------------------------- import/export with some updated values --------------------------------- **/

$lObject->setValue('integer', 2);
$lObject->getValue('object')->setValue('plop2', 'plop2');
$lObject->getValue('lonelyForeignObjectTwo')->setValue("plop3", "heyplop33");

$lPublicStdObject = '{"id1":1,"id2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}]}';
$lPublicXml       = '<testDb id1="1" id2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" __inheritance__="objectWithIdAndMore"/></objectsWithId></testDb>';
$lPublicFlattened = '{"id1":1,"id2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"__inheritance__\":\"objectWithIdAndMore\"}]"}';

$lPrivateStdObject = '{"id1":1,"id2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}]}';
$lPrivateXml       = '<testDb id1="1" id2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objectsWithId></testDb>';
$lPrivateFlattened = '{"id1":1,"id2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]"}';

$lSerialStdObject = '{"id_1":1,"id_2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}]}';
$lSerialXml       = '<testDb id_1="1" id_2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id></testDb>';
$lSerialFlattened = '{"id_1":1,"id_2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]"}';

// -- public
if (json_encode($lObject->toPublicStdObject(null, true)) !== $lPublicStdObject) {
	throw new Exception('bad object Values');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPublicXml(null, true)->asXML())) !== $lPublicXml) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->toPublicFlattenedArray(null, true)) !== $lPublicFlattened) {
	throw new Exception('bad object Values');
}

// -- private
if (json_encode($lObject->toPrivateStdObject(null, true)) !== $lPrivateStdObject) {
	throw new Exception('bad object Values');
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toPrivateXml(null, true)->asXML())) !== $lPrivateXml) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->toPrivateFlattenedArray(null, true)) !== $lPrivateFlattened) {
	throw new Exception('bad object Values');
}

// -- serial with foreign main object export
$lArray = [];
$lObject->getValue('childrenTestDb')->getValue(0)->setValue('name', 'test_name');
$lObject->flagValueAsUpdated('id1');
$lObject->getValue('mainParentTestDb')->getValue('childrenTestDb')->getValue(0)->setValue('integer', 1);
if (json_encode($lObject->toSerialStdObject(null, true, null, $lArray)) !== $lSerialStdObject) {
	throw new Exception('bad object Values');
}
if (json_encode($lArray) !== '{"testDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"mainTestDb":{"1":{"id":1}},"childTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}') {
	var_dump(json_encode($lArray));
	throw new Exception('bad foreign objects Values');
}

$lArray = [];
if (trim(str_replace('<?xml version="1.0"?>', '', $lObject->toSerialXml(null, true, null, $lArray)->asXML())) !== $lSerialXml) {
	throw new Exception('bad object Values');
}
if (json_encode($lArray) !== '{"testDb":{"[1,\"23\"]":{"@attributes":{"id_1":"1","id_2":"23","integer":"1"}},"[1,\"50\"]":{"@attributes":{"id_1":"1","id_2":"50"}},"[1,\"101\"]":{"@attributes":{"id_1":"1","id_2":"101"}},"[2,\"50\"]":{"@attributes":{"id_1":"2","id_2":"50"}},"[2,\"102\"]":{"@attributes":{"id_1":"2","id_2":"102"}}},"mainTestDb":{"1":{"@attributes":{"id":"1"}}},"childTestDb":{"1":{"@attributes":{"id":"1","name":"test_name","parent_id_1":"1"}},"2":{"@attributes":{"id":"2","parent_id_1":"1"}}}}') {
	var_dump(json_encode($lArray));
	throw new Exception('bad foreign objects Values');
}

$lArray = [];
if (json_encode($lObject->toSqlDatabase(null, true, null, $lArray)) !== $lSerialFlattened) {
	throw new Exception('bad object Values');
}
if (json_encode($lArray) !== '{"testDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"mainTestDb":{"1":{"id":1}},"childTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}') {
	var_dump(json_encode($lArray));
	throw new Exception('bad foreign objects Values');
}


$time_end = microtime(true);
var_dump('partial import export test exec time '.($time_end - $time_start));
