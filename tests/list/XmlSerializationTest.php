<?php

use comhon\object\singleton\InstanceModel;

$lTestXmlModel = InstanceModel::getInstance()->getInstanceModel('testXml');
$lTestXml = $lTestXmlModel->loadObject('plop2');

$lTestXml->setValue('name', 'plop4');
$lTestXml->save();
