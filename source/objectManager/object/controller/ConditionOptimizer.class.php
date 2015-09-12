<?php

use GenLib\Utils\Utils;
abstract class ConditionOptimizer {
	
	/**
	 * transform linkedConditions in $pLinkedConditions to Conditions if it's possible
	 * @param LinkedConditions $pLinkedConditions
	 */
	public static function linkedConditionsToConditions($pLinkedConditions) {
		$lNewLinkedConditions = new LinkedConditions($pLinkedConditions->getLink());
		self::_linkedConditionsToConditions($lNewLinkedConditions, $pLinkedConditions);
		return $lNewLinkedConditions;
	}
	
	/**
	 * transform linkedConditions to Conditions if it's possible
	 * @param LinkedConditions $pNewLinkedConditions
	 * @param LinkedConditions $pLinkedConditions
	 */
	private static function _linkedConditionsToConditions($pNewLinkedConditions, $pLinkedConditions) {
		$lLink = $pLinkedConditions->getLink();
		foreach ($pLinkedConditions->getConditions() as $lCondition) {
			$pNewLinkedConditions->addCondition($lCondition);
		}
		foreach ($pLinkedConditions->getLinkedConditions() as $lLinkedConditions) {
			if ($lLinkedConditions->hasOnlyOneCondition() || ($lLinkedConditions->getLink() == $lLink)) {
				self::_linkedConditionsToConditions($pNewLinkedConditions, $lLinkedConditions);
			}else {
				$pNewLinkedConditions->addLinkedConditions(self::linkedConditionsToConditions($lLinkedConditions));
			}
		}
	}
	
	/**
	 * optimize query conditions to optimize execution time of query
	 * 
	 * @param unknown $pLinkedConditions
	 */
	public static function optimizeConditions($pLinkedConditions) {
		$lFlattenedConditions = $pLinkedConditions->getFlattenedConditions("md5");
		$lLiteralKeys = array();
		foreach ($lFlattenedConditions as $lKey => $lCondition) {
			$lLiteralKeys[] = $lKey;
		
		}
		$pLinkedConditions = ConditionOptimizer::linkedConditionsToConditions($pLinkedConditions);
		//trigger_error(var_export($pLinkedConditions->export(), true));
		$lLogicalConjunctions = self::_setLogicalConjunctions($pLinkedConditions, $lFlattenedConditions, $lLiteralKeys);
		$lEssentialPrimeImplicants = self::_execQuineMcCluskeyAlgorithm($lLogicalConjunctions);
		$lLiteralsToFactoryze = self::_findLiteralsToFactoryze($lEssentialPrimeImplicants);
		$lLinkedConditions = self::_setFinalLinkedConditions($lEssentialPrimeImplicants, $lFlattenedConditions, $lLiteralsToFactoryze, $lLiteralKeys);
		//trigger_error(var_export($lLinkedConditions->export(), true));
		return $lLinkedConditions;
	}
	
	private static function _setLogicalConjunctions($pLinkedConditions, $pFlattenedConditions, $pLiteralKeys) {
		$lLiteralValues = array();
		$lLiterals = array();
		$lLogicalConjunctions = array();
		foreach ($pFlattenedConditions as $lKey => $lCondition) {
			$lLiteralValues[] = false;
			$lLiterals[$lKey] = false;
				
		}
		$lNbTrueValues = 0;
		$i = count($pFlattenedConditions) - 1;
		while ($i > -1) {
			if ($lLiteralValues[$i] === false) {
				$lLiteralValues[$i] = true;
				$lLiterals[$pLiteralKeys[$i]] = true;
				$lNbTrueValues++;
				for ($j = $i + 1; $j < count($pFlattenedConditions); $j++) {
					$lLiteralValues[$j] = false;
					$lLiterals[$pLiteralKeys[$j]] = false;
					$lNbTrueValues--;
				}
				$i = count($pFlattenedConditions) - 1;
				$lSatisfied = $pLinkedConditions->isSatisfied($lLiterals);
		
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

		if (count($lNewLogicalConjunctions) > 0) {
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
				$lAllConjunctionsMatches = Utils::array_merge_extended($lAllConjunctionsMatches, $lConjunctionsMatches);
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
		usort($lMatrix, array("ConditionOptimizer", "sortByLastValue"));
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
		if (count($pEssentialPrimeImplicants) > 0) {
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
				if (count($lLiteralsToFactoryze) == 0) {
					break;
				}
			}
		}
		return array_keys($lLiteralsToFactoryze);
	}
	
	private static function _setFinalLinkedConditions($pEssentialPrimeImplicants, $pFlattenedConditions, $pLiteralsToFactoryze, $pLiteralKeys) {
		$lLiteralsToFactoryzeByKey = array();
		$lFirstConjunction = new LinkedConditions("and");
		if (count($pLiteralsToFactoryze) > 0) {
			foreach ($pLiteralsToFactoryze as $pLiteralIndex) {
				$lFirstConjunction->addCondition($pFlattenedConditions[$pLiteralKeys[$pLiteralIndex]]);
				$lLiteralsToFactoryzeByKey[$pLiteralIndex] = null;
			}
		}

		$lDisjunction = new LinkedConditions("or");
		$lFirstConjunction->addLinkedConditions($lDisjunction);
		
		foreach ($pEssentialPrimeImplicants as $lEssentialPrimeImplicantValues) {
			$lConjunction = new LinkedConditions("and");
			foreach ($lEssentialPrimeImplicantValues as $lIndex => $lValue) {
				// if literal hasn't been factorised
				if (!array_key_exists($lIndex, $lLiteralsToFactoryzeByKey)) {
					if ($lValue === true) {
						$lConjunction->addCondition($pFlattenedConditions[$pLiteralKeys[$lIndex]]);
					}else if ($lValue === false) {
						$lCondition = $pFlattenedConditions[$pLiteralKeys[$lIndex]];
						$lOppositeCondition = new Condition($lCondition->getModel()->getModelName(), $lCondition->getPropertyName(), Condition::$sOppositeConditions[$lCondition->getOperator()], $lCondition->getValue());
						$lConjunction->addCondition($lOppositeCondition);
					}
				}
		
			}
			$lDisjunction->addLinkedConditions($lConjunction);
		}
		return $lFirstConjunction;
	}
}