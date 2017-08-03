<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Logic;

use Comhon\Exception\ArgumentException;

abstract class Literal extends Formula {
	
	/** @var string */
	const EQUAL      = '=';
	
	/** @var string */
	const SUPP       = '>';
	
	/** @var string */
	const INF        = '<';
	
	/** @var string */
	const SUPP_EQUAL = '>=';
	
	/** @var string */
	const INF_EQUAL  = '<=';
	
	/** @var string */
	const DIFF       = '<>';
	
	/** @var string|integer */
	protected $id;
	
	/** @var string */
	protected $operator;
	
	/** @var array */
	protected static $allowedOperators = [
		self::EQUAL      => null,
		self::SUPP       => null,
		self::INF        => null,
		self::SUPP_EQUAL => null,
		self::INF_EQUAL  => null,
		self::DIFF       => null
	];
	
	/** @var array */
	protected static $oppositeOperator = [
		self::EQUAL      => self::DIFF,
		self::INF        => self::SUPP_EQUAL,
		self::INF_EQUAL  => self::SUPP,
		self::SUPP       => self::INF_EQUAL,
		self::SUPP_EQUAL => self::INF,
		self::DIFF       => self::EQUAL
	];
	
	/**
	 * @param string $operator
	 * @throws \Exception
	 */
	public function __construct($operator) {
		if (!array_key_exists($operator, static::$allowedOperators)) {
			throw new ArgumentException($operator, static::$allowedOperators, 1);
		}
		$this->operator  = $operator;
	}
	
	/**
	 * @return string|number
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @param string|number $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * @return boolean
	 */
	public function hasId() {
		return !is_null($this->id);
	}
	
	/**
	 * @return string
	 */
	public function getOperator() {
		return $this->operator;
	}
	
	/**
	 * reverse operator
	 * 
	 * exemple :
	 * self::EQUAL become self::DIFF
	 * self::INF become self::SUPP_EQUAL
	 */
	public function reverseOperator() {
		$this->operator = static::$oppositeOperator[$this->operator];
	}
	
}