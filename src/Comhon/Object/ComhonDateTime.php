<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object;

class ComhonDateTime extends \DateTime {
	
	private $isUpdated = false;
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::add()
	 */
	public function add($interval) {
		parent::add($interval);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::modify()
	 */
	public function modify($modify) {
		parent::modify($modify);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setDate()
	 */
	public function setDate($year, $month , $day) {
		parent::setDate($year, $month , $day);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setISODate()
	 */
	public function setISODate($year, $week, $day = null) {
		parent::setISODate($year, $week, $day);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setTime()
	 */
	public function setTime($hour, $minute, $second = null) {
		parent::setTime($hour, $minute, $second);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::setTimestamp()
	 */
	public function setTimestamp($unixtimestamp) {
		parent::setTimestamp($unixtimestamp);
		$this->isUpdated = true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DateTime::sub()
	 */
	public function sub($interval) {
		parent::sub($interval);
		$this->isUpdated = true;
	}
	
	/**
	 * verify if datetime has been updated
	 * @return boolean
	 */
	public function isUpdated() {
		return $this->isUpdated;
	}
	
	/**
	 * reset updated status
	 */
	public function resetUpdatedStatus() {
		$this->isUpdated = false;
	}
	
}