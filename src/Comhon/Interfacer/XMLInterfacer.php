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

class XMLInterfacer extends NoScalarTypedInterfacer {
	
	/** @var string */
	const NS_XSI = 'xmlns:xsi';
	
	/** @var string */
	const NS_NULL_VALUE = 'xsi:nil';
	
	/** @var string */
	const NULL_VALUE = 'nil';
	
	/** @var string */
	const NIL_URI = 'http://www.w3.org/2001/XMLSchema-instance';
	
	/** @var \DOMDocument */
	private $domDocument;
	
	final public function __construct() {
		$this->domDocument = new \DOMDocument();
		$this->domDocument->preserveWhiteSpace = false;
		$this->setDateTimeZone(new \DateTimeZone(date_default_timezone_get()));
	}
	
	/**
	 * get child node with name $name (if several children have same name, the first one is returned).
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
	 * add attribute namespace URI for null values.
	 *
	 * @param \DOMElement $node
	 */
	public function addNullNamespaceURI(\DOMElement $node) {
		if ($node->hasAttribute(self::NS_XSI)) {
			return;
		}
		// first solution found, create real attribute namespace
		// there is perhaps a more simple way
		if ($node->ownerDocument->firstChild === $node) {
			// no need to update Dom
			$node->ownerDocument->createAttributeNS(self::NIL_URI, self::NS_NULL_VALUE);
		}else {
			// node must be appended at first place to DomDocument before create attribute ns
			// and node must be replaced at original place if needed
			$parent = $node->parentNode;
			$next = $node->nextSibling;
			if (is_null($node->ownerDocument->firstChild)) {
				$node->ownerDocument->appendChild($node);
			} else {
				$node->ownerDocument->insertBefore($node, $node->ownerDocument->firstChild);
			}
			$node->ownerDocument->createAttributeNS(self::NIL_URI, self::NS_NULL_VALUE);
			$node->ownerDocument->removeChild($node);
			
			if ($parent) {
				if ($next) {
					$parent->insertBefore($node, $next);
				} else {
					$parent->appendChild($node);
				}
			}
		}
		// second solution found, create fake attribute namespace
		// more simple but not recognized as attribute namespace
		// and we cannot get it with getAttribute()
		// $node->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
	}
	
	/**
	 * add attribute namespace xsi:nil on given node
	 * 
	 * @param \DOMElement $node
	 */
	public function setNodeAsNull(\DOMElement $node) {
		$node->setAttributeNS(self::NIL_URI, self::NULL_VALUE, 'true');
	}
	
	/**
	 * Dumps the internal XML tree back into a string
	 *
	 * @param \DOMElement $node
	 * @param boolean $prettyPrint
	 * @return false|string
	 */
	private function saveXML(\DOMElement $node, $prettyPrint) {
		$node->ownerDocument->formatOutput = $prettyPrint;
		return $node->ownerDocument->saveXML($node);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getMediaType()
	 */
	public function getMediaType() {
		return 'application/xml';
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
			$value = $node->getAttribute($name);
			return $value;
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
		return ($value instanceof \DOMElement) ? $this->isNodeNull($value) : is_null($value);
	}
	
	/**
	 * get traversable node (return children \DOMElement in array)
	 *
	 * @param \DOMElement $node
	 * @return \DOMElement[]
	 */
	public function getTraversableNode($node) {
		if (!($node instanceof \DOMElement)) {
			throw new ArgumentException($node, '\DOMElement', 1);
		}
		$array = [];
		foreach ($node->childNodes as $domNode) {
			if ($domNode instanceof \DOMElement) {
				if ($domNode->hasAttribute(self::ASSOCIATIVE_KEY)) {
					if (array_key_exists($domNode->getAttribute(self::ASSOCIATIVE_KEY), $array)) {
						throw new ComhonException("duplicated key '$domNode->nodeName'");
					}
					$array[$domNode->getAttribute(self::ASSOCIATIVE_KEY)] = $domNode;
				} else {
					$array[] = $domNode;
				}
			}
		}
		return $array;
	}
	
	/**
	 * get child nodes names that are null
	 *
	 * @param \DOMElement $node
	 * @return string[]
	 */
	public function getNullNodes(\DOMElement $node) {
		$array = [];
		foreach ($node->childNodes as $domNode) {
			if ($domNode instanceof \DOMElement && $this->isNodeNull($domNode)) {
				$array[] = $domNode->nodeName;
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
	 * 
	 * Warning! if $value is a \DomNode and doesn't belong to same \DomDocument than $node,
	 * $value is copied and the copy is set on $node.
	 * So modify $value later will not modify set value.
	 * 
	 * Warning! if you set a null value as node, 
	 * parent node (direct or not) must be in null namespace (see XmlInterfacer::addNullNamespaceURI).
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
					$this->setNodeAsNull($childNode);
				} else {
					$childNode->appendChild($childNode->ownerDocument->createTextNode($value));
				}
			} else {
				if (is_null($value)) {
					$childNode = $node->appendChild($node->ownerDocument->createElement($name));
					$this->setNodeAsNull($childNode);
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
	 * @param string $key
	 * @param string $name node name to add. must be provided if $value if scalar value.
	 */
	public function addAssociativeValue(&$node, $value, $key, $name = null) {
		$this->setValue($node, $value, $name, true);
		$this->setValue($node->lastChild, $key, self::ASSOCIATIVE_KEY);
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
	 * extract text from node
	 * 
	 * @param \DOMElement $node
	 * @return string|null
	 */
	public function extractNodeText(\DOMElement $node) {
		if ($this->isNodeNull($node)) {
			return null;
		}
		if ($node->childNodes->length != 1) {
			throw new ComhonException('malformed node, should only contain one text');
		}
		if ($node->childNodes->item(0)->nodeType != XML_TEXT_NODE) {
			throw new ComhonException('malformed node, should only contain one text');
		}
		return $node->childNodes->item(0)->nodeValue;
	}
	
}
