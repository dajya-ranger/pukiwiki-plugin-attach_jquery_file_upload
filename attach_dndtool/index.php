<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/**
 * 修正情報
 *
 * PukiWiki attachプラグイン用ファイルアップロード独自設定・拡張モジュール
 *
 * @author		オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @copyright	Copyright © 2020, dajya-ranger.com
 * @link		https://dajya-ranger.com/pukiwiki/attach-jquery-file-upload/
 * @example		@linkの内容を参照
 * @license		Apache License 2.0
 * @version		0.1.0
 * @since 		0.1.0 2020/08/14 暫定初公開（独自拡張）
 *
 */

error_reporting(E_ALL | E_STRICT);

// ドキュメントルートのindex.phpをrequireすると/lib/pukiwiki.phpをrequireし、
// 最終的にスキンファイルを出力するため、最小限の定義のみを記述する
if (! defined('DATA_HOME')) define('DATA_HOME', '');
if (! defined('LIB_DIR')) define('LIB_DIR', 'lib/');

// PukiWikiメイン設定ファイル
require_once($_SERVER['DOCUMENT_ROOT'] . '/pukiwiki.ini.php');
// encode関数用
require_once($_SERVER['DOCUMENT_ROOT'] . '/' . LIB_DIR . 'func.php');
// attach.inc.php用ファイルアップロード拡張クラス
require_once(dirname(__FILE__) . '/UploadHandler.php');

