<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as Object;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Model\Model;
use Object\Person;
use Object\Man;
use Comhon\Object\ComhonDateTime;
use Object\Woman;
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

$womanModel  = ModelManager::getInstance()->getInstanceModel('woman');
$manModel    = ModelManager::getInstance()->getInstanceModel('man');
$personModel = ModelManager::getInstance()->getInstanceModel('person');

$woman  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$person = MainObjectCollection::getInstance()->getObject(2, 'person');

if (!is_null($person) || !is_null($woman)) {
	throw new \Exception('object already initialized');
}
if ($personModel->getSerializationSettings() !== $womanModel->getSerializationSettings()) {
	throw new \Exception('not same serialization instance');
}
if ($personModel->getSerializationSettings() !== $manModel->getSerializationSettings()) {
	throw new \Exception('not same serialization instance');
}
if ($personModel->getSerialization()->getInheritanceKey() != 'sex') {
	throw new \Exception('bad inheritance key');
}

$person = $personModel->getObjectInstance(false);
$person->setId(2);
$woman = $personModel->loadObject(2);

if ($woman->getModel() !== $womanModel) {
	throw new \Exception('not good model');
}
if ($woman !== $person) {
	throw new \Exception('not same instance object');
}
if ($woman !== MainObjectCollection::getInstance()->getObject(2, 'woman')) {
	throw new \Exception('object not in objectcollection');
}
if ($woman !== MainObjectCollection::getInstance()->getObject(2, 'person')) {
	throw new \Exception('object not in objectcollection');
}

MainObjectCollection::getInstance()->removeObject($woman);

$woman2  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$person2 = MainObjectCollection::getInstance()->getObject(2, 'person');

if (!is_null($person2) || !is_null($woman2)) {
	throw new \Exception('object not removed');
}

MainObjectCollection::getInstance()->addObject($woman);

$woman  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$person = MainObjectCollection::getInstance()->getObject(2, 'person');

if (is_null($person) || is_null($woman)) {
	throw new \Exception('object not added');
}


try {
	$woman = $manModel->loadObject(7);
	$throw = true;
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('cast \'man\' to \'woman\' should not work');
}

$woman = MainObjectCollection::getInstance()->getObject(2, 'woman');
$woman->loadValue('bodies');

$man = $manModel->loadObject(1);
$man->loadValue('children');
if ($man->getValue('children')->count() != 3) {
	throw new \Exception('bad children count');
}
foreach ($man->getValue('children') as $child) {
	switch ($child->getId()) {
		case 5:  if ($child->getModel()->getName() !== 'man') throw new \Exception('bad model : '.$child->getModel()->getName()); break;
		case 6:  if ($child->getModel()->getName() !== 'man') throw new \Exception('bad model : '.$child->getModel()->getName()); break;
		case 11: if ($child->getModel()->getName() !== 'woman') throw new \Exception('bad model : '.$child->getModel()->getName()); break;
		default: throw new \Exception('bad id '.$child->getId());
	}
}

foreach (MainObjectCollection::getInstance()->getModelObjects('person') as $testPerson) {
	if ($testPerson->getId() === 1 ) {
		if (!($testPerson instanceof \Comhon\Object\Object)) {
			throw new \Exception('wrong class');
		}
	} else if ($testPerson->getId() === 11) {
		if (!($testPerson instanceof Woman)) {
			throw new \Exception('wrong class');
		}
	} else if (!($testPerson instanceof Person)) {
		throw new \Exception('wrong class');
	}
}

/** @var Object $body */
$body = $woman->getValue('bodies')->getValue(0);
if (json_encode($body->getValue('tatoos')->export($stdPrivateInterfacer)) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":5,"__inheritance__":"man"}},{"type":"sentence","location":"arm","tatooArtist":{"id":6,"__inheritance__":"man"}},{"type":"sentence","location":"leg","tatooArtist":{"id":5,"__inheritance__":"man"}}]') {
	throw new \Exception('not same object values');
}
$body->setValue('arts', $body->getProperty('arts')->getModel()->getObjectInstance());
$body->getValue('arts')->pushValue($body->getValue('tatoos')->getValue(0));
$body->getValue('arts')->pushValue($body->getValue('piercings')->getValue(0));

