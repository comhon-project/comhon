<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Property;

use Comhon\Model\SimpleModel;
use Comhon\Model\ModelIndex;
use Comhon\Exception\ComhonException;

class AutoProperty extends Property {
	
	private $auto;
	
	const INCREMENTAL = 'incremental';
	
	/**
	 * 
	 * @param \Comhon\Model\SimpleModel $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isRequired
	 * @param boolean $isSerializable
	 * @param boolean $isInterfacedAsNodeXml
	 * @param string[] $dependencies
	 * @param string $auto
	 * @throws \Exception
	 */
	public function __construct(SimpleModel $model, $name, $serializationName = null, $isId = false, $isPrivate = false, $isRequired = false, $isSerializable = true, $isInterfacedAsNodeXml = null, $dependencies = [], $auto = null) {
		parent::__construct($model, $name, $serializationName, $isId, $isPrivate, $isRequired, $isSerializable, true, null, $isInterfacedAsNodeXml, [], $dependencies);
		
		if (!($model instanceof ModelIndex) || $auto !== self::INCREMENTAL) {
			throw new ComhonException("auto value '$auto' not allowed on property model '{$model->getName()}'");
		}
		$this->auto = $auto;
	}
	
	/**
	 * return the function name that auto generate value
	 * 
	 * @return string
	 */
	public function getAutoFunction() {
		return $this->auto;
	}
	
}