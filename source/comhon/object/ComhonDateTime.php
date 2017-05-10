<?php
namespace comhon\object;

class ComhonDateTime extends \DateTime {
	
	private $mIsUpdated = false;
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::add()
	 */
	public function add($interval) {
		parent::add($interval);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::modify()
	 */
	public function modify($modify) {
		parent::modify($modify);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setDate()
	 */
	public function setDate($year, $month , $day) {
		parent::setDate($year, $month , $day);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setISODate()
	 */
	public function setISODate($year, $week, $day = null) {
		parent::setISODate($year, $week, $day);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setTime()
	 */
	public function setTime($hour, $minute, $second = null) {
		parent::setTime($hour, $minute, $second);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setTimestamp()
	 */
	public function setTimestamp($unixtimestamp) {
		parent::setTimestamp($unixtimestamp);
		$this->mIsUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::sub()
	 */
	public function sub($interval) {
		parent::sub($interval);
		$this->mIsUpdated = true;
	}
	
	/**
	 * verify if datetime has been updated
	 * @return boolean
	 */
	public function isUpdated() {
		return $this->mIsUpdated;
	}
	
	/**
	 * reset updated status
	 */
	public function resetUpdatedStatus() {
		$this->mIsUpdated = false;
	}
	
	/**
	 *
	 * @param Interfacer $pInterfacer
	 * @return mixed|null
	 */
	public function __debugInfo() {
		return get_object_vars($this);
	}
	
}