if (!compareJson(json_encode($body->getValue('arts')->export($stdPrivateInterfacer)), '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":5,"__inheritance__":"man"},"__inheritance__":"body\\\\tatoo"},{"type":"earring","location":"ear","piercer":{"id":5,"__inheritance__":"man"},"__inheritance__":"body\\\\piercing"}]')) {
	throw new \Exception('not same object values');
}
$xmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$bodyTwo = $body->getModel()->import($body->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
$xmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($bodyTwo === $body) {
	throw new \Exception('same object instance');
}
if (json_encode($bodyTwo->getValue('arts')->export($stdPrivateInterfacer)) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":5,"__inheritance__":"man"},"__inheritance__":"body\\\\tatoo"},{"type":"earring","location":"ear","piercer":{"id":5,"__inheritance__":"man"},"__inheritance__":"body\\\\piercing"}]') {
	throw new \Exception('not same object values');
}
$bodyTwo = $body->getModel()->import($body->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if ($bodyTwo !== $body) {
	throw new \Exception('not same object instance');
}

$woman->reorderValues();
if (!compareJson(json_encode($woman->export($stdPrivateInterfacer)), '{"id":2,"firstName":"Marie","lastName":"Smith","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":null,"bestFriend":{"id":5,"__inheritance__":"man"},"father":null,"mother":null,"bodies":[1]}')) {
	throw new \Exception('not same object values : '.json_encode($woman->export($stdPrivateInterfacer)));
}
if (!compareXML($xmlPrivateInterfacer->toString($woman->export($xmlPrivateInterfacer)), '<woman xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="2" firstName="Marie" lastName="Smith" birthDate="2016-11-13T20:04:05+01:00"><birthPlace xsi:nil="true"/><bestFriend id="5" __inheritance__="man"/><father xsi:nil="true"/><mother xsi:nil="true"/><bodies><body>1</body></bodies></woman>')) {
	throw new \Exception('not same object values');
}
if (!compareJson(json_encode($woman->export($flattenArraySerialInterfacer)), '{"id":2,"first_name":"Marie","lastName":"Smith","birth_date":"2016-11-13T20:04:05+01:00","birth_place":null,"best_friend":5,"father_id":null,"mother_id":null}')) {
	throw new \Exception('not same object values : '.json_encode($woman->export($flattenArraySerialInterfacer)));
}

$man = MainObjectCollection::getInstance()->getObject(5, 'man');

if ($man->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($man->getValue('bestFriend')->getModel() !== $personModel) {
	throw new \Exception('bad model');
}
$man->loadValue('bestFriend');
if (!$man->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($man->getValue('bestFriend')->getModel() !== $womanModel) {
	throw new \Exception('bad model');
}
$woman = $womanModel->loadObject(8);

if ($woman->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($woman->getValue('bestFriend')->getModel() !== $personModel) {
	throw new \Exception('bad model');
}
$womanNine = $womanModel->loadObject(9);
if ($womanNine->getModel() !== $womanModel) {
	throw new \Exception('bad model');
}

if ($woman->getValue('bestFriend') !== $womanNine) {
	throw new \Exception('not same object instance');
}

$bestFriend = $man->getValue('bestFriend');
$father = $man->getValue('father');
$mother = $man->getValue('mother');

$manStdObject = $man->export($stdPrivateInterfacer);
$manXml = $man->export($xmlPrivateInterfacer);
$manSql = $man->export($flattenArrayPrivateInterfacer);

$manImported = $manModel->import($man->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if ($manImported !== $man) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('bestFriend') !== $bestFriend) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('father') !== $father) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('mother') !== $mother) {
	throw new \Exception('not same object instance');
}

$manImported = $manModel->import($manImported->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if ($manImported !== $man) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('bestFriend') !== $bestFriend) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('father') !== $father) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('mother') !== $mother) {
	throw new \Exception('not same object instance');
}

$manImported = $manModel->import($manImported->export($flattenArrayPrivateInterfacer), $flattenArrayPrivateInterfacer);
if ($manImported !== $man) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('bestFriend') !== $bestFriend) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('father') !== $father) {
	throw new \Exception('not same object instance');
}
if ($manImported->getValue('mother') !== $mother) {
	throw new \Exception('not same object instance');
}

if (!compareJson(json_encode($manStdObject), json_encode($manImported->export($stdPrivateInterfacer)))) {
	throw new \Exception('not same string object');
}
if (!compareDomElement($manXml, $manImported->export($xmlPrivateInterfacer))) {
	throw new \Exception('not same string object');
}
if (!compareJson(json_encode($manSql), json_encode($manImported->export($flattenArrayPrivateInterfacer)))) {
	throw new \Exception('not same string object');
}

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$object = $dbTestModel->loadObject('[1,"1501774389"]');
$object->reorderValues();

