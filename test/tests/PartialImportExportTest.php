<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

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

/*********************************************/

$stdPrivateUpdatedInterfacer = new StdObjectInterfacer();
$stdPrivateUpdatedInterfacer->setPrivateContext(true);
$stdPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$stdPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$stdPublicUpdatedInterfacer = new StdObjectInterfacer();
$stdPublicUpdatedInterfacer->setPrivateContext(false);
$stdPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$stdPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$stdSerialUpdatedInterfacer = new StdObjectInterfacer();
$stdSerialUpdatedInterfacer->setPrivateContext(true);
$stdSerialUpdatedInterfacer->setSerialContext(true);
$stdSerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$stdSerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$xmlPrivateUpdatedInterfacer = new XMLInterfacer();
$xmlPrivateUpdatedInterfacer->setPrivateContext(true);
$xmlPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$xmlPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$xmlPublicUpdatedInterfacer= new XMLInterfacer();
$xmlPublicUpdatedInterfacer->setPrivateContext(false);
$xmlPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$xmlPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$xmlSerialUpdatedInterfacer = new XMLInterfacer();
$xmlSerialUpdatedInterfacer->setPrivateContext(true);
$xmlSerialUpdatedInterfacer->setSerialContext(true);
$xmlSerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$xmlSerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$flattenArrayPrivateUpdatedInterfacer = new AssocArrayInterfacer();
$flattenArrayPrivateUpdatedInterfacer->setPrivateContext(true);
$flattenArrayPrivateUpdatedInterfacer->setFlattenValues(true);
$flattenArrayPrivateUpdatedInterfacer->setFlagValuesAsUpdated(false);
$flattenArrayPrivateUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$flattenArrayPublicUpdatedInterfacer = new AssocArrayInterfacer();
$flattenArrayPublicUpdatedInterfacer->setPrivateContext(false);
$flattenArrayPublicUpdatedInterfacer->setFlattenValues(true);
$flattenArrayPublicUpdatedInterfacer->setFlagValuesAsUpdated(false);
$flattenArrayPublicUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$flattenArraySerialUpdatedInterfacer = new AssocArrayInterfacer();
$flattenArraySerialUpdatedInterfacer->setPrivateContext(true);
$flattenArraySerialUpdatedInterfacer->setFlattenValues(true);
$flattenArraySerialUpdatedInterfacer->setSerialContext(true);
$flattenArraySerialUpdatedInterfacer->setFlagValuesAsUpdated(false);
$flattenArraySerialUpdatedInterfacer->setExportOnlyUpdatedValues(true);

$privateUpdatedValues = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"string":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false}';
$publicUpdatedValues  = '{"id1":false,"id2":false,"date":false,"timestamp":false,"object":false,"objectWithId":false,"integer":false,"mainParentTestDb":false,"objectsWithId":false,"foreignObjects":false,"lonelyForeignObject":false,"lonelyForeignObjectTwo":false,"defaultValue":false,"boolean":false,"boolean2":false}';

$privateStdObject = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$privateXml       = '<root defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></root>';
$privateFlattened = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$publicStdObject  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":[1,2]}';
$publicXml        = '<root defaultValue="default" id1="1" id2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><objectWithId plop="plop" plop2="plop2"/><mainParentTestDb>1</mainParentTestDb><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId><foreignObjects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreignObjects><lonelyForeignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonelyForeignObjectTwo>11</lonelyForeignObjectTwo><childrenTestDb><childTestDb>1</childTestDb><childTestDb>2</childTestDb></childrenTestDb></root>';
$publicFlattened  = '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","objectWithId":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"mainParentTestDb":1,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","foreignObjects":"[{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","lonelyForeignObject":"{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}","lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true,"childrenTestDb":"[1,2]"}';

