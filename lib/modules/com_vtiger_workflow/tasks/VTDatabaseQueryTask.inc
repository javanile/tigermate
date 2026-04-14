<?php
require_once 'modules/com_vtiger_workflow/VTTaskManager.inc';

class VTDatabaseQueryTask extends VTTask {

    public $executeImmediately = true;

    public function getFieldNames() {
        return array('query');
    }

    public function doTask($entityData) {
        global $adb;
        $dumpInfo  = date('Y-m-d H:i:s') . "\n";
        $dumpInfo .= "class: " . get_class($entityData) . "\n";
        $dumpInfo .= "id: " . $entityData->getId() . "\n";
        $dumpInfo .= "moduleName: " . (property_exists($entityData, 'moduleName') ? $entityData->moduleName : 'n/a') . "\n";
        $dumpInfo .= "has changes(): " . (method_exists($entityData, 'getChanges') ? 'yes' : 'no') . "\n";
        $dumpInfo .= "has getOldValues(): " . (method_exists($entityData, 'getOldValues') ? 'yes' : 'no') . "\n";
        $dumpInfo .= "has getData(): " . (method_exists($entityData, 'getData') ? 'yes' : 'no') . "\n";
        $dumpInfo .= "methods: " . implode(', ', get_class_methods($entityData)) . "\n";
        $dumpInfo .= "properties: " . implode(', ', array_keys(get_object_vars($entityData))) . "\n";
        if (method_exists($entityData, 'getData')) {
            $dumpInfo .= "data: " . var_export($entityData->getData(), true) . "\n";
        }
        if (method_exists($entityData, 'getChanges')) {
            $dumpInfo .= "changes: " . var_export($entityData->getChanges(), true) . "\n";
        }
        if (method_exists($entityData, 'getOldValues')) {
            $dumpInfo .= "oldValues: " . var_export($entityData->getOldValues(), true) . "\n";
        }
        // dump full object last
        $dumpInfo .= "full_object: " . var_export($entityData, true) . "\n";
        $dumpInfo .= str_repeat('-', 80) . "\n\n";
        //file_put_contents(__FILE__ . '.dump', $dumpInfo, FILE_APPEND);
        $statements = self::splitStatements($this->query);
        $lastInsertId = null;
        $lastRow = array();

        foreach ($statements as $stmt) {
            $resolved = self::replaceVariables($stmt, $entityData, $lastInsertId, $lastRow);
            if (empty($resolved)) continue;
            if (!self::isAllowed($resolved)) continue;

            $result = $adb->query($resolved, false);
            if (!$result) continue;

            $type = self::statementType($resolved);
            if ($type === 'SELECT') {
                $row = $adb->fetch_array($result);
                $lastRow = $row ? array_filter($row, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY) : array();
            } elseif ($type === 'INSERT' || $type === 'REPLACE') {
                $lastInsertId = $adb->database->Insert_ID();
            }
        }
    }

    public static function splitStatements(string $sql): array {
        return array_values(array_filter(array_map('trim', explode(';', $sql))));
    }

    public static function statementType(string $sql): string {
        // Strip leading -- comment lines before checking type
        $stripped = preg_replace('/^\s*(--[^\n]*\n?)+/m', '', $sql);
        $upper = strtoupper(ltrim($stripped));
        foreach (['SELECT', 'INSERT', 'UPDATE', 'REPLACE'] as $type) {
            if (str_starts_with($upper, $type)) return $type;
        }
        return '';
    }

    public static function isAllowed(string $sql): bool {
        $type = self::statementType($sql);
        return in_array($type, ['SELECT', 'INSERT', 'UPDATE', 'REPLACE']);
    }

    public static function replaceVariables(string $sql, $entityData, $lastInsertId = null, array $lastRow = []): string {
        // 1. $_row[field]
        $sql = preg_replace_callback('/\$_row\[([a-zA-Z_][a-zA-Z0-9_]*)\]/', function ($m) use ($lastRow) {
            $val = $lastRow[$m[1]] ?? null;
            return ($val !== null && $val !== '') ? addslashes($val) : '0';
        }, $sql);

        // 2. $_id (crmid of the current record — extracted from vtiger format e.g. "13x4" → 4)
        $rawId = $entityData->getId();
        $crmid = strpos($rawId, 'x') !== false ? (int)substr($rawId, strpos($rawId, 'x') + 1) : (int)$rawId;
        $sql = str_replace('$_id', $crmid, $sql);

        // 3. $_last_insert_id
        $sql = str_replace('$_last_insert_id', $lastInsertId !== null ? (int)$lastInsertId : 'NULL', $sql);

        // 4. $fieldname (entity fields — skip those starting with _)
        $sql = preg_replace_callback('/\$([a-zA-Z][a-zA-Z0-9_]*)/', function ($m) use ($entityData) {
            return addslashes((string)$entityData->get($m[1]));
        }, $sql);

        return $sql;
    }
}
