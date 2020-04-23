<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

class Cli {
	
	const FILTER_VALUE = 'value';
	const FILTER_KEY = 'key';
	const FILTER_MULTI = 'multi';
	const FILTER_REGEX = 'regex';
	
	public static $STDIN = STDIN;
	public static $STDOUT = STDOUT;
	
	/**
	 * ask a question to terminal user
	 * 
	 * @param string $question the question to ask
	 * @param string $default default response if user specify nothing 
	 *               - if $filter is specified and $filterType set to Cli::FILTER_KEY, 
	 *                 $default must be a key of given response array
	 *               - if $filter is specified and $filterType set to Cli::FILTER_MULTI, 
	 *                 $default must be an array and each value must be a key of given response array
	 * @param string[] $filter the possible responses for closed questions 
	 *                 (may be an array of values or an array of regular expressions)
	 * @param string $filterType if $filter is given, define filter type that must be applied
	 *               - Cli::FILTER_VALUE if user must type a value specified in given filter list (default)
	 *               - Cli::FILTER_KEY if user must type a key specified in given filter list
	 *               - Cli::FILTER_MULTI if user must type at least one key specified in given filter list
	 *               - Cli::FILTER_REGEX if user must type a value that must match with 
	 *                 at least one of regular expressions specified in given filter list
	 * @return string the response given
	 */
	public static function ask($question, $default = null, $filter = null, $filterType = self::FILTER_VALUE) {
		$choicesAndDefault = '';
		
		if (!is_string($question) || $question == '') {
			throw new \Exception("question must be not empty string");
		}
		if (!is_null($filter)) {
			if (empty($filter)) {
				throw new \Exception("filter must be null or not empty array");
			}
			foreach ($filter as $value) {
				if (!is_string($value)) {
					throw new \Exception('provided filter responses must be an array of strings');
				}
			}
			if ($filterType === self::FILTER_VALUE) {
				$choicesAndDefault = self::_getFlatSimpleChoices($filter, $default);
			} elseif ($filterType === self::FILTER_KEY) {
				$choicesAndDefault = self::_getFlatIndexedChoices($filter, $default);
			} elseif ($filterType === self::FILTER_MULTI) {
				$choicesAndDefault = self::_getFlatMultiChoices($filter, $default);
			}elseif ($filterType === self::FILTER_REGEX) {
				$choicesAndDefault = self::_getFlatRegexChoices($filter, $default);
			}else {
				throw new \Exception("invalid filter type '$filterType'");
			}
		} elseif (!is_null($default)) {
			if (!is_string($default)) {
				throw new \Exception('without filter, provided default response must be a string');
			}
			$choicesAndDefault = "(default : $default)";
		}
		$instruction = $question.' '.$choicesAndDefault.PHP_EOL.'> ';
		
		// first try
		fwrite(self::$STDOUT, $instruction);
		$response = self::_getResponse($filterType);
		if (self::_isAcceptableResponse($response, $default, $filter, $filterType)) {
			return $response == '' ? $default : $response;
		}
		
		// try again
		$instruction = "\033[41mInvalid response\033[0m".PHP_EOL.$instruction;
		do {
			fwrite(self::$STDOUT, $instruction);
			$response = self::_getResponse($filterType);
		} while (!self::_isAcceptableResponse($response, $default, $filter, $filterType));
		
		return $response == '' ? $default : $response;
	}
	
	/**
	 * get flat choises
	 *
	 * @param string[] $filter the possible responses for closed questions
	 * @param string $default default response if user specify nothing
	 * @return string
	 */
	private static function _getFlatSimpleChoices($filter, $default) {
		if (!is_null($default)) {
			if (!is_string($default)) {
				throw new \Exception('with filter Cli::FILTER_VALUE, provided default response must be a string');
			}
			$key = array_search($default, $filter);
			if ($key === false) {
				throw new \Exception("default response '$default' not found in responses array");
			}
			$filter[$key] = "({$filter[$key]})";
		}
		return '['.implode('/', $filter).']';
	}
	
