<?php

use Comhon\Model\Restriction\Regex;
use Comhon\Model\Restriction\Interval;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelDateTime;
use Comhon\Model\ModelInteger;
use Comhon\Model\ModelFloat;
use Comhon\Object\ComhonDateTime;
use Comhon\Model\Restriction\Enum;
use Comhon\Exception\MalformedIntervalException;
use Comhon\Model\ModelString;
use Comhon\Exception\NotSupportedModelIntervalException;
use Comhon\Exception\NotExistingRegexException;

$time_start = microtime(true);

$groupedRegexTests = [
	'color' => [
		'azeBazeAze',
		'#a1F233',
		'#a33',
		'rgb(25,0,12)',
		'rgb(  25  ,  0  ,  12  )',
		'rgba(25%,0.0%,12%,0.45)',
		'rgba(  25%  ,  0.0%  ,  12%  ,  0.45  )',
		'hsl(25,12%,10%)',
		'hsl(  25  ,  12%  ,  10%  )',
		'hsla(250,0%,12.01%,40%)',
		'hsla(  250  ,  0%  ,  12.01%  ,  40% )'
	],
	'hexColor' => [
		'#a1F233',
		'#a33'
	],
	'rgbColor' => [
		'rgb(25,0,12)',
		'rgb(  25  ,  0  ,  12  )'
	],
	'rgbaColor' => [
		'rgba(25%,0.0%,12%,0.45)',
		'rgba(  25%  ,  0.0%  ,  12%  ,  0.45  )'
	],
	'hslColor' => [
		'hsl(25,12%,10%)',
		'hsl(  25  ,  12%  ,  10%  )'
	],
	'hslaColor' => [
		'hsla(250,0%,12.01%,40%)',
		'hsla(  250  ,  0%  ,  12.01%  ,  40% )'
	],
	'email' => [
		'azezae-aze.aze@azeze.com',
		'azezazeaze@azeze.com',
	],
	'url' => [
		'http://foo.com/blah_blah',
		'http://foo.com/blah_blah/',
		'http://foo.com/blah_blah_(wikipedia)',
		'http://foo.com/blah_blah_(wikipedia)_(again)',
		'http://www.example.com/wpstyle/?p=364',
		'https://www.example.com/foo/?bar=baz&inga=42&quux',
		'http://✪df.ws/123',
		'http://userid:password@example.com:8080',
		'http://userid:password@example.com:8080/',
		'http://userid@example.com',
		'http://userid@example.com/',
		'http://userid@example.com:8080',
		'http://userid@example.com:8080/',
		'http://userid:password@example.com',
		'http://userid:password@example.com/',
		'http://142.42.1.1/',
		'http://142.42.1.1:8080/',
		'http://➡.ws/䨹',
		'http://⌘.ws',
		'http://⌘.ws/',
		'http://foo.com/blah_(wikipedia)#cite-1',
		'http://foo.com/blah_(wikipedia)_blah#cite-1',
		'http://foo.com/unicode_(✪)_in_parens',
		'http://foo.com/(something)?after=parens',
		'http://☺.damowmow.com/',
		'http://code.google.com/events/#&product=browser',
		'http://j.mp',
		'ftp://foo.bar/baz',
		'http://foo.bar/?q=Test%20URL-encoded%20stuff',
		'http://مثال.إختبار',
		'http://例子.测试',
		'http://उदाहरण.परीक्षा',
		'http://-.~_!$&\'()*+,;=:%40:80%2f::::::@example.com',
		'http://1337.net',
		'http://a.b-c.de',
		'http://223.255.255.254'
	],
	'userName' => [
		'aze-AZd_aze'
	],
	Interval::INTEGER_INTERVAL => [
		'[0,1]',
		'[ 0 , 41 [',
		'], 155]',
		']0 ,  [',
		'],[',
		'[-4,1]',
		'[ -10 , -1]'
	],
	Interval::FLOAT_INTERVAL => [
		'[0,1]',
		'[ 0.15 , 17 [',
		'], 1.49]',
		']0 ,  [',
		'[-4.5,1]',
		'[ -10 , -1.45]'
	],
	Interval::DATETIME_INTERVAL => [
		// there's no verification on date format only interval structure is checked
		'[0 45 Taze , -1aze zeeee]',
		'[0 45 Taze , -1aze zeeee[',
	]
];


