<?php
namespace objectManagerLib\object\object;

abstract class SerializationUnit extends Object {
	
	public abstract function saveObject($pObject, $pModel);
	public abstract function loadObject($pObject);
	public abstract function hasReturnValue();
	
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}