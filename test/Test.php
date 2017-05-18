<?php

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/comhon/source/');
set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/ObjectManagerLib/source/');

require_once 'Comhon.php';

spl_autoload_register(function ($pClass) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php';
});

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
		throw new Exception('not valid json : '.$jsonOne);
	}
	$arrayTwo = json_decode($jsonTwo, true);
	if (!is_array($arrayTwo)) {
		throw new Exception('not valid json : '.$jsonTwo);
	}
	return compareArray($arrayOne, $arrayTwo);
}

/**
 * 
 * @param array $arrayOne
 * @param array $arrayTwo
 * @param array $stack
 * @return boolean
 */
function compareArray(array $arrayOne, array $arrayTwo, array &$stack = []) {
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
			if (!compareArray($value, $arrayTwo[$key], $stack)) {
				return false;
			}
		} elseif (($value instanceof \stdClass) && ($arrayTwo[$key] instanceof \stdClass)) {
			if (!compareStdObject($value, $arrayTwo[$key], $stack)) {
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
 * @param array $stack
 * @return boolean
 */
function compareStdObject(\stdClass $stdObjectOne, \stdClass $stdObjectTwo, array &$stack = []) {
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
			if (!compareArray($value, $stdObjectTwo->$key, $stack)) {
				return false;
			}
		} elseif (($value instanceof \stdClass) && ($stdObjectTwo->$key instanceof \stdClass)) {
			if (!compareStdObject($value, $stdObjectTwo->$key, $stack)) {
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
 * @param array $stack
 * @return boolean
 */
function compareXML($XMLOne, $XMLTwo, array &$stack = []) {
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
		throw new Exception('manage only xml with one and only one root node');
	}
	return compareDomElement($DOMDocOne->childNodes->item(0), $DOMDocTwo->childNodes->item(0));
}

/**
 * 
 * @param \DOMElement $DOMElementOne
 * @param \DOMElement $DOMElementTwo
 * @param array $stack
 * @return boolean
 */
function compareDomElement(\DOMElement $DOMElementOne, \DOMElement $DOMElementTwo, array &$stack = []) {
	if ($DOMElementOne->nodeName !== $DOMElementTwo->nodeName) {
		trigger_error('nodes have different names : ' . implode('.', $stack) . ".{$DOMElementOne->nodeName} !== " . implode('.', $stack) . '.' . $DOMElementOne->nodeName);
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
		throw new Exception('do not manage comparison with node containing at same level text and element');
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
		throw new Exception('do not manage comparison with node containing at same level text and element');
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
			if (!compareDomElement($node, $DOMElementsTwo[$key][$index], $stack)) {
				return false;
			}
		}
	}
	array_pop($stack);
	return true;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'XmlSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'JsonSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ImportExportTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleForeignTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ComplexRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleIdRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestFailureTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateVsComplexRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'PartialImportExportTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'DatabaseSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'InterfacerTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ToStringDebugTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RestrictionTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ValueRestrictionTest.php';

/*
interfacer (limit inf appear more often) : 
	json
		model  0.0088510513 < 0.01793503
		extend model 0.000944 < 0.003141164
	xml
		model 0.0097460 < 0.019051
		extends model 0.0014588 < 0.0047380924
*/

// add export null value
// objectarray implement traversable
// fill instead of fillobject (only for object not for modfel)

// add error code
// remove $p $l
// psr
// add Php doc

// partial load for aggregation (perhaps add setting to set max length load aggreagtion)
// allow to active only one value among several values (a or b might be set but not a and b)
// define in manifest if property is requestable
// real unit test
// rapide load Unique model in modelManager
// from object specifying object path (object.property.property)
// mandatory value when serialize
// common models/values in unique files
// left/inner join simple/function litteral
// versionning get instance model 3rd parameter version
// inheritage with join table
// manifest validator