$groupedMalformedRegexTests = [
	'color' => [
		'azeBaze1Aze',
		'azeBazeAze ',
		' azeBazeAze',
		'#a1b233 ',
		'#a1b23r',
		'#ea33',
		'rgb(256,0,12)',
		'rgb254,0,12)',
		'rgb(254,0,12',
		'rgb(25,0,12,1)',
		'rgb(25 0,12)',
		'rgb(25,0 12)',
		'rgba(10%,0.0%,12%,0.45',
		'rgba(10%,0.0%,12%,.45)',
		'rgba(10%,0.0%,.12%,0.45)',
		'rgba10%,0.0%,12%,0.45)',
		'rgba(100%,0.0%,12%0.45)',
		'rgba(100%,0.0%,12% 0.45)',
		'rgba(110%,0.0%,12%,0.45)',
		'rgba(25%,0.0%,12%, 45)',
		'rgba(25%,0.0%,12%)',
		'hsl(36,12%,10%',
		'hsl36,12%,10%)',
		'hsl(361,12%,10%)',
		'hsl(2500,12%,10%)',
		'hsl(2500,12%,10)',
		'hsla(250,0%,12.01%,40%',
		'hsla250,0%,12.01%,40%)',
		'hsla(450,0%,12.01%,40%)',
		'hsla(250,0%,12.01%,1.40)',
		'hsla(250,0%,12.01%)'
	],
	'hexColor' => [
		'#a1b233 ',
		'#a1b23r',
		'#ea33'
	],
	'rgbColor' => [
		'rgb(256,0,12)',
		'rgb254,0,12)',
		'rgb(254,0,12',
		'rgb(25,0,12,1)',
		'rgb(25 0,12)',
		'rgb(25,0 12)'
	],
	'rgbaColor' => [
		'rgba(10%,0.0%,12%,0.45',
		'rgba10%,0.0%,12%,0.45)',
		'rgba(100%,0.0%,12%0.45)',
		'rgba(100%,0.0%,12% 0.45)',
		'rgba(110%,0.0%,12%,0.45)',
		'rgba(25%,0.0%,12%, 45)',
		'rgba(25%,0.0%,12%)'
	],
	'hslColor' => [
		'hsl(36,12%,10%',
		'hsl36,12%,10%)',
		'hsl(361,12%,10%)',
		'hsl(2500,12%,10%)',
		'hsl(2500,12%,10)'
	],
	'hslaColor' => [
		'hsla(250,0%,12.01%,40%',
		'hsla250,0%,12.01%,40%)',
		'hsla(450,0%,12.01%,40%)',
		'hsla(250,0%,12.01%,1.40)',
		'hsla(250,0%,12.01%)'
	],
	'email' => [
		'azezae-aze.aze@azeze',
		'azezazeazeazeze.com',
	],
	'url' => [
		'http://',
		'http://.',
		'http://..',
		'http://../',
		'http://?',
		'http://??',
		'http://??/',
		'http://#',
		'http://##',
		'http://##/',
		'http://foo.bar?q=Spaces should be encoded',
		'//',
		'//a',
		'///a',
		'///',
		'http:///a',
		'foo.com',
		'rdar://1234',
		'h://test',
		'http:// shouldfail.com',
		':// should fail',
		'http://foo.bar/foo(bar)baz quux',
		'ftps://foo.bar/',
		'http://-error-.invalid/',
		'http://a.b--c.de/',
		'http://-a.b.co',
		'http://a.b-.co',
		'http://0.0.0.0',
		'http://10.1.1.0',
		'http://10.1.1.255',
		'http://224.1.1.1',
		'http://1.1.1.1.1',
		'http://123.123.123',
		'http://3628126748',
		'http://.www.foo.bar/',
		'http://www.foo.bar./',
		'http://.www.foo.bar./',
		'http://10.1.1.1',
		'http://10.1.1.254',
	],
	'userName' => [
		'aze-AZd _aze',
		'aze-AZd:_aze',
		'a',
		'aze-AZd_azee-AZd_azee-AZd_aze'
	],
	Interval::INTEGER_INTERVAL => [
		'[0 1]',
		'[ 0 , 41 ',
		'1 , 155]',
		']0 , ,1 [',
		']0 , 1.45 [',
		'] - , 1 [',
		']0 , - [',
		']e,[',
		'[,e]'
	],
	Interval::FLOAT_INTERVAL => [
			'[0 1]',
			'[ 0 , 41 ',
			'1 , 155]',
			']0 , ,1 [',
			']0 , .45 [',
			']0 , . [',
			']0 , -.01 [',
			'] - , 1 [',
			']0 , - [',
			']e,[',
			'[,e]'
	],
	Interval::DATETIME_INTERVAL => [
		// there's no verification on date format only interval structure is checked
		'[0 1]',
		'[ 0 , 41 ',
		'1 , 155]',
		']0 , ,1 [',
	]
];

