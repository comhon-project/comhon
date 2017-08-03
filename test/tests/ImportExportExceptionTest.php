<?php

use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\InterfaceException;
use Comhon\Model\MainModel;
use Comhon\Model\ModelArray;
use Comhon\Object\ComhonObject;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Model\Model;

$time_start = microtime(true);

/**
 * test function setValue() with unexpected value type
 *
 * @param ComhonObject $object
 * @param string $property
 * @param mixed $value
 * @param string $expectedMessage
 * @throws ComhonException
 */
function verifException(InterfaceException $e, $code, $message, $stringifiedProperties, $class, $function) {
	if ($e->getCode() !== $code) {
		throw new ComhonException("unexpected exception code {$e->getCode()}, (message : {$e->getMessage()})");
	}
	if ($e->getMessage() !== $message) {
		throw new ComhonException("unexpected exception message \"{$e->getMessage()}\" !== \"$message\"");
	}
	if ($e->getStringifiedProperties() !== $stringifiedProperties) {
		throw new ComhonException("unexpected exception properties \"{$e->getStringifiedProperties()}\" !== \"$stringifiedProperties\"");
	}
	$trace = $e->getTrace();
	if ($trace[0]['class']!== $class) {
		throw new \Exception("unexpected exception trace, unexpected last class : ".json_encode($e->getTrace()));
	}
	if ($trace[0]['function'] !== $function) {
		throw new \Exception("unexpected exception trace, unexpected last function : ".json_encode($e->getTrace()));
	}
	
	if ($e->getCode() !== $e->getOriginalException()->getCode()) {
		throw new ComhonException("unexpected exception code {$e->getCode()}");
	}
	if ($e->getMessage() !== $e->getOriginalException()->getMessage()) {
		throw new ComhonException("unexpected exception message \"{$e->getMessage()}\" !== \"$e->getOriginalException()->getMessage()\"");
	}
	if ($e->getOriginalException() instanceof InterfaceException) {
		throw new ComhonException("unexpected exception class");
	}
}

$testModel = ModelManager::getInstance()->getInstanceModel('test');
$test = $testModel->getObjectInstance();

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);

$stdTest = new stdClass();
$stdTest->objectContainer = new stdClass();
$stdTest->objectContainer->objectValueTwo = new stdClass();

$obj = new stdClass();
$obj->propertyOne = 12;
$stdTest->objectContainer->objectValueTwo->propertyTwoArray = [];
$stdTest->objectContainer->objectValueTwo->propertyTwoArray[] = null;
$stdTest->objectContainer->objectValueTwo->propertyTwoArray[] = $obj;

/******************************************************************/
/**                            import                            **/
/******************************************************************/

/*************************** test import object from interfacer **************************/

$throw = true;
try {
	$test = $stdPrivateInterfacer->import($stdTest, $testModel);
} catch (ImportException $e) {
	verifException(
		$e, 
		ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
		"value must be a string, integer '12' given", 
		'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne', 
		Interfacer::class, 
		'import'
	);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test import object from model **************************/

$throw = true;
try {
	$test = $testModel->import($stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			MainModel::class,
			'import'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test fillobject from model **************************/

$testObj = $testModel->getObjectInstance();

$throw = true;
try {
	$test = $testModel->fillObject($testObj, $stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			MainModel::class,
			'fillObject'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test fill from object **************************/

$testObj = $testModel->getObjectInstance();

$throw = true;
try {
	$test = $testObj->fill($stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ComhonObject::class,
			'fill'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test import array from model **************************/

$testModelArray = new ModelArray($testModel, 'child');
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$test = $testModelArray->import($stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ModelArray::class,
			'import'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test fill array from model **************************/

$testModelArray = new ModelArray($testModel, 'child');
$testArray = $testModelArray->getObjectInstance();
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$test = $testModelArray->fillObject($testArray, $stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ModelArray::class,
			'fillObject'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test fill from objectArray **************************/

$testModelArray = new ModelArray($testModel, 'child');
$testArray = $testModelArray->getObjectInstance();
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$test = $testArray->fill($stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ComhonObject::class,
			'fill'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}


/******************************************************************/
/**                            export                            **/
/******************************************************************/

$testModel = ModelManager::getInstance()->getInstanceModel('test');
$test = $testModel->getObjectInstance();

$objectContainer  = $test->initValue('objectContainer');
$objectValueTwo   = $objectContainer->initValue('objectValueTwo');
$propertyTwoArray = $objectValueTwo->initValue('propertyTwoArray');

$testObjModel = ModelManager::getInstance()->getInstanceModel('test\object');
$objInArray = $testObjModel->getObjectInstance();
$objInArray->setValue('propertyOne', 12, true, false);

$propertyTwoArray->pushValue(null);
$propertyTwoArray->pushValue($objInArray);

/*************************** test export object from interfacer **************************/

$throw = true;
try {
	$testStd = $stdPrivateInterfacer->export($test);
} catch (ExportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			Interfacer::class,
			'export'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test export object from model **************************/

$throw = true;
try {
	$testStd = $testModel->export($test, $stdPrivateInterfacer);
} catch (ExportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			Model::class,
			'export'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test export from object **************************/


$throw = true;
try {
	$testStd = $test->export($stdPrivateInterfacer);
} catch (ExportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ComhonObject::class,
			'export'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test export array from model **************************/

$testModelArray = new ModelArray($testModel, 'child');
$testArray = $testModelArray->getObjectInstance();
$testArray->pushValue(null);
$testArray->pushValue($test);

$throw = true;
try {
	$testStd = $testModelArray->export($testArray, $stdPrivateInterfacer);
} catch (ExportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			Model::class,
			'export'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test export from objectArray **************************/

$throw = true;
try {
	$testStd = $testArray->export($stdPrivateInterfacer);
} catch (ExportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			ComhonObject::class,
			'export'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

$time_end = microtime(true);
var_dump('import export exception test exec time '.($time_end - $time_start));