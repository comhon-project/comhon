# Comhon!
(under building, there's no release for the moment but you can use master branch)
## Synopsis

Comhon! (Common Object Manager, Hashmap Or Nothing!) is an object manager based on gess what ?... hashmap!

It will allow you to import, export, serialize (in sql database, xml file, json file) objects without any line of code.
You just have to describe your model in a manifest (xml file or soon json file) that will match with an sql table for exemple and you will be able to Select/Insert/Update without write your sql query (actually like an ORM)

You don't have to create any classes (actually you can if you're not comfortable without them) :
* your object will be represent by an hashmap :```['first_name' => 'john', 'last_name' => 'john', 'age' => 21]```
* an object has a related model that is an hashmap of properties that describe allowed values and their types `['first_name' => 'string', 'last_name' => 'string', 'age' => 'integer']`

(properties map is not a simple map of string but to simplify you don't need to know more)

## Some others features
* advanced object managment ([see wiki page] (https://github.com/jeanphilippe-p/ObjectManagerLib/wiki/Object-management))
* provide API to request sql database and build automatically complexe sql requests without knowing sql syntaxe ([see wiki page] (https://github.com/jeanphilippe-p/ObjectManagerLib/wiki/Objects-request-api))

## Manifest Example

The list of all your manifests must be referenced in an XML file. The path to this file must be defined in the file `config.json` with the key `manifestList` (see Installation chapter for more informations).
```XML
<?xml version="1.0" encoding="UTF-8"?>
<list>
	<manifest type="person">relative/path/to/person/manifest.xml</manifest>
	<manifest type="house">relative/path/to/house/manifest.xml</manifest>
	<manifest type="town">relative/path/to/town/manifest.xml</manifest>
</list>
```

simple manifest to describe a person
```XML
<manifest>
	<properties>
		<property type="string">firstName</property>
		<property type="string">lastName</property>
		<property type="integer">age</property>
	</properties>
</manifest>
```

## Code Example

```PHP
// first way to instanciate an object
$personModel = InstanceModel::getInstance()->getInstanceModel('person');
$person = $personModel->getObjectInstance();

// second way to instanciate an object
$person = new Object('person');

// set object values
$person->setValue('age', 21);
$person->setValue('foo', 'bar'); // will not work because person doesn't have property 'foo'

// get an object value
$age = $person->getValue('age');

// import from json
$person = $personModel->fromOject(json_decode('{"first_name":"john","last_name":"john","age":21}'));

// export to xml
$simpleXmlElement = $person->toXml();

// load, update and save an object (from/to sql database or xml file or json file)
$loadedPerson = $personModel->loadObject('id_of_a_person');
$loadedPerson->setValue('age', 25);
$loadedPerson->save();
```

## Motivation

give a chance to make a comhon object managment in your project and avoid many specifics cases in your code source that make your project unmaintainable and hard to improve

## Installation

download and copy this project folder where you want on your system

create the directory `/etc/comhon`

create a json file named `config.json` in previous directory (absolute path would be `/etc/comhon/config.json`)

and finaly put at least these two following entries to allow comhon library to find your manifests
```
{
  "manifestList": "/absolute/path/to/directory/where/is/saved/file/manifestList.xml",
  "serializationList": "/absolute/path/to/directory/where/is/saved/file/serializationList.xml"
}
```

you're done, now you just have to include file `ObjectManagerLib.php` in your sources

## API Reference

for more informations take a look at [wiki page] (https://github.com/jeanphilippe-p/ObjectManagerLib/wiki)
