<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('modules/com_vtiger_workflow/VTEntityCache.inc');
require_once('modules/com_vtiger_workflow/VTWorkflowUtils.php');

class VTCreateEntityTask extends VTTask {

	public $executeImmediately = true;
	//array which contains the focus instances of reference fields 
	private $referenceFieldFocusList = array();

	public function getFieldNames() {
		return array('entity_type', 'reference_field', 'field_value_mapping', 'upsert_key_field');
	}

	public function doTask($entity) {
		global $adb, $current_user,$default_timezone;
		$util = new VTWorkflowUtils();

		$admin = $util->adminUser();
		$moduleName = $entity->getModuleName();
		$entityId = $entity->getId();
		$recordId = vtws_getIdComponents($entityId);
		$recordId = $recordId[1];

		$entityType = $this->entity_type;		
		if(!vtlib_isModuleActive($entityType)) {
			$util->revertUser();
			return;
		}

		$fieldValueMapping = array();
		if (!empty($this->field_value_mapping)) {
			$fieldValueMapping = Zend_Json::decode($this->field_value_mapping);
		}

		if (!empty($entityType) && !empty($fieldValueMapping) && php7_count($fieldValueMapping) > 0) {
			require_once('data/CRMEntity.php');

			$newEntity = CRMEntity::getInstance($entityType);
			$newEntity->mode = '';
			$newEntityData = VTEntityData::fromCRMEntity($newEntity);
			$entityModuleHandler = vtws_getModuleHandlerFromName($entityType, $current_user);
			$handlerMeta = $entityModuleHandler->getMeta();
			$moduleFields = $handlerMeta->getModuleFields();
			$ownerFields = $handlerMeta->getOwnerFields();

			$focus = CRMEntity::getInstance($moduleName);
			$focus->id = $recordId;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($recordId, $moduleName);

			foreach ($fieldValueMapping as $fieldInfo) {
				$fieldName = $fieldInfo['fieldname'];
				$referenceModule = $fieldInfo['modulename'];
				$fieldType = '';
				$fieldValueType = $fieldInfo['valuetype'];
				$fieldValue = trim($fieldInfo['value']);
				$moduleFieldInstance = $moduleFields[$fieldName];

				//If field is invisible, then continue
				if (!$moduleFieldInstance) {
					continue;
				}
				preg_match('/\((\w+) : \((\w+)\) (\w+)\)/',$fieldValue,$matches);
				if ($fieldValueType == 'fieldname') {
					$field = $fieldValue;
					$module = $referenceModule;
					if ($referenceModule == $entityType) {
						$fieldValue = $newEntity->column_fields[$fieldValue];
					} else if (php7_count($matches) > 0) {
						$referenceField = $matches[1];
						$referencedModule = $matches[2];
						$referencedFieldName = $matches[3];
						$field = $referencedFieldName;
						$module = $referenceModule;
						$referenceRecordId = $focus->column_fields[$referenceField];
						if(empty($referenceRecordId) || isRecordExists($referenceRecordId) === false){
							//if no value exists for the reference field then we dont have to update
							//if reference record is deleted then we dont have to update
							continue;
						}
						$referenceFieldFocus = $this->getReferenceFieldFocus($referencedModule, $referenceField, $referenceRecordId);
						$fieldValue = $referenceFieldFocus->column_fields[$referencedFieldName];
					} else {
						$fieldValue = decode_html($focus->column_fields[$fieldValue]);
					}

					$moduleModel = Vtiger_Module_Model::getInstance($module);
					$fieldInstance = $moduleModel->getField($field);
					if($fieldInstance) {
						$dataType = $fieldInstance->getFieldDataType();
						if(in_array($dataType, array('currency', 'double'))) {
							$fieldValue = CurrencyField::convertToUserFormat($fieldValue, null, true);
						}
						if ($fieldInstance->getFieldDataType() == 'reference' && $moduleFieldInstance->getFieldDataType() != 'reference') {
							if (!empty($fieldValue)) {
								if (!empty($fieldInstance)) {
									$referenceList = $fieldInstance->getReferenceList();
									if ((php7_count($referenceList) == 1) && $referenceList[0] == "Users") {
										$userRecordLabels = Vtiger_Functions::getOwnerRecordLabels($fieldValue);
										$fieldValue = $userRecordLabels[$fieldValue];
									} elseif ((php7_count($referenceList) == 1) && $referenceList[0] == "Currency") {
										$fieldValue = getCurrencyName($fieldValue);
									} elseif ($fieldInstance->getFieldName() == "roleid") {
										$fieldValue = getRoleName($fieldValue);
									} else {
										$fieldValue = Vtiger_Util_Helper::getRecordName($fieldValue);
									}
								} else {
									$fieldValue = Vtiger_Util_Helper::getRecordName($fieldValue);
								}
							} else {
								//Not value is there for reference fields . So skip this field mapping
								continue;
							}
						}
					}
				} elseif ($fieldValueType == 'expression') {
					require_once 'modules/com_vtiger_workflow/expression_engine/include.inc';
					//Added to generate date value in user timezone.
					date_default_timezone_set($current_user->time_zone);
					try{
						$parser = new VTExpressionParser(new VTExpressionSpaceFilter(new VTExpressionTokenizer($fieldValue)));
						$expression = $parser->expression();
						$exprEvaluater = new VTFieldExpressionEvaluater($expression);
						if ($referenceModule == $entityType) {
							$fieldValue = $exprEvaluater->evaluate($newEntityData);
						} else {
							$fieldValue = $exprEvaluater->evaluate($entity);
						}
					} catch (Exception $e) {
						echo $e->getMessage();
						throw $e;
					}
					date_default_timezone_set($default_timezone);
				} elseif (preg_match('/([^:]+):boolean$/', $fieldValue, $match)) {
					$fieldValue = $match[1];
					if ($fieldValue == 'true') {
						$fieldValue = '1';
					} else {
						$fieldValue = '0';
					}
				}

				if (in_array($fieldName, $ownerFields) && $fieldValue === '$source_assigned_user_id') {
					if (!empty($focus->column_fields['assigned_user_id'])) {
						$fieldValue = $focus->column_fields['assigned_user_id'];
					} else {
						$fieldValue = $focus->column_fields[$fieldName];
					}
				}

				if (in_array($fieldName, $ownerFields) && !is_numeric($fieldValue)) {
					$userId = getUserId_Ol($fieldValue);
					$groupId = getGrpId($fieldValue);

					if ($userId == 0 && $groupId == 0) {
						$fieldValue = $focus->column_fields[$fieldName];
					} else {
						$fieldValue = ($userId == 0) ? $groupId : $userId;
					}
				}
				if ($moduleFieldInstance->getFieldDataType() == 'reference') {
					$allowNameToIdEncode = true;
					//To not encode fieldvalue to id since if it is fieldname and reference field the value itself is id
					if (($fieldType == 'fieldname') && !empty($fieldInstance) && ($fieldInstance->getFieldDataType() != 'reference')) {
						$allowNameToIdEncode = false;
					}
					if ($allowNameToIdEncode) {
						$referenceModuleList = $moduleFieldInstance->getReferenceList();
						$fieldReferenceModule = $referenceModuleList[0];

						if (is_numeric($fieldValue) && isRecordExists($fieldValue) && getSalesEntityType($fieldValue) == $fieldReferenceModule) {
							$recordId = $fieldValue;
						} else {
							$recordId = Vtiger_Util_Helper::getRecordId($fieldValue, $fieldReferenceModule, true);
						}

						if (!empty($recordId)) {
							$fieldValue = $recordId;
						} else {
							$fieldValue = '';
						}
					}
				}

				$newEntity->column_fields[$fieldName] = $fieldValue;
			}
			// Upsert: se upsert_key_field è configurato, cerca un record esistente e aggiornalo
			if (!empty($this->upsert_key_field)) {
				$keyValue = $newEntity->column_fields[$this->upsert_key_field];
				if (!empty($keyValue)) {
					$existingId = $this->findExistingRecord($entityType, $this->upsert_key_field, $keyValue);
					if (!empty($existingId)) {
						$resolvedFields = $newEntity->column_fields;
						$newEntity = CRMEntity::getInstance($entityType);
						$newEntity->id = $existingId;
						$newEntity->mode = 'edit';
						$newEntity->retrieve_entity_info($existingId, $entityType);
						foreach ($resolvedFields as $fname => $fvalue) {
							$newEntity->column_fields[$fname] = $fvalue;
						}
					}
				}
			}

			if (empty($newEntity->column_fields[$this->reference_field])) {
				$newEntity->column_fields[$this->reference_field] = $focus->id;
			}
			// To handle cyclic process
			$newEntity->_from_workflow = true;
			$newEntity->column_fields['source'] = 'WORKFLOW';

			// Inventory modules (SalesOrder/Invoice/Quote/PurchaseOrder) rebuild their
			// line items and calculated totals (total/subtotal) from $_REQUEST inside
			// save_module(). In a workflow there are no line items in the request, so a
			// plain save would delete the existing line items and zero the totals.
			// Mirror VTUpdateFieldsTask and skip the line-item rebuild so manually
			// entered materials/totals are preserved (especially on the upsert branch).
			$newEntity->isLineItemUpdate = false;
			try {

				//If the module triggering workflow and new entity we are creating are same, Then it might end up in infinite loop.
				//So we need to call saveentity than save in order to avoid workflow triggering for new entity
				if($moduleName == $entityType) { //TODO: Need to check for conditions as well, If new entity satisfies current workflow conditions and triggers same workflow
					$newEntity->saveentity($entityType);
				} else{
					$newEntity->save($entityType);
				}
				relateEntities($focus, $moduleName, $recordId, $entityType, $newEntity->id);
			} catch (DuplicateException $e) {
				$workFlowManager = new VTWorkflowManager($adb);
				$workFlow = $workFlowManager->retrieve($this->workflowId);

				$mailBody = vtranslate('LBL_DUPLICATION_FAILURE_FROM_WORKFLOWS', $entityType, vtranslate('SINGLE_'.$entityType, $entityType),
														decode_html($workFlow->workflowname), vtranslate('SINGLE_'.$entityType, $entityType));
				sendMailToUserOnDuplicationPrevention($entityType, $newEntity->column_fields, $mailBody);
			} catch (Exception $e) {
			}
			$util->revertUser();
			return;
		}

		$util->revertUser();
	}

