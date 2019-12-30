<?php

use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\InterfaceException;
use Comhon\Model\Model;
use Comhon\Model\ModelArray;
use Comhon\Object\AbstractComhonObject;

$time_start = microtime(true);

/**
 * test function setValue() with unexpected value type
 *
 * @param AbstractComhonObject $object
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
	if (strpos($e->getMessage(), $e->getOriginalException()->getMessage()) === false) {
		throw new ComhonException("unexpected exception message \"{$e->getMessage()}\" !== \"{$e->getOriginalException()->getMessage()}\"");
	}
	if ($e->getOriginalException() instanceof InterfaceException) {
		throw new ComhonException("unexpected exception class");
	}
}

$testModel = ModelManager::getInstance()->getInstanceModel('Test\Test');

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
	$stdPrivateInterfacer->import($stdTest, $testModel);
} catch (ImportException $e) {
	verifException(
		$e, 
		ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
		'Something goes wrong on \'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
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
	$testModel->import($stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			Model::class,
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
	$testModel->fillObject($testObj, $stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			Model::class,
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
	$testObj->fill($stdTest, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
			"value must be a string, integer '12' given",
			'.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			AbstractComhonObject::class,
			'fill'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

/*************************** test import array from model **************************/

$testModelArray = new ModelArray($testModel, false, 'child');
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$testModelArray->import($stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
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

$testModelArray = new ModelArray($testModel, false, 'child');
$testArray = $testModelArray->getObjectInstance();
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$testModelArray->fillObject($testArray, $stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
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

$testModelArray = new ModelArray($testModel, false, 'child');
$testArray = $testModelArray->getObjectInstance();
$stdTestArray = [null, $stdTest];

$throw = true;
try {
	$testArray->fill($stdTestArray, $stdPrivateInterfacer);
} catch (ImportException $e) {
	verifException(
			$e,
			ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
			'Something goes wrong on \'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne\' value : '.PHP_EOL.
			"value must be a string, integer '12' given",
			'.1.objectContainer.objectValueTwo.propertyTwoArray.1.propertyOne',
			AbstractComhonObject::class,
			'fill'
			);
	$throw = false;
}
if ($throw) {
	throw new ComhonException("should throw exception");
}

$time_end = microtime(true);
var_dump('import export exception test exec time '.($time_end - $time_start));