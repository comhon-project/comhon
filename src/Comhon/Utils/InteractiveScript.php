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

abstract class InteractiveScript {
	
	/**
	 *
	 * @var string
	 */
	private $isInteractive = true;
	
	/**
	 * 
	 * @param boolean $isInteractive if true, messages or questions are displayed.
	 *                               ortherwise, messages are not displayed and default response is chosen for questions.
	 *                               if a question doesn't have default response and script is not in interactive mode,
	 *                               an exception is thrown
	 */
	public function __construct($isInteractive) {
		$this->isInteractive = $isInteractive;
	}
	
	/**
	 * in interactive mode, ask question to user. otherwise default response is chosen.
	 * if a question doesn't have default response and script is not in interactive mode, an exception is thrown.
	 * 
	 * @see \Comhon\Utils\Cli::ask()
	 */
	protected function ask($question, $default = null, $filter = null, $filterType = Cli::FILTER_VALUE) {
		if ($this->isInteractive) {
			return Cli::ask($question, $default, $filter, $filterType);
		} elseif (!is_null($default)) {
			return $default;
		}
		throw new \Exception('interactive mode is desactivated and question doesn\'t have default response');
	}
	
	/**
	 * in interactive mode, display message
	 *
	 * @param string $message
	 * @param boolean $addEndOfLine
	 */
	protected function displayMessage($message, $addEndOfLine = true) {
		if ($this->isInteractive) {
			fwrite(Cli::$STDOUT, $message);
			if ($addEndOfLine) {
				fwrite(Cli::$STDOUT, PHP_EOL);
			}
		}
	}
	
	/**
	 * in interactive mode, ask to user if script execution must continue.
	 * if user answer no, an exception is thrown with provided error message.
	 * 
	 * @param string $errorMessage error description.
	 *                             example : "file is not found"
	 * @param string $stopOrContinueMessage warn user from what will happen if he continue script execution.
	 *                             example : "do you want to stop or continue without file ?"
	 * @param string $continueMessage confirm what will happen when continue.
	 *                             example : "file is ignored"
	 */
	protected function displayContinue($errorMessage, $stopOrContinueMessage = null, $continueMessage = null) {
		$question = "\033[0;31m{$errorMessage}\033[0m".PHP_EOL
			.(empty($stopOrContinueMessage) ? '' : ($stopOrContinueMessage.PHP_EOL))
			."Would you like to continue ?";
		$response = $this->ask($question, 'no', ['yes', 'no']);
		
		if ($response === 'no') {
			throw new \Exception($errorMessage);
		} else {
			$this->displayMessage("\033[1;30m{$continueMessage}\033[0m".PHP_EOL);
		}
	}
    
}
