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
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\ComhonException;

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
	 * @see \Comhon\Model\SimpleModel::_importScalarValue()
	 */
	protected function _importScalarValue($value, Interfacer $interfacer) {
		if (!is_string($value)) {
			if (is_int($value) && $interfacer->getMediaType() == 'application/x-yaml') {
				// symfony YAML library automaticaly transform date time without quote to timestamp
				// and date time without timezone are processed with UTC timezome by default
				// but comhon framework use server default timezone.
				// and we can't know if there was a specified timezone in originale value.
				// so we can't have the wanted value according server default timezone.
				// so dateTime values must be quoted.
				throw new ComhonException('dateTime value must be quoted in YAML format');
			}
			throw new UnexpectedValueTypeException($value, 'string');
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