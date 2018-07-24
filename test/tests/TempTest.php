<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Comhon\Exception\NotDefinedModelException;

Config::setLoadPath(__DIR__ . '/../config/config-json-pgsql.json');

var_dump(ModelManager::getInstance()->getInstanceModel('Comhon\Config')->getName());
var_dump('---------------');
ModelManager::getInstance()->getInstanceModel('Comhon\XmlFile');
ModelManager::getInstance()->getInstanceModel('Test\Person');

die();