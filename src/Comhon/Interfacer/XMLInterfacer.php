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

use Comhon\Model\Model;

class XMLInterfacer extends Interfacer implements NoScalarTypedInterfacer {
	
	const NS_NULL_VALUE = 'xsi:nil';
	const NULL_VALUE = 'nil';
	const NIL_URI = 'http://www.w3.org/2001/XMLSchema-instance';
	
	private $domDocument;
	private $nullElements = [];
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * @throws \Exception
	 */
	protected function _initInstance() {
		$this->domDocument = new \DOMDocument();
		$this->nullElements = [];
	}
	
	/**
	 * initialize export
	 */
	public function initializeExport() {
		$this->domDocument = new \DOMDocument();
		$this->nullElements = [];
		parent::initializeExport();
	}
	
	/**
	 * finalize export
	 * @param mixed $rootNode
	 */
	public function finalizeExport($rootNode) {
		parent::finalizeExport($rootNode);
		$this->domDocument->appendChild($rootNode);
		if (!empty($this->nullElements)) {
			$this->domDocument->createAttributeNS(self::NIL_URI, self::NS_NULL_VALUE);
			foreach ($this->nullElements as $domElement) {
				$domElement->setAttributeNS(self::NIL_URI, self::NULL_VALUE, 'true');
			}
			$this->nullElements = [];
		}
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param string $propertyName
	 * @return \DOMElement|null
	 */
	private function getChildNode($node, $propertyName) {
		foreach ($node->childNodes as $child) {
			if ($child->nodeName === $propertyName) {
				return $child;
			}
		}
		return null;
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return \DOMElement|string|null
	 */
	public function &getValue(&$node, $propertyName, $asNode = false) {
		if ($asNode) {
			$childNode = $this->getChildNode($node, $propertyName);
			if (!is_null($childNode) && $this->isNodeNull($childNode)) {
				$childNode = null;
			}
			return $childNode;
		} else if ($node->hasAttribute($propertyName)) {
			$attribute = $node->getAttribute($propertyName);
			if ($attribute == self::NS_NULL_VALUE) {
				$attribute = null;
			}
			return $attribute;
		}
		// ugly but we return reference so we have to return a variable
		$null = null;
		return $null;
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @return boolean
	 */
	public function isNodeNull(\DOMElement $node) {
		return $node->hasAttributeNS(self::NIL_URI, self::NULL_VALUE);
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return boolean
	 */
	public function hasValue($node, $propertyName, $asNode = false) {
		return $asNode ? 
			!is_null($this->getChildNode($node, $propertyName)) 
			: $node->hasAttribute($propertyName);
	}
	
	/**
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNullValue($value) {
		return ($value instanceof \DOMElement) ? $this->isNodeNull($value) : (is_null($value) || $value === self::NS_NULL_VALUE);
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param boolean $getElementName if true return nodes names as key other wise return indexes
	 * @return array|null
	 */
	public function getTraversableNode($node, $getElementName = false) {
		if (!($node instanceof \DOMElement)) {
			throw new \Exception('bad node type');
		}
		$array = [];
		if ($getElementName) {
			foreach ($node->childNodes as $domNode) {
				if ($domNode->nodeType === XML_ELEMENT_NODE) {
					if (array_key_exists($domNode->nodeName, $array)) {
						throw new \Exception("duplicated name '$domNode->nodeName'");
					}
					$array[$domNode->nodeName] = $domNode;
				}
			}
		}
		else {
			foreach ($node->childNodes as $domNode) {
				if ($domNode->nodeType === XML_ELEMENT_NODE) {
					$array[] = $domNode;
				}
			}
		}
		return $array;
	}
	
	/**
	 * verify if value is a DOMElement
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNodeValue($value) {
		return ($value instanceof \DOMElement);
	}
	
	/**
	 * verify if value is a DOMElement
	 * @param mixed $value
	 * @return boolean
	 */
	public function isArrayNodeValue($value) {
		return ($value instanceof \DOMElement);
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $value
	 * @return mixed
	 */
	public function isComplexInterfacedId($value) {
		return ($value instanceof \DOMElement) && $value->hasAttribute(self::COMPLEX_ID_KEY);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * @param mixed $value
	 * @return mixed
	 */
	public function isFlattenComplexInterfacedId($value) {
		return $this->isComplexInterfacedId($value);
	}
	
	/**
	 * 
	 * @param \DOMElement $node
	 * @param mixed $value if scalar value, set attribute. else if \DOMElement, append child
	 * @param string $name used only if $value if scalar value
	 * @param boolean $asNode used only if $value if scalar value
	 * @return \DOMNode|null return added node or null if nothing added
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!($node instanceof \DOMElement)) {
			throw new \Exception('first parameter should be an instance of \DOMElement');
		}
		if ($value instanceof \DOMNode) {
			return $node->appendChild($value);
		} else {
			if ($asNode) {
				$childNode = $node->appendChild($this->domDocument->createElement($name));
				if (is_null($value)) {
					// xsi:nil attributes cannot be added in parallel due to namespace
					// actually in export context $node is not currently added to it's parent
					// and DomNode can't find xsi namespace and throw exception
					// so xsi:nil attribute will be added for each node at the end of export
					$this->nullElements[] = $childNode;
				} else {
					$childNode->appendChild($this->domDocument->createTextNode($value));
				}
				return $childNode;
			} else {
				if (is_null($value)) {
					return $node->setAttribute($name, self::NS_NULL_VALUE);
				} else {
					return $node->setAttribute($name, $value);
				}
			}
		}
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param string $name
	 * @param boolean $asNode
	 * @return mixed
	 */
	public function unsetValue(&$node, $name, $asNode = false) {
		if ($asNode) {
			$domElement= $this->getChildNode($node, $name);
			if (!is_null($domElement)) {
				$node->removeChild($domElement);
			}
		} else {
			$node->removeAttribute($name);
		}
	}
	
	/**
	 *
	 * @param \DOMElement $node
	 * @param \DOMNode $value
	 * @param string $name used only if $value if scalar value
	 * @return \DOMElement
	 */
	public function addValue(&$node, $value, $name = null) {
		return $this->setValue($node, $value, $name, true);
	}
	
	/**
	 * @param string $name
	 * return \DOMElement
	 */
	public function createNode($name = null) {
		if (is_null($name)) {
			throw new \Exception('first parameter can not be null');
		}
		return $this->domDocument->createElement($name);
	}
	
	/**
	 * @param string $name
	 * @return \DOMElement
	 */
	public function createNodeArray($name = null) {
		return $this->createNode($name);
	}
	
	/**
	 * transform given node to string
	 * @param \DOMElement $node
	 * @return string
	 */
	public function toString($node) {
		return $this->domDocument->saveXML($node);
	}
	
	/**
	 * write file with given content
	 * @param \DOMElement $node
	 * @param string $path
	 * @return boolean
	 */
	public function write($node, $path) {
		return file_put_contents($path, $this->domDocument->saveXML($node)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * @param string $path
	 * @return \DOMElement|boolean return false on failure
	 */
	public function read($path) {
		if (!$this->domDocument->load($path)) {
			return false;
		}
		if ($this->domDocument->childNodes->length !== 1 || !($this->domDocument->childNodes->item(0) instanceof \DOMElement)) {
			trigger_error('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			return false;
		}
		return $this->domDocument->childNodes->item(0);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param \DOMElement $node
	 * @param string $name
	 */
	public function flattenNode(&$node, $name) {
		$domElement = $this->getChildNode($node, $name);
		if (!is_null($domElement)) {
			$string = '';
			$toRemove = [];
			foreach ($domElement->childNodes as $child) {
				$toRemove[] = $child;
				$string .= $this->domDocument->saveXML($child);
			}
			foreach ($toRemove as $child) {
				$domElement->removeChild($child);
			}
			$domElement->appendChild($this->domDocument->createTextNode($string));
		}
	}
	
	/**
	 * unflatten value (transform string to object)
	 * @param array $node
	 * @param string $name
	 */
	public function unFlattenNode(&$node, $name) {
		$domElement = $this->getChildNode($node, $name);
		if (!is_null($domElement)) {
			if ($this->extractNodeText($domElement) === '') {
				return;
			}
			$tempDoc = new \DOMDocument();
			$tempDoc->loadXML('<temp>'.$this->extractNodeText($domElement).'</temp>');
			
			if ($tempDoc->childNodes->length !== 1 || !($tempDoc->childNodes->item(0) instanceof \DOMElement)) {
				throw new \Exception('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			}
			$toRemove = [];
			foreach ($domElement->childNodes as $child) {
				$toRemove[] = $child;
			}
			foreach ($toRemove as $child) {
				$domElement->removeChild($child);
			}
			foreach ($tempDoc->childNodes->item(0)->childNodes as $child) {
				$childNode = $this->domDocument->importNode($child, true);
				$domElement->appendChild($childNode);
			}
		}
	}
	
	/**
	 * replace value
	 * @param \DOMElement $node
	 * @param string $name
	 * @param mixed $value
	 */
	public function replaceValue(&$node, $name, $value) {
		$domElement = $this->getChildNode($node, $name);
		if (!is_null($domElement)) {
			$node->removeChild($domElement);
			$this->setValue($node, $value, $name, true);
		}
	}
	
	/**
	 * @param string $value
	 * @return integer
	 */
	public function castValueToString($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		return $value;
	}
	
	/**
	 * @param string $value
	 * @return float
	 */
	public function castValueToInteger($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if (!is_numeric($value)) {
			throw new \Exception('value has to be numeric');
		}
		return (integer) $value;
	}
	
	/**
	 * @param string $value
	 * @return boolean
	 */
	public function castValueToFloat($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if (!is_numeric($value)) {
			throw new \Exception('value has to be numeric');
		}
		return (float) $value;
	}
	
	/**
	 * @param string $value
	 * @return boolean
	 */
	public function castValueToBoolean($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if ($value !== '0' && $value !== '1') {
			throw new \Exception('value has to be "0" or "1"');
		}
		return $value === '1';
	}
	
	/**
	 * 
	 * @param \DOMElement $node
	 * @return string
	 */
	public function extractNodeText(\DOMElement $node) {
		if ($node->childNodes->length != 1) {
			throw new \Exception('malformed node, should only contain one text');
		}
		if ($node->childNodes->item(0)->nodeType != XML_TEXT_NODE) {
			throw new \Exception('malformed node, should only contain one text');
		}
		return $node->childNodes->item(0)->nodeValue;
	}
	
	/**
	 *
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param Model $model
	 */
	public function addMainForeignObject($node, $nodeId, Model $model) {
		if (!is_null($this->mainForeignObjects)) {
			$modelName = $model->getName();
			if (!$this->hasValue($this->mainForeignObjects, $modelName, true)) {
				$this->setValue($this->mainForeignObjects, $this->createNode($modelName));
				$this->mainForeignIds[$modelName] = [];
			}
			if (isset($this->mainForeignIds[$modelName][$nodeId])) {
				$this->getValue($this->mainForeignObjects, $modelName, true)->removeChild($this->mainForeignIds[$modelName][$nodeId]);
			}
			$this->unsetValue($this->getValue($this->mainForeignObjects, $modelName, true), $nodeId, true);
			$this->setValue($this->getValue($this->mainForeignObjects, $modelName, true), $node);
			$this->mainForeignIds[$modelName][$nodeId] = $node;
		}
	}
	
	/**
	 *
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param Model $model
	 */
	public function removeMainForeignObject($nodeId, Model $model) {
		if (!is_null($this->mainForeignObjects)) {
			$modelName = $model->getName();
			if ($this->hasValue($this->mainForeignObjects, $modelName, true)) {
				$this->getValue($this->mainForeignObjects, $modelName, true)->removeChild($this->mainForeignIds[$modelName][$nodeId]);
				unset($this->mainForeignIds[$modelName][$nodeId]);
			}
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function hasMainForeignObject($modelName, $id) {
		return !is_null($this->mainForeignIds)
			&& array_key_exists($modelName, $this->mainForeignIds)
			&& array_key_exists($id, $this->mainForeignIds[$modelName]);
	}
}
