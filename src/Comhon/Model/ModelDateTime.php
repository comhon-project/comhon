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

class ModelDateTime extends SimpleModel {
	
	const ID = 'dateTime';
	
	protected function _init() {
		$this->modelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		return $this->toString($value, $interfacer->getDateTimeZone(), $interfacer->getDateTimeFormat());
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @return ComhonDateTime|null
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
	 * 
	 * @param string $value
	 * @param \DateTimeZone $dateTimeZone
	 * @return ComhonDateTime
	 */
	public function fromString($value, \DateTimeZone $dateTimeZone) {
		$dateTime = new ComhonDateTime($value, $dateTimeZone);
		if ($dateTime->getTimezone()->getName() !== $dateTimeZone->getName()) {
			$dateTime->setTimezone($dateTimeZone);
		}
		return $dateTime;
	}
	
	public function toString(ComhonDateTime $dateTime, $dateTimeZone, $dateFormat = 'c') {
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
	
	public function castValue($value) {
		throw new \Exception('cannot cast datetime object');
	}
	
	public function verifValue($value) {
		if (!($value instanceof ComhonDateTime)) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be an instance of dateTime, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
}