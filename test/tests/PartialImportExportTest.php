<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

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

/*********************************************/

$lStdPrivateUpdatedInterfacer = new StdObjectInterfacer();
$lStdPrivateUpdatedInterfacer->setPrivateContext(true);
$lStdPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lStdPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lStdPublicUpdatedInterfacer = new StdObjectInterfacer();
$lStdPublicUpdatedInterfacer->setPrivateContext(false);
$lStdPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lStdPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lStdSerialUpdatedInterfacer = new StdObjectInterfacer();
$lStdSerialUpdatedInterfacer->setPrivateContext(true);
$lStdSerialUpdatedInterfacer->setSerialContext(true);
$lStdSerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lStdSerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lXmlPrivateUpdatedInterfacer = new XMLInterfacer();
$lXmlPrivateUpdatedInterfacer->setPrivateContext(true);
$lXmlPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lXmlPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lXmlPublicUpdatedInterfacer= new XMLInterfacer();
$lXmlPublicUpdatedInterfacer->setPrivateContext(false);
$lXmlPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lXmlPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lXmlSerialUpdatedInterfacer = new XMLInterfacer();
$lXmlSerialUpdatedInterfacer->setPrivateContext(true);
$lXmlSerialUpdatedInterfacer->setSerialContext(true);
$lXmlSerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lXmlSerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lFlattenArrayPrivateUpdatedInterfacer = new AssocArrayInterfacer();
$lFlattenArrayPrivateUpdatedInterfacer->setPrivateContext(true);
$lFlattenArrayPrivateUpdatedInterfacer->setFlattenValues(true);
$lFlattenArrayPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lFlattenArrayPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lFlattenArrayPublicUpdatedInterfacer = new AssocArrayInterfacer();
$lFlattenArrayPublicUpdatedInterfacer->setPrivateContext(false);
$lFlattenArrayPublicUpdatedInterfacer->setFlattenValues(true);
$lFlattenArrayPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lFlattenArrayPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lFlattenArraySerialUpdatedInterfacer = new AssocArrayInterfacer();
$lFlattenArraySerialUpdatedInterfacer->setPrivateContext(true);
$lFlattenArraySerialUpdatedInterfacer->setFlattenValues(true);
$lFlattenArraySerialUpdatedInterfacer->setSerialContext(true);
$lFlattenArraySerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$lFlattenArraySerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$lPrivateUpdatedValues = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"string":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false}';
$lPublicUpdatedValues  = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false}';

$lPrivateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$lPrivateXml       = '<testDb defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" __inheritance__="objectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></testDb>';
$lPrivateFlattened = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$lPublicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$lPublicXml        = '<testDb defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" __inheritance__="objectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" __inheritance__="objectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></testDb>';
$lPublicFlattened  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$lSerializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';
$lSerializedXML    = '<testDb default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="objectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="objectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="objectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="objectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="objectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="objectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="objectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></testDb>';
$lSqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"objectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"objectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"objectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"objectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,"1501774389"]');