// PukiWiki attachプラグイン用設定 Start
class exOptions {
	function get_server_var($id) {
		return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
	}
	function get_full_url() {
		$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ||
			!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
			strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
		return
			($https ? 'https://' : 'http://').
			(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
			(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
				($https && $_SERVER['SERVER_PORT'] === 443 ||
				$_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
				substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	}
	function uploadDir(){
		return dirname($this->get_server_var('SCRIPT_FILENAME'));
	}
	function uploadUrl(){
		return $this->get_full_url();
	}
}

$exOptions = new exOptions();
$upDirName = UPLOAD_DIR;
$upDir = '/../' . $upDirName;

$options = array(
	// ファイルアップロード先変更
	'upload_dir' => $exOptions->uploadDir() . $upDir,
	'upload_url' => $exOptions->uploadUrl() . $upDir,
	// アップロード許可ファイル（アップロードファイル規制なし）
	'accept_file_types' => '/.*/i',
	// アップロード拒否ファイル（拡張子指定の拒否でアップロードファイル規制）
	'reject_file_types' => '/\.(html?|js|exe)$/i',
	// アップロードファイルのサムネイル出力を殺す
	'image_versions' => array(
	),
);

$error_messages = array(
	1 => 'アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。',
	2 => 'アップロードされたファイルは、HTML フォームで指定された MAX_FILE_SIZE を超えています。',
	3 => 'アップロードされたファイルは一部のみしかアップロードされていません。',
	4 => 'ファイルはアップロードされませんでした。',
	6 => 'テンポラリフォルダがありません。',
	7 => 'ディスクへの書き込みに失敗しました。',
	8 => 'PHP の拡張モジュールがファイルのアップロードを中止しました。',
	'post_max_size' => 'アップロードされたファイルは、php.ini の post_max_size ディレクティブの値を超えています。',
	'max_file_size' => 'ファイルが大きすぎます。',
	'min_file_size' => 'ファイルが小さすぎます。',
	'accept_file_types' => '許可されていないファイルタイプです。',
	'max_number_of_files' => 'ファイルの最大数を超えました。',
	'invalid_file_type' => '無効なファイルタイプです。',
	'max_width' => '画像が最大幅を超えています。',
	'min_width' => '画像には最小幅が必要です。',
	'max_height' => '画像が最大の高さを超えています。',
	'min_height' => '画像には最小の高さが必要です。',
	'abort' => 'ファイルのアップロードが中止されました。',
	'image_resize' => '画像サイズの変更ができませんでした。',
	'file_exists' => 'ファイルが存在しています。'
);
// PukiWiki attachプラグイン用設定 End

// PukiWiki attachプラグイン用継承拡張クラス Start
class exUploadHandler extends UploadHandler {
	protected function get_upload_path($file_name = null, $version = null) {
		// ファイル名＝エンコードページ名_エンコードファイル名
		$file_name = $file_name ? encode($this->page) . '_'
			. encode($file_name) : '';

		if (empty($version)) {
			$version_path = '';
		} else {
			$version_dir = @$this->options['image_versions'][$version]['upload_dir'];
			if ($version_dir) {
				return $version_dir.$this->get_user_path().$file_name;
			}
			$version_path = $version.'/';
		}
		return $this->options['upload_dir'].$this->get_user_path()
			.$version_path.$file_name;
	}

	protected function get_file_name($file_path, $name, $size, $type, $error,
		$index, $content_range) {
		$name = $this->trim_file_name($file_path, $name, $size, $type, $error,
			$index, $content_range);
		return $name;
	}

	protected function validate($uploaded_file, $file, $error, $index, $content_range) {
		if ($error) {
			$file->error = $this->get_error_message($error);
			return false;
		}
		// ファイル存在チェックエラー
		if ((! $this->overwrite) && file_exists($this->get_upload_path($file->name))) {
			$file->error = $this->get_error_message('file_exists');
			return false;
		}
		// アップロードファイル拒否エラー
		if (preg_match($this->options['reject_file_types'], $file->name)) {
			$file->error = $this->get_error_message('accept_file_types');
			return false;
		}
		$content_length = $this->fix_integer_overflow(
			(int)$this->get_server_var('CONTENT_LENGTH')
		);
		$post_max_size = $this->get_config_bytes(ini_get('post_max_size'));
		if ($post_max_size && ($content_length > $post_max_size)) {
			$file->error = $this->get_error_message('post_max_size');
			return false;
		}
		if (!preg_match($this->options['accept_file_types'], $file->name)) {
			$file->error = $this->get_error_message('accept_file_types');
			return false;
		}
		if ($uploaded_file && is_uploaded_file($uploaded_file)) {
			$file_size = $this->get_file_size($uploaded_file);
		} else {
			$file_size = $content_length;
		}
		if ($this->options['max_file_size'] && (
				$file_size > $this->options['max_file_size'] ||
				$file->size > $this->options['max_file_size'])
		) {
			$file->error = $this->get_error_message('max_file_size');
			return false;
		}
		if ($this->options['min_file_size'] &&
			$file_size < $this->options['min_file_size']) {
			$file->error = $this->get_error_message('min_file_size');
			return false;
		}
		if (is_int($this->options['max_number_of_files']) &&
			($this->count_file_objects() >= $this->options['max_number_of_files']) &&
			// Ignore additional chunks of existing files:
			!is_file($this->get_upload_path($file->name))) {
			$file->error = $this->get_error_message('max_number_of_files');
			return false;
		}
		if (!$content_range && $this->has_image_file_extension($file->name)) {
			return $this->validate_image_file($uploaded_file, $file, $error, $index);
		}
		return true;
	}

	public function post($print_response = true) {
		if ($this->get_query_param('_method') === 'DELETE') {
			return $this->delete($print_response);
		}
		// ページ名セット
		$this->page = $this->get_post_param('page');
		// 上書きフラグセット
		$this->overwrite = $this->get_post_param('overwrite');

		$upload = $this->get_upload_data($this->options['param_name']);
		// Parse the Content-Disposition header, if available:
		$content_disposition_header = $this->get_server_var('HTTP_CONTENT_DISPOSITION');
		$file_name = $content_disposition_header ?
			rawurldecode(preg_replace(
				'/(^[^"]+")|("$)/',
				'',
				$content_disposition_header
			)) : null;
		// Parse the Content-Range header, which has the following form:
		// Content-Range: bytes 0-524287/2000000
		$content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
		$content_range = $content_range_header ?
			preg_split('/[^0-9]+/', $content_range_header) : null;
		$size =  @$content_range[3];
		$files = array();
		if ($upload) {
			if (is_array($upload['tmp_name'])) {
				// param_name is an array identifier like "files[]",
				// $upload is a multi-dimensional array:
				foreach ($upload['tmp_name'] as $index => $value) {
					$files[] = $this->handle_file_upload(
						$upload['tmp_name'][$index],
						$file_name ? $file_name : $upload['name'][$index],
						$size ? $size : $upload['size'][$index],
						$upload['type'][$index],
						$upload['error'][$index],
						$index,
						$content_range
					);
				}
			} else {
				// param_name is a single object identifier like "file",
				// $upload is a one-dimensional array:
				$files[] = $this->handle_file_upload(
					isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
					$file_name ? $file_name : (isset($upload['name']) ?
						$upload['name'] : null),
					$size ? $size : (isset($upload['size']) ?
						$upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
					isset($upload['type']) ?
						$upload['type'] : $this->get_server_var('CONTENT_TYPE'),
					isset($upload['error']) ? $upload['error'] : null,
					null,
					$content_range
				);
			}
		}
		$response = array($this->options['param_name'] => $files);
		return $this->generate_response($response, $print_response);
	}
}
// PukiWiki attachプラグイン用継承拡張クラス End

// ファイルアップロード拡張クラス生成
$upload_handler = new exUploadHandler($options, TRUE, $error_messages);
