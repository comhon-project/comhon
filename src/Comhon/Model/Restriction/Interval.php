<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Restriction;

use Comhon\Model\ModelFloat;
use Comhon\Model\ModelInteger;
use Comhon\Model\ModelDateTime;
use Comhon\Object\ComhonDateTime;
use Comhon\Exception\Restriction\MalformedIntervalException;
use Comhon\Exception\Restriction\NotSupportedModelIntervalException;
use Comhon\Model\AbstractModel;
use Comhon\Model\SimpleModel;

class Interval extends Restriction {
	
	// following regexs doesn't verify if left endpoint is inferior than right endpoint
	// there's no verification on date format only interval structure is checked
	
	/**
	 * @var string regex to check date time interval validity
	 *     regex doesn't verify if left endpoint is inferior than right endpoint
	 *     there's no verification on date format only interval structure is checked
	 */
	const DATETIME_INTERVAL = '/^([\\[\\]])([^,]*),([^,]*)([\\[\\]])$/';
	
	/**
	 * @var string regex to check float interval validity
	 *     regex doesn't verify if left endpoint is inferior than right endpoint
	 */
	const FLOAT_INTERVAL    = '/^([\\[\\]])\\s*((?:-?\\d+(?:\\.\\d+)?)|(?:\\d*))\\s*,\\s*((?:-?\\d+(?:\\.\\d+)?)|(?:\\d*))\\s*([\\[\\]])$/';
	
	/**
	 * @var string regex to check integer interval validity
	 *     regex doesn't verify if left endpoint is inferior than right endpoint
	 */
	const INTEGER_INTERVAL  = '/^([\\[\\]])\\s*((?:-?\\d+)|(?:\\d*))\\s*,\\s*((?:-?\\d+)|(?:\\d*))\\s*([\\[\\]])$/';
	
	/** @var mixed */
	protected $leftEndPoint  = null;
	
	/** @var mixed */
	protected $rightEndPoint = null;
	
	/** @var boolean */
	protected $isLeftClosed  = true;
	
	/** @var boolean */
	protected $isRightClosed = true;
	
	/** @var \Comhon\Model\SimpleModel */
	private $model;
	
	/**
	 * 
	 * @param string $interval
	 * @param \Comhon\Model\SimpleModel $model
	 * @throws \Comhon\Exception\Restriction\MalformedIntervalException
	 * @throws \Comhon\Exception\Restriction\NotSupportedModelIntervalException
	 */
	public function __construct($interval, SimpleModel $model) {
		$matches = [];
		if ($model instanceof ModelFloat) {
			if (!preg_match(self::FLOAT_INTERVAL, $interval, $matches)) {
				throw new MalformedIntervalException($interval);
			}
			$matches[2] = $matches[2] === '' ? null : (float) $matches[2];
			$matches[3] = $matches[3] === '' ? null : (float) $matches[3];
		}
		elseif ($model instanceof ModelInteger) {
			if (!preg_match(self::INTEGER_INTERVAL, $interval, $matches)) {
				throw new MalformedIntervalException($interval);
			}
			$matches[2] = $matches[2] === '' ? null : (integer) $matches[2];
			$matches[3] = $matches[3] === '' ? null : (integer) $matches[3];
		}
		elseif ($model instanceof ModelDateTime) {
			if (!preg_match(self::DATETIME_INTERVAL, $interval, $matches)) {
				throw new MalformedIntervalException($interval);
			}
			$matches[2] = trim($matches[2]);
			$matches[3] = trim($matches[3]);
			$matches[2] = $matches[2] === '' ? null : new \DateTime($matches[2]);
			$matches[3] = $matches[3] === '' ? null : new \DateTime($matches[3]);
		} else {
			throw new NotSupportedModelIntervalException($model);
		}
		$this->isLeftClosed  = $matches[1] === '[';
		$this->isRightClosed = $matches[4] === ']';
		$this->leftEndPoint  = $matches[2];
		$this->rightEndPoint = $matches[3];
		$this->model = $model;
		
		if ($this->leftEndPoint > $this->rightEndPoint) {
			throw new MalformedIntervalException($interval);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		if (is_null($value)) {
			return false;
		}
		if (!is_null($this->leftEndPoint)) {
			if ($this->isLeftClosed) {
				if ($value < $this->leftEndPoint) {
					return false;
				}
			} elseif ($value <= $this->leftEndPoint) {
				return false;
			}
		}
		if (!is_null($this->rightEndPoint)) {
			if ($this->isRightClosed) {
				if ($value > $this->rightEndPoint) {
					return false;
				}
			} elseif ($value >= $this->rightEndPoint) {
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return $this === $restriction
			|| (
				($restriction instanceof Interval)
				&& $this->model === $restriction->model
				&& $this->isLeftClosed  === $restriction->isLeftClosed
				&& $this->isRightClosed === $restriction->isRightClosed
				&& $this->leftEndPoint  === $restriction->leftEndPoint
				&& $this->rightEndPoint === $restriction->rightEndPoint
			);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(AbstractModel $model) {
		return $model === $this->model;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toMessage()
	 */
	public function toMessage($value) {
		if (!is_float($value) && !is_integer($value) && !($value instanceof ComhonDateTime)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Interval must be an integer, float or instance of ComhonDateTime, instance of $class given";
		}
		
		return (($value instanceof ComhonDateTime) ? $value->format('c') : $value) 
			. ' is' . ($this->satisfy($value) ? ' ' : ' not ')
			. 'in interval '
			. ($this->isLeftClosed ? '[' : ']')
			. (($this->leftEndPoint instanceof \DateTime)	? $this->leftEndPoint->format('c')	: $this->leftEndPoint)
			. ','
			. (($this->rightEndPoint instanceof \DateTime) ? $this->rightEndPoint->format('c') : $this->rightEndPoint)
			. ($this->isRightClosed ? ']' : '[');
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return ($this->isLeftClosed ? '[' : ']')
			. (($this->leftEndPoint instanceof \DateTime)	? $this->leftEndPoint->format('c')	: $this->leftEndPoint)
			. ','
			. (($this->rightEndPoint instanceof \DateTime) ? $this->rightEndPoint->format('c') : $this->rightEndPoint)
			. ($this->isRightClosed ? ']' : '[');
	}
	
}