<?php

use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\api\ObjectService;
use comhon\object\object\SqlTable;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;
use comhon\controller\CompositionLoader;
use comhon\object\model\Model;

$time_start = microtime(true);

$lWomanModel  = InstanceModel::getInstance()->getInstanceModel('woman');
$lManModel    = InstanceModel::getInstance()->getInstanceModel('man');
$lPersonModel = InstanceModel::getInstance()->getInstanceModel('person');

$lWoman  = MainObjectCollection::getInstance()->getObject(2, 'woman');
$lPerson = MainObjectCollection::getInstance()->getObject(2, 'person');

if (!is_null($lPerson) && !is_null($lWoman)) {
	throw new Exception('object already initialized');
}
if ($lPersonModel->getSerialization() !== $lWomanModel->getSerialization()) {
	throw new Exception('not same serialization instance');
}
if ($lPersonModel->getSerialization() !== $lManModel->getSerialization()) {
	throw new Exception('not same serialization instance');
}
if ($lPersonModel->getSerialization()->getInheritanceKey() != 'sex') {
	throw new Exception('bad inheritance key');
}

$lPerson = $lPersonModel->getObjectInstance(false);
$lPerson->setValue('id', '2');
MainObjectCollection::getInstance()->addObject($lPerson);
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
if (count($lMan->getValue('children')->getValues()) != 3) {
	throw new \Exception('bad children count');
}
foreach ($lMan->getValue('children')->getValues() as $lChild) {
	switch ($lChild->getValue('id')) {
		case 5:  if ($lChild->getModel()->getModelName() !== 'man') throw new \Exception('bad model : '.$lChild->getModel()->getModelName()); break;
		case 6:  if ($lChild->getModel()->getModelName() !== 'man') throw new \Exception('bad model : '.$lChild->getModel()->getModelName()); break;
		case 11: if ($lChild->getModel()->getModelName() !== 'woman') throw new \Exception('bad model : '.$lChild->getModel()->getModelName()); break;
		default: throw new \Exception('bad id');
	}
}

$lBody = $lWoman->getValue('bodies')->getValue(0);
if (json_encode($lBody->getValue('tatoos')->toPrivateStdObject()) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"}},{"type":"sentence","location":"arm","tatooArtist":{"id":"6","__inheritance__":"man"}},{"type":"sentence","location":"leg","tatooArtist":{"id":"5","__inheritance__":"man"}}]') {
	throw new \Exception('not same object values');
}
$lBody->setValue('arts', $lBody->getModel()->getPropertyModel('arts')->getObjectInstance());
$lBody->getValue('arts')->pushValue($lBody->getValue('tatoos')->getValue(0));
$lBody->getValue('arts')->pushValue($lBody->getValue('piercings')->getValue(0));

if (json_encode($lBody->getValue('arts')->toPrivateStdObject()) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"},"__inheritance__":"tatoo"},{"type":"earring","location":"ear","piercer":{"id":"5","__inheritance__":"man"},"__inheritance__":"piercing"}]') {
	throw new \Exception('not same object values');
}
$lBodyTwo = $lBody->getModel()->fromPrivateXml($lBody->toPrivateXml(), Model::NO_MERGE);
if ($lBodyTwo === $lBody) {
	throw new \Exception('same object instance');
}
if (json_encode($lBodyTwo->getValue('arts')->toPrivateStdObject()) !== '[{"type":"sentence","location":"shoulder","tatooArtist":{"id":"5","__inheritance__":"man"},"__inheritance__":"tatoo"},{"type":"earring","location":"ear","piercer":{"id":"5","__inheritance__":"man"},"__inheritance__":"piercing"}]') {
	throw new \Exception('not same object values');
}
$lBodyTwo = $lBody->getModel()->fromPrivateXml($lBody->toPrivateXml());
if ($lBodyTwo !== $lBody) {
	throw new \Exception('not same object instance');
}

if (json_encode($lWoman->toPrivateStdObject()) !== '{"children":"__UNLOAD__","homes":"__UNLOAD__","id":"2","bodies":[1],"firstName":"Marie","lastName":"Smith","birthDate":"2016-11-13T20:04:05+01:00","bestFriend":{"id":"5","__inheritance__":"man"}}') {
	throw new \Exception('not same object values : '.json_encode($lWoman->toPrivateStdObject()));
}
if (trim(str_replace('<?xml version="1.0"?>', '', $lWoman->toPrivateXml()->asXML())) !== '<woman id="2" firstName="Marie" lastName="Smith" birthDate="2016-11-13T20:04:05+01:00"><children __UNLOAD__="1"/><homes __UNLOAD__="1"/><bodies><body>1</body></bodies><bestFriend __inheritance__="man">5</bestFriend></woman>') {
	throw new \Exception('not same object values');
}
if (json_encode($lWoman->toSqlDatabase()) !== '{"id":"2","first_name":"Marie","lastName":"Smith","birth_date":"2016-11-13T20:04:05+01:00","best_friend":"5"}') {
	throw new \Exception('not same object values : '.json_encode($lWoman->toSqlDatabase()));
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

$lManStdObject = $lMan->toPrivateStdObject();
$lManXml = $lMan->toPrivateXml();
$lManSql = $lMan->toPrivateFlattenedArray();

$lManImported = $lManModel->fromPrivateStdObject($lMan->toPrivateStdObject());
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

$lManImported = $lManModel->fromPrivateXml($lManImported->toPrivateXml());
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

$lManImported = $lManModel->fromPrivateFlattenedArray($lManImported->toPrivateFlattenedArray());
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

if (json_encode($lManStdObject) !== json_encode($lManImported->toPrivateStdObject())) {
	throw new \Exception('not same string object');
}
if ($lManXml->asXML() !== $lManImported->toPrivateXml()->asXML()) {
	throw new \Exception('not same string object');
}
if (json_encode($lManSql) !== json_encode($lManImported->toPrivateFlattenedArray())) {
	throw new \Exception('not same string object');
}

$lDbTestModel = InstanceModel::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,1501774389]');

if (json_encode($lObject->toPrivateStdObject()) !== '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-15T13:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11"}') {
	var_dump(json_encode($lObject->toPrivateStdObject()));
	throw new \Exception('bad private object value '.json_encode($lObject->toPrivateStdObject()));
}

if (json_encode($lObject->toPublicStdObject()) !== '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-15T13:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11"}') {
	throw new \Exception('bad public object value');
}
$lStdObject = $lObject->toPrivateStdObject();
$lStdObject->string = 'azeazeazeazeaze';
$lStdObject->objectsWithId[0]->plop3 = 'azeazeazeazeaze';
$lObject->fromPublicStdObject($lStdObject);
if (json_encode($lObject->toPrivateStdObject()) !== '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-15T13:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"nnnn","integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11"}') {
	throw new \Exception('bad private object value');
}

if (count($lObject->getValue('objectsWithId')->getValues()) !== 5) {
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
$lObject = $lDbTestModel->fromPrivateStdObject($lObject->toPrivateStdObject(), Model::NO_MERGE);
if ($lObjectOne === $lObject) {
	throw new \Exception('same object instance');
}

if (count($lObject->getValue('objectsWithId')->getValues()) !== 5) {
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


$lObject = $lDbTestModel->fromPrivateXml($lObject->toPrivateXml(), Model::NO_MERGE);
if ($lObjectOne === $lObject) {
	throw new \Exception('same object instance');
}

if (count($lObject->getValue('objectsWithId')->getValues()) !== 5) {
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


$time_end = microtime(true);
var_dump('extended value test exec time '.($time_end - $time_start));