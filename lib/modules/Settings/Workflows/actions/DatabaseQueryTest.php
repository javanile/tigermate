<?php
require_once 'modules/com_vtiger_workflow/tasks/VTDatabaseQueryTask.inc';
require_once 'modules/com_vtiger_workflow/include.inc';

class Settings_Workflows_DatabaseQueryTest_Action extends Settings_Vtiger_Basic_Action {

    function __construct() {
        parent::__construct();
        $this->exposeMethod('run');
        $this->exposeMethod('getTables');
        $this->exposeMethod('previewTable');
    }

    public function process(Vtiger_Request $request) {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function getTables(Vtiger_Request $request) {
        global $adb;
        $res = $adb->query('SHOW TABLES', false);
        $tables = array();
        while ($row = $adb->fetch_row($res)) {
            $tables[] = $row[0];
        }
        $response = new Vtiger_Response();
        $response->setResult($tables);
        $response->emit();
    }

    public function previewTable(Vtiger_Request $request) {
        global $adb;
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $request->get('table_name'));
        if (empty($table)) {
            $response = new Vtiger_Response();
            $response->setError('Nome tabella non valido.');
            $response->emit();
            return;
        }
        $res = $adb->query('SELECT * FROM `' . $table . '` LIMIT 10', false);
        if (!$res || $adb->database->ErrorNo() !== 0) {
            $response = new Vtiger_Response();
            $response->setError($adb->database->ErrorMsg() ?: 'Tabella non trovata.');
            $response->emit();
            return;
        }
        $rows = array();
        while ($row = $adb->fetch_array($res)) {
            $rows[] = array_filter($row, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
        }
        $response = new Vtiger_Response();
        $response->setResult($rows);
        $response->emit();
    }

    public function run(Vtiger_Request $request) {
        global $adb, $current_user;

        $sql        = $request->getRaw('query');
        $recordId   = (int)$request->get('record_id');
        $moduleName = $request->get('module_name');

        // Validate record exists, is not deleted, and is accessible to current user
        $checkResult = $adb->pquery(
            'SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
            array($recordId)
        );
        if (!$checkResult || $adb->num_rows($checkResult) === 0) {
            $response = new Vtiger_Response();
            $response->setError('Record di test non trovato. Cambiare, provare con un altro record.');
            $response->emit();
            return;
        }

        if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
            $response = new Vtiger_Response();
            $response->setError('Record di test non trovato. Cambiare, provare con un altro record.');
            $response->emit();
            return;
        }

        // Load entity data from record
        try {
            $focus = CRMEntity::getInstance($moduleName);
            $focus->retrieve_entity_info($recordId, $moduleName);
            $focus->id = $recordId;
            $entityData = VTEntityData::fromCRMEntity($focus);
        } catch (Exception $e) {
            $response = new Vtiger_Response();
            $response->setError('Record di test non trovato. Cambiare, provare con un altro record.');
            $response->emit();
            return;
        }

        $statements   = VTDatabaseQueryTask::splitStatements($sql);
        $lastInsertId = null;
        $lastRow      = array();
        $results      = array();

        foreach ($statements as $stmt) {
            $resolved = VTDatabaseQueryTask::replaceVariables($stmt, $entityData, $lastInsertId, $lastRow);
            if (empty($resolved)) continue;

            $type    = VTDatabaseQueryTask::statementType($resolved);
            $allowed = VTDatabaseQueryTask::isAllowed($resolved);

            $entry = array(
                'original' => $stmt,
                'resolved' => $resolved,
                'type'     => $type ?: strtoupper(substr(ltrim($resolved), 0, 6)),
                'allowed'  => $allowed,
                'error'    => null,
                'rows'     => null,
                'row'      => null,
                'affected' => null,
                'last_insert_id' => null,
            );

            if (!$allowed) {
                $entry['error'] = 'Statement type not allowed. Only SELECT, INSERT, UPDATE, REPLACE are permitted.';
                $results[] = $entry;
                continue;
            }

            $res = $adb->query($resolved, false);

            $errNo  = $adb->database->ErrorNo();
            $errMsg = $adb->database->ErrorMsg();

            if ($errNo !== 0 || $res === false) {
                $entry['error'] = $errMsg ?: 'Unknown SQL error';
            } elseif ($type === 'SELECT') {
                $rows = array();
                while ($row = $adb->fetch_array($res)) {
                    $rows[] = array_filter($row, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                }
                $lastRow         = !empty($rows) ? $rows[0] : array();
                $entry['rows']   = $rows;
                $entry['row']    = $lastRow;
            } else {
                $entry['affected'] = $adb->database->Affected_Rows();
                if ($type === 'INSERT' || $type === 'REPLACE') {
                    $lastInsertId           = $adb->database->Insert_ID();
                    $entry['last_insert_id'] = $lastInsertId;
                }
            }

            $results[] = $entry;
        }

        $response = new Vtiger_Response();
        $response->setResult($results);
        $response->emit();
    }
}