	/**
	 * get flat indexed choises 
	 *
	 * @param string[] $filter the possible responses for closed questions
	 * @param string $default default response if user specify nothing
	 * @return string
	 */
	private static function _getFlatIndexedChoices($filter, $default) {
		$flatChoices = '';
		if (!is_null($default)) {
			if (!is_string($default) && !is_int($default)) {
				throw new \Exception('with filter Cli::FILTER_KEY, provided default response must be a string or an integer');
			}
			if (!array_key_exists($default, $filter)) {
				throw new \Exception("default response key '$default' not found in responses array");
			}
		}
		foreach ($filter as $key => $value) {
			$printKey = !is_null($default) && $key == $default ? "($key)" : $key;
			$flatChoices .= PHP_EOL.'  '.$printKey.': '.$value;
		}
		return $flatChoices;
	}
	
	
	
	/**
	 * get flat multiple choises
	 *
	 * @param string[] $filter the possible responses for closed questions
	 * @param string $default default response if user specify nothing
	 * @return string
	 */
	private static function _getFlatMultiChoices($filter, $default) {
		$flatChoices = PHP_EOL.'you can type several responses in one time by separating them with commas.';
		if (!is_null($default)) {
			if (!is_array($default) || empty($default)) {
				throw new \Exception("with filter Cli::FILTER_MULTI, provided default response must be a not empty array");
			}
			foreach ($default as $value) {
				if (!is_string($value) && !is_int($value)) {
					throw new \Exception('with filter Cli::FILTER_MULTI, provided default response must be an array of strings or integers');
				}
				if (!array_key_exists($value, $filter)) {
					throw new \Exception("default response key '$value' not found in responses array");
				}
			}
		}
		foreach ($filter as $key => $value) {
			$printKey = !is_null($default) && in_array($key, $default) ? "($key)" : $key;
			$flatChoices .= PHP_EOL.'  '.$printKey.': '.$value;
		}
		return $flatChoices;
	}
	
	/**
	 * get flat regural expressions choices
	 *
	 * @param string[] $filter the possible responses for closed questions
	 * @param string $default default response if user specify nothing
	 * @return string
	 */
	private static function _getFlatRegexChoices($filter, $default) {
		if (!is_null($default) && !is_string($default)) {
			throw new \Exception('with filter Cli::FILTER_REGEX, provided default response must be a string');
		}
		return (is_null($default) ? '' : "(default : $default)").PHP_EOL
			.'response must match with at least one of following regular expressions : '.PHP_EOL.' - '
			.implode(PHP_EOL.' - ', $filter);
	}
	
	/**
	 * is acceptable response
	 *
	 * @param string $response the user response
	 * @param string $default default response if user specify nothing
	 * @param string[] $filter the possible responses for closed questions
	 * @param boolean $filterType if $filter is given, define filter type that must be applied
	 * @return boolean
	 */
	private static function _isAcceptableResponse($response, $default, $filter, $filterType) {
		return (is_null($filter) && $response !== '')
			|| (!is_null($filter) 
				&& (
					($filterType == self::FILTER_VALUE && in_array($response, $filter))
					|| ($filterType == self::FILTER_KEY && array_key_exists($response, $filter))
					|| ($filterType == self::FILTER_MULTI && self::_isValidMultiResponse($response, $filter))
					|| ($filterType == self::FILTER_REGEX && self::_isSatisfiedRegexs($response, $filter))
				)
			)
			|| ($response === '' && !is_null($default));
	}
	
	/**
	 * get the response from user
	 *
	 * @param string $filterType define filter type that must be applied
	 * @return string|string[] 
	 */
	private static function _getResponse($filterType = null) {
		$response = trim(fgets(self::$STDIN));
		if ($filterType != self::FILTER_MULTI || $response == '') {
			return $response;
		}
		$responses = explode(',', $response);
		foreach ($responses as &$element) {
			$element = trim($element);
		}
		return array_values(array_unique($responses));
	}
	
	/**
	 * verify if at least there is a match with one of given regular expressions
	 *
	 * @param string $response the user response
	 * @param string[] $regexs the possible responses for closed questions
	 * @return boolean
	 */
	private static function _isSatisfiedRegexs($response, $regexs) {
		foreach ($regexs as $regex) {
			if (preg_match($regex, $response) === 1) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * verify if multiple responses are all valid
	 *
	 * @param string[] $responses the user multiple responses (might be an empty string for empty response)
	 * @param string[] $filter the possible responses for closed questions
	 * @return boolean
	 */
	private static function _isValidMultiResponse($responses, $filter) {
		if (empty($responses)) {
			return false;
		}
		foreach ($responses as $response) {
			if (!array_key_exists($response, $filter)) {
				return false;
			}
		}
		return true;
	}
}
