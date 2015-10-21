<?php
namespace objectManagerLib\object\object;

abstract class SerializationUnit extends Object {
	
	public abstract function saveObject($pObject, $pModel);
	public abstract function loadObject($pObject, $pId, $pPropertySerializationName = null, $pParentModel = null);
	public abstract function hasReturnValue();
}