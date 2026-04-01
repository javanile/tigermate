<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ******************************************************************************/
require_once("include/events/SqlResultIterator.inc");
class VTEntityMethodManager{
	
	function __construct($adb){
		$this->adb = $adb;
	}
	
	
	function addEntityMethod($moduleName, $methodName, $functionPath, $functionName){
		$adb = $this->adb;
		$id = $adb->getUniqueId("com_vtiger_workflowtasks_entitymethod");
		$adb->pquery("insert into com_vtiger_workflowtasks_entitymethod (workflowtasks_entitymethod_id, module_name, function_path, function_name, method_name) values (?,?,?,?,?)", array($id, $moduleName, $functionPath, $functionName, $methodName));
	}
	
	
	
	function executeMethod($entityData, $methodName, $params = array()){
		$adb = $this->adb;
		$moduleName = $entityData->getModuleName();
		$result = $adb->pquery(
			"SELECT function_path, function_name FROM com_vtiger_workflowtasks_entitymethod WHERE method_name=? AND (module_name=? OR module_name='*') ORDER BY (module_name='*') ASC LIMIT 1",
			array($methodName, $moduleName)
		);
		if($adb->num_rows($result)!=0){
			$data = $adb->raw_query_result_rowdata($result, 0);
			require_once($data['function_path']);
			$data['function_name']($entityData, $params);
		}
	}

	function methodsForModule($moduleName){
		$adb = $this->adb;
		$result = $adb->pquery(
			"SELECT method_name FROM com_vtiger_workflowtasks_entitymethod WHERE module_name=? OR module_name='*'",
			array($moduleName)
		);
		$it = new SqlResultIterator($adb, $result);
		$methodNames = array();
		foreach($it as $row){
			$methodNames[] = $row->method_name;
		}
		return $methodNames;
	}
	/*
	private function methodExists($object, $methodName){
		$className = get_class($object);
		$class = new ReflectionClass($className);
		$methods = $class->getMethods();
		foreach($methods as $method){
			if($method->getName()==$methodName){
				return true;
			}
		}
		return false;
	}*/

	/**
	 * Function to remove workflowtasks entity method 
	 * @param <String> Module Name
	 * @param <String> Entity Method Name.
	 */
	function getMethodFields($moduleName, $methodName){
		$adb = $this->adb;
		$result = $adb->pquery(
			"SELECT function_path, function_name FROM com_vtiger_workflowtasks_entitymethod WHERE method_name=? AND (module_name=? OR module_name='*') ORDER BY (module_name='*') ASC LIMIT 1",
			array($methodName, $moduleName)
		);
		if($adb->num_rows($result) != 0){
			$data = $adb->raw_query_result_rowdata($result, 0);
			require_once($data['function_path']);
			$fieldsFunc = $data['function_name'] . '_fields';
			if(function_exists($fieldsFunc)){
				return $fieldsFunc();
			}
		}
		return array();
	}

	function removeEntityMethod($moduleName, $methodName){
		$adb = $this->adb;
		$adb->pquery("DELETE FROM com_vtiger_workflowtasks_entitymethod WHERE module_name = ? and method_name= ?", array($moduleName, $methodName));
	}
}
?>