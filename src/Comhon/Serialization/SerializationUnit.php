<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization;

use Comhon\Object\UniqueObject;
use Comhon\Model\Model;

abstract class SerializationUnit {
	
	/** @var string create operation */
	const CREATE = 'create';

	/** 
	 * update whole object. all values are saved even if there are not flagged as updated.
	 * if a value is not set, null value may be set according serialization unit (example : \Comhon\Serialization\SqlTable)
	 * 
	 * @var string update operation 
	 */
	const UPDATE = 'update';
	
	/** 
	 * patch object (partial update). only values flagged as updated are saved.
	 * if a value is not set AND flagged as updated, 
	 * null value may be set according serialization unit (example : \Comhon\Serialization\SqlTable).
	 * 
	 * path method is not allowed for file serialization (\Comhon\Serialization\SerializationFile).
	 * 
	 * @var string update operation 
	 */
	const PATCH = 'patch';
	
	/**
	 * get serialization unit instance
	 *
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	abstract public static function getInstance();
	
	/**
	 * save specified comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $operation
	 * @throws \Exception
	 * @return integer number of saved objects
	 */
	abstract public function saveObject(UniqueObject $object, $operation = null);
	
	/**
	 * load specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 * @return boolean true if object is successfully loaded, false otherwise
	 */
	abstract public function loadObject(UniqueObject $object, $propertiesFilter = null);
	
	/**
	 * delete specified comhon object from serialization according its id
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer number of deleted objects
	 */
	abstract public function deleteObject(UniqueObject $object);
	
	/**
	 * verify if serialization has incremental id when save object without id
	 *
	 * @param \Comhon\Model\Model $model
	 */
	abstract public function hasIncrementalId(Model $model);
	
}