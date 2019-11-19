<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser\V_3_0;

use Comhon\Manifest\Parser\V_2_0\ManifestParser as ParentManifestParser;
use Comhon\Interfacer\XMLInterfacer;

class ManifestParser extends ParentManifestParser {

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::isAbstract()
	 */
	public function isAbstract() {
		return $this->_getBooleanValue($this->manifest, self::IS_ABSTRACT, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::isSharedParentId()
	 */
	public function isSharedParentId() {
		return $this->_getBooleanValue($this->manifest, self::SHARE_PARENT_ID, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::sharedId()
	 */
	public function sharedId() {
		return $this->interfacer->getValue($this->manifest, self::SHARED_ID);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getExtends()
	 */
	public function getExtends() {
		if ($this->interfacer->hasValue($this->manifest, self::_EXTENDS, true)) {
			$extends = $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, self::_EXTENDS, true));
			if ($this->interfacer instanceof XMLInterfacer) {
				foreach ($extends as $key => $domNode) {
					$extends[$key] = $this->interfacer->extractNodeText($domNode);
				}
			}
		} else {
			$extends = null;
		}
		
		return $extends;
	}
}
