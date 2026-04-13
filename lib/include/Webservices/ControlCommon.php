<?php
/*+***********************************************************************************
 * Shared helpers for inspect/control webservice operations.
 *************************************************************************************/

require_once 'include/utils/CommonUtils.php';

function control_ws_require_admin($user) {
	if (!$user || !is_admin($user)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Administrative privileges are required');
	}
}

function control_ws_normalize_command($command) {
	$command = strtolower(trim((string) $command));
	$map = array(
		'db' => 'database',
		'database' => 'database',
		'fs' => 'filesystem',
		'filesystem' => 'filesystem',
	);
	return isset($map[$command]) ? $map[$command] : $command;
}

function control_ws_require_value($name, $value) {
	if ($value === null || trim((string) $value) === '') {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, "Missing required parameter: $name");
	}
	return $value;
}

function control_ws_is_truthy($value) {
	if (is_bool($value)) {
		return $value;
	}
	$value = strtolower(trim((string) $value));
	return in_array($value, array('1', 'true', 'yes', 'on'), true);
}

function control_ws_root_path() {
	return str_replace('\\', '/', realpath(dirname(__DIR__, 3)));
}

function control_ws_relative_path($path) {
	$path = str_replace('\\', '/', trim((string) $path));
	$path = ltrim($path, '/');
	$parts = explode('/', $path);
	$normalized = array();

	foreach ($parts as $part) {
		if ($part === '' || $part === '.') {
			continue;
		}
		if ($part === '..') {
			if (empty($normalized)) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Path traversal is not allowed');
			}
			array_pop($normalized);
			continue;
		}
		$normalized[] = $part;
	}

	return implode('/', $normalized);
}

function control_ws_resolve_path($path) {
	$root = control_ws_root_path();
	$relative = control_ws_relative_path($path);
	return $relative === '' ? $root : $root . '/' . $relative;
}

function control_ws_exposed_path($fullPath) {
	$root = control_ws_root_path();
	$fullPath = str_replace('\\', '/', $fullPath);
	if ($fullPath === $root) {
		return '';
	}
	$prefix = $root . '/';
	if (strpos($fullPath, $prefix) === 0) {
		return substr($fullPath, strlen($prefix));
	}
	return $fullPath;
}

function control_ws_assert_exists($fullPath, $type = null) {
	if (!file_exists($fullPath)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Target path not found');
	}
	if ($type === 'file' && !is_file($fullPath)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Target is not a file');
	}
	if ($type === 'dir' && !is_dir($fullPath)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Target is not a directory');
	}
}

function control_ws_stat_item($fullPath) {
	$stat = @stat($fullPath);
	$type = is_dir($fullPath) ? 'dir' : (is_file($fullPath) ? 'file' : 'other');
	return array(
		'path' => control_ws_exposed_path($fullPath),
		'name' => basename($fullPath),
		'type' => $type,
		'size' => $type === 'file' && $stat ? (int) $stat['size'] : null,
		'mtime' => $stat ? (int) $stat['mtime'] : null,
		'readable' => is_readable($fullPath),
		'writable' => is_writable($fullPath),
		'executable' => is_executable($fullPath),
	);
}

function control_ws_validate_identifier($name, $label) {
	if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, "Invalid $label");
	}
	return $name;
}

function control_ws_normalize_sql($sql) {
	$sql = trim((string) $sql);
	$sql = rtrim($sql, " \t\n\r\0\x0B;");
	if ($sql === '') {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Missing SQL statement');
	}
	if (strpos($sql, ';') !== false) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Multiple SQL statements are not allowed');
	}
	return $sql;
}

function control_ws_assert_readonly_sql($sql) {
	$sql = control_ws_normalize_sql($sql);
	if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $sql)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Only read-only SQL statements are allowed');
	}
	return $sql;
}

function control_ws_assert_mutating_sql($sql) {
	$sql = control_ws_normalize_sql($sql);
	if (preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $sql)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Read-only SQL is not allowed in control/database/execute');
	}
	return $sql;
}

function control_ws_fetch_result_rows($result, $adb) {
	$rows = array();
	$columns = array();

	if (!$result) {
		return array('columns' => $columns, 'rows' => $rows, 'rowCount' => 0);
	}

	$rowCount = (int) $adb->num_rows($result);
	for ($i = 0; $i < $rowCount; ++$i) {
		$row = $adb->fetchByAssoc($result, $i);
		if ($row === null) {
			continue;
		}
		if (empty($columns)) {
			$columns = array_keys($row);
		}
		$rows[] = $row;
	}

	return array('columns' => $columns, 'rows' => $rows, 'rowCount' => count($rows));
}

function control_ws_delete_path_recursive($path) {
	if (is_file($path) || is_link($path)) {
		return @unlink($path);
	}

	$items = @scandir($path);
	if ($items === false) {
		return false;
	}

	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$child = $path . '/' . $item;
		if (!control_ws_delete_path_recursive($child)) {
			return false;
		}
	}

	return @rmdir($path);
}
