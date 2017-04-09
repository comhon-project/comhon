<?php
namespace comhon\interfacer;

class XMLInterfacer extends Interfacer {

	private $mDomDocument;
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * @throws \Exception
	 */
	public function __construct() {
		$this->mDomDocument = new \DOMDocument();
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
	 * serialize given node
	 * @param \DOMNode $pNode
	 * @return string
	 */
	public function serialize($pNode) {
		return $this->mDomDocument->saveXML($pNode);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param \DOMElement $pNode
	 * @param string $pName
	 */
	public function flattenNode(&$pNode, $pName) {
		$lDomElement = $pNode->getElementsByTagName($pName)->item(0);
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
	 * replace value
	 * @param \DOMElement $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	public function replaceValue(&$pNode, $pName, $pValue) {
		$lDomElement = $pNode->getElementsByTagName($pName)->item(0);
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
	protected function _verifyNode($pNode) {
		return ($pNode instanceof \DOMElement);
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @return boolean
	 */
	protected function _needTransformation($pNode) {
		return ($pNode instanceof \SimpleXMLElement);
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @return boolean
	 */
	protected function _transform($pNode) {
		return dom_import_simplexml($pNode);
	}
	
}
