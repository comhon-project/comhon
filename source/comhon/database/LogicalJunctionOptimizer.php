<?php
namespace comhon\database;

use comhon\utils\Utils;
abstract class LogicalJunctionOptimizer {
	
	/**
	 * transform logical junctions in $pLogicalJunction to literals if it's possible
	 * @param LogicalJunction $pLogicalJunction
	 */
	public static function logicalJunctionToLiterals($pLogicalJunction) {
		$lNewLogicalJunction = new LogicalJunction($pLogicalJunction->getType());
		self::_logicalJunctionToLiterals($lNewLogicalJunction, $pLogicalJunction);
		return $lNewLogicalJunction;
	}
	
	/**
	 * transform logical junctions to literals if it's possible
	 * @param LogicalJunction $pNewLogicalJunction
	 * @param LogicalJunction $pLogicalJunction
	 */
	private static function _logicalJunctionToLiterals($pNewLogicalJunction, $pLogicalJunction) {
		$lLink = $pLogicalJunction->getType();
		foreach ($pLogicalJunction->getLiterals() as $lLiteral) {
			$pNewLogicalJunction->addLiteral($lLiteral);
		}
		foreach ($pLogicalJunction->getLogicalJunction() as $lLogicalJunction) {
			if ($lLogicalJunction->hasOnlyOneLiteral() || ($lLogicalJunction->getType() == $lLink)) {
				self::_logicalJunctionToLiterals($pNewLogicalJunction, $lLogicalJunction);
			}else {
				$pNewLogicalJunction->addLogicalJunction(self::logicalJunctionToLiterals($lLogicalJunction));
			}
		}
	}
	
	/**
	 * optimize query literals to optimize execution time of query
	 * @param unknown $pLogicalJunction
	 * @param integer $pCountMax	optimisation will not be executed if there is more literals than $pCountMax
	 * 								actually, optimization is exponential and it can take more time than request itself
	 */
	public static function optimizeLiterals($pLogicalJunction, $pCountMax = 10) {
		$lFlattenedLiterals = $pLogicalJunction->getFlattenedLiterals("md5");
		$lLiteralKeys = array();
		foreach ($lFlattenedLiterals as $lKey => $lLiteral) {
			$lLiteralKeys[] = $lKey;
		
		}
		if (count($lLiteralKeys) > $pCountMax) {
			return $pLogicalJunction;
		}
		$pLogicalJunction = LogicalJunctionOptimizer::logicalJunctionToLiterals($pLogicalJunction);
		$lLogicalConjunctions = self::_setLogicalConjunctions($pLogicalJunction, $lFlattenedLiterals, $lLiteralKeys);
		$lEssentialPrimeImplicants = self::_execQuineMcCluskeyAlgorithm($lLogicalConjunctions);
		$lLiteralsToFactoryze = self::_findLiteralsToFactoryze($lEssentialPrimeImplicants);
		$lLogicalJunction = self::_setFinalLogicalJunction($lEssentialPrimeImplicants, $lFlattenedLiterals, $lLiteralsToFactoryze, $lLiteralKeys);
		
		return $lLogicalJunction;
	}
	
	private static function _setLogicalConjunctions($pLogicalJunction, $pFlattenedLiterals, $pLiteralKeys) {
		$lLiteralValues = array();
		$lLiterals = array();
		$lLogicalConjunctions = array();
		foreach ($pFlattenedLiterals as $lKey => $lLiteral) {
			$lLiteralValues[] = false;
			$lLiterals[$lKey] = false;
				
		}
		$lNbTrueValues = 0;
		$i = count($pFlattenedLiterals) - 1;
		while ($i > -1) {
			if ($lLiteralValues[$i] === false) {
				$lLiteralValues[$i] = true;
				$lLiterals[$pLiteralKeys[$i]] = true;
				$lNbTrueValues++;
				for ($j = $i + 1; $j < count($pFlattenedLiterals); $j++) {
					$lLiteralValues[$j] = false;
					$lLiterals[$pLiteralKeys[$j]] = false;
					$lNbTrueValues--;
				}
				$i = count($pFlattenedLiterals) - 1;
				$lSatisfied = $pLogicalJunction->isSatisfied($lLiterals);
		
				if ($lSatisfied) {
					$lLogicalConjunctions[$lNbTrueValues][] = $lLiteralValues;
				}
			}else {
				$i--;
			}
		}
		return $lLogicalConjunctions;
	}
	
