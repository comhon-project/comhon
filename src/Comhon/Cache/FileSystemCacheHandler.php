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

use Comhon\Exception\Cache\CacheException;
use Comhon\Utils\Utils;

class FileSystemCacheHandler extends CacheHandler {

	/** @var string */
	private $directory;
	
	/**
	 * 
	 * @param string $directory apth to directory where cache is stored
	 */
	public function __construct(string $directory) {
		if (!file_exists($directory)) {
			if (!mkdir($directory, 0777, true)) {
				throw new CacheException("failure when trying to create cache directory '$directory'");
			}
		}
		$this->directory = realpath($directory);
		if ($this->directory === false) {
			throw new CacheException("failure when trying to get realpath of cache directory '$directory'");
		}
	}
	
	/**
	 * transform key to path and verify if path is actually in cache directory and not outside 
	 * (may be outside cache directory if key contains some "..")
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getPath(string $key) {
		if (strpos($key, '..') !== false) {
			$path = explode(DIRECTORY_SEPARATOR, $this->directory . DIRECTORY_SEPARATOR . $key);
			$stack = [];
			foreach ($path as $seg) {
				if ($seg == '..') {
					array_pop($stack);
					continue;
				}
				if ($seg == '.') {
					continue;
				}
				$stack[] = $seg;
			}
			
			$path = implode('/', $stack);
			if (strpos($path, $this->directory) !== 0) {
				throw new CacheException("invalid key '$key', path is outside cache directory");
			}
		} else {
			$path = $this->directory . DIRECTORY_SEPARATOR . $key;
		}
		return $path;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::hasValue()
	 */
	public function hasValue(string $key) {
		return file_exists($this->getPath($key));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getValue()
	 */
	public function getValue(string $key) {
		$path = $this->getPath($key);
		if (file_exists($path)) {
			$content = file_get_contents($path);
			return $content === false ? null : $content;
		}
		return null;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::registerValue()
	 */
	public function registerValue(string $key, string $value) {
		$path = $this->getPath($key);
		if (!file_exists(dirname($path))) {
			mkdir(dirname($path), 0777, true);
		}
		if (file_put_contents($path, $value) === false) {
			throw new CacheException("write cache file $key failed (verify folder rights)");
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::reset()
	 */
	public function reset() {
		$configPath = $this->getPath($this->getConfigKey());
		if (file_exists($configPath) && !unlink($configPath)) {
			return false;
		}
		$modelsPath = $this->getPath($this->getModelPrefixKey());
		if (file_exists($modelsPath) && !Utils::deleteDirectory($modelsPath)) {
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
		return 'config';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getModelPrefixKey()
	 */
	public function getModelPrefixKey() {
		return 'model/';
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getDirectory() {
		return $this->directory;
	}
	
}