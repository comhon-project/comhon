<?php
namespace GenLib\objectManager\object\object;

abstract class SerializationUnit extends Object {
	
	public abstract function saveObject($pObject, $pModel);
	public abstract function loadObject($pId, $pModel, $pLoadDepth);
	public abstract function hasReturnValue();
}