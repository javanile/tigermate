<?php
/*+***********************************************************************************
 * Read-only inspect webservice.
 *************************************************************************************/

require_once 'include/Webservices/ControlCommon.php';

function vtws_inspect($command, $action, $table, $path, $sql, $offset, $length, $user) {
	global $adb;

	control_ws_require_admin($user);

	$command = control_ws_normalize_command($command);
	$action = strtolower(trim((string) $action));

	if ($command === 'database') {
		return vtws_inspect_database($action, $table, $sql, $adb);
	}

	if ($command === 'filesystem') {
		return vtws_inspect_filesystem($action, $path, $offset, $length);
	}

	throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown inspect command');
}

function vtws_inspect_database($action, $table, $sql, $adb) {
	if ($action === 'tables') {
		$result = $adb->query('SHOW TABLES');
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to inspect database tables');
		}
		$data = control_ws_fetch_result_rows($result, $adb);
		$items = array();
		foreach ($data['rows'] as $row) {
			$items[] = array_shift($row);
		}
		return array(
			'command' => 'database',
			'action' => 'tables',
			'items' => $items,
		);
	}

	if ($action === 'columns') {
		$table = control_ws_validate_identifier(control_ws_require_value('table', $table), 'table');
		$result = $adb->query("SHOW FULL COLUMNS FROM `$table`");
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to inspect table columns');
		}
		$data = control_ws_fetch_result_rows($result, $adb);
		$data['command'] = 'database';
		$data['action'] = 'columns';
		$data['table'] = $table;
		return $data;
	}

	if ($action === 'indexes') {
		$table = control_ws_validate_identifier(control_ws_require_value('table', $table), 'table');
		$result = $adb->query("SHOW INDEX FROM `$table`");
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to inspect table indexes');
		}
		$data = control_ws_fetch_result_rows($result, $adb);
		$data['command'] = 'database';
		$data['action'] = 'indexes';
		$data['table'] = $table;
		return $data;
	}

	if ($action === 'query') {
		$sql = control_ws_assert_readonly_sql($sql);
		$result = $adb->query($sql);
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to execute read-only SQL query');
		}
		$data = control_ws_fetch_result_rows($result, $adb);
		$data['command'] = 'database';
		$data['action'] = 'query';
		$data['sql'] = $sql;
		return $data;
	}

	if ($action === 'explain') {
		$sql = control_ws_assert_readonly_sql($sql);
		if (!preg_match('/^EXPLAIN\b/i', $sql)) {
			$sql = 'EXPLAIN ' . $sql;
		}
		$result = $adb->query($sql);
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to explain SQL query');
		}
		$data = control_ws_fetch_result_rows($result, $adb);
		$data['command'] = 'database';
		$data['action'] = 'explain';
		$data['sql'] = $sql;
		return $data;
	}

	throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown inspect/database action');
}

function vtws_inspect_filesystem($action, $path, $offset, $length) {
	$fullPath = control_ws_resolve_path($path);

	if ($action === 'list') {
		control_ws_assert_exists($fullPath, 'dir');
		$items = array();
		$entries = scandir($fullPath);
		if ($entries === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to list filesystem path');
		}
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$items[] = control_ws_stat_item($fullPath . '/' . $entry);
		}
		return array(
			'command' => 'filesystem',
			'action' => 'list',
			'path' => control_ws_exposed_path($fullPath),
			'items' => $items,
		);
	}

	if ($action === 'stat') {
		control_ws_assert_exists($fullPath);
		return array(
			'command' => 'filesystem',
			'action' => 'stat',
			'item' => control_ws_stat_item($fullPath),
		);
	}

	if ($action === 'read') {
		control_ws_assert_exists($fullPath, 'file');
		if (!is_readable($fullPath)) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'File is not readable');
		}

		$offset = trim((string) $offset) === '' ? 0 : max(0, (int) $offset);
		$length = trim((string) $length) === '' ? null : max(0, (int) $length);

		$handle = fopen($fullPath, 'rb');
		if ($handle === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to open file');
		}
		if ($offset > 0) {
			fseek($handle, $offset);
		}
		$content = $length === null ? stream_get_contents($handle) : fread($handle, $length);
		fclose($handle);

		return array(
			'command' => 'filesystem',
			'action' => 'read',
			'path' => control_ws_exposed_path($fullPath),
			'offset' => $offset,
			'length' => $length,
			'content' => $content,
			'size' => filesize($fullPath),
		);
	}

	throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown inspect/filesystem action');
}