$serializedObject = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"object_with_id":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"foreign_objects":[{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}],"lonely_foreign_object":{"id":"11","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},"lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';
$serializedXML    = '<root default_value="default" id_1="1" id_2="1501774389" date="2016-04-12T05:14:33+02:00" timestamp="2016-10-13T11:50:19+02:00" string="nnnn" integer="2" boolean="0" boolean2="1"><object plop="plop" plop2="plop2"/><object_with_id plop="plop" plop2="plop2"/><main_test_id>1</main_test_id><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objects_with_id><foreign_objects><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><foreignObject id="1" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><foreignObject>1</foreignObject><foreignObject>11</foreignObject><foreignObject id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></foreign_objects><lonely_foreign_object id="11" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><lonely_foreign_object_two>11</lonely_foreign_object_two></root>';
$sqlArray         = '{"default_value":"default","id_1":1,"id_2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","object_with_id":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","string":"nnnn","integer":2,"main_test_id":1,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","foreign_objects":"[{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]","lonely_foreign_object":"{\"id\":\"11\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}","lonely_foreign_object_two":"11","boolean":false,"boolean2":true}';

$dbTestModel = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
$object = $dbTestModel->loadObject('[1,"1501774389"]');

if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
$object = $dbTestModel->loadObject('[1,"1501774389"]', null, true);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
$object->unsetValue('manBodyJson', false);
$object->unsetValue('womanXml', false);
if (!compareJson(json_encode($object->export($stdPrivateInterfacer)), $privateStdObject)) {
	throw new \Exception('bad object Values');
}

/** ----------------------------- import/export stdObject --------------------------------- **/
// -- private
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
$object->fill($object->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if (!compareJson(json_encode($object->getUpdatedValues()), $privateUpdatedValues)) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareJson(json_encode($object->export($stdPrivateUpdatedInterfacer)), $privateStdObject)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($stdPrivateInterfacer), $stdPrivateUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (!compareJson(json_encode($object->export($stdPrivateUpdatedInterfacer)), '{"id1":1,"id2":"1501774389"}')) {
	throw new \Exception('bad object Values');
}

// -- public
$object->fill($object->export($stdPublicInterfacer), $stdPublicInterfacer);
if (json_encode($object->getUpdatedValues()) !== $publicUpdatedValues) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareJson(json_encode($object->export($stdPublicUpdatedInterfacer)), $publicStdObject)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($stdPublicInterfacer), $stdPublicUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->export($stdPublicUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new \Exception('bad object Values');
}

// -- serial
$object->fill($object->export($stdSerialInterfacer), $stdSerialUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('bad updated Values');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->export($stdSerialInterfacer)) !== $serializedObject) {
	throw new \Exception('bad object Values');
}
if (json_encode($object->export($stdSerialUpdatedInterfacer)) !== '{"id_1":1,"id_2":"1501774389"}') {
	throw new \Exception('bad object Values');
}

/** ----------------------------- import/export xml --------------------------------- **/
// -- private
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
$object->fill($object->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if (json_encode($object->getUpdatedValues()) !== $privateUpdatedValues) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareXML($xmlPrivateUpdatedInterfacer->toString($object->export($xmlPrivateUpdatedInterfacer)), $privateXml)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($xmlPrivateInterfacer), $xmlPrivateUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (!compareXML($xmlPrivateUpdatedInterfacer->toString($object->export($xmlPrivateUpdatedInterfacer)), '<root id1="1" id2="1501774389"/>')) {
	throw new \Exception('bad object Values');
}

// -- public
$object->fill($object->export($xmlPublicInterfacer), $xmlPublicInterfacer);
if (json_encode($object->getUpdatedValues()) !== $publicUpdatedValues) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareXML($xmlPublicUpdatedInterfacer->toString($object->export($xmlPublicUpdatedInterfacer)), $publicXml)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($xmlPublicInterfacer), $xmlPublicUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (!compareXML($xmlPublicUpdatedInterfacer->toString($object->export($xmlPublicUpdatedInterfacer)), '<root id1="1" id2="1501774389"/>')) {
	throw new \Exception('bad object Values');
}

// -- serial
$object->fill($object->export($xmlSerialInterfacer), $xmlSerialUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('bad updated Values');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (!compareXML($xmlSerialInterfacer->toString($object->export($xmlSerialInterfacer)), $serializedXML)) {
	throw new \Exception('bad object Values');
}
if (!compareXML($xmlSerialUpdatedInterfacer->toString($object->export($xmlSerialUpdatedInterfacer)), '<root id_1="1" id_2="1501774389"/>')) {
	throw new \Exception('bad object Values');
}

/** ----------------------------- import/export flattened array --------------------------------- **/
// -- private
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
$object->fill($object->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if (json_encode($object->getUpdatedValues()) !== $privateUpdatedValues) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareJson(json_encode($object->export($flattenArrayPrivateUpdatedInterfacer)), $privateFlattened)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->export($flattenArrayPrivateUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new \Exception('bad object Values');
}

// -- public
$object->fill($object->export($flattenArrayPublicInterfacer), $flattenArrayPublicInterfacer);
if (json_encode($object->getUpdatedValues()) !== $publicUpdatedValues) {
	throw new \Exception('bad updated Values');
}
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!compareJson(json_encode($object->export($flattenArrayPublicUpdatedInterfacer)), $publicFlattened)) {
	throw new \Exception('bad object Values');
}
$object->fill($object->export($flattenArrayPublicInterfacer), $flattenArrayPublicUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated Value');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->export($flattenArrayPublicUpdatedInterfacer)) !== '{"id1":1,"id2":"1501774389"}') {
	throw new \Exception('bad object Values');
}

// -- serial
$object->fill($object->export($flattenArraySerialInterfacer), $flattenArraySerialUpdatedInterfacer);
if (json_encode($object->getUpdatedValues()) !== '[]') {
	throw new \Exception('bad updated Values');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if (json_encode($object->export($flattenArraySerialInterfacer)) !== $sqlArray) {
	throw new \Exception('bad object Values');
}
if (json_encode($object->export($flattenArraySerialUpdatedInterfacer)) !== '{"id_1":1,"id_2":"1501774389"}') {
	throw new \Exception('bad object Values');
}

/** ----------------------------- import/export with some updated values --------------------------------- **/

$object->setValue('integer', 2);
$object->getValue('object')->setValue('plop2', 'plop2');
$object->getValue('lonelyForeignObjectTwo')->setValue("plop3", "heyplop33");

$publicStdObject = '{"id1":1,"id2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}]}';
$publicXml       = '<root id1="1" id2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId></root>';
$publicFlattened = '{"id1":1,"id2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]"}';

$privateStdObject = '{"id1":1,"id2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}]}';
$privateXml       = '<root id1="1" id2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objectsWithId><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objectsWithId></root>';
$privateFlattened = '{"id1":1,"id2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objectsWithId":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]"}';

$serialStdObject = '{"id_1":1,"id_2":"1501774389","object":{"plop":"plop","plop2":"plop2"},"integer":2,"objects_with_id":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\\\TestDb\\\\ObjectWithIdAndMore"}]}';
$serialXml       = '<root id_1="1" id_2="1501774389" integer="2"><object plop="plop" plop2="plop2"/><objects_with_id><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" plop4="heyplop4" __inheritance__="Test\TestDb\ObjectWithIdAndMoreMore"/><objectWithId plop="1" plop2="heyplop2" plop3="heyplop3" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/><objectWithId plop="1" plop2="heyplop2"/><objectWithId plop="11" plop2="heyplop22"/><objectWithId plop="11" plop2="heyplop22" plop3="heyplop33" __inheritance__="Test\TestDb\ObjectWithIdAndMore"/></objects_with_id></root>';
$serialFlattened = '{"id_1":1,"id_2":"1501774389","object":"{\"plop\":\"plop\",\"plop2\":\"plop2\"}","integer":2,"objects_with_id":"[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"__inheritance__\":\"Test\\\\\\\\TestDb\\\\\\\\ObjectWithIdAndMore\"}]"}';

// -- public
if (json_encode($object->export($stdPublicUpdatedInterfacer)) !== $publicStdObject) {
	throw new \Exception('bad object Values');
}
if (!compareXML($xmlPublicUpdatedInterfacer->toString($object->export($xmlPublicUpdatedInterfacer)), $publicXml)) {
	throw new \Exception('bad object Values');
}
if (json_encode($object->export($flattenArrayPublicUpdatedInterfacer)) !== $publicFlattened) {
	throw new \Exception('bad object Values');
}

// -- private
if (json_encode($object->export($stdPrivateUpdatedInterfacer)) !== $privateStdObject) {
	throw new \Exception('bad object Values');
}
if (!compareXML($xmlPrivateUpdatedInterfacer->toString($object->export($xmlPrivateUpdatedInterfacer)), $privateXml)) {
	throw new \Exception('bad object Values');
}
if (json_encode($object->export($flattenArrayPrivateUpdatedInterfacer)) !== $privateFlattened) {
	throw new \Exception('bad object Values');
}

// -- serial with foreign main object export
$object->getValue('mainParentTestDb')->loadValue('childrenTestDb', null, true);
$object->getValue('childrenTestDb')->getValue(0)->setValue('name', 'test_name');
$object->flagValueAsUpdated('id1');
$object->getValue('objectsWithId')->getValue(0)->setValue('plop3', $object->getValue('objectsWithId')->getValue(0)->getValue('plop3'));
$object->getValue('object')->setValue('plop2', $object->getValue('object')->getValue('plop2'));
$object->flagValueAsUpdated('integer');

// reorder values due to different ordering between mysql and postgresql
$values = $object->getValue('mainParentTestDb')->getValue('childrenTestDb')->getValues();
foreach ($values as $i => $value) {
	if ($i < 0 || $i > 5) {
		throw new \Exception('wrong index'.$i);
	}
	if ($value->getId() === '[1,"23"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(0, $value);
	}
	if ($value->getId() === '[1,"50"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(1, $value);
	}
	if ($value->getId() === '[1,"101"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(2, $value);
	}
	if ($value->getId() === '[2,"50"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(3, $value);
	}
	if ($value->getId() === '[2,"102"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(4, $value);
	}
	if ($value->getId() === '[1,"1501774389"]') {
		$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->setValue(5, $value);
	}
}

$object->getValue('mainParentTestDb')->getValue('childrenTestDb')->getValue(0)->setValue('integer', 1);

$stdSerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (!compareJson(json_encode($object->export($stdSerialUpdatedInterfacer)), $serialStdObject)) {
	throw new \Exception('bad object Values');
}
if (!compareJson(json_encode($stdSerialUpdatedInterfacer->getMainForeignObjects()), '{"Test\\\\TestDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"Test\\\\MainTestDb":{"1":{"id":1}},"Test\\\\ChildTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}')) {
	throw new \Exception('bad foreign objects Values 1');
}

$xmlSerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (!compareXML($xmlSerialUpdatedInterfacer->toString($object->export($xmlSerialUpdatedInterfacer)), $serialXml)) {
	throw new \Exception('bad object Values');
}
if (!compareXML($xmlSerialUpdatedInterfacer->toString($xmlSerialUpdatedInterfacer->getMainForeignObjects()), '<objects><TestDb namespace="Test\"><root id_1="1" id_2="23" integer="1"/><root id_1="1" id_2="50"/><root id_1="1" id_2="101"/><root id_1="2" id_2="50"/><root id_1="2" id_2="102"/></TestDb><ChildTestDb namespace="Test\"><root id="1" name="test_name" parent_id_1="1"/><root id="2" parent_id_1="1"/></ChildTestDb><MainTestDb namespace="Test\"><root id="1"/></MainTestDb></objects>')) {
	throw new \Exception('bad foreign objects Values 2');
}

$flattenArraySerialUpdatedInterfacer->setExportMainForeignObjects(true);
if (json_encode($object->export($flattenArraySerialUpdatedInterfacer)) !== $serialFlattened) {
	throw new \Exception('bad object Values');
}
if (!compareJson(json_encode($flattenArraySerialUpdatedInterfacer->getMainForeignObjects()), '{"Test\\\\TestDb":{"[1,\"23\"]":{"id_1":1,"id_2":"23","integer":1},"[1,\"50\"]":{"id_1":1,"id_2":"50"},"[1,\"101\"]":{"id_1":1,"id_2":"101"},"[2,\"50\"]":{"id_1":2,"id_2":"50"},"[2,\"102\"]":{"id_1":2,"id_2":"102"}},"Test\\\\MainTestDb":{"1":{"id":1}},"Test\\\\ChildTestDb":{"1":{"id":1,"name":"test_name","parent_id_1":1},"2":{"id":2,"parent_id_1":1}}}')) {
	throw new \Exception('bad foreign objects Values 3');
}


$time_end = microtime(true);
var_dump('partial import export test exec time '.($time_end - $time_start));
