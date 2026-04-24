<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

 Class Project_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the summary information for module
	 * @return <array> - values which need to be shown as summary
	 */
	public function getSummaryInfo() {
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$projectTaskInstance = Vtiger_Module_Model::getInstance('ProjectTask');
		if($userPrivilegesModel->hasModulePermission($projectTaskInstance->getId())) {
			$adb = PearDatabase::getInstance();

			$query ='SELECT smownerid,enddate,projecttaskstatus,projecttaskpriority
					FROM vtiger_projecttask
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_projecttask.projecttaskid
								AND vtiger_crmentity.deleted=0
							WHERE vtiger_projecttask.projectid = ? ';

			$result = $adb->pquery($query, array($this->getId()));

			$tasksOpen = $taskCompleted = $taskDue = $taskDeferred = $numOfPeople = 0;
			$highTasks = $lowTasks = $normalTasks = $otherTasks = 0;
			$currentDate = date('Y-m-d');
			$inProgressStatus = array('Open', 'In Progress');
			$usersList = array();

			while($row = $adb->fetchByAssoc($result)) {
				$projectTaskStatus = $row['projecttaskstatus'];
				switch($projectTaskStatus){
					case 'Open'		: $tasksOpen++;		break;
					case 'Deferred'	: $taskDeferred++;	break;
					case 'Completed': $taskCompleted++;	break;
				}
				$projectTaskPriority = $row['projecttaskpriority'];
				switch($projectTaskPriority){
					case 'high' : $highTasks++;break;
					case 'low' : $lowTasks++;break;
					case 'normal' : $normalTasks++;break;
					default : $otherTasks++;break;
				}

				if(!empty($row['enddate']) && (strtotime($row['enddate']) < strtotime($currentDate)) &&
						(in_array($row['projecttaskstatus'], $inProgressStatus))) {
					$taskDue++;
				}
				$usersList[] = $row['smownerid'];
			}

			$usersList = array_unique($usersList);
			$numOfPeople = php7_count($usersList);

			$summaryInfo['projecttaskstatus'] =  array(
													'LBL_TASKS_OPEN'	=> $tasksOpen,
													'Progress'			=> $this->get('progress'),
													'LBL_TASKS_DUE'		=> $taskDue,
													'LBL_TASKS_COMPLETED'=> $taskCompleted,
			);

			$summaryInfo['projecttaskpriority'] =  array(
													'LBL_TASKS_HIGH'	=> $highTasks,
													'LBL_TASKS_NORMAL'	=> $normalTasks,
													'LBL_TASKS_LOW'		=> $lowTasks,
													'LBL_TASKS_OTHER'	=> $otherTasks,
			);
		}

		return $summaryInfo;
	}

	/** 
	 * Function to get the project task for a project
	 * @return <Array> - $projectTasks
	 */
	public function getProjectTasks() {
		$recordId  = $this->getId();
		$db = PearDatabase::getInstance();
		$projectTasks = array();
		$progressQueryParts = $this->getGanttProgressQueryParts(
			'ProjectTask',
			'vtiger_projecttask',
			'projecttaskid',
			array(
				'primary' => Settings_Vtiger_GanttConfig_Model::TASK_PRIMARY_PROGRESS_FIELD_KEY,
				'secondary' => Settings_Vtiger_GanttConfig_Model::TASK_SECONDARY_PROGRESS_FIELD_KEY,
			)
		);

		$sql = "SELECT vtiger_projecttask.projecttaskid as recordid,vtiger_projecttask.projecttaskname as name,vtiger_projecttask.startdate,vtiger_projecttask.enddate,vtiger_projecttask.projecttaskstatus".$progressQueryParts['selectSql']." FROM vtiger_projecttask 
				INNER JOIN vtiger_crmentity  ON vtiger_projecttask.projecttaskid = vtiger_crmentity.crmid
				".$progressQueryParts['joinSql']."
				WHERE vtiger_projecttask.projectid=? AND vtiger_crmentity.deleted=0 AND vtiger_projecttask.startdate IS NOT NULL AND vtiger_projecttask.enddate IS NOT NULL";

		$result = $db->pquery($sql, array($recordId));
		$i = -1;
		while($record = $db->fetchByAssoc($result)){
			$record['id'] = $i;
			$record['name'] = decode_html(textlength_check($record['name']));
			$record['status'] = self::getGanttStatus($record['projecttaskstatus']);
			$record['start'] = strtotime($record['startdate']) * 1000;
			$record['duration'] = $this->getDuration($record['startdate'], $record['enddate']);
			$record['end'] = strtotime($record['enddate']) * 1000;
			$this->appendGanttProgressData($record, $progressQueryParts);
			$projectTasks[] = $record;
			$i--;
		}

		return $projectTasks;
	}

	/**
	 * Function to get the duration
	 * @param <string> $startDate,$endDate
	 * @return $duration
	 */
	public function getDuration($startDate,$endDate) {
		$difference = strtotime($endDate) - strtotime($startDate);
		$duration = floor($difference/(3600*24)+1);

		// if the start date and end date are same
		if($duration == 0) {
			return $duration+0.1;
		} else if($duration < 0) { // if end date is null or less than start date
			return 0; 
		}

		return $duration;
	}

	/**
	 * Function to get the project milestones for the gantt chart
	 * @return <Array> - $milestones as gantt-compatible task objects
	 */
	public function getProjectMilestones() {
		$recordId = $this->getId();
		$db = PearDatabase::getInstance();
		$progressQueryParts = $this->getGanttProgressQueryParts(
			'ProjectMilestone',
			'vtiger_projectmilestone',
			'projectmilestoneid',
			array(
				'primary' => Settings_Vtiger_GanttConfig_Model::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY,
				'secondary' => Settings_Vtiger_GanttConfig_Model::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY,
			)
		);

		$sql = "SELECT vtiger_projectmilestone.projectmilestoneid as recordid, vtiger_projectmilestone.projectmilestonename as name,
					   vtiger_projectmilestone.projectmilestonedate as startdate, vtiger_projectmilestone.projectmilestonedeliverydate as enddate".$progressQueryParts['selectSql']."
				FROM vtiger_projectmilestone
				INNER JOIN vtiger_crmentity ON vtiger_projectmilestone.projectmilestoneid = vtiger_crmentity.crmid
				".$progressQueryParts['joinSql']."
				WHERE vtiger_projectmilestone.projectid=? AND vtiger_crmentity.deleted=0
				  AND vtiger_projectmilestone.projectmilestonedate IS NOT NULL";

		$result = $db->pquery($sql, array($recordId));
		$milestones = [];
		$i = -10001;

		while ($record = $db->fetchByAssoc($result)) {
			$startDate = $record['startdate'];
			$endDate   = !empty($record['enddate']) ? $record['enddate'] : $startDate;
			$record['id']       = $i;
			$record['name']     = decode_html(textlength_check($record['name']));
			$record['status']   = 'STATUS_MILESTONE';
			$record['type']     = 'milestone';
			$record['start']    = strtotime($startDate) * 1000;
			$record['end']      = strtotime($endDate) * 1000;
			$record['duration'] = $this->getDuration($startDate, $endDate);
			$record['readonly'] = true;
			$this->appendGanttProgressData($record, $progressQueryParts);
			$milestones[] = $record;
			$i--;
		}

		return $milestones;
	}

	public function getGanttChartItems($displayMode = 'all') {
		return self::filterGanttChartItems(
			array_merge($this->getProjectTasks(), $this->getProjectMilestones()),
			$displayMode
		);
	}

	public static function filterGanttChartItems($items, $displayMode = 'all') {
		switch ($displayMode) {
			case 'tasks':
				return array_values(array_filter($items, function ($item) {
					return empty($item['type']) || $item['type'] !== 'milestone';
				}));
			case 'milestones':
				return array_values(array_filter($items, function ($item) {
					return isset($item['type']) && $item['type'] === 'milestone';
				}));
			default:
				return $items;
		}
	}

	static public function getGanttStatus($status) {
		switch($status) {
			case 'Open'			: return 'STATUS_UNDEFINED';
			case 'In Progress'  : return 'STATUS_ACTIVE';
			case 'Completed'	: return 'STATUS_DONE';
			case 'Deferred'		: return 'STATUS_SUSPENDED';
			case 'Canceled'		: return 'STATUS_FAILED';
			default				: return $status;
		}
	}

 function getStatusColors() {
		$statusColorMap = array();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT *FROM vtiger_projecttask_status_color');
		if ($db->num_rows($result) > 0) {
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$status = decode_html($db->query_result($result, $i, 'status'));
				$color = $db->query_result($result, $i, 'color');
				if (empty($color)) {
					$color = $db->query_result($result, $i, 'defaultcolor');
				}
				$statusColorMap[$status] = $color;
			}
		}

		return $statusColorMap;
	}

	static function getGanttStatusCss($status, $color) {
		return '.taskStatus[status="'.self::getGanttStatus($status).'"]{
					background-color: '.$color.';
				}';
	}

	static function getGanttSvgStatusCss($status, $color) {
		return '.taskStatusSVG[status="'.self::getGanttStatus($status).'"]{
					fill: '.$color.';
				}';
	}

	protected function appendGanttProgressData(&$record, $progressQueryParts) {
		$record['ganttHasPrimaryProgressSlot'] = !empty($progressQueryParts['selectedFields']['primary']);
		$record['ganttHasSecondaryProgressSlot'] = !empty($progressQueryParts['selectedFields']['secondary']);
		$record['ganttUsesCustomProgress'] = $record['ganttHasPrimaryProgressSlot'] || $record['ganttHasSecondaryProgressSlot'];
		$record['ganttPrimaryProgress'] = $record['ganttHasPrimaryProgressSlot']
			? self::normalizeGanttProgressValue($record['gantt_primary_progress_value'])
			: null;
		$record['ganttSecondaryProgress'] = $record['ganttHasSecondaryProgressSlot']
			? self::normalizeGanttProgressValue($record['gantt_secondary_progress_value'])
			: null;
		unset($record['gantt_primary_progress_value'], $record['gantt_secondary_progress_value']);
	}

	protected function getGanttProgressQueryParts($moduleName, $baseTable, $recordIdColumn, $settingKeys) {
		$settings = Settings_Vtiger_GanttConfig_Model::getStoredSettings();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$queryParts = array(
			'joinSql' => '',
			'selectSql' => '',
			'selectedFields' => array(),
		);
		if (!$moduleModel) {
			return $queryParts;
		}

		$joins = array();
		$selects = array();
		$aliases = array(
			'primary' => 'gantt_primary_progress_value',
			'secondary' => 'gantt_secondary_progress_value',
		);

		foreach ($settingKeys as $slot => $settingKey) {
			$fieldName = isset($settings[$settingKey]) ? $settings[$settingKey] : Settings_Vtiger_GanttConfig_Model::NONE_FIELD_VALUE;
			if (empty($fieldName) || $fieldName === Settings_Vtiger_GanttConfig_Model::NONE_FIELD_VALUE) {
				continue;
			}
			$fieldModel = $moduleModel->getField($fieldName);
			if (!$fieldModel || $fieldModel->getFieldDataType() !== 'percentage') {
				continue;
			}
			$tableName = $fieldModel->get('table');
			$columnName = $fieldModel->get('column');
			if (empty($tableName) || empty($columnName)) {
				continue;
			}
			if ($tableName !== $baseTable && !isset($joins[$tableName])) {
				$joins[$tableName] = ' LEFT JOIN '.$tableName.' ON '.$tableName.'.'.$recordIdColumn.' = '.$baseTable.'.'.$recordIdColumn;
			}
			$selects[] = $tableName.'.'.$columnName.' AS '.$aliases[$slot];
			$queryParts['selectedFields'][$slot] = $fieldName;
		}

		if (!empty($joins)) {
			$queryParts['joinSql'] = implode(' ', $joins);
		}
		if (!empty($selects)) {
			$queryParts['selectSql'] = ', '.implode(', ', $selects);
		}

		return $queryParts;
	}

	public static function normalizeGanttProgressValue($value) {
		if ($value === null || $value === '') {
			return 0;
		}
		$value = decode_html($value);
		if ($value === '--none--') {
			return 0;
		}
		if (is_string($value)) {
			$value = str_replace('%', '', trim($value));
		}
		if (!is_numeric($value)) {
			return 0;
		}
		$value = (float) $value;
		if ($value < 0) {
			$value = 0;
		}
		return $value + 0;
	}
}

?>
