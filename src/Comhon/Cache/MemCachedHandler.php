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

class MemCachedHandler extends CacheHandler {

	/**
	 * 
	 * @var \Memcached
	 */
	private $memcached;
	
	/**
	 * 
	 * @param string $server string that contain "host" and "port" server informations separated by ";"
	 *                       example : host=127.0.0.1;port=11211
	 *                       this parameter is optional and options on memcached instance may be applied 
	 *                       by getting memcached object with method getMemCachedObject().
	 *                           
	 */
	public function __construct(string $server = null) {
		$this->memcached = new \Memcached();
		if (!empty($server)) {
			$expoded = explode(';', $server);
			if (count($expoded) !== 2) {
				throw new CacheException("invalid memcached server '$server', must look like 'host=127.0.0.1;port=11211'");
			}
			$finalInfos = [];
			foreach ($expoded as $info) {
				$expodedInfo = explode('=', $info);
				if (count($expodedInfo) !== 2) {
					throw new CacheException("invalid memcached server '$server', must look like 'host=127.0.0.1;port=11211'");
				}
				if ($expodedInfo[0] == 'host') {
					$finalInfos['host'] = $expodedInfo[1];
				} elseif ($expodedInfo[0] == 'port') {
					$finalInfos['port'] = $expodedInfo[1];
				} else {
					throw new CacheException("invalid memcached server '$server', must look like 'host=127.0.0.1;port=11211'");
				}
			}
			if (count($finalInfos) !== 2) {
				throw new CacheException("invalid memcached server '$server', must look like 'host=127.0.0.1;port=11211'");
			}
			$this->memcached->addServer($finalInfos['host'], $finalInfos['port']);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::hasValue()
	 */
	public function hasValue(string $key) {
		$this->memcached->get($key);
		
		return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::getValue()
	 */
	public function getValue(string $key) {
		$value = $this->memcached->get($key);
		return $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND
			? null : $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::registerValue()
	 */
	public function registerValue(string $key, string $value) {
		return $this->memcached->set($key, $value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Cache\CacheHandler::reset()
	 * Warning!!! this method invalid all cached values
	 */
	public function reset() {
		return $this->memcached->flush();
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
		return 'model-';
	}
	
	/**
	 * 
	 * @return \Memcached
	 */
	public function getMemCachedObject() {
		return $this->memcached;
	}
	
}