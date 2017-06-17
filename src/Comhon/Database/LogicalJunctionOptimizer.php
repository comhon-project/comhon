<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

use Comhon\Utils\Utils;

abstract class LogicalJunctionOptimizer {
	
	/**
	 * transform logical junctions in $logicalJunction to literals if it's possible
	 * 
	 * @param LogicalJunction $logicalJunction
	 * @return \Comhon\Database\LogicalJunction
	 */
	public static function logicalJunctionToLiterals($logicalJunction) {
		$newLogicalJunction = new LogicalJunction($logicalJunction->getType());
		self::_logicalJunctionToLiterals($newLogicalJunction, $logicalJunction);
		return $newLogicalJunction;
	}
	
	/**
	 * transform logical junctions to literals if it's possible
	 * @param LogicalJunction $newLogicalJunction
	 * @param LogicalJunction $logicalJunction
	 */
	private static function _logicalJunctionToLiterals($newLogicalJunction, $logicalJunction) {
		$link = $logicalJunction->getType();
		foreach ($logicalJunction->getLiterals() as $literal) {
			$newLogicalJunction->addLiteral($literal);
		}
		foreach ($logicalJunction->getLogicalJunctions() as $subLogicalJunction) {
			if ($subLogicalJunction->hasOnlyOneLiteral() || ($subLogicalJunction->getType() == $link)) {
				self::_logicalJunctionToLiterals($newLogicalJunction, $subLogicalJunction);
			}else {
				$newLogicalJunction->addLogicalJunction(self::logicalJunctionToLiterals($subLogicalJunction));
			}
		}
	}
	
	/**
	 * optimize query literals to optimize execution time of query
	 * @param unknown $logicalJunction
	 * @param integer $countMax	optimisation will not be executed if there is more literals than $countMax
	 * 								actually, optimization is exponential and it can take more time than request itself
	 * @return \Comhon\Database\LogicalJunction
	 */
	public static function optimizeLiterals($logicalJunction, $countMax = 10) {
		$flattenedLiterals = $logicalJunction->getFlattenedLiterals(true);
		$literalKeys = [];
		foreach ($flattenedLiterals as $key => $literal) {
			$literalKeys[] = $key;
		
		}
		if (count($literalKeys) > $countMax) {
			return $logicalJunction;
		}
		$logicalJunction = LogicalJunctionOptimizer::logicalJunctionToLiterals($logicalJunction);
		$logicalConjunctions = self::_setLogicalConjunctions($logicalJunction, $flattenedLiterals, $literalKeys);
		$essentialPrimeImplicants = self::_execQuineMcCluskeyAlgorithm($logicalConjunctions);
		$literalsToFactoryze = self::_findLiteralsToFactoryze($essentialPrimeImplicants);
		$newLogicalJunction = self::_setFinalLogicalJunction($essentialPrimeImplicants, $flattenedLiterals, $literalsToFactoryze, $literalKeys);
		
		return $newLogicalJunction;
	}
	
	/**
	 * 
	 * @param unknown $logicalJunction
	 * @param unknown $flattenedLiterals
	 * @param unknown $literalKeys
	 * @return array|boolean[]
	 */
	private static function _setLogicalConjunctions($logicalJunction, $flattenedLiterals, $literalKeys) {
		$literalValues = [];
		$literals = [];
		$logicalConjunctions = [];
		foreach ($flattenedLiterals as $key => $literal) {
			$literalValues[] = false;
			$literals[$key] = false;
				
		}
		$nbTrueValues = 0;
		$i = count($flattenedLiterals) - 1;
		while ($i > -1) {
			if ($literalValues[$i] === false) {
				$literalValues[$i] = true;
				$literals[$literalKeys[$i]] = true;
				$nbTrueValues++;
				for ($j = $i + 1; $j < count($flattenedLiterals); $j++) {
					$literalValues[$j] = false;
					$literals[$literalKeys[$j]] = false;
					$nbTrueValues--;
				}
				$i = count($flattenedLiterals) - 1;
				$satisfied = $logicalJunction->isSatisfied($literals);
		
				if ($satisfied) {
					$logicalConjunctions[$nbTrueValues][] = $literalValues;
				}
			}else {
				$i--;
			}
		}
		return $logicalConjunctions;
	}
	
