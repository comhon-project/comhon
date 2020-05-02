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
use Comhon\Exception\Model\CastStringException;
use Comhon\Utils\Utils;

class XMLInterfacer extends NoScalarTypedInterfacer {
	
	/** @var string */
	const NS_XSI = 'xmlns:xsi';
	
	/** @var string */
	const NS_NULL_VALUE = 'xsi:nil';
	
	/** @var string */
	const NULL_VALUE = 'nil';
	
	/** @var string */
	const NIL_URI = 'http://www.w3.org/2001/XMLSchema-instance';
	
	/** @var integer */
	const INDEX_NODE_CONTAINER = 0;
	
	/** @var integer */
	const INDEX_NODES = 1;
	
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
		$this->domDocument->preserveWhiteSpace = false;
		$this->nullElements = [];
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
	 * add attribute namespace URI for null values if provided dom element has null child nodes (deep search).
	 * if there is no null child nodes, nothing is updated.
	 *
	 * @param \DOMElement $root
	 */
	private function addNullNamespaceURI(\DOMElement $root) {
		if ($root->hasAttribute(self::NS_XSI)) {
			return;
		}
		$add = $root->hasAttribute(self::NS_NULL_VALUE);
		if (!$add) {
			$stack = [$root];
			while(!$add && !empty($stack)) {
				/** @var \DOMNode $node */
				$node = array_pop($stack);
				
				foreach ($node->childNodes as $childNode) {
					if ($childNode instanceof \DOMElement) {
						if ($this->isNodeNull($childNode)) {
							$add = true;
							break;
						}
						$stack[] = $childNode;
					}
				}
			}
		}
		if ($add) {
			// first solution found, create real attribute namespace
			// there is perhaps a more simple way
			if ($root->ownerDocument->firstChild === $root) {
				// no need to update Dom
				$root->ownerDocument->createAttributeNS(self::NIL_URI, self::NS_NULL_VALUE);
			}else {
				// node must be appended at first place to DomDocument before create attribute ns
				// and node must be replaced at original place if needed
				$parent = $root->parentNode;
				$next = $root->nextSibling;
				if (is_null($root->ownerDocument->firstChild)) {
					$root->ownerDocument->appendChild($root);
				} else {
					$root->ownerDocument->insertBefore($root, $root->ownerDocument->firstChild);
				}
				$root->ownerDocument->createAttributeNS(self::NIL_URI, self::NS_NULL_VALUE);
				$root->ownerDocument->removeChild($root);
				
				if ($parent) {
					if ($next) {
						$parent->insertBefore($root, $next);
					} else {
						$parent->appendChild($root);
					}
				}
			}
			// second solution found, create fake attribute namespace
			// more simple but not recognized as attribute namespace
			// and we cannot get it with getAttribute()
			// $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		}
	}
	
	/**
	 * Dumps the internal XML tree back into a string
	 *
	 * @param \DOMElement $node
	 * @param boolean $prettyPrint
	 * @return false|string
	 */
	private function saveXML(\DOMElement $node, $prettyPrint) {
		$this->addNullNamespaceURI($node);
		$node->ownerDocument->formatOutput = $prettyPrint;
		return $node->ownerDocument->saveXML($node);
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
		return $node->hasAttributeNS(self::NIL_URI, self::NULL_VALUE) 
			|| (
				!is_null($node->attributes->getNamedItem(self::NS_NULL_VALUE))
				&& $node->attributes->getNamedItem(self::NS_NULL_VALUE)->value == 'true'
			);
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
	 * @param boolean $isAssociative
	 * @return boolean
	 */
	public function isArrayNodeValue($value, $isAssociative) {
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
	 * Set value in $node with attribute or node $name according $asNode.
	 * Warning! if $value is a \DomNode and doesn't belong to same \DomDocument than $node,
	 * $value is copied and the copy is set on $node.
	 * So modify $value later will not modify set value.
	 * 
	 * @param \DOMElement $node
	 * @param mixed $value if scalar value, set attribute. else if \DOMElement, append child
	 * @param string $name used only if $value if scalar value
	 * @param boolean $asNode if true add node otherwise add attribute
	 *     used only if $value if scalar value
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!($node instanceof \DOMElement)) {
			throw new ArgumentException($node, '\DOMElement', 1);
		}
		if ($value instanceof \DOMNode) {
			if ($node->ownerDocument !== $value->ownerDocument) {
				$value = $node->ownerDocument->importNode($value, true);
			}
			$node->appendChild($value);
		} else {
			if ($asNode) {
				$childNode = $node->appendChild($node->ownerDocument->createElement($name));
				if (is_null($value)) {
					$childNode->setAttribute(self::NS_NULL_VALUE, 'true');
				} else {
					$childNode->appendChild($childNode->ownerDocument->createTextNode($value));
				}
				$childNode;
			} else {
				if (is_null($value)) {
					$node->setAttribute($name, self::NS_NULL_VALUE);
				} else {
					$node->setAttribute($name, $value);
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
	 */
	public function addValue(&$node, $value, $name = null) {
		$this->setValue($node, $value, $name, true);
	}
	
	/**
	 * add value to $node
	 *
	 * @param \DOMElement $node
	 * @param \DOMNode $value
	 * @param string $name used only if $value if scalar value
	 */
	public function addAssociativeValue(&$node, $value, $name = null) {
		$this->setValue($node, $value, $name, true);
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
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function toString($node, $prettyPrint = false) {
		return $this->saveXML($node, $prettyPrint);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::fromString()
	 */
	public function fromString($string) {
		try {
			if (!$this->domDocument->loadXML($string)) {
				return null;
			}
		} catch (\Exception $e) {
			return null;
		}
		if ($this->domDocument->childNodes->length !== 1 || !($this->domDocument->childNodes->item(0) instanceof \DOMElement)) {
			trigger_error('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			return null;
		}
		return $this->domDocument->childNodes->item(0);
	}
	
	/**
	 * write file with given content
	 * 
	 * @param \DOMElement $node
	 * @param string $path
	 * @param bool $prettyPrint
	 * @return boolean
	 */
	public function write($node, $path, $prettyPrint = false) {
		return file_put_contents($path, $this->saveXML($node, $prettyPrint)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * 
	 * @param string $path
	 * @return \DOMElement|null return null on failure
	 */
	public function read($path) {
		try {
			if (!$this->domDocument->load($path)) {
				return null;
			}
		} catch (\Exception $e) {
			return null;
		}
		if ($this->domDocument->childNodes->length !== 1 || !($this->domDocument->childNodes->item(0) instanceof \DOMElement)) {
			trigger_error('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			return null;
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
				$string .= $this->saveXML($child, false);
			}
			foreach ($toRemove as $child) {
				$domElement->removeChild($child);
			}
			$domElement->appendChild($domElement->ownerDocument->createTextNode($string));
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
		if (!is_integer($value) && !ctype_digit($value)) {
			throw new CastStringException($value, 'integer');
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
			throw new CastStringException($value, 'float');
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
	
}
