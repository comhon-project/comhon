<?php
namespace comhon\model\restriction;

use comhon\model\Model;
use comhon\model\ModelFloat;
use comhon\model\ModelInteger;
use comhon\model\ModelDateTime;

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
				throw new \Exception("interval '$pInterval' not valid");
			}
			$lMatches[2] = empty($lMatches[2]) ? null : (float) $lMatches[2];
			$lMatches[3] = empty($lMatches[3]) ? null : (float) $lMatches[3];
		}
		elseif ($pModel instanceof ModelInteger) {
			if (!preg_match(self::INTEGER_INTERVAL, $pInterval, $lMatches)) {
				throw new \Exception("interval '$pInterval' not valid");
			}
			$lMatches[2] = empty($lMatches[2]) ? null : (integer) $lMatches[2];
			$lMatches[3] = empty($lMatches[3]) ? null : (integer) $lMatches[3];
		}
		elseif ($pModel instanceof ModelDateTime) {
			if (!preg_match(self::DATETIME_INTERVAL, $pInterval, $lMatches)) {
				throw new \Exception("interval '$pInterval' not valid");
			}
			$lMatches[2] = trim($lMatches[2]);
			$lMatches[3] = trim($lMatches[3]);
			$lMatches[2] = empty($lMatches[2]) ? null : new \DateTime($lMatches[2]);
			$lMatches[3] = empty($lMatches[3]) ? null : new \DateTime($lMatches[3]);
		}
		$this->mIsLeftClosed  = $lMatches[1] === '[';
		$this->mIsRightClosed = $lMatches[4] === ']';
		$this->mLeftEndPoint  = $lMatches[2];
		$this->mRightEndPoint = $lMatches[3];
		
		if ($this->mLeftEndPoint > $this->mRightEndPoint) {
			throw new \Exception("interval '$pInterval' not valid");
		}
	}
	
	public function satisfy($pValue) {
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
	
}