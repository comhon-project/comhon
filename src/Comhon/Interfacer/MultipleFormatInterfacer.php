<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Interfacer;

use Comhon\Exception\ArgumentException;

abstract class MultipleFormatInterfacer extends Interfacer {
	
	/** @var string */
	protected $format;
	
	/**
	 * media type indexed by format name
	 * 
	 * @var array
	 */
	const ALLOWED_FORMATS = [
		'yaml' => 'application/x-yaml',
		'json' => 'application/json' 
		
	];
	
	/**
	 * 
	 * @param string $format format to use when load value from string (or file)
	 *                       and when transform object to string (or when save it into a file).
	 *                       format must belong to self::ALLOWED_FORMATS. it may be either a format or media type.
	 */
	final public function __construct($format = 'json') {
		if (array_key_exists($format, self::ALLOWED_FORMATS)) {
			$this->format = $format;
		} elseif (($key = array_search($format, self::ALLOWED_FORMATS)) !== false) {
			$this->format = $key;
		} else {
			$enum = array_merge(array_keys(self::ALLOWED_FORMATS), array_values(self::ALLOWED_FORMATS));
			throw new ArgumentException($format, 'string', 1, $enum);
		}
		$this->setDateTimeZone(new \DateTimeZone(date_default_timezone_get()));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getMediaType()
	 */
	public function getMediaType() {
		return self::ALLOWED_FORMATS[$this->format];
	}
	
	/**
	 * write file with given content
	 *
	 * @param \stdClass $node
	 * @param string $path
	 * @param bool $prettyPrint
	 * @return boolean
	 */
	public function write($node, $path, $prettyPrint = false) {
		return file_put_contents($path, $this->toString($node, $prettyPrint)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 *
	 * @param string $path
	 * @return array|null return null on failure
	 */
	public function read($path) {
		$json = file_get_contents($path);
		return $json ? $this->fromString($json) : null;
	}
	
}