	/**
	 * 
	 * @param unknown $logicalConjunctions
	 * @return unknown[]
	 */
	private static function _execQuineMcCluskeyAlgorithm(&$logicalConjunctions) {
		$primeImplicants = [];
		self::_findPrimeImplicants($logicalConjunctions, $primeImplicants);
		return self::_findEssentialPrimeImplicants($logicalConjunctions, $primeImplicants);
	}
	
	/**
	 * 
	 * @param unknown $logicalConjunctions
	 * @param unknown $primeImplicants
	 */
	private static function _findPrimeImplicants($logicalConjunctions, &$primeImplicants) {
		$i = 0;
		$nbVisitedConjunctions = 0;
		$newLogicalConjunctions = [];
		$previousLastAddedConjunctions = [];
		while ($nbVisitedConjunctions < count($logicalConjunctions)) {
			$lastAddedConjunctions = [];
			$k = $i + 1;
			if ((array_key_exists($i, $logicalConjunctions))) {
				foreach ($logicalConjunctions[$i] as $firstIndex => $baseValues) {
					$match = false;
					if ($nbVisitedConjunctions < count($logicalConjunctions) - 1) {
						while (!array_key_exists($k, $logicalConjunctions)) {
							$k++;
						}
						foreach ($logicalConjunctions[$k] as $secondIndex => $values) {
							$indexDifference = null;
							for ($j = 0; $j < count($baseValues); $j++) {
								if ($baseValues[$j] !== $values[$j]) {
									if (!is_null($indexDifference)) {
										$indexDifference = null;
										break;
									}
									$indexDifference = $j;
								}
							}
							if (!is_null($indexDifference)) {
								$match = true;
								$lastAddedConjunctions[$secondIndex] = null;
								$newLogicalConjunctions[$i][] = $baseValues;
								$newLogicalConjunctions[$i][count($newLogicalConjunctions[$i]) - 1][$indexDifference] = null;
							}
						}
					}
					if (!$match && !array_key_exists($firstIndex, $previousLastAddedConjunctions)) {
						$primeImplicants[] = $baseValues;
					}
				}
				$previousLastAddedConjunctions = $lastAddedConjunctions;
				$nbVisitedConjunctions++;
			}
			$i = $k;
		}

		if (!empty($newLogicalConjunctions)) {
			self::_findPrimeImplicants($newLogicalConjunctions, $primeImplicants);
		}
	}
	
	/**
	 * 
	 * @param unknown $allLogicalConjunctions
	 * @param unknown $primeImplicants
	 * @return unknown[]
	 */
	private static function _findEssentialPrimeImplicants($allLogicalConjunctions, $primeImplicants) {
		$essentialPrimeImplicants = [];
		$matrix = self::_buildMatrix($allLogicalConjunctions, $primeImplicants);
		
		$allConjunctionsMatches = [];
		for ($i = 0; $i < count($matrix); $i++) {
			if (!array_key_exists($i, $allConjunctionsMatches)) {
				$nbImplicantsMatches = array_pop($matrix[$i]);
				$currentNbImplicantsMatches = 0;
				$indexConjunctionsMatches = 0;
				$conjunctionsMatches = [];
				$j = 0;
				while (($j < count($matrix[$i])) && ($currentNbImplicantsMatches < $nbImplicantsMatches)) {
					if ($matrix[$i][$j]) {
						$arrayMatches = [];
						$currentNbImplicantsMatches++;
						for ($k = 0; $k < count($matrix); $k++) {
							if ($matrix[$k][$j] && (!array_key_exists($i, $allConjunctionsMatches))) {
								$arrayMatches[$k] = null;
							}
						}
						if (count($arrayMatches) > count($conjunctionsMatches)) {
							$indexConjunctionsMatches = $j;
							$conjunctionsMatches = $arrayMatches;
						}
					}
					$j++;
				}
				$allConjunctionsMatches = Utils::array_merge($allConjunctionsMatches, $conjunctionsMatches);
				$essentialPrimeImplicants[] = $primeImplicants[$indexConjunctionsMatches];
			}
		}
		return $essentialPrimeImplicants;
	}
	
