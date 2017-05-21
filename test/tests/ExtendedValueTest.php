<?php

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\collection\MainObjectCollection;
use comhon\model\Model;
use object\Person;
use object\Man;
use comhon\object\ComhonDateTime;
use object\Woman;
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

$lWomanModel  = ModelManager::getInstance()->getInstanceModel('woman');
$lManModel    = ModelManager::getInstance()->getInstanceModel('man');
$lPersonModel = ModelManager::getInstance()->getInstanceModel('person');

$lWoman  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$lPerson = MainObjectCollection::getInstance()->getObject(2, 'person');

if (!is_null($lPerson) || !is_null($lWoman)) {
	throw new Exception('object already initialized');
}
if ($lPersonModel->getSerializationSettings() !== $lWomanModel->getSerializationSettings()) {
	throw new Exception('not same serialization instance');
}
if ($lPersonModel->getSerializationSettings() !== $lManModel->getSerializationSettings()) {
	throw new Exception('not same serialization instance');
}
if ($lPersonModel->getSerialization()->getInheritanceKey() != 'sex') {
	throw new Exception('bad inheritance key');
}

$lPerson = $lPersonModel->getObjectInstance(false);
$lPerson->setId('2');
$lWoman = $lPersonModel->loadObject(2);

if ($lWoman->getModel() !== $lWomanModel) {
	throw new Exception('not good model');
}
if ($lWoman !== $lPerson) {
	throw new Exception('not same instance object');
}
if ($lWoman !== MainObjectCollection::getInstance()->getObject(2, 'woman')) {
	throw new Exception('object not in objectcollection');
}
if ($lWoman !== MainObjectCollection::getInstance()->getObject(2, 'person')) {
	throw new Exception('object not in objectcollection');
}

MainObjectCollection::getInstance()->removeObject($lWoman);

$lWoman2  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$lPerson2 = MainObjectCollection::getInstance()->getObject(2, 'person');

if (!is_null($lPerson2) || !is_null($lWoman2)) {
	throw new Exception('object not removed');
}

MainObjectCollection::getInstance()->addObject($lWoman);

$lWoman  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$lPerson = MainObjectCollection::getInstance()->getObject(2, 'person');

if (is_null($lPerson) || is_null($lWoman)) {
	throw new Exception('object not added');
}


try {
	$lWoman = $lManModel->loadObject('7');
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('cast \'man\' to \'woman\' should not work');
}

$lWoman = MainObjectCollection::getInstance()->getObject(2, 'woman');
$lWoman->loadValue('bodies');

$lMan = $lManModel->loadObject('1');
$lMan->loadValue('children');
if ($lMan->getValue('children')->count() != 3) {
	throw new \Exception('bad children count');
}
foreach ($lMan->getValue('children') as $lChild) {
	switch ($lChild->getId()) {
		case 5:  if ($lChild->getModel()->getName() !== 'man') throw new \Exception('bad model : '.$lChild->getModel()->getName()); break;
		case 6:  if ($lChild->getModel()->getName() !== 'man') throw new \Exception('bad model : '.$lChild->getModel()->getName()); break;
		case 11: if ($lChild->getModel()->getName() !== 'woman') throw new \Exception('bad model : '.$lChild->getModel()->getName()); break;
		default: throw new \Exception('bad id');
	}
}

foreach (MainObjectCollection::getInstance()->getModelObjects('person') as $lTestPerson) {
	if ($lTestPerson->getId() === '1' ) {
		if (!($lTestPerson instanceof \comhon\object\_final\Object)) {
			throw new Exception('wrong class');
		}
	} else if ($lTestPerson->getId() === '11') {
		if (!($lTestPerson instanceof Woman)) {
			throw new Exception('wrong class');
		}
	} else if (!($lTestPerson instanceof Person)) {
		throw new Exception('wrong class');
	}
}

/** @var Object $lBody */
$lBody = $lWoman->getValue('bodies')->getValue(0);
if (json_encode($lBody->getValue('tatoos')->export($lStdPrivateInterfacer)) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"}},{"type":"sentence","location":"arm","tatooArtist":{"id":"6","__inheritance__":"man"}},{"type":"sentence","location":"leg","tatooArtist":{"id":"5","__inheritance__":"man"}}]') {
	throw new \Exception('not same object values');
}
$lBody->setValue('arts', $lBody->getProperty('arts')->getModel()->getObjectInstance());
$lBody->getValue('arts')->pushValue($lBody->getValue('tatoos')->getValue(0));
$lBody->getValue('arts')->pushValue($lBody->getValue('piercings')->getValue(0));