foreach ($groupedRegexTests as $pattern => $regexTests) {
	if (substr($pattern, 0, 1) == '/') { // pattern
		foreach ($regexTests as $regexTest) {
			if (preg_match($pattern, $regexTest) !== 1) {
				throw new \Exception("$pattern '$regexTest' should be valid");
			}
		}
	} else { // name of a pattern
		$regex = new Regex($pattern);
		foreach ($regexTests as $regexTest) {
			if ($regex->satisfy($regexTest) !== true) {
				throw new \Exception("$pattern '$regexTest' should be valid");
			}
		}
	}
}

foreach ($groupedMalformedRegexTests as $pattern => $regexTests) {
	if (substr($pattern, 0, 1) == '/') { // pattern
		foreach ($regexTests as $regexTest) {
			if (preg_match($pattern, $regexTest) !== 0) {
				throw new \Exception("$pattern '$regexTest' should be not valid");
			}
		}
	} else { // name of a pattern
		$regex = new Regex($pattern);
		foreach ($regexTests as $regexTest) {
			if ($regex->satisfy($regexTest) !== false) {
				throw new \Exception("$pattern '$regexTest' should be not valid");
			}
		}
	}
}

/** ********************** integer interval *********************** **/

$modelInteger = ModelManager::getInstance()->getInstanceModel(ModelInteger::ID);
$throw = true;
try {
	$interval = new Interval('] 1 ,-1 [', $modelInteger);
} catch (MalformedIntervalException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should throw exception (left > right)');
}
$interval = new Interval('] -2 ,15 [', $modelInteger);
if (
	!$interval->satisfy(13) 
	|| !$interval->satisfy(-1)
	|| $interval->satisfy(-2)
	|| $interval->satisfy(15)
	|| $interval->satisfy(-101)
	|| $interval->satisfy(101)
) {
	throw new \Exception('unexpected statisfaction');
}

/** ********************** float interval *********************** **/

$modelFloat = ModelManager::getInstance()->getInstanceModel(ModelFloat::ID);
$throw = true;
try {
	$interval = new Interval('] 1.12 ,-1.45 [', $modelFloat);
} catch (MalformedIntervalException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should throw exception (left > right)');
}
$interval = new Interval('[ -2.12 ,15 ]', $modelFloat);
if (
	!$interval->satisfy(13.45)
	|| !$interval->satisfy(-1)
	|| !$interval->satisfy(-2.12)
	|| !$interval->satisfy(15)
	|| $interval->satisfy(-101)
	|| $interval->satisfy(101.24)
) {
	throw new \Exception('unexpected statisfaction');
}

/** ********************** datetime interval *********************** **/

$modelDateTime = ModelManager::getInstance()->getInstanceModel(ModelDateTime::ID);
$throw = true;
try {
	$interval = new Interval('] 2017-05-01 12:53:54 ,2016-05-01 12:53:54 [', $modelDateTime);
} catch (MalformedIntervalException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should throw exception (left > right)');
}
$interval = new Interval('[ 2016-05-01 12:53:54 ,2017-05-01 12:53:54 [', $modelDateTime);
if (
	!$interval->satisfy(new ComhonDateTime('2016-10-01 12:53:54'))
	|| !$interval->satisfy(new ComhonDateTime('2016-05-01 12:53:54'))
	|| $interval->satisfy(new ComhonDateTime('2017-05-01 12:53:54'))
	|| $interval->satisfy(new ComhonDateTime('2010-05-01 12:53:54'))
	|| $interval->satisfy(new ComhonDateTime('2020-05-01 12:53:54'))
) {
	throw new \Exception('unexpected statisfaction');
}

/** ********************** enum *********************** **/

$enum = new Enum([0, 45.45 , 753, 'aezaze']);

if (
	!$enum->satisfy(45.4500)
	|| !$enum->satisfy('45.45')
	|| !$enum->satisfy(0)
	|| !$enum->satisfy(753)
	|| !$enum->satisfy('753')
	|| !$enum->satisfy('aezaze')
	|| $enum->satisfy('2020-05-01 12:53:54')
	|| $enum->satisfy(789)
	|| $enum->satisfy(0.78)
) {
	throw new \Exception('unexpected statisfaction');
}

/** *********************** other malformed restriction ***************************** **/

$modelString = ModelManager::getInstance()->getInstanceModel(ModelString::ID);
$throw = true;
try {
	$interval = new Interval('] 2017-05-01 12:53:54 ,2016-05-01 12:53:54 [', $modelString);
} catch (NotSupportedModelIntervalException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should throw exception (left > right)');
}

$throw = true;
try {
	$regex = new Regex('bad_name');
} catch (NotExistingRegexException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should throw exception (left > right)');
}

$time_end = microtime(true);
var_dump('restriction test exec time '.($time_end - $time_start));
