<?php
/*+***********************************************************************************
 * Mutating control webservice.
 *************************************************************************************/

require_once 'include/Webservices/ControlCommon.php';

function vtws_control($command, $action, $path, $target, $sql, $content, $recursive, $createParents, $encoding, $transaction, $user) {
	global $adb;

	control_ws_require_admin($user);

	$command = control_ws_normalize_command($command);
	$action = strtolower(trim((string) $action));

	if ($command === 'database') {
		return vtws_control_database($action, $sql, $transaction, $adb);
	}

	if ($command === 'filesystem') {
		return vtws_control_filesystem($action, $path, $target, $content, $recursive, $createParents, $encoding);
	}

	throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown control command');
}

function vtws_control_database($action, $sql, $transaction, $adb) {
	if ($action !== 'execute') {
		throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown control/database action');
	}

	$sql = control_ws_assert_mutating_sql($sql);
	$useTransaction = control_ws_is_truthy($transaction);

	if ($useTransaction) {
		$adb->query('START TRANSACTION');
	}

	$result = $adb->query($sql);
	if ($result === false) {
		if ($useTransaction) {
			$adb->query('ROLLBACK');
		}
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to execute SQL statement');
	}

	if ($useTransaction) {
		$adb->query('COMMIT');
	}

	return array(
		'command' => 'database',
		'action' => 'execute',
		'sql' => $sql,
		'transaction' => $useTransaction,
		'affectedRows' => (int) $adb->getAffectedRowCount($result),
	);
}

function vtws_control_filesystem($action, $path, $target, $content, $recursive, $createParents, $encoding) {
	$fullPath = control_ws_resolve_path($path);

	if ($action === 'mkdir') {
		if (file_exists($fullPath)) {
			if (!is_dir($fullPath)) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Target path already exists and is not a directory');
			}
			return array(
				'command' => 'filesystem',
				'action' => 'mkdir',
				'path' => control_ws_exposed_path($fullPath),
				'created' => false,
			);
		}
		if (!@mkdir($fullPath, 0775, true)) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to create directory');
		}
		return array(
			'command' => 'filesystem',
			'action' => 'mkdir',
			'path' => control_ws_exposed_path($fullPath),
			'created' => true,
		);
	}

	if ($action === 'write') {
		$dir = dirname($fullPath);
		if (!is_dir($dir)) {
			if (!control_ws_is_truthy($createParents)) {
				throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Parent directory does not exist');
			}
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to create parent directories');
			}
		}

		$encoding = strtolower(trim((string) $encoding));
		if ($encoding === 'base64') {
			$decoded = base64_decode((string) $content, true);
			if ($decoded === false) {
				throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Invalid base64 content');
			}
			$content = $decoded;
		}

		if (file_put_contents($fullPath, (string) $content) === false) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to write file');
		}

		return array(
			'command' => 'filesystem',
			'action' => 'write',
			'path' => control_ws_exposed_path($fullPath),
			'size' => filesize($fullPath),
		);
	}

	if ($action === 'delete') {
		control_ws_assert_exists($fullPath);
		$recursive = control_ws_is_truthy($recursive);

		if (is_dir($fullPath) && !$recursive) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Recursive flag is required to delete directories');
		}

		$deleted = is_dir($fullPath) ? control_ws_delete_path_recursive($fullPath) : @unlink($fullPath);
		if (!$deleted) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to delete path');
		}

		return array(
			'command' => 'filesystem',
			'action' => 'delete',
			'path' => control_ws_exposed_path($fullPath),
			'recursive' => $recursive,
		);
	}

	if ($action === 'rename') {
		control_ws_assert_exists($fullPath);
		$target = control_ws_require_value('target', $target);
		$targetPath = control_ws_resolve_path($target);
		$targetDir = dirname($targetPath);

		if (!is_dir($targetDir)) {
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Target directory does not exist');
		}

		if (!@rename($fullPath, $targetPath)) {
			throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'Unable to rename path');
		}

		return array(
			'command' => 'filesystem',
			'action' => 'rename',
			'path' => control_ws_exposed_path($fullPath),
			'target' => control_ws_exposed_path($targetPath),
		);
	}

	throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, 'Unknown control/filesystem action');
}