if (!compareJson(json_encode($object->export($stdPrivateInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad private object value '.json_encode($object->export($stdPrivateInterfacer)));
}

if (!compareJson(json_encode($object->export($stdPublicInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad public object value');
}
$stdObject = $object->export($stdPrivateInterfacer);
$stdObject->string = 'azeazeazeazeaze';
$stdObject->objectsWithId[0]->plop3 = 'azeazeazeazeaze';
$object->fill($stdObject, $stdPublicInterfacer);
if (!compareJson(json_encode($object->export($stdPrivateInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad private object value '.json_encode($object->export($stdPrivateInterfacer)));
}

if ($object->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($object->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($object->getValue('objectsWithId')->getValue($i) !== $object->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(1) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(4) !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($object->getValue('lonelyForeignObjectTwo') !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}

$objectOne = $object;
$stdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$object = $dbTestModel->import($object->export($stdPrivateInterfacer), $stdPrivateInterfacer);
$stdPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($objectOne === $object) {
	throw new \Exception('same object instance');
}

if ($object->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($object->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($object->getValue('objectsWithId')->getValue($i) !== $object->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(1) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(4) !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($object->getValue('lonelyForeignObjectTwo') !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}

$xmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$object = $dbTestModel->import($object->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
$xmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($objectOne === $object) {
	throw new \Exception('same object instance');
}

if ($object->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($object->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($object->getValue('objectsWithId')->getValue($i) !== $object->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(0) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(1) === $object->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($object->getValue('objectsWithId')->getValue(4) !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($object->getValue('lonelyForeignObjectTwo') !== $object->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
/** ****************************** test load new value ****************************** **/

$womanModelXml   = ModelManager::getInstance()->getInstanceModel('womanXml');
$womanModelXmlEX = ModelManager::getInstance()->getInstanceModel('womanXmlExtended');
$manModelJson    = ModelManager::getInstance()->getInstanceModel('manBodyJson');
$manModelJsonEx  = ModelManager::getInstance()->getInstanceModel('manBodyJsonExtended');

$obj = $manModelJson->loadObject(156);
if ($obj->getModel()->getName() !== $manModelJson->getName()) {
	throw new \Exception('bad model name');
}
$obj->save();

$obj = $manModelJson->loadObject(1567);
$obj1567 = $obj;
if ($obj->getModel()->getName() !== $manModelJsonEx->getName()) {
	throw new \Exception("bad model name : {$obj->getModel()->getName()} !== {$manModelJsonEx->getName()}");
}
$obj->save();
$obj = $womanModelXml->loadObject(2);
if ($obj->getModel()->getName() !== $womanModelXml->getName()) {
	throw new \Exception('bad model name');
}
$obj->save();

$obj = $womanModelXml->loadObject(3);
$obj3 = $obj;
if ($obj->getModel()->getName() !== $womanModelXmlEX->getName()) {
	throw new \Exception('bad model name');
}
$obj->save();

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$object = $dbTestModel->loadObject('[4,"50"]');

if ($object->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object already loaded');
}
$object->loadValue('womanXml');
if (!$object->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object not loaded');
}
$obj = $womanModelXml->loadObject(4);
if ($obj !== $object->getValue('womanXml')) {
	throw new \Exception('not same instance object');
}

if ($object->getValue('manBodyJson')->isLoaded()) {
	throw new \Exception('object already loaded');
}
$obj = $manModelJson->loadObject(4567);
if ($obj !== $object->getValue('manBodyJson')) {
	throw new \Exception('not same instance object');
}

/** ****************** export private with foreign main object ********************* **/

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
/** @var Object $object */
$object = $dbTestModel->loadObject('[40,"50"]');
$object->loadValue('mainParentTestDb');

$stdPrivateInterfacer->setExportMainForeignObjects(true);
$object->export($stdPrivateInterfacer);
$array = $stdPrivateInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}

$flattenArrayPrivateInterfacer->setExportMainForeignObjects(true);
$object->export($flattenArrayPrivateInterfacer);
$array = $flattenArrayPrivateInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}

$xmlPrivateInterfacer->setExportMainForeignObjects(true);
$object->export($xmlPrivateInterfacer);
$XML = $xmlPrivateInterfacer->getMainForeignObjects();
if (!compareXML($xmlPrivateInterfacer->toString($XML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

/** ****************** export public with foreign main object ********************* **/

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$object = $dbTestModel->loadObject('[40,"50"]');
$object->loadValue('mainParentTestDb');
$array = [];
$stdPublicInterfacer->setExportMainForeignObjects(true);
$object->export($stdPublicInterfacer);
$array = $stdPublicInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$array = [];
$flattenArrayPublicInterfacer->setExportMainForeignObjects(true);
$object->export($flattenArrayPublicInterfacer);
$array = $flattenArrayPublicInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$array = [];
$xmlPublicInterfacer->setExportMainForeignObjects(true);
$object->export($xmlPublicInterfacer);
$XML = $xmlPublicInterfacer->getMainForeignObjects();
if (!compareXML($xmlPublicInterfacer->toString($XML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

/** ****************** export serial with foreign main object ********************* **/

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$object = $dbTestModel->loadObject('[40,"50"]');
$object->loadValue('mainParentTestDb');
$array = [];
$stdSerialInterfacer->setExportMainForeignObjects(true);
$object->export($stdSerialInterfacer);
$array = $stdSerialInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$array = [];
$flattenArraySerialInterfacer->setExportMainForeignObjects(true);
$object->export($flattenArraySerialInterfacer);
$array = $flattenArraySerialInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($array), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":3,"lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$array = [];
$xmlSerialInterfacer->setExportMainForeignObjects(true);
$object->export($xmlSerialInterfacer);
$XML = $xmlSerialInterfacer->getMainForeignObjects();
if (!compareXML($xmlSerialInterfacer->toString($XML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

if (!$object->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object not loaded');
}
if ($obj3 !== $object->getValue('womanXml')) {
	throw new \Exception('not same instance object');
}
if (!$object->getValue('manBodyJson')->isLoaded()) {
	throw new \Exception('object not loaded');
}
if ($obj1567 !== $object->getValue('manBodyJson')) {
	throw new \Exception('not same instance object');
}

/** ****************** test extended object class ********************* **/

$person = new Person();
if ($person->getModel() !== ModelManager::getInstance()->getInstanceModel('person')) {
	throw new \Exception('not same instance model');
}
$person->cast(ModelManager::getInstance()->getInstanceModel('man'));
if ($person->getModel() !== ModelManager::getInstance()->getInstanceModel('man')) {
	throw new \Exception('not same instance model');
}

$man = new Man();
if ($man->getModel() !== ModelManager::getInstance()->getInstanceModel('man')) {
	throw new \Exception('not same instance model');
}

$man->setFirstName('Jean');
$man->setLastName('De La Fontaine');
$man->setBirthDate(new ComhonDateTime('1674-03-02'));

if (!compareJson(json_encode($man->export($stdPrivateInterfacer)), '{"firstName":"Jean","lastName":"De La Fontaine","birthDate":"1674-03-02T00:00:00+01:00"}')) {
	throw new \Exception('bad value');
}

/** ****************** test extended local model from main model ********************* **/

$localExtendsMainModel = ModelManager::getInstance()->getInstanceModel('localExtendsMain');
$localPlaceModel = ModelManager::getInstance()->getInstanceModel('localExtendsMain\localPlace');
$object = $localExtendsMainModel->getObjectInstance();

$object->initValue('localExtendsMainObj');
$object->getValue('localExtendsMainObj')->setValue('id', 12);
$object->getValue('localExtendsMainObj')->cast($localPlaceModel);
$object->getValue('localExtendsMainObj')->setValue('stringValue', 'aze');
$object->setValue('localExtendsMainObjForeign', $object->getValue('localExtendsMainObj'));

$object->fill($object->export($stdPrivateInterfacer), $stdPrivateInterfacer);

if ($object->getValue('localExtendsMainObj') !== $object->getValue('localExtendsMainObjForeign')) {
	throw new \Exception('not same instance model');
}
if (!compareJson(json_encode($object->export($stdPrivateInterfacer)), '{"stringValue":"plop","floatValue":1.5,"booleanValue":true,"indexValue":0,"percentageValue":1,"dateValue":"2016-11-13T20:04:05+01:00","localExtendsMainObj":{"id":12,"stringValue":"aze","__inheritance__":"localExtendsMain\\\\localPlace"},"localExtendsMainObjForeign":{"id":12,"__inheritance__":"localExtendsMain\\\\localPlace"}}')) {
	throw new \Exception('bad value');
}


$time_end = microtime(true);
var_dump('extended value test exec time '.($time_end - $time_start));