	private function findExistingRecord($moduleName, $fieldName, $fieldValue) {
		global $adb;
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$fieldModel = $moduleModel->getField($fieldName);
		if (!$fieldModel) return null;
		$column = $fieldModel->get('column');
		$table = $fieldModel->get('table');
		$moduleId = strtolower($moduleName) . 'id';
		$sql = "SELECT vtiger_crmentity.crmid FROM vtiger_crmentity
		        INNER JOIN {$table} ON vtiger_crmentity.crmid = {$table}.{$moduleId}
		        WHERE vtiger_crmentity.deleted = 0 AND {$table}.{$column} = ?
		        LIMIT 1";
		$result = $adb->pquery($sql, [$fieldValue]);
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result, 0, 'crmid');
		}
		return null;
	}

	public function getReferenceFieldFocus($referencedModule,$referenceField,$referenceRecordId){
		global $current_user;
		$referenceRecordFocus = $this->referenceFieldFocusList[$referenceField][$referencedModule][$referenceRecordId];
		if(empty($referenceRecordFocus)){
			$referenceRecordFocus = CRMEntity::getInstance($referencedModule);
			$referenceRecordFocus->id = $referenceRecordId;
			$referenceRecordFocus->mode = 'edit';
			$referenceRecordFocus->retrieve_entity_info($referenceRecordId, $referencedModule);
			$referenceRecordFocus->clearSingletonSaveFields();

			$referenceModuleHandler = vtws_getModuleHandlerFromName($referencedModule, $current_user);
			$referenceHandlerMeta = $referenceModuleHandler->getMeta();

			$referenceRecordFocus->column_fields = DataTransform::sanitizeDateFieldsForInsert($referenceRecordFocus->column_fields,$referenceHandlerMeta);
			$referenceRecordFocus->column_fields = DataTransform::sanitizeCurrencyFieldsForInsert($referenceRecordFocus->column_fields,$referenceHandlerMeta);

			$this->referenceFieldFocusList[$referenceField][$referencedModule][$referenceRecordId] = $referenceRecordFocus;
		}
		return $referenceRecordFocus;
	}

}
?>
