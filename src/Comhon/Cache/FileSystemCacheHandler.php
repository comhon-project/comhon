<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Cache;

class FileSystemCacheHandler extends CacheHandler {

	/** @var string */
	private $directory;
	
	/**
	 * 
	 * @param string $directory apth to directory where cache is stored
	 */
	public function __construct(string $directory) {
		$this->setDirectory($directory);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::hasValue()
	 */
	public function hasValue(string $key) {
		return file_exists($key);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getValue()
	 */
	public function getValue(string $key) {
		return file_get_contents($key);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::setValue()
	 */
	public function setValue(string $key, string $value) {
		if (!file_exists(dirname($key))) {
			mkdir(dirname($key), 0777, true);
		}
		file_put_contents($key, $value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::reset()
	 */
	public function reset() {
		if (file_exists($this->getConfigKey()) && !unlink($this->getConfigKey())) {
			return false;
		}
		if (file_exists($this->getSqlTableModelKey()) && !unlink($this->getSqlTableModelKey())) {
			return false;
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getConfigKey()
	 */
	public function getConfigKey() {
		return $this->directory . DIRECTORY_SEPARATOR . 'config';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getSqlTableModelKey()
	 */
	public function getSqlTableModelKey() {
		return $this->directory . DIRECTORY_SEPARATOR . 'sqlTable_sqlDatabase';
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getDirectory() {
		return $this->directory;
	}
	/**
	 *
	 * @return string
	 */
	public function setDirectory(string $directory) {
		$this->directory = $directory;
	}
	
}