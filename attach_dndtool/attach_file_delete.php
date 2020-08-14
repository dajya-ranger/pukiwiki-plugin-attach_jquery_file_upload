<?php
/**
 * attach_file_delete.php
 *
 * 添付ファイル削除モジュール
 *
 * @author		オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @copyright	Copyright © 2020, dajya-ranger.com
 * @link		https://dajya-ranger.com/pukiwiki/attach-jquery-file-upload/
 * @example		@linkの内容を参照
 * @license		Apache License 2.0
 * @version		0.1.0
 * @since 		0.1.0 2020/08/14 暫定初公開
 *
 */

// エラー出力レベル設定（実行時エラーとパースエラーのみ出力）
error_reporting(E_ERROR | E_PARSE);

// 主処理
if (isset($_POST['filename'])) {
	$filename = $_POST['filename'];
	// ファイル削除
	unlink($filename);
	if (file_exists($filename . '.log')) {
		// ログファイルが存在する場合は一緒に削除
		unlink($filename . '.log');
	}
}
