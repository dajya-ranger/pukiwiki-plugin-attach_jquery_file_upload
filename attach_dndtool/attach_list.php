<?php
/**
 * attach_list.php
 *
 * 添付ファイルリスト出力プラグイン外部呼び出しモジュール
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

// 外部呼び出しEXIT要求フラグ
define('EXTERNAL_CALL_REQUIRE_EXIT', TRUE);
// 外部呼び出しプラグイン名
define('EXTERNAL_CALL_PLUGIN_NAME', 'attach_list');

// PukiWikiメインスクリプトファイル読み込み
require_once($_SERVER['DOCUMENT_ROOT'] . '/index.php');
// プラグインファイル読み込み
require_once(PLUGIN_DIR . EXTERNAL_CALL_PLUGIN_NAME . '.inc.php');

// 主処理
$body = '';
if (isset($_POST['args'])) {
	// 引数がセットされている場合
	if (exist_plugin_convert(EXTERNAL_CALL_PLUGIN_NAME)) {
		// ※プラグインを呼び出す際はexist_plugin_convertしないと呼び出せない
		$args = $_POST['args'];
		// プラグイン呼び出し
		$body = do_plugin_convert(EXTERNAL_CALL_PLUGIN_NAME, $args);
	}
	// HTMLコード（BODY部）出力
	echo $body;
}
