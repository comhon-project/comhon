<?php

use comhon\object\singleton\ModelManager;

$lTestXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$lTestXml = $lTestXmlModel->loadObject('plop2');

$lTestXml->setId('plop4');
$lTestXml->save();
