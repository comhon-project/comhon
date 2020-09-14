<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Interfacer;

use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Symfony\Component\Yaml\Yaml;

class StdObjectInterfacer extends MultipleFormatInterfacer {

	/**
	 * get value in $node with property $name
	 *
	 * @param \stdClass $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 * @return mixed|null null if doesn't exist
	 */
	public function &getValue(&$node, $name, $asNode = false) {
		if (property_exists($node, $name)) {
			return $node->$name;
		} else {
			// ugly but we return reference so we have to return a variable
			$null = null;
			return $null;
		}
	}
	
	/**
	 * verify if $node contain value with property $name
	 * 
	 * @param \stdClass $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 * @return boolean
	 */
	public function hasValue($node, $name, $asNode = false) {
		return property_exists($node, $name);
	}
	
	/**
	 * verify if value is null
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNullValue($value) {
		return is_null($value);
	}
	
	/**
	 * get traversable node (return $node)
	 *
	 * @param array|\stdClass $node
	 * @return array|\stdClass
	 */
	public function getTraversableNode($node) {
		if (!is_array($node) && !($node instanceof \stdClass)) {
			throw new ArgumentException($node, ['\stdClass', 'array'], 1);
		}
		return $node;
	}
	
	/**
	 * verify if value is an stdClass object
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNodeValue($value) {
		return ($value instanceof \stdClass);
	}
	
	/**
	 * verify if value is an array
	 * 
	 * @param mixed $value
	 * @param boolean $isAssociative
	 * @return boolean
	 */
	public function isArrayNodeValue($value, $isAssociative) {
		return (!$isAssociative && is_array($value)) || ($isAssociative && is_object($value));
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isComplexInterfacedId($value) {
		return is_object($value);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isFlattenComplexInterfacedId($value) {
		return is_string($value) && substr($value, 0, 6) == '{"id":';
	}
	
	/**
	 * set value in $node with property $name
	 * 
	 * @param \stdClass $node
	 * @param mixed $value
	 * @param string $name must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!($node instanceof \stdClass)) {
			throw new ArgumentException($node, '\stdClass', 1);
		}
		if (is_null($name)) {
			throw new ArgumentException($name, 'string', 3);
		}
		$node->$name = $value;
	}
	
	/**
	 * unset value in $node with property $name
	 *
	 * @param \stdClass $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 */
	public function unsetValue(&$node, $name, $asNode = false) {
		unset($node->$name);
	}
	
	/**
	 * add value to $node
	 *
	 * @param array $node
	 * @param mixed $value
	 * @param string $name not used (but needed to stay compatible with interface)
	 */
	public function addValue(&$node, $value, $name = null) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		$node[] = $value;
	}
	
	/**
	 * add value to $node
	 *
	 * @param array $node
	 * @param mixed $value
	 * @param string $key
	 * @param string $name not used (there is a default value to stay compatible with interface)
	 */
	public function addAssociativeValue(&$node, $value, $key, $name = null) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		if (is_null($key)) {
			throw new ArgumentException($key, 'string', 3);
		}
		$node[$key] = $value;
	}
	
	/**
	 * create \stdClass node
	 * 
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return \stdClass
	 */
	public function createNode($name = null) {
		return new \stdClass();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getNodeClasses()
	 */
	public function getNodeClasses() {
		return [\stdClass::class];
	}
	
	/**
	 * create array node
	 * 
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return array
	 */
	public function createArrayNode($name = null) {
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getArrayNodeClasses()
	 */
	public function getArrayNodeClasses() {
		return ['array'];
	}
    
	/**
	 * transform given node to string
	 * 
	 * @param \stdClass $node
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function toString($node, $prettyPrint = false) {
		switch ($this->format) {
			case 'json':
				return $prettyPrint ? json_encode($node, JSON_PRETTY_PRINT) : json_encode($node);
			case 'yaml':
				return Yaml::dump($node, 1000, 4, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
			default:
				throw new ComhonException('undefined format '.$this->format);
		}
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::fromString()
	 */
	public function fromString($string) {
		switch ($this->format) {
			case 'json':
				return json_decode($string);
			case 'yaml':
				return Yaml::parse($string, Yaml::PARSE_OBJECT_FOR_MAP);
			default:
				throw new ComhonException('undefined format '.$this->format);
		}
	}
	
	/**
	 * write file with given content
	 * 
	 * @param mixed $node
	 * @param string $path
	 * @param bool $prettyPrint
	 * @return boolean
	 */
	public function write($node, $path, $prettyPrint = false) {
		$data = $prettyPrint ? json_encode($node, JSON_PRETTY_PRINT) : json_encode($node);
		return file_put_contents($path, $data) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * 
	 * @param string $path
	 * @return \stdClass|null return null on failure
	 */
	public function read($path) {
		$json = file_get_contents($path);
		return $json ? json_decode($json) : null;
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * 
	 * @param \stdClass $node
	 * @param string $name
	 */
	public function flattenNode(&$node, $name) {
		if (isset($node->$name)) {
			$node->$name = json_encode($node->$name);
		}
	}
	
	/**
	 * unflatten value (transform string to object/array)
	 * 
	 * @param array $node
	 * @param string $name
	 */
	public function unFlattenNode(&$node, $name) {
		if (isset($name, $node) && is_string($node->$name)) {
			$node->$name = json_decode($node->$name);
		}
	}
	
	/**
	 * replace value in property $name by $value (fail if property $name doesn't exist)
	 * 
	 * @param \stdClass $node
	 * @param string $name
	 * @param mixed $value
	 */
	public function replaceValue(&$node, $name, $value) {
		if (property_exists($node, $name)) {
			$this->setValue($node, $value, $name);
		}
	}
	
}
