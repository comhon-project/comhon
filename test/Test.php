<?php

use Comhon\Object\Config\Config;
use Comhon\Database\DatabaseHandler;

if ($argc === 1) {
	$configFile = 'config-xml-mysql.json';
} else {
	switch ($argv[1]) {
		case 'pgsql':
			$configFile = 'config-json-pgsql.json';
			break;
		case 'mysql':
			$configFile = 'config-xml-mysql.json';
			break;
		
		default:
			echo "invalid first argument, must be 'pgsql' or 'mysql'.\n";
			exit();
	}
}

$loader = require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

echo "load config file $configFile... ";
Config::setLoadPath(__DIR__.'/config/'.$configFile);
echo "success".PHP_EOL;

echo "initializing database... ";

$dbHandlerMySql = DatabaseHandler::getInstanceWithDataBaseId('1');
$dbHandlerMySql->getPDO()->exec(file_get_contents('./data/database/database_mysql.sql'));

$dbHandlerPgSql = DatabaseHandler::getInstanceWithDataBaseId('2');
$dbHandlerPgSql->getPDO()->exec(file_get_contents('./data/database/database_pgsql.sql'));

echo "success".PHP_EOL;

echo PHP_EOL;

/**
 * 
 * @param mixed $value
 * @return string|mixed
 */
function transformValueToString($value) {
	if (is_object($value)) {
		return 'Object';
	}
	if (is_array($value)) {
		return 'Array';
	}
	if (is_bool($value)) {
		return $value ? 'true' : 'false';
	}
	return $value;
}

/**
 * 
 * @param string $jsonOne
 * @param string $jsonTwo
 * @return boolean
 */
function compareJson($jsonOne, $jsonTwo) {
	$arrayOne = json_decode($jsonOne, true);
	if (!is_array($arrayOne)) {
		throw new \Exception('not valid json : '.$jsonOne);
	}
	$arrayTwo = json_decode($jsonTwo, true);
	if (!is_array($arrayTwo)) {
		throw new \Exception('not valid json : '.$jsonTwo);
	}
	return compareArray($arrayOne, $arrayTwo);
}

/**
 *
 * @param array $arrayOne
 * @param array $arrayTwo
 * @return boolean
 */
function compareArray(array $arrayOne, array $arrayTwo) {
	$stack = [];
	$success = _compareArray($arrayOne, $arrayTwo, $stack);
	if (!$success) {
		var_dump(json_encode($arrayOne));
		var_dump(json_encode($arrayTwo));
	}
	return $success;
}

/**
 * 
 * @param array $arrayOne
 * @param array $arrayTwo
 * @param array $stack
 * @return boolean
 */
function _compareArray(array $arrayOne, array $arrayTwo, array &$stack) {
	if (count($arrayOne) != count($arrayTwo)) {
		trigger_error('not same array keys : .' . implode('.', $stack) . ' -> ' . json_encode(array_keys($arrayOne)) . ' != ' . json_encode(array_keys($arrayTwo)));
		return false;
	}
	foreach ($arrayOne as $key => $value) {
		$stack[] = $key;
		if (!array_key_exists($key, $arrayTwo)) {
			trigger_error('key ' . implode('.', $stack) . ' exists in first array but doesn\'t exist in second array');
			return false;
		}
		if (is_array($value) && is_array($arrayTwo[$key])) {
			if (!_compareArray($value, $arrayTwo[$key], $stack)) {
				return false;
			}
		} elseif (($value instanceof \stdClass) && ($arrayTwo[$key] instanceof \stdClass)) {
			if (!_compareStdObject($value, $arrayTwo[$key], $stack)) {
				return false;
			}
		} elseif ($value !== $arrayTwo[$key]) {
			$valueOne = transformValueToString($value);
			$valueTwo = transformValueToString($arrayTwo[$key]);
			trigger_error(sprintf('.%s -> %s (%s) != %s (%s)', implode('.', $stack), $valueOne, gettype($value), $valueTwo, gettype($arrayTwo[$key])));
			return false;
		}
		array_pop($stack);
	}
	return true;
}

/**
 * 
 * @param \stdClass $stdObjectOne
 * @param \stdClass $stdObjectTwo
 * @return boolean
 */