if (json_encode($lBody->getValue('arts')->export($lStdPrivateInterfacer)) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"},"__inheritance__":"tatoo"},{"type":"earring","location":"ear","piercer":{"id":"5","__inheritance__":"man"},"__inheritance__":"piercing"}]') {
	throw new \Exception('not same object values');
}
$lXmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lBodyTwo = $lBody->getModel()->import($lBody->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
$lXmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($lBodyTwo === $lBody) {
	throw new \Exception('same object instance');
}
if (json_encode($lBodyTwo->getValue('arts')->export($lStdPrivateInterfacer)) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"},"__inheritance__":"tatoo"},{"type":"earring","location":"ear","piercer":{"id":"5","__inheritance__":"man"},"__inheritance__":"piercing"}]') {
	throw new \Exception('not same object values');
}
$lBodyTwo = $lBody->getModel()->import($lBody->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if ($lBodyTwo !== $lBody) {
	throw new \Exception('not same object instance');
}

$lWoman->reorderValues();
if (!compareJson(json_encode($lWoman->export($lStdPrivateInterfacer)), '{"id":"2","firstName":"Marie","lastName":"Smith","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":null,"bestFriend":{"id":"5","__inheritance__":"man"},"father":null,"mother":null,"bodies":[1]}')) {
	var_dump(json_encode($lWoman->export($lStdPrivateInterfacer)));
	throw new \Exception('not same object values : '.json_encode($lWoman->export($lStdPrivateInterfacer)));
}
if (!compareXML($lXmlPrivateInterfacer->toString($lWoman->export($lXmlPrivateInterfacer)), '<woman xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="2" firstName="Marie" lastName="Smith" birthDate="2016-11-13T20:04:05+01:00"><birthPlace xsi:nil="true"/><bestFriend id="5" __inheritance__="man"/><father xsi:nil="true"/><mother xsi:nil="true"/><bodies><body>1</body></bodies></woman>')) {
	var_dump($lXmlPrivateInterfacer->toString($lWoman->export($lXmlPrivateInterfacer)));
	throw new \Exception('not same object values');
}
if (!compareJson(json_encode($lWoman->export($lFlattenArraySerialInterfacer)), '{"id":"2","first_name":"Marie","lastName":"Smith","birth_date":"2016-11-13T20:04:05+01:00","birth_place":null,"best_friend":"5","father_id":null,"mother_id":null}')) {
	throw new \Exception('not same object values : '.json_encode($lWoman->export($lFlattenArraySerialInterfacer)));
}

$lMan = MainObjectCollection::getInstance()->getObject(5, 'man');

if ($lMan->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($lMan->getValue('bestFriend')->getModel() !== $lPersonModel) {
	throw new \Exception('bad model');
}
$lMan->loadValue('bestFriend');
if (!$lMan->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($lMan->getValue('bestFriend')->getModel() !== $lWomanModel) {
	throw new \Exception('bad model');
}
$lWoman = $lWomanModel->loadObject('8');

if ($lWoman->getValue('bestFriend')->isLoaded()) {
	throw new \Exception('object already loaded');
}
if ($lWoman->getValue('bestFriend')->getModel() !== $lPersonModel) {
	throw new \Exception('bad model');
}
$lWomanNine = $lWomanModel->loadObject(9);
if ($lWomanNine->getModel() !== $lWomanModel) {
	throw new \Exception('bad model');
}

if ($lWoman->getValue('bestFriend') !== $lWomanNine) {
	throw new \Exception('not same object instance');
}

$lBestFriend = $lMan->getValue('bestFriend');
$lFather = $lMan->getValue('father');
$lMother = $lMan->getValue('mother');

$lManStdObject = $lMan->export($lStdPrivateInterfacer);
$lManXml = $lMan->export($lXmlPrivateInterfacer);
$lManSql = $lMan->export($lFlattenArrayPrivateInterfacer);

$lManImported = $lManModel->import($lMan->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if ($lManImported !== $lMan) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('bestFriend') !== $lBestFriend) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('father') !== $lFather) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('mother') !== $lMother) {
	throw new \Exception('not same object instance');
}

$lManImported = $lManModel->import($lManImported->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if ($lManImported !== $lMan) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('bestFriend') !== $lBestFriend) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('father') !== $lFather) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('mother') !== $lMother) {
	throw new \Exception('not same object instance');
}

