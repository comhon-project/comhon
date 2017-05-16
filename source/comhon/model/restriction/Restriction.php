<?php
namespace comhon\model\restriction;

interface Restriction {
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function satisfy($pValue);
	
}