function compareStdObject(\stdClass $stdObjectOne, \stdClass $stdObjectTwo) {
	$stack = [];
	$success = _compareStdObject($stdObjectOne, $stdObjectTwo, $stack);
	if (!$success) {
		var_dump(json_encode($stdObjectOne));
		var_dump(json_encode($stdObjectTwo));
	}
	return $success;
}

/**
 * 
 * @param \stdClass $stdObjectOne
 * @param \stdClass $stdObjectTwo
 * @param array $stack
 * @return boolean
 */
function _compareStdObject(\stdClass $stdObjectOne, \stdClass $stdObjectTwo, array &$stack) {
	$arrayOne = [];
	$arrayTwo = [];
	foreach ($stdObjectOne as $key => $value) {
		$arrayOne[] = $key;
	}
	foreach ($stdObjectTwo as $key => $value) {
		$arrayTwo[] = $key;
	}
	if (count($arrayOne) !== count($arrayTwo)) {
		trigger_error('not same object properties : .' . implode('.', $stack) . ' -> ' . json_encode($arrayOne) . ' != ' . json_encode($arrayTwo));
		return false;
	}
	foreach ($stdObjectOne as $key => $value) {
		$stack[] = $key;
		if (!isset($stdObjectTwo->$key)) {
			trigger_error('property ' . implode('.', $stack) . ' exists in first object but doesn\'t exist in second object');
			return false;
		}
		if (is_array($value) && is_array($stdObjectTwo->$key)) {
			if (!_compareArray($value, $stdObjectTwo->$key, $stack)) {
				return false;
			}
		} elseif (($value instanceof \stdClass) && ($stdObjectTwo->$key instanceof \stdClass)) {
			if (!_compareStdObject($value, $stdObjectTwo->$key, $stack)) {
				return false;
			}
		} else  if ($value !== $stdObjectTwo->$key) {
			$valueOne = transformValueToString($value);
			$valueTwo = transformValueToString($stdObjectTwo->$key);
			trigger_error(sprintf('.%s -> %s (%s) != %s (%s)', implode('.', $stack), $valueOne, gettype($value), $valueTwo, gettype($stdObjectTwo->$key)));
			return false;
		}
		array_pop($stack);
	}
	return true;
}

/**
 *
 * @param string $XMLOne
 * @param string $XMLTwo
 * @return boolean
 */
function compareXML($XMLOne, $XMLTwo) {
	$DOMDocOne = new \DOMDocument();
	$DOMDocOne->loadXML($XMLOne);
	$DOMDocTwo = new \DOMDocument();
	$DOMDocTwo->loadXML($XMLTwo);
	
	if (
		$DOMDocOne->childNodes->length !== 1
		|| !($DOMDocOne->childNodes->item(0) instanceof \DOMElement)
		|| $DOMDocTwo->childNodes->length !== 1
		|| !($DOMDocTwo->childNodes->item(0) instanceof \DOMElement)
	) {
		throw new \Exception('manage only xml with one and only one root node');
	}
	return compareDomElement($DOMDocOne->childNodes->item(0), $DOMDocTwo->childNodes->item(0));
}

/**
 *
 * @param \DOMElement $DOMElementOne
 * @param \DOMElement $DOMElementTwo
 * @return boolean
 */
function compareDomElement(\DOMElement $DOMElementOne, \DOMElement $DOMElementTwo) {
	$stack = [];
	$success = _compareDomElement($DOMElementOne, $DOMElementTwo, $stack);
	if (!$success) {
		var_dump($DOMElementOne->ownerDocument->saveXML($DOMElementOne));
		var_dump($DOMElementTwo->ownerDocument->saveXML($DOMElementTwo));
	}
	return $success;
}

/**
 * 
 * @param \DOMElement $DOMElementOne
 * @param \DOMElement $DOMElementTwo
 * @param array $stack
 * @return boolean
 */