$lManImported = $lManModel->import($lManImported->export($lFlattenArrayPrivateInterfacer), $lFlattenArrayPrivateInterfacer);
if ($lManImported !== $lMan) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('bestFriend') !== $lBestFriend) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('father') !== $lFather) {
	throw new \Exception('not same object instance');
}
if ($lManImported->getValue('mother') !== $lMother) {
	throw new \Exception('not same object instance');
}

if (!compareJson(json_encode($lManStdObject), json_encode($lManImported->export($lStdPrivateInterfacer)))) {
	throw new \Exception('not same string object');
}
if (!compareDomElement($lManXml, $lManImported->export($lXmlPrivateInterfacer))) {
	throw new \Exception('not same string object');
}
if (!compareJson(json_encode($lManSql), json_encode($lManImported->export($lFlattenArrayPrivateInterfacer)))) {
	throw new \Exception('not same string object');
}

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,"1501774389"]');
$lObject->reorderValues();

if (!compareJson(json_encode($lObject->export($lStdPrivateInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad private object value '.json_encode($lObject->export($lStdPrivateInterfacer)));
}

if (!compareJson(json_encode($lObject->export($lStdPublicInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad public object value');
}
$lStdObject = $lObject->export($lStdPrivateInterfacer);
$lStdObject->string = 'azeazeazeazeaze';
$lStdObject->objectsWithId[0]->plop3 = 'azeazeazeazeaze';
$lObject->fill($lStdObject, $lStdPublicInterfacer);
if (!compareJson(json_encode($lObject->export($lStdPrivateInterfacer)), '{"id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","defaultValue":"default","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad private object value '.json_encode($lObject->export($lStdPrivateInterfacer)));
}

if ($lObject->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($lObject->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($lObject->getValue('objectsWithId')->getValue($i) !== $lObject->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(1) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(4) !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($lObject->getValue('lonelyForeignObjectTwo') !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}

$lObjectOne = $lObject;
$lStdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lObject = $lDbTestModel->import($lObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
$lStdPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($lObjectOne === $lObject) {
	throw new \Exception('same object instance');
}

if ($lObject->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($lObject->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($lObject->getValue('objectsWithId')->getValue($i) !== $lObject->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(1) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(4) !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($lObject->getValue('lonelyForeignObjectTwo') !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}

$lXmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lObject = $lDbTestModel->import($lObject->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
$lXmlPrivateInterfacer->setMergeType(Interfacer::MERGE);
if ($lObjectOne === $lObject) {
	throw new \Exception('same object instance');
}

if ($lObject->getValue('objectsWithId')->count() !== 5) {
	throw new \Exception('bad count objectsWithId');
}
for ($i = 0; $i < 5; $i++) {
	if (!is_object($lObject->getValue('objectsWithId')->getValue($i))) {
		throw new \Exception('not object');
	}
	if ($lObject->getValue('objectsWithId')->getValue($i) !== $lObject->getValue('foreignObjects')->getValue($i)) {
		throw new \Exception('not same object instance');
	}
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(1)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(0) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(1) === $lObject->getValue('objectsWithId')->getValue(2)) {
	throw new \Exception('same object instance');
}
if ($lObject->getValue('objectsWithId')->getValue(4) !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
if ($lObject->getValue('lonelyForeignObjectTwo') !== $lObject->getValue('lonelyForeignObject')) {
	throw new \Exception('not same object instance');
}
/** ****************************** test load new value ****************************** **/

$lWomanModelXml   = ModelManager::getInstance()->getInstanceModel('womanXml');
$lWomanModelXmlEX = ModelManager::getInstance()->getInstanceModel('womanXmlExtended');
$lManModelJson    = ModelManager::getInstance()->getInstanceModel('manBodyJson');
$lManModelJsonEx  = ModelManager::getInstance()->getInstanceModel('manBodyJsonExtended');

$lObj = $lManModelJson->loadObject(156);
if ($lObj->getModel()->getName() !== $lManModelJson->getName()) {
	throw new \Exception('bad model name');
}
$lObj->save();

$lObj = $lManModelJson->loadObject(1567);
$lObj1567 = $lObj;
if ($lObj->getModel()->getName() !== $lManModelJsonEx->getName()) {
	throw new \Exception("bad model name : {$lObj->getModel()->getName()} !== {$lManModelJsonEx->getName()}");
}
$lObj->save();
$lObj = $lWomanModelXml->loadObject('2');
if ($lObj->getModel()->getName() !== $lWomanModelXml->getName()) {
	throw new \Exception('bad model name');
}
$lObj->save();

$lObj = $lWomanModelXml->loadObject('3');
$lObj3 = $lObj;
if ($lObj->getModel()->getName() !== $lWomanModelXmlEX->getName()) {
	throw new \Exception('bad model name');
}
$lObj->save();

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[4,"50"]');

if ($lObject->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object already loaded');
}
$lObject->loadValue('womanXml');
if (!$lObject->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object not loaded');
}
$lObj = $lWomanModelXml->loadObject('4');
if ($lObj !== $lObject->getValue('womanXml')) {
	throw new \Exception('not same instance object');
}

if ($lObject->getValue('manBodyJson')->isLoaded()) {
	throw new \Exception('object already loaded');
}
$lObj = $lManModelJson->loadObject('4567');
if ($lObj !== $lObject->getValue('manBodyJson')) {
	throw new \Exception('not same instance object');
}

/** ****************** export private with foreign main object ********************* **/

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
/** @var Object $lObject */
$lObject = $lDbTestModel->loadObject('[40,"50"]');
$lObject->loadValue('mainParentTestDb');

$lStdPrivateInterfacer->setExportMainForeignObjects(true);
$lObject->export($lStdPrivateInterfacer);
$lArray = $lStdPrivateInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}

$lFlattenArrayPrivateInterfacer->setExportMainForeignObjects(true);
$lObject->export($lFlattenArrayPrivateInterfacer);
$lArray = $lFlattenArrayPrivateInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}

$lXmlPrivateInterfacer->setExportMainForeignObjects(true);
$lObject->export($lXmlPrivateInterfacer);
$lXML = $lXmlPrivateInterfacer->getMainForeignObjects();
if (!compareXML($lXmlPrivateInterfacer->toString($lXML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

/** ****************** export public with foreign main object ********************* **/

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[40,"50"]');
$lObject->loadValue('mainParentTestDb');
$lArray = [];
$lStdPublicInterfacer->setExportMainForeignObjects(true);
$lObject->export($lStdPublicInterfacer);
$lArray = $lStdPublicInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$lArray = [];
$lFlattenArrayPublicInterfacer->setExportMainForeignObjects(true);
$lObject->export($lFlattenArrayPublicInterfacer);
$lArray = $lFlattenArrayPublicInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$lArray = [];
$lXmlPublicInterfacer->setExportMainForeignObjects(true);
$lObject->export($lXmlPublicInterfacer);
$lXML = $lXmlPublicInterfacer->getMainForeignObjects();
if (!compareXML($lXmlPublicInterfacer->toString($lXML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

/** ****************** export serial with foreign main object ********************* **/

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[40,"50"]');
$lObject->loadValue('mainParentTestDb');
$lArray = [];
$lStdSerialInterfacer->setExportMainForeignObjects(true);
$lObject->export($lStdSerialInterfacer);
$lArray = $lStdSerialInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":{"plop":"ploooop","plop2":"ploooop2"}}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$lArray = [];
$lFlattenArraySerialInterfacer->setExportMainForeignObjects(true);
$lObject->export($lFlattenArraySerialInterfacer);
$lArray = $lFlattenArraySerialInterfacer->getMainForeignObjects();
if (!compareJson(json_encode($lArray), '{"testDb":[],"mainTestDb":{"2":{"id":2,"name":"qsdqsd","obj":"{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}"}},"manBodyJsonExtended":{"1567":{"id":1567,"date":"2010-12-24T00:00:00+01:00","height":1.8,"weight":80,"baldness":false}},"womanXmlExtended":{"3":{"id":"3","lastName":"Smith"}}}')) {
	throw new \Exception('not same foreign objects');
}
$lArray = [];
$lXmlSerialInterfacer->setExportMainForeignObjects(true);
$lObject->export($lXmlSerialInterfacer);
$lXML = $lXmlSerialInterfacer->getMainForeignObjects();
if (!compareXML($lXmlSerialInterfacer->toString($lXML), '<objects><testDb/><mainTestDb><mainTestDb id="2" name="qsdqsd"><obj plop="ploooop" plop2="ploooop2"/></mainTestDb></mainTestDb><manBodyJsonExtended><manBodyJsonExtended id="1567" date="2010-12-24T00:00:00+01:00" height="1.8" weight="80" baldness="0"/></manBodyJsonExtended><womanXmlExtended><womanXmlExtended id="3" lastName="Smith"/></womanXmlExtended></objects>')) {
	throw new \Exception('not same foreign objects');
}

if (!$lObject->getValue('womanXml')->isLoaded()) {
	throw new \Exception('object not loaded');
}
if ($lObj3 !== $lObject->getValue('womanXml')) {
	throw new \Exception('not same instance object');
}
if (!$lObject->getValue('manBodyJson')->isLoaded()) {
	throw new \Exception('object not loaded');
}
if ($lObj1567 !== $lObject->getValue('manBodyJson')) {
	throw new \Exception('not same instance object');
}

/** ****************** test extended object class ********************* **/

$lPerson = new Person();
if ($lPerson->getModel() !== ModelManager::getInstance()->getInstanceModel('person')) {
	throw new \Exception('not same instance model');
}
$lPerson->cast(ModelManager::getInstance()->getInstanceModel('man'));
if ($lPerson->getModel() !== ModelManager::getInstance()->getInstanceModel('man')) {
	throw new \Exception('not same instance model');
}

$lMan = new Man();
if ($lMan->getModel() !== ModelManager::getInstance()->getInstanceModel('man')) {
	throw new \Exception('not same instance model');
}

$lMan->setFirstName('Jean');
$lMan->setLastName('De La Fontaine');
$lMan->setBirthDate(new ComhonDateTime('1674-03-02'));

if (!compareJson(json_encode($lMan->export($lStdPrivateInterfacer)), '{"firstName":"Jean","lastName":"De La Fontaine","birthDate":"1674-03-02T00:00:00+01:00"}')) {
	var_dump(json_encode($lMan->export($lStdPrivateInterfacer)));
	throw new \Exception('bad value');
}

/** ****************** test extended local model from main model ********************* **/

$lLocalExtendsMainModel = ModelManager::getInstance()->getInstanceModel('localExtendsMain');
$lLocalPlaceModel = ModelManager::getInstance()->getInstanceModel('localPlace', 'localExtendsMain');
$lObject = $lLocalExtendsMainModel->getObjectInstance();

$lObject->initValue('localExtendsMainObj');
$lObject->getValue('localExtendsMainObj')->setValue('id', 12);
$lObject->getValue('localExtendsMainObj')->cast($lLocalPlaceModel);
$lObject->getValue('localExtendsMainObj')->setValue('stringValue', 'aze');
$lObject->setValue('localExtendsMainObjForeign', $lObject->getValue('localExtendsMainObj'));

$lObject->fill($lObject->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);

if ($lObject->getValue('localExtendsMainObj') !== $lObject->getValue('localExtendsMainObjForeign')) {
	throw new \Exception('not same instance model');
}
if (!compareJson(json_encode($lObject->export($lStdPrivateInterfacer)), '{"stringValue":"plop","floatValue":1.5,"booleanValue":true,"dateValue":"2016-11-13T20:04:05+01:00","localExtendsMainObj":{"id":12,"stringValue":"aze","__inheritance__":"localPlace"},"localExtendsMainObjForeign":{"id":12,"__inheritance__":"localPlace"}}')) {
	throw new \Exception('bad value');
}


$time_end = microtime(true);
var_dump('extended value test exec time '.($time_end - $time_start));