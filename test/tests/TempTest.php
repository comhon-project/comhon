<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Comhon\Exception\NotDefinedModelException;

Config::setLoadPath(__DIR__ . '/../config/config-json-pgsql.json');

echo ModelManager::getInstance()->getInstanceModel('Comhon\Config');

die();