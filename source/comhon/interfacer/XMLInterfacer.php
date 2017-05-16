<?php
namespace comhon\interfacer;

use comhon\model\Model;

class XMLInterfacer extends Interfacer implements NoScalarTypedInterfacer {

	private $mDomDocument;
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * @throws \Exception
	 */
	protected function _initInstance() {
		$this->mDomDocument = new \DOMDocument();
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param string $pPropertyName
	 * @return \DOMElement|null
	 */
	private function getChildNode($pNode, $pPropertyName) {
		foreach ($pNode->childNodes as $lChild) {
			if ($lChild->nodeName === $pPropertyName) {
				return $lChild;
			}
		}
		return null;
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return \DOMElement|string|null
	 */
	public function &getValue(&$pNode, $pPropertyName, $pAsNode = false) {
		$lValue = ($pAsNode) 
			? $this->getChildNode($pNode, $pPropertyName)
			: ($pNode->hasAttribute($pPropertyName) ? $pNode->getAttribute($pPropertyName) : null);
		return $lValue;
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return boolean
	 */
	public function hasValue($pNode, $pPropertyName, $pAsNode = false) {
		return $pAsNode ? 
			!is_null($this->getChildNode($pNode, $pPropertyName)) 
			: $pNode->hasAttribute($pPropertyName);
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param boolean $pGetElementName if true return nodes names as key other wise return indexes
	 * @return array|null
	 */
	public function getTraversableNode($pNode, $pGetElementName = false) {
		if (is_null($pNode)) {
			return [];
		}
		$lArray = [];
		if ($pGetElementName) {
			foreach ($pNode->childNodes as $lDomNode) {
				if ($lDomNode->nodeType === XML_ELEMENT_NODE) {
					if (array_key_exists($lDomNode->nodeName, $lArray)) {
						throw new \Exception("duplicated name '$lDomNode->nodeName'");
					}
					$lArray[$lDomNode->nodeName] = $lDomNode;
				}
			}
		}
		else {
			foreach ($pNode->childNodes as $lDomNode) {
				if ($lDomNode->nodeType === XML_ELEMENT_NODE) {
					$lArray[] = $lDomNode;
				}
			}
		}
		return $lArray;
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $pValue
	 * @return mixed
	 */
	public function isComplexInterfacedId($pValue) {
		return ($pValue instanceof \DOMElement) && $pValue->hasAttribute(self::COMPLEX_ID_KEY);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * @param mixed $pValue
	 * @return mixed
	 */
	public function isFlattenComplexInterfacedId($pValue) {
		return $this->isComplexInterfacedId($pValue);
	}
	
	/**
	 * 
	 * @param \DOMElement $pNode
	 * @param mixed $pValue if scalar value, set attribute. else if \DOMElement, append child
	 * @param string $pName used only if $pValue if scalar value
	 * @param boolean $pAsNode used only if $pValue if scalar value
	 * @return \DOMNode|null return added node or null if nothing added
	 */
	public function setValue(&$pNode, $pValue, $pName = null, $pAsNode = false) {
		if (is_null($pValue)) {
			return null;
		}
		if ($pValue instanceof \DOMNode) {
			return $pNode->appendChild($pValue);
		} else {
			if ($pAsNode) {
				$lChildNode = $pNode->appendChild($this->mDomDocument->createElement($pName));
				$lChildNode->appendChild($this->mDomDocument->createTextNode($pValue));
				return $lChildNode;
			} else {
				return $pNode->setAttribute($pName, $pValue);
			}
		}
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	public function deleteValue(&$pNode, $pName, $pAsNode = false) {
		if ($pAsNode) {
			$lDomElement= $this->getChildNode($pNode, $pName);
			if (!is_null($lDomElement)) {
				$pNode->removeChild($lDomElement);
			}
		} else {
			$pNode->removeAttribute($pName);
		}
	}
	
	/**
	 *
	 * @param \DOMElement $pNode
	 * @param \DOMNode $pValue
	 * @param string $pName used only if $pValue if scalar value
	 * @return \DOMElement
	 */
	public function addValue(&$pNode, $pValue, $pName = null) {
		return $this->setValue($pNode, $pValue, $pName, true);
	}
	
	/**
	 * @param string $pName
	 * return \DOMElement
	 */
	public function createNode($pName = null) {
		if (is_null($pName)) {
			throw new \Exception('first parameter can not be null');
		}
		return $this->mDomDocument->createElement($pName);
	}
	
	/**
	 * @param string $pName
	 * @return \DOMElement
	 */
	public function createNodeArray($pName = null) {
		return $this->createNode($pName);
	}
	
	/**
	 * transform given node to string
	 * @param \DOMElement $pNode
	 * @return string
	 */
	public function toString($pNode) {
		return $this->mDomDocument->saveXML($pNode);
	}
	
	/**
	 * write file with given content
	 * @param \DOMElement $pNode
	 * @param string $pPath
	 * @return boolean
	 */
	public function write($pNode, $pPath) {
		return file_put_contents($pPath, $this->mDomDocument->saveXML($pNode)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * @param string $pPath
	 * @return \DOMElement|boolean return false on failure
	 */
	public function read($pPath) {
		if (!$this->mDomDocument->load($pPath)) {
			return false;
		}
		if ($this->mDomDocument->childNodes->length !== 1 || !($this->mDomDocument->childNodes->item(0) instanceof \DOMElement)) {
			trigger_error('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			return false;
		}
		return $this->mDomDocument->childNodes->item(0);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param \DOMElement $pNode
	 * @param string $pName
	 */
	public function flattenNode(&$pNode, $pName) {
		$lDomElement = $this->getChildNode($pNode, $pName);
		if (!is_null($lDomElement)) {
			$lString = '';
			$lToRemove = [];
			foreach ($lDomElement->childNodes as $lChild) {
				$lToRemove[] = $lChild;
				$lString .= $this->mDomDocument->saveXML($lChild);
			}
			foreach ($lToRemove as $lChild) {
				$lDomElement->removeChild($lChild);
			}
			$lDomElement->appendChild($this->mDomDocument->createTextNode($lString));
		}
	}
	
	/**
	 * unflatten value (transform string to object)
	 * @param array $pNode
	 * @param string $pName
	 */
	public function unFlattenNode(&$pNode, $pName) {
		$lDomElement = $this->getChildNode($pNode, $pName);
		if (!is_null($lDomElement)) {
			if ($this->extractNodeText($lDomElement) === '') {
				return;
			}
			$lTempDoc = new \DOMDocument();
			$lTempDoc->loadXML('<temp>'.$this->extractNodeText($lDomElement).'</temp>');
			
			if ($lTempDoc->childNodes->length !== 1 || !($lTempDoc->childNodes->item(0) instanceof \DOMElement)) {
				throw new \Exception('wrong xml, XMLInterfacer manage only xml with one and only one root node');
			}
			$lToRemove = [];
			foreach ($lDomElement->childNodes as $lChild) {
				$lToRemove[] = $lChild;
			}
			foreach ($lToRemove as $lChild) {
				$lDomElement->removeChild($lChild);
			}
			foreach ($lTempDoc->childNodes->item(0)->childNodes as $lChild) {
				$lNode = $this->mDomDocument->importNode($lChild, true);
				$lDomElement->appendChild($lNode);
			}
		}
	}
	
	/**
	 * replace value
	 * @param \DOMElement $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	public function replaceValue(&$pNode, $pName, $pValue) {
		$lDomElement = $this->getChildNode($pNode, $pName);
		if (!is_null($lDomElement)) {
			$pNode->removeChild($lDomElement);
			$this->setValue($pNode, $pValue, $pName, true);
		}
	}
	
	/**
	 * verify if node is instance of DomElement
	 * @param mixed $pNode
	 * @return boolean
	 */
	public function verifyNode($pNode) {
		return ($pNode instanceof \DOMElement);
	}
	
	/**
	 * @param string $pValue
	 * @return integer
	 */
	public function castValueToString($pValue) {
		if ($pValue instanceof \DOMElement) {
			$pValue = $this->extractNodeText($pValue);
		}
		return $pValue;
	}
	
	/**
	 * @param string $pValue
	 * @return float
	 */
	public function castValueToInteger($pValue) {
		if ($pValue instanceof \DOMElement) {
			$pValue = $this->extractNodeText($pValue);
		}
		if (!is_numeric($pValue)) {
			throw new \Exception('value has to be numeric');
		}
		return (integer) $pValue;
	}
	
	/**
	 * @param string $pValue
	 * @return boolean
	 */
	public function castValueToFloat($pValue) {
		if ($pValue instanceof \DOMElement) {
			$pValue = $this->extractNodeText($pValue);
		}
		if (!is_numeric($pValue)) {
			throw new \Exception('value has to be numeric');
		}
		return (float) $pValue;
	}
	
	/**
	 * @param string $pValue
	 * @return boolean
	 */
	public function castValueToBoolean($pValue) {
		if ($pValue instanceof \DOMElement) {
			$pValue = $this->extractNodeText($pValue);
		}
		if ($pValue !== '0' && $pValue !== '1') {
			throw new \Exception('value has to be "0" or "1"');
		}
		return $pValue === '1';
	}
	
	/**
	 * 
	 * @param \DOMElement $pNode
	 * @return string
	 */
	public function extractNodeText(\DOMElement $pNode) {
		if ($pNode->childNodes->length != 1) {
			throw new \Exception('malformed node, should only contain one text');
		}
		if ($pNode->childNodes->item(0)->nodeType != XML_TEXT_NODE) {
			throw new \Exception('malformed node, should only contain one text');
		}
		return $pNode->childNodes->item(0)->nodeValue;
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function addMainForeignObject($pNode, $pNodeId, Model $pModel) {
		if (!is_null($this->mMainForeignObjects)) {
			$lModelName = $pModel->getName();
			if (!$this->hasValue($this->mMainForeignObjects, $lModelName, true)) {
				$this->setValue($this->mMainForeignObjects, $this->createNode($lModelName));
				$this->mMainForeignIds[$lModelName] = [];
			}
			if (isset($this->mMainForeignIds[$lModelName][$pNodeId])) {
				$this->getValue($this->mMainForeignObjects, $lModelName, true)->removeChild($this->mMainForeignIds[$lModelName][$pNodeId]);
			}
			$this->deleteValue($this->getValue($this->mMainForeignObjects, $lModelName, true), $pNodeId, true);
			$this->setValue($this->getValue($this->mMainForeignObjects, $lModelName, true), $pNode);
			$this->mMainForeignIds[$lModelName][$pNodeId] = $pNode;
		}
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function removeMainForeignObject($pNodeId, Model $pModel) {
		if (!is_null($this->mMainForeignObjects)) {
			$lModelName = $pModel->getName();
			if ($this->hasValue($this->mMainForeignObjects, $lModelName, true)) {
				$this->getValue($this->mMainForeignObjects, $lModelName, true)->removeChild($this->mMainForeignIds[$lModelName][$pNodeId]);
				unset($this->mMainForeignIds[$lModelName][$pNodeId]);
			}
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function hasMainForeignObject($pModelName, $pId) {
		return !is_null($this->mMainForeignIds)
			&& array_key_exists($pModelName, $this->mMainForeignIds)
			&& array_key_exists($pId, $this->mMainForeignIds[$pModelName]);
	}
}
