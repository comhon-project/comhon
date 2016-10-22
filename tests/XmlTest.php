<?php

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/public_html/ObjectManagerLib');

require_once 'ObjectManagerLib.php';

use objectManagerLib\object\singleton\InstanceModel;

$lTestXmlModel = InstanceModel::getInstance()->getInstanceModel('testXml');
$lTestXml = $lTestXmlModel->loadObject('plop2');

$lTestXml->setValue('name', 'plop4');
$lTestXml->save();