	private static function _execQuineMcCluskeyAlgorithm(&$pLogicalConjunctions) {
		$lPrimeImplicants = array();
		self::_findPrimeImplicants($pLogicalConjunctions, $lPrimeImplicants);
		return self::_findEssentialPrimeImplicants($pLogicalConjunctions, $lPrimeImplicants);
	}
	
	private static function _findPrimeImplicants($pLogicalConjunctions, &$lPrimeImplicants) {
		$i = 0;
		$lNbVisitedConjunctions = 0;
		$lNewLogicalConjunctions = array();
		$lPreviousLastAddedConjunctions = array();
		while ($lNbVisitedConjunctions < count($pLogicalConjunctions)) {
			$lLastAddedConjunctions = array();
			$k = $i + 1;
			if ((array_key_exists($i, $pLogicalConjunctions))) {
				foreach ($pLogicalConjunctions[$i] as $lFirstIndex => $lBaseValues) {
					$lMatch = false;
					if ($lNbVisitedConjunctions < count($pLogicalConjunctions) - 1) {
						while (!array_key_exists($k, $pLogicalConjunctions)) {
							$k++;
						}
						foreach ($pLogicalConjunctions[$k] as $lSecondIndex => $lValues) {
							$lIndexDifference = null;
							for ($j = 0; $j < count($lBaseValues); $j++) {
								if ($lBaseValues[$j] !== $lValues[$j]) {
									if (!is_null($lIndexDifference)) {
										$lIndexDifference = null;
										break;
									}
									$lIndexDifference = $j;
								}
							}
							if (!is_null($lIndexDifference)) {
								$lMatch = true;
								$lLastAddedConjunctions[$lSecondIndex] = null;
								$lNewLogicalConjunctions[$i][] = $lBaseValues;
								$lNewLogicalConjunctions[$i][count($lNewLogicalConjunctions[$i]) - 1][$lIndexDifference] = null;
							}
						}
					}
					if (!$lMatch && !array_key_exists($lFirstIndex, $lPreviousLastAddedConjunctions)) {
						$lPrimeImplicants[] = $lBaseValues;
					}
				}
				$lPreviousLastAddedConjunctions = $lLastAddedConjunctions;
				$lNbVisitedConjunctions++;
			}
			$i = $k;
		}

		if (!empty($lNewLogicalConjunctions)) {
			self::_findPrimeImplicants($lNewLogicalConjunctions, $lPrimeImplicants);
		}
	}
	
	private static function _findEssentialPrimeImplicants($pAllLogicalConjunctions, $pPrimeImplicants) {
		$lEssentialPrimeImplicants = array();
		$lMatrix = self::_buildMatrix($pAllLogicalConjunctions, $pPrimeImplicants);
		
		$lAllConjunctionsMatches = array();
		for ($i = 0; $i < count($lMatrix); $i++) {
			if (!array_key_exists($i, $lAllConjunctionsMatches)) {
				$lNbImplicantsMatches = array_pop($lMatrix[$i]);
				$lCurrentNbImplicantsMatches = 0;
				$lIndexConjunctionsMatches = 0;
				$lConjunctionsMatches = array();
				$j = 0;
				while (($j < count($lMatrix[$i])) && ($lCurrentNbImplicantsMatches < $lNbImplicantsMatches)) {
					if ($lMatrix[$i][$j]) {
						$lArrayMatches = array();
						$lCurrentNbImplicantsMatches++;
						for ($k = 0; $k < count($lMatrix); $k++) {
							if ($lMatrix[$k][$j] && (!array_key_exists($i, $lAllConjunctionsMatches))) {
								$lArrayMatches[$k] = null;
							}
						}
						if (count($lArrayMatches) > count($lConjunctionsMatches)) {
							$lIndexConjunctionsMatches = $j;
							$lConjunctionsMatches = $lArrayMatches;
						}
					}
					$j++;
				}
				$lAllConjunctionsMatches = Utils::array_merge($lAllConjunctionsMatches, $lConjunctionsMatches);
				$lEssentialPrimeImplicants[] = $pPrimeImplicants[$lIndexConjunctionsMatches];
			}
		}
		return $lEssentialPrimeImplicants;
	}
	
