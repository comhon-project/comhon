<?php
namespace comhon\model\restriction;

use comhon\model\Model;
use comhon\model\ModelFloat;
use comhon\model\ModelInteger;
use comhon\model\ModelDateTime;
use comhon\object\ComhonDateTime;
use comhon\exception\MalformedIntervalException;
use comhon\exception\NotSupportedModelIntervalException;

class Interval implements Restriction {
	
	// following regexs doesn't verify if left endpoint is inferior than right endpoint
	// there's no verification on date format only interval structure is checked
	const DATETIME_INTERVAL = '/^([\\[\\]])([^,]*),([^,]*)([\\[\\]])$/';
	const FLOAT_INTERVAL    = '/^([\\[\\]])\\s*((?:-?\\d+(?:\\.\\d+)?)|(?:\\d*))\\s*,\\s*((?:-?\\d+(?:\\.\\d+)?)|(?:\\d*))\\s*([\\[\\]])$/';
	const INTEGER_INTERVAL  = '/^([\\[\\]])\\s*((?:-?\\d+)|(?:\\d*))\\s*,\\s*((?:-?\\d+)|(?:\\d*))\\s*([\\[\\]])$/';
	
	/** @var mixed */
	private $mLeftEndPoint  = null;
	
	/** @var mixed */
	private $mRightEndPoint = null;
	
	/** @var boolean */
	private $mIsLeftClosed  = true;
	
	/** @var boolean */
	private $mIsRightClosed = true;
	
	public function __construct($pInterval, Model $pModel) {
		$lMatches = [];
		if ($pModel instanceof ModelFloat) {
			if (!preg_match(self::FLOAT_INTERVAL, $pInterval, $lMatches)) {
				throw new MalformedIntervalException($pInterval);
			}
			$lMatches[2] = $lMatches[2] === '' ? null : (float) $lMatches[2];
			$lMatches[3] = $lMatches[3] === '' ? null : (float) $lMatches[3];
		}
		elseif ($pModel instanceof ModelInteger) {
			if (!preg_match(self::INTEGER_INTERVAL, $pInterval, $lMatches)) {
				throw new MalformedIntervalException($pInterval);
			}
			$lMatches[2] = $lMatches[2] === '' ? null : (integer) $lMatches[2];
			$lMatches[3] = $lMatches[3] === '' ? null : (integer) $lMatches[3];
		}
		elseif ($pModel instanceof ModelDateTime) {
			if (!preg_match(self::DATETIME_INTERVAL, $pInterval, $lMatches)) {
				throw new MalformedIntervalException($pInterval);
			}
			$lMatches[2] = trim($lMatches[2]);
			$lMatches[3] = trim($lMatches[3]);
			$lMatches[2] = $lMatches[2] === '' ? null : new \DateTime($lMatches[2]);
			$lMatches[3] = $lMatches[3] === '' ? null : new \DateTime($lMatches[3]);
		} else {
			throw new NotSupportedModelIntervalException($pModel);
		}
		$this->mIsLeftClosed  = $lMatches[1] === '[';
		$this->mIsRightClosed = $lMatches[4] === ']';
		$this->mLeftEndPoint  = $lMatches[2];
		$this->mRightEndPoint = $lMatches[3];
		
		if ($this->mLeftEndPoint > $this->mRightEndPoint) {
			throw new MalformedIntervalException($pInterval);
		}
	}
	
	/**
	 * 
	 * @param mixed $pValue
	 */
	public function satisfy($pValue) {
		if (is_null($pValue)) {
			return false;
		}
		if (!is_null($this->mLeftEndPoint)) {
			if ($this->mIsLeftClosed) {
				if ($pValue < $this->mLeftEndPoint) {
					return false;
				}
			} elseif ($pValue <= $this->mLeftEndPoint) {
				return false;
			}
		}
		if (!is_null($this->mRightEndPoint)) {
			if ($this->mIsRightClosed) {
				if ($pValue > $this->mRightEndPoint) {
					return false;
				}
			} elseif ($pValue >= $this->mRightEndPoint) {
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Interval $pRestriction
	 */
	public function isEqual(Restriction $pRestriction) {
		return $this === $pRestriction
			|| $this->mIsLeftClosed  == $pRestriction->mIsLeftClosed
			|| $this->mIsRightClosed == $pRestriction->mIsRightClosed
			|| $this->mLeftEndPoint  == $pRestriction->mLeftEndPoint
			|| $this->mRightEndPoint == $pRestriction->mRightEndPoint;
	}
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $pModel
	 */
	public function isAllowedModel(Model $pModel) {
		return ($pModel instanceof ModelInteger)
			|| ($pModel instanceof ModelFloat)
			|| ($pModel instanceof ModelDateTime);
	}
	
	/**
	 * stringify restriction and value
	 * @param mixed $pValue
	 */
	public function toString($pValue) {
		if (!is_float($pValue) && !is_integer($pValue) && !($pValue instanceof ComhonDateTime)) {
			$lClass = gettype($pValue) == 'object' ? get_class($pValue) : gettype($pValue);
			return "Value passed to Interval must be an instance of integer, float or comhon\object\ComhonDateTime, instance of $lClass given";
		}
		
		return (($pValue instanceof ComhonDateTime) ? $pValue->format('c') : $pValue) 
			. ' is' . ($this->satisfy($pValue) ? ' ' : ' not ')
			. 'in interval '
			. ($this->mIsLeftClosed ? '[' : ']')
			. (($this->mLeftEndPoint instanceof \DateTime)	? $this->mLeftEndPoint->format('c')	: $this->mLeftEndPoint)
			. ','
			. (($this->mRightEndPoint instanceof \DateTime) ? $this->mRightEndPoint->format('c') : $this->mRightEndPoint)
			. ($this->mIsRightClosed ? ']' : '[');
	}
	
}