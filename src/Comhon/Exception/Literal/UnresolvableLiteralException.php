<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Literal;

use Comhon\Model\Model;
use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class UnresolvableLiteralException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Model $model
	 */
	public function __construct(Model $model) {
		$message = 'Cannot resolve literal with model \''.$model->getName().'\', it might be applied on several properties';
		parent::__construct($message, ConstantException::UNRESOLVABLE_LITERAL_EXCEPTION);
	}
	
}