	/**
	 * 
	 * @param unknown $allLogicalConjunctions
	 * @param unknown $primeImplicants
	 * @return boolean[][]|number[][]
	 */
	private static function _buildMatrix($allLogicalConjunctions, $primeImplicants) {
		$matrix = [];
		foreach ($allLogicalConjunctions as $key => $logicalConjunctions) {
			foreach ($logicalConjunctions as $index => $values) {
				$nbMatches = 0;
				$matches = [];
				foreach ($primeImplicants as $primeImplicant) {
					$match = true;
					for ($i = 0; $i < count($values); $i++) {
						if (!is_null($primeImplicant[$i]) && ($values[$i] !== $primeImplicant[$i])) {
							$match = false;
							break;
						}
					}
					$matches[] = $match;
					if ($match) {
						$nbMatches++;
					}
				}
				$matches[] = $nbMatches;
				$matrix[] = $matches;
			}
		}
		usort($matrix, ['Comhon\Database\LogicalJunctionOptimizer', 'sortByLastValue']);
		return $matrix;
	}
	
	/**
	 * 
	 * @param unknown $array1
	 * @param unknown $array2
	 * @return number
	 */
	public static function sortByLastValue($array1, $array2) {
		if ($array1[count($array1) - 1] == $array2[count($array2) - 1]) {
			return 0;
		}
		return ($array1[count($array1) - 1] < $array2[count($array2) - 1]) ? -1 : 1;
	}
	
	/**
	 * 
	 * @param array $essentialPrimeImplicants
	 * @return array
	 */
	private static function _findLiteralsToFactoryze($essentialPrimeImplicants) {
		$literalsToFactoryze = [];
		if (!empty($essentialPrimeImplicants)) {
			foreach ($essentialPrimeImplicants[0] as  $i => $value) {
				if (!is_null($value)) {
					$literalsToFactoryze[$i] = $value;
				}
			}
			foreach ($essentialPrimeImplicants as $essentialPrimeImplicantValues) {
				$indexes = [];
				foreach ($literalsToFactoryze as $i => $value) {
					if ($value !== $essentialPrimeImplicantValues[$i]) {
						$indexes[] = $i;
					}
				}
				foreach ($indexes as $index) {
					unset($literalsToFactoryze[$index]);
				}
				if (empty($literalsToFactoryze)) {
					break;
				}
			}
		}
		return array_keys($literalsToFactoryze);
	}
	
	/**
	 * 
	 * @param unknown $essentialPrimeImplicants
	 * @param unknown $flattenedLiterals
	 * @param unknown $literalsToFactoryze
	 * @param unknown $literalKeys
	 * @return \Comhon\Database\LogicalJunction
	 */
	private static function _setFinalLogicalJunction($essentialPrimeImplicants, $flattenedLiterals, $literalsToFactoryze, $literalKeys) {
		$literalsToFactoryzeByKey = [];
		$firstConjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		if (!empty($literalsToFactoryze)) {
			foreach ($literalsToFactoryze as $literalIndex) {
				$firstConjunction->addLiteral($flattenedLiterals[$literalKeys[$literalIndex]]);
				$literalsToFactoryzeByKey[$literalIndex] = null;
			}
		}

		$disjunction = new LogicalJunction(LogicalJunction::DISJUNCTION);
		$firstConjunction->addLogicalJunction($disjunction);
		
		foreach ($essentialPrimeImplicants as $essentialPrimeImplicantValues) {
			$conjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
			foreach ($essentialPrimeImplicantValues as $index => $value) {
				// if literal hasn't been factorised
				if (!array_key_exists($index, $literalsToFactoryzeByKey)) {
					if ($value === true) {
						$conjunction->addLiteral($flattenedLiterals[$literalKeys[$index]]);
					}else if ($value === false) {
						$literal = $flattenedLiterals[$literalKeys[$index]];
						$oppositeLiteral = clone $literal;
						$oppositeLiteral->reverseOperator();
						$conjunction->addLiteral($oppositeLiteral);
					}
				}
		
			}
			$disjunction->addLogicalJunction($conjunction);
		}
		return $firstConjunction;
	}
}