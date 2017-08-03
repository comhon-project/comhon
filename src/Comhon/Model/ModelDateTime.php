<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Object\ComhonDateTime;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Exception\ComhonException;
use Comhon\Exception\UnexpectedValueTypeException;

class ModelDateTime extends SimpleModel {
	
	/** @var string */
	const ID = 'dateTime';
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::_initializeModelName()
	 */
	protected function _initializeModelName() {
		$this->modelName = self::ID;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::exportSimple()
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		return $this->toString($value, $interfacer->getDateTimeZone(), $interfacer->getDateTimeFormat());
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::importSimple()
	 */
	public function importSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToString($value);
		}
		return $this->fromString($value, $interfacer->getDateTimeZone());
	}
	
	/**
	 * instanciate ComhonDateTime object
	 * 
	 * @param string $time
	 * @param \DateTimeZone $dateTimeZone
	 * @return \Comhon\Object\ComhonDateTime
	 */
	public function fromString($time, \DateTimeZone $dateTimeZone) {
		$dateTime = new ComhonDateTime($time, $dateTimeZone);
		if ($dateTime->getTimezone()->getName() !== $dateTimeZone->getName()) {
			$dateTime->setTimezone($dateTimeZone);
		}
		return $dateTime;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\ComhonDateTime $dateTime
	 * @param \DateTimeZone $dateTimeZone
	 * @param string $dateFormat
	 * @return string
	 */
	public function toString(ComhonDateTime $dateTime, \DateTimeZone $dateTimeZone, $dateFormat = 'c') {
		if ($dateTimeZone->getName() == $dateTime->getTimezone()->getName()) {
			return $dateTime->format($dateFormat);
		}
		else {
			$OriginDateTimeZone = $dateTime->getTimezone();
			$dateTime->setTimezone($dateTimeZone);
			$dateTimeString = $dateTime->format($dateFormat);
			$dateTime->setTimezone($OriginDateTimeZone);
			return $dateTimeString;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::castValue()
	 */
	public function castValue($value) {
		throw new ComhonException('cannot cast datetime object');
	}
	
	/**
	 * verify if value is a ComhonDateTime object
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!($value instanceof ComhonDateTime)) {
			throw new UnexpectedValueTypeException($value, ComhonDateTime::class);
		}
		return true;
	}
}