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
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\CastStringException;

class XMLInterfacer extends Interfacer implements NoScalarTypedInterfacer {
	
	/** @var string */
	const NS_NULL_VALUE = 'xsi:nil';
	
	/** @var string */
	const NULL_VALUE = 'nil';
	
	/** @var string */
	const NIL_URI = 'http://www.w3.org/2001/XMLSchema-instance';
	
	/** @var \DOMDocument */
	private $domDocument;
	
	/** @var \DOMElement[] */
	private $nullElements = [];
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * 
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
	 * 
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
	 * get child node with name $name
	 *
	 * @param \DOMElement $node
	 * @param string $name
	 * @return \DOMElement|null null if doesn't exist
	 */
	private function getChildNode($node, $name) {
		foreach ($node->childNodes as $child) {
			if ($child->nodeName === $name) {
				return $child;
			}
		}
		return null;
	}
	
	/**
	 * get value in $node with attribute or node $name according $asNode
	 *
	 * @param \DOMElement $node
	 * @param string $name
	 * @param boolean $asNode if true search value in nodes otherwise search in attributes
	 * @return \DOMElement|string|null null if doesn't exist
	 */
	public function &getValue(&$node, $name, $asNode = false) {
		if ($asNode) {
			$childNode = $this->getChildNode($node, $name);
			if (!is_null($childNode) && $this->isNodeNull($childNode)) {
				$childNode = null;
			}
			return $childNode;
		} else if ($node->hasAttribute($name)) {
			$attribute = $node->getAttribute($name);
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
	 * verify if node is null (i.e. if has attribute xsi:nil="true")
	 *
	 * @param \DOMElement $node
	 * @return boolean
	 */
	public function isNodeNull(\DOMElement $node) {
		return $node->hasAttributeNS(self::NIL_URI, self::NULL_VALUE);
	}
	
	/**
	 * verify if $node contain value with attribute or node $name according $asNode
	 *
	 * @param \DOMElement $node
	 * @param string $name
	 * @param boolean $asNode if true search value in nodes otherwise search in attributes
	 * @return boolean
	 */
	public function hasValue($node, $name, $asNode = false) {
		return $asNode ? 
			!is_null($this->getChildNode($node, $name)) 
			: $node->hasAttribute($name);
	}
	
	/**
	 * verify if value is null
	 * 
	 * values considered as null are :
	 *     - null
	 *     - "xsi:nil"
	 *     - \DOMElement with attribute xsi:nil="true"
	 *     
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNullValue($value) {
		return ($value instanceof \DOMElement) ? $this->isNodeNull($value) : (is_null($value) || $value === self::NS_NULL_VALUE);
	}
	
	/**
	 * get traversable node (return children \DOMElement in array)
	 *
	 * @param \DOMElement $node
	 * @param boolean $getElementName 
	 *     if true, return array indexed by nodes names
	 *     in this cases all nodes must have unique name otherwise an exception will be thrown
	 * @return array|null
	 */
	public function getTraversableNode($node, $getElementName = false) {
		if (!($node instanceof \DOMElement)) {
			throw new ArgumentException($node, '\DOMElement', 1);
		}
		$array = [];
		if ($getElementName) {
			foreach ($node->childNodes as $domNode) {
				if ($domNode->nodeType === XML_ELEMENT_NODE) {
					if (array_key_exists($domNode->nodeName, $array)) {
						throw new ComhonException("duplicated name '$domNode->nodeName'");
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
	 * verify if value is a \DOMElement
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNodeValue($value) {
		return ($value instanceof \DOMElement);
	}
	
	/**
	 * verify if value is a \DOMElement
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isArrayNodeValue($value) {
		return ($value instanceof \DOMElement);
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isComplexInterfacedId($value) {
		return ($value instanceof \DOMElement) && $value->hasAttribute(self::COMPLEX_ID_KEY);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isFlattenComplexInterfacedId($value) {
		return $this->isComplexInterfacedId($value);
	}
	
	/**
	 * set value in $node with attribute or node $name according $asNode
	 * 
	 * @param \DOMElement $node
	 * @param mixed $value if scalar value, set attribute. else if \DOMElement, append child
	 * @param string $name used only if $value if scalar value
	 * @param boolean $asNode if true add node otherwise add attribute
	 *     used only if $value if scalar value
	 * @return \DOMNode|null return added node or null if nothing added
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!($node instanceof \DOMElement)) {
			throw new ArgumentException($node, '\DOMElement', 1);
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
	 * unset value in $node with attribute or node $name according $asNode
	 *
	 * @param \DOMElement $node
	 * @param string $name
	 * @param boolean $asNode if true search value in nodes otherwise search in attributes
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
	 * add value to $node
	 *
	 * @param \DOMElement $node
	 * @param \DOMNode $value
	 * @param string $name used only if $value if scalar value
	 * @return \DOMNode
	 */
	public function addValue(&$node, $value, $name = null) {
		return $this->setValue($node, $value, $name, true);
	}
	
	/**
	 * create \DOMElement node
	 * 
	 * @param string $name
	 * @return \DOMElement
	 */
	public function createNode($name = null) {
		if (is_null($name)) {
			throw new ArgumentException($name, 'string', 1);
		}
		return $this->domDocument->createElement($name);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getNodeClasses()
	 */
	public function getNodeClasses() {
		return [\SimpleXMLElement::class, \DOMNode::class];
	}
	
	/**
	 * create \DOMElement node
	 * 
	 * @param string $name
	 * @return \DOMElement
	 */
	public function createArrayNode($name = null) {
		return $this->createNode($name);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getArrayNodeClasses()
	 */
	public function getArrayNodeClasses() {
		return [\SimpleXMLElement::class, \DOMNode::class];
	}
	
	/**
	 * transform given node to string
	 * 
	 * @param \DOMElement $node
	 * @return string
	 */
	public function toString($node) {
		return $this->domDocument->saveXML($node);
	}
	
	/**
	 * write file with given content
	 * 
	 * @param \DOMElement $node
	 * @param string $path
	 * @return boolean
	 */
	public function write($node, $path) {
		return file_put_contents($path, $this->domDocument->saveXML($node)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * 
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
	 * 
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
	 * 
	 * @param array $node
	 * @param string $name
	 */
	public function unFlattenNode(&$node, $name) {
		$domElement = $this->getChildNode($node, $name);
		if (!is_null($domElement) && $this->extractNodeText($domElement) !== '') {
			$tempDoc = new \DOMDocument();
			$tempDoc->loadXML('<temp>'.$this->extractNodeText($domElement).'</temp>');
			
			if ($tempDoc->childNodes->length !== 1 || !($tempDoc->childNodes->item(0) instanceof \DOMElement)) {
				throw new ComhonException('wrong xml, XMLInterfacer manage xml with one and only one root node');
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
	 * replace value in node $name by $value (fail if node $name doesn't exist)
	 * 
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToString()
	 */
	public function castValueToString($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		return $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToInteger()
	 */
	public function castValueToInteger($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if (!is_numeric($value)) {
			throw new CastStringException($value, 'numeric');
		}
		return (integer) $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToFloat()
	 */
	public function castValueToFloat($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if (!is_numeric($value)) {
			throw new CastStringException($value, 'numeric');
		}
		return (float) $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToBoolean()
	 */
	public function castValueToBoolean($value) {
		if ($value instanceof \DOMElement) {
			$value = $this->extractNodeText($value);
		}
		if ($value !== '0' && $value !== '1') {
			throw new CastStringException($value, ['0', '1']);
		}
		return $value === '1';
	}
	
	/**
	 * extract text from node
	 * 
	 * @param \DOMElement $node
	 * @return string
	 */
	public function extractNodeText(\DOMElement $node) {
		if ($node->childNodes->length != 1) {
			throw new ComhonException('malformed node, should only contain one text');
		}
		if ($node->childNodes->item(0)->nodeType != XML_TEXT_NODE) {
			throw new ComhonException('malformed node, should only contain one text');
		}
		return $node->childNodes->item(0)->nodeValue;
	}
	
	/**
	 *
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param \Comhon\Model\Model $model
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
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::removeMainForeignObject()
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
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::hasMainForeignObject()
	 */
	public function hasMainForeignObject($modelName, $id) {
		return !is_null($this->mainForeignIds)
			&& array_key_exists($modelName, $this->mainForeignIds)
			&& array_key_exists($id, $this->mainForeignIds[$modelName]);
	}
}