function _compareDomElement(\DOMElement $DOMElementOne, \DOMElement $DOMElementTwo, array &$stack) {
	if ($DOMElementOne->nodeName !== $DOMElementTwo->nodeName) {
		trigger_error('nodes have different names : ' . implode('.', $stack) . ".{$DOMElementOne->nodeName} !== " . implode('.', $stack) . '.' . $DOMElementTwo->nodeName);
		return false;
	}
	$stack[] = $DOMElementOne->nodeName;
	$arrayOne = [];
	$arrayTwo = [];
	foreach ($DOMElementTwo->attributes as $key => $attribute) {
		$arrayTwo[$key] = $attribute->value;
	}
	foreach ($DOMElementOne->attributes as $key => $attribute) {
		$stack[] = $key;
		$arrayOne[$key] = $attribute->value;
		if (!array_key_exists($key, $arrayTwo)) {
			trigger_error('attribute ' . implode('.', $stack) . ' exists in first xml but doesn\'t exist in second xml');
			return false;
		} else  if ($attribute->value !== $arrayTwo[$key]) {
			trigger_error(sprintf('not same attribute value : .%s -> %s (%s) != %s (%s)', implode('.', $stack), $attribute->value, gettype($attribute->value), $arrayTwo[$key], gettype($arrayTwo[$key])));
			return false;
		}
		array_pop($stack);
	}
	if (count($arrayOne) !== count($arrayTwo)) {
		trigger_error('not same xml attributes : .' . implode('.', $stack) . ' -> ' . json_encode(array_keys($arrayOne)) . ' != ' . json_encode(array_keys($arrayTwo)));
		return false;
	}
	$DOMElementsOne = [];
	$DOMTextOne = '';
	$DOMElementsTwo = [];
	$DOMTextTwo = '';
	foreach ($DOMElementOne->childNodes as $childNode) {
		if ($childNode->nodeType === XML_TEXT_NODE) {
			$DOMTextOne .= $childNode->nodeValue;
		} else {
			$Nodename = $childNode->nodeName;
			if (!array_key_exists($Nodename, $DOMElementsOne)) {
				$DOMElementsOne[$Nodename] = [];
			}
			$DOMElementsOne[$Nodename][] = $childNode;
		}
	}
	if (!empty($DOMTextOne) && !empty($DOMElementsOne)) {
		throw new \Exception('do not manage comparison with node containing at same level text and element');
	}
	foreach ($DOMElementTwo->childNodes as $childNode) {
		if ($childNode->nodeType === XML_TEXT_NODE) {
			$DOMTextTwo .= $childNode->nodeValue;
		} else {
			$Nodename = $childNode->nodeName;
			if (!array_key_exists($Nodename, $DOMElementsTwo)) {
				$DOMElementsTwo[$Nodename] = [];
			}
			$DOMElementsTwo[$Nodename][] = $childNode;
		}
	}
	if (!empty($DOMTextTwo) && !empty($DOMElementsTwo)) {
		throw new \Exception('do not manage comparison with node containing at same level text and element');
	}
	if ($DOMTextOne !== $DOMTextTwo) {
		trigger_error(sprintf('not same node value : .%s -> %s (%s) != %s (%s)', implode('.', $stack), $DOMTextOne, gettype($DOMTextOne), $DOMTextTwo, gettype($DOMTextTwo)));
		return false;
	}
	if (count($DOMElementsOne) !== count($DOMElementsTwo)) {
		trigger_error('not same nodes : .' . implode('.', $stack) . ' -> ' . json_encode(array_keys($DOMElementsOne)) . ' != ' . json_encode(array_keys($DOMElementsTwo)));
		return false;
	}
	foreach ($DOMElementsOne as $key => $nodes) {
		if (!array_key_exists($key, $DOMElementsTwo)) {
			trigger_error('node ' . implode('.', $stack) . ".$key exists in first xml but doesn\'t exist in second xml");
			return false;
		}
		if (count($nodes) !== count($DOMElementsTwo[$key])) {
			trigger_error('different count of nodes ' . implode('.', $stack) . '.' . $key);
			return false;
		}
		foreach ($nodes as $index => $node) {
			if (!_compareDomElement($node, $DOMElementsTwo[$key][$index], $stack)) {
				return false;
			}
		}
	}
	array_pop($stack);
	return true;
}
try {
	$plopTimes = [];
	$time_start_global = microtime(true);
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'TempTest.php';
	$time_end_local = microtime(true);
	$plopTimes['TempTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ModelTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ModelTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestTest.php';
	$time_end_local = microtime(true);
	$plopTimes['RequestTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ValueTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ValueTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedModelTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ExtendedModelTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedValueTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ExtendedValueTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'XmlSerializationTest.php';
	$time_end_local = microtime(true);
	$plopTimes['XmlSerializationTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'JsonSerializationTest.php';
	$time_end_local = microtime(true);
	$plopTimes['JsonSerializationTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ImportExportTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ImportExportTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ImportExportExceptionTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ImportExportExceptionTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleForeignTest.php';
	$time_end_local = microtime(true);
	$plopTimes['MultipleForeignTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateRequestTest.php';
	$time_end_local = microtime(true);
	$plopTimes['IntermediateRequestTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ComplexRequestTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ComplexRequestTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleIdRequestTest.php';
	$time_end_local = microtime(true);
	$plopTimes['MultipleIdRequestTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestFailureTest.php';
	$time_end_local = microtime(true);
	$plopTimes['RequestFailureTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateVsComplexRequestTest.php';
	$time_end_local = microtime(true);
	$plopTimes['IntermediateVsComplexRequestTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'PartialImportExportTest.php';
	$time_end_local = microtime(true);
	$plopTimes['PartialImportExportTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'DatabaseSerializationTest.php';
	$time_end_local = microtime(true);
	$plopTimes['DatabaseSerializationTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'InterfacerTest.php';
	$time_end_local = microtime(true);
	$plopTimes['InterfacerTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RestrictionTest.php';
	$time_end_local = microtime(true);
	$plopTimes['RestrictionTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ValueRestrictionTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ValueRestrictionTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ToStringDebugTest.php';
	$time_end_local = microtime(true);
	$plopTimes['ToStringDebugTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'SelectQueryTest.php';
	$time_end_local = microtime(true);
	$plopTimes['SelectQueryTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'FormulaTest.php';
	$time_end_local = microtime(true);
	$plopTimes['FormulaTest'] = $time_end_local - $time_start_local;
	$time_start_local = microtime(true);
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'SetValueExceptionTest.php';
	$time_end_local = microtime(true);
	$plopTimes['SetValueExceptionTest'] = $time_end_local - $time_start_local;
	$time_end_global = microtime(true);
	echo (PHP_EOL."global test exec time ".($time_end_global- $time_start_global).PHP_EOL);
} catch (\Exception $e) {
	var_dump("FAILURE !!!"
		."\ncode : " . $e->getCode()
		."\nmessage : " . $e->getMessage()
		."\nclass exception : " . get_class($e)
		."\ntrace : \n" . json_encode($e->getTrace())
	);
}

/*if (file_exists(__DIR__."/count.txt")) {
	
	foreach (json_decode(file_get_contents(__DIR__."/times.json")) as $key => $value) {
		$plopTimes[$key] += $value;
	}
	file_put_contents(__DIR__."/times.json", json_encode($plopTimes));
	file_put_contents(__DIR__."/count.txt", intval(file_get_contents(__DIR__."/count.txt")) + 1);
	
} else {
	file_put_contents(__DIR__."/times.json", json_encode($plopTimes));
	file_put_contents(__DIR__."/count.txt", 0);
}*/

// TODO for version > 3.0
// staticPath to staticPath
// verify main, serializable, abstract, sharedId, shareParentId in local types

// during import/export verify mandatory properties
// during import test if several objects (not foreign) have same id. if they have, throw exception
// during import (at the end) verify if all foreign (not main) are loaded (verify setting throw exception)
// request defined by comhon manifest

// add selectquery select count(*) on query and func(*) on table
// restriction string length (perhaps several restrictions same time)
// replace self tests by phpunit tests
// partial load for aggregation (perhaps add setting to set max length load aggreagtion)
// transaction serialization
// allow to active only one value among several values (a or b might be set but not a and b)
// define in manifest if property is requestable
// from object specifying object path (object.property.property)
// mandatory value when serialize
// common models/values in unique files
// left/inner join simple/function litteral
// versionning for manifest (get versionned instance model)
// inheritage with join table
// manifest validator
// request order not only on requested model
