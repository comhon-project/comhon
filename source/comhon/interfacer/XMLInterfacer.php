<?php
namespace comhon\interfacer;

class XMLInterfacer implements Interfacer {

	private $mDomDocument;
	
	/**
	 * initialize DomDocument that may be serialized later in xml format
	 * @param string $pRootName
	 * @throws \Exception
	 * @return DOMNode
	 */
	public function initialize($pRootName = null) {
		if (is_null($pRootName)) {
			throw new \Exception('interfacer initialization error : missing root name');
		}
		$this->mDomDocument = new \DOMDocument();
		return $this->mDomDocument->appendChild($this->mDomDocument->createElement($pRootName));
	}
	
	/**
	 * 
	 * @param \DOMElement $pNode
	 * @param mixed $pValue if scalar value, set attribute. else if \DOMElement, append child
	 * @param string $pName used only if $pValue if scalar value
	 * @param boolean $pAsNode used only if $pValue if scalar value
	 * @return \DOMNode|null return added node or null if nothing added
	 */
	public function setValue($pNode, $pValue, $pName = null, $pAsNode = false) {
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
	 * serialize DomDocument previously initialized
	 * @return string
	 */
	public function serialize() {
		return $this->mDomDocument->saveXML();
	}
	
}
