# Comhon!
## Synopsis

[Comhon!](https://github.com/comhon-project/comhon/wiki) (Common Object Manager, Hashmap Or Nothing!) is an object manager based on gess what ?... hashmap!

It will allow you to import, export, serialize (in sql database, xml file, json file) objects without any line of code. You don't have to create any classes (actually you can if you're not comfortable without them), you just have to describe your model in a manifest (xml file or json file). For exemple a manifest may be linked to an sql table and you will be able to Select/Insert/Update without write your sql query (actually like an ORM)

## Some others features
* advanced object managment ([see wiki page](https://github.com/comhon-project/comhon/wiki/Object-management))
* provide a request format to request any object by any client ([see wiki page](https://github.com/comhon-project/comhon/wiki/requester))
* provide automatic and dynamic REST API ([see wiki page](https://github.com/comhon-project/comhon/wiki/rest-api))

## Manifest Example
A Manifest permit to describe a concept by listing its properties. Manifests can be defined in XML or JSON format

simple XML manifest to describe a person :
```XML
<manifest version="3.0" name="Example\Person">
    <properties>
        <property name="identifier" is_id="1" __inheritance__="Comhon\Manifest\Property\String"/>
        <property name="firstName" __inheritance__="Comhon\Manifest\Property\String"/>
        <property name="lastName" __inheritance__="Comhon\Manifest\Property\String"/>
        <property name="age" __inheritance__="Comhon\Manifest\Property\Integer"/>
    </properties>
</manifest>
```

simple JSON manifest to describe a person :
```JSON
{
    "version": "3.0",
    "name": "Example\\Person",
    "properties": [
        {
            "name": "identifier",
            "is_id": true,
            "__inheritance__": "Comhon\\Manifest\\Property\\String"
        },
        {
            "name": "firstName",
            "__inheritance__": "Comhon\\Manifest\\Property\\String"
        },
        {
            "name": "lastName",
            "__inheritance__": "Comhon\\Manifest\\Property\\String"
        },
        {
            "name": "age",
            "__inheritance__": "Comhon\\Manifest\\Property\\Integer"
        }
    ]
}
```
for more informations to build complexes manifests take a look at [manifest wiki page](https://github.com/comhon-project/comhon/wiki/Manifest)

## Code Example

```PHP
// first way to instanciate a comhon object
$personModel = InstanceModel::getInstance()->getInstanceModel('Example\Person');
$person = $personModel->getObjectInstance();

// second way to instanciate a comhon object
$person = new ComhonObject('Example\Person');

// third way to instanciate a comhon object only if you have defined a class
$person = new Person();

// set comhon object values
$person->setValue('age', 21);
$person->setValue('foo', 'bar'); // will not work because person doesn't have property 'foo'

// get a comhon object value
$age = $person->getValue('age');

// instanciate a comhon object by importing json
$interfacer = new StdObjectInterfacer();
$person = $interfacer->import(json_decode('{"first_name":"john","last_name":"john","age":21}'), $personModel);

// fill an existing comhon object by importing json
$interfacer = new StdObjectInterfacer();
$person->fill(json_decode('{"first_name":"john","last_name":"john","age":21}'), $interfacer);

// export a comhon object to xml and print it as string
$interfacer = new XMLInterfacer();
$nodeXML = $interfacer->export($person);
echo $interfacer->toString($nodeXML);
// output : <root first_name="john" last_name="john" age=21 />

// load, update and save an object (from/to sql database or xml file or json file)
$loadedPerson = $personModel->loadObject('id_of_a_person');
$loadedPerson->setValue('age', 25);
$loadedPerson->save();
```

## Motivation

give a chance to make a comhon object managment in your project and avoid many specifics cases in your code source that make your project unmaintainable and hard to improve

## Installation

for more informations take a look at [installation wiki page](https://github.com/comhon-project/comhon/wiki/Installation)

## Documentation

for more informations take a look at [wiki page](https://github.com/comhon-project/comhon/wiki)
