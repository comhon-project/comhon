<?php
namespace comhon\interfacer;

interface NoScalarTypedInterfacer{
	
	/**
	 * 
	 * @param mixed $pValue
	 */
	public function castValueToString($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToInteger($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToFloat($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToBoolean($pValue);
	
}