if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
$lObject = $lDbTestModel->loadObject('[1,"1501774389"]', null, true);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
$lObject->unsetValue('manBodyJson', false);
$lObject->unsetValue('womanXml', false);
if (!compareJson(json_encode($lObject->export($lStdPrivateInterfacer)), $lPrivateStdObject)) {
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
$lObject->fill($lObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if (!compareJson(json_encode($lObject->getUpdatedValues()), $lPrivateUpdatedValues)) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareJson(json_encode($lObject->export($lStdPrivateUpdatedInterfacer)), $lPrivateStdObject)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lStdPrivateInterfacer), $lStdPrivateUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (!compareJson(json_encode($lObject->export($lStdPrivateUpdatedInterfacer)), '{"id1":1,"id2":"1501774389"}')) {
	throw new Exception('bad object Values');
}

// -- public
$lObject->fill($lObject->export($lStdPublicInterfacer), $lStdPublicInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareJson(json_encode($lObject->export($lStdPublicUpdatedInterfacer)), $lPublicStdObject)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lStdPublicInterfacer), $lStdPublicUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->export($lStdPublicUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fill($lObject->export($lStdSerialInterfacer), $lStdSerialUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->export($lStdSerialInterfacer)) !== $lSerializedObject) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->export($lStdSerialUpdatedInterfacer)) !== '{"id_1":1,"id_2":"1501774389"}') {
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
$lObject->fill($lObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== $lPrivateUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareXML($lXmlPrivateUpdatedInterfacer->toString($lObject->export($lXmlPrivateUpdatedInterfacer)), $lPrivateXml)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lXmlPrivateInterfacer), $lXmlPrivateUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (!compareXML($lXmlPrivateUpdatedInterfacer->toString($lObject->export($lXmlPrivateUpdatedInterfacer)), '<testDb id1="1" id2="1501774389"/>')) {
	throw new Exception('bad object Values');
}

// -- public
$lObject->fill($lObject->export($lXmlPublicInterfacer), $lXmlPublicInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareXML($lXmlPublicUpdatedInterfacer->toString($lObject->export($lXmlPublicUpdatedInterfacer)), $lPublicXml)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lXmlPublicInterfacer), $lXmlPublicUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (!compareXML($lXmlPublicUpdatedInterfacer->toString($lObject->export($lXmlPublicUpdatedInterfacer)), '<testDb id1="1" id2="1501774389"/>')) {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fill($lObject->export($lXmlSerialInterfacer), $lXmlSerialUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (!compareXML($lXmlSerialInterfacer->toString($lObject->export($lXmlSerialInterfacer)), $lSerializedXML)) {
	throw new Exception('bad object Values');
}
if (!compareXML($lXmlSerialUpdatedInterfacer->toString($lObject->export($lXmlSerialUpdatedInterfacer)), '<testDb id_1="1" id_2="1501774389"/>')) {
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
$lObject->fill($lObject->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== $lPrivateUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareJson(json_encode($lObject->export($lFlattenArrayPrivateUpdatedInterfacer)), $lPrivateFlattened)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->export($lFlattenArrayPrivateUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- public
$lObject->fill($lObject->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== $lPublicUpdatedValues) {
	throw new Exception('bad updated Values');
}
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!compareJson(json_encode($lObject->export($lFlattenArrayPublicUpdatedInterfacer)), $lPublicFlattened)) {
	throw new Exception('bad object Values');
}
$lObject->fill($lObject->export($lFlattenArrayPublicInterfacer), $lFlattenArrayPublicUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated Value');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->export($lFlattenArrayPublicUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new Exception('bad object Values');
}

// -- serial
$lObject->fill($lObject->export($lFlattenArraySerialInterfacer), $lFlattenArraySerialUpdatedInterfacer);
if (json_encode($lObject->getUpdatedValues()) !== '[]') {
	throw new Exception('bad updated Values');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if (json_encode($lObject->export($lFlattenArraySerialInterfacer)) !== $lSqlArray) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->export($lFlattenArraySerialUpdatedInterfacer)) !== '{"id_1":1,"id_2":"1501774389"}') {
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
if (json_encode($lObject->export($lStdPublicUpdatedInterfacer)) !== $lPublicStdObject) {
	throw new Exception('bad object Values');
}
if (!compareXML($lXmlPublicUpdatedInterfacer->toString($lObject->export($lXmlPublicUpdatedInterfacer)), $lPublicXml)) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->export($lFlattenArrayPublicUpdatedInterfacer)) !== $lPublicFlattened) {
	throw new Exception('bad object Values');
}

// -- private
if (json_encode($lObject->export($lStdPrivateUpdatedInterfacer)) !== $lPrivateStdObject) {
	throw new Exception('bad object Values');
}
if (!compareXML($lXmlPrivateUpdatedInterfacer->toString($lObject->export($lXmlPrivateUpdatedInterfacer)), $lPrivateXml)) {
	throw new Exception('bad object Values');
}
if (json_encode($lObject->export($lFlattenArrayPrivateUpdatedInterfacer)) !== $lPrivateFlattened) {
	throw new Exception('bad object Values');
}

// -- serial with foreign main object export
$lObject->getValue('mainParentTestDb')->loadValue('childrenTestDb', null, true);
$lObject->getValue('childrenTestDb')->getValue(0)->setValue('name', 'test_name');
$lObject->flagValueAsUpdated('id1');
$lObject->getValue('objectsWithId')->getValue(0)->setValue('plop3', $lObject->getValue('objectsWithId')->getValue(0)->getValue('plop3'));
$lObject->getValue('object')->setValue('plop2', $lObject->getValue('object')->getValue('plop2'));
$lObject->flagValueAsUpdated('integer');
$lObject->getValue('mainParentTestDb')->getValue('childrenTestDb')->getValue(0)->setValue('integer', 1);

$lStdSerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (!compareJson(json_encode($lObject->export($lStdSerialUpdatedInterfacer)), $lSerialStdObject)) {
	throw new Exception('bad object Values');
}
if (!compareJson(json_encode($lStdSerialUpdatedInterfacer->getMainForeignObjects()), '{"testDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"mainTestDb":{"1":{"id":1}},"childTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}')) {
	throw new Exception('bad foreign objects Values');
}

$lXmlSerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (!compareXML($lXmlSerialUpdatedInterfacer->toString($lObject->export($lXmlSerialUpdatedInterfacer)), $lSerialXml)) {
	throw new Exception('bad object Values');
}
if (!compareXML($lXmlSerialUpdatedInterfacer->toString($lXmlSerialUpdatedInterfacer->getMainForeignObjects()), '<objects><testDb><testDb id_1="1" id_2="23" integer="1"/><testDb id_1="1" id_2="50"/><testDb id_1="1" id_2="101"/><testDb id_1="2" id_2="50"/><testDb id_1="2" id_2="102"/></testDb><childTestDb><childTestDb id="1" name="test_name" parent_id_1="1"/><childTestDb id="2" parent_id_1="1"/></childTestDb><mainTestDb><mainTestDb id="1"/></mainTestDb></objects>')) {
	throw new Exception('bad foreign objects Values');
}

$lFlattenArraySerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (json_encode($lObject->export($lFlattenArraySerialUpdatedInterfacer)) !== $lSerialFlattened) {
	throw new Exception('bad object Values');
}
if (!compareJson(json_encode($lFlattenArraySerialUpdatedInterfacer->getMainForeignObjects()), '{"testDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"mainTestDb":{"1":{"id":1}},"childTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}')) {
	throw new Exception('bad foreign objects Values');
}


$time_end = microtime(true);
var_dump('partial import export test exec time '.($time_end - $time_start));