	private static function _buildMatrix($pAllLogicalConjunctions, $pPrimeImplicants) {
		$lMatrix = array();
		foreach ($pAllLogicalConjunctions as $lKey => $lLogicalConjunctions) {
			foreach ($lLogicalConjunctions as $lIndex => $lValues) {
				$lNbMatches = 0;
				$lMatches = array();
				foreach ($pPrimeImplicants as $lPrimeImplicant) {
					$lMatch = true;
					for ($i = 0; $i < count($lValues); $i++) {
						if (!is_null($lPrimeImplicant[$i]) && ($lValues[$i] !== $lPrimeImplicant[$i])) {
							$lMatch = false;
							break;
						}
					}
					$lMatches[] = $lMatch;
					if ($lMatch) {
						$lNbMatches++;
					}
				}
				$lMatches[] = $lNbMatches;
				$lMatrix[] = $lMatches;
			}
		}
		usort($lMatrix, array("comhon\database\LogicalJunctionOptimizer", "sortByLastValue"));
		return $lMatrix;
	}
	
	public static function sortByLastValue($lArray1, $lArray2) {
		if ($lArray1[count($lArray1) - 1] == $lArray2[count($lArray2) - 1]) {
			return 0;
		}
		return ($lArray1[count($lArray1) - 1] < $lArray2[count($lArray2) - 1]) ? -1 : 1;
	}
	
	/**
	 * 
	 * @param array $pEssentialPrimeImplicants
	 * @return array
	 */
	private static function _findLiteralsToFactoryze($pEssentialPrimeImplicants) {
		$lLiteralsToFactoryze = array();
		if (!empty($pEssentialPrimeImplicants)) {
			foreach ($pEssentialPrimeImplicants[0] as  $i => $lValue) {
				if (!is_null($lValue)) {
					$lLiteralsToFactoryze[$i] = $lValue;
				}
			}
			foreach ($pEssentialPrimeImplicants as $lEssentialPrimeImplicantValues) {
				$lIndexes = array();
				foreach ($lLiteralsToFactoryze as $i => $lValue) {
					if ($lValue !== $lEssentialPrimeImplicantValues[$i]) {
						$lIndexes[] = $i;
					}
				}
				foreach ($lIndexes as $lIndex) {
					unset($lLiteralsToFactoryze[$lIndex]);
				}
				if (empty($lLiteralsToFactoryze)) {
					break;
				}
			}
		}
		return array_keys($lLiteralsToFactoryze);
	}
	
	private static function _setFinalLogicalJunction($pEssentialPrimeImplicants, $pFlattenedLiterals, $pLiteralsToFactoryze, $pLiteralKeys) {
		$lLiteralsToFactoryzeByKey = array();
		$lFirstConjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		if (!empty($pLiteralsToFactoryze)) {
			foreach ($pLiteralsToFactoryze as $pLiteralIndex) {
				$lFirstConjunction->addLiteral($pFlattenedLiterals[$pLiteralKeys[$pLiteralIndex]]);
				$lLiteralsToFactoryzeByKey[$pLiteralIndex] = null;
			}
		}

		$lDisjunction = new LogicalJunction(LogicalJunction::DISJUNCTION);
		$lFirstConjunction->addLogicalJunction($lDisjunction);
		
		foreach ($pEssentialPrimeImplicants as $lEssentialPrimeImplicantValues) {
			$lConjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
			foreach ($lEssentialPrimeImplicantValues as $lIndex => $lValue) {
				// if literal hasn't been factorised
				if (!array_key_exists($lIndex, $lLiteralsToFactoryzeByKey)) {
					if ($lValue === true) {
						$lConjunction->addLiteral($pFlattenedLiterals[$pLiteralKeys[$lIndex]]);
					}else if ($lValue === false) {
						$lLiteral = $pFlattenedLiterals[$pLiteralKeys[$lIndex]];
						$lOppositeLiteral = clone $lLiteral;
						$lOppositeLiteral->reverseOperator();
						$lConjunction->addLiteral($lOppositeLiteral);
					}
				}
		
			}
			$lDisjunction->addLogicalJunction($lConjunction);
		}
		return $lFirstConjunction;
	}
}