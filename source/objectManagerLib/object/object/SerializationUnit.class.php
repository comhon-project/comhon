<?php
namespace objectManagerLib\object\object;

abstract class SerializationUnit extends Object {
	
	public abstract function saveObject($pObject, $pModel);
	public abstract function loadObject($pObject, $pId, $pPropertySerializationName = null, $pParentModel = null);
	public abstract function hasReturnValue();
	
	public function loadCompositionIds($pObject, $pId, $pColumn, $pParentModel) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}