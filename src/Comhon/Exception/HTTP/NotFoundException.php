<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\HTTP;

use Comhon\Model\Model;

class NotFoundException extends ResponseException {
	
	public function __construct(Model $model, $id) {
		parent::__construct(404, "resource '{$model->getName()}' with id '$id' not found");
	}
}
