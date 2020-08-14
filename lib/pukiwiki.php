<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// pukiwiki.php
// Copyright
//   2002-2016 PukiWiki Development Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki main script

/**
 * 修正情報
 *
 * @author	オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @since 	2020/08/14 ドキュメントルートからの絶対パス設定に変更・外部呼び出しEXIT要求以外の場合はHTMLコンバート
 *
 */

/* ドキュメントルートからの絶対パス設定に変更
if (! defined('DATA_HOME')) define('DATA_HOME', '');
*/
if (! defined('DATA_HOME')) {
	define('DATA_HOME',	$_SERVER['DOCUMENT_ROOT'] . '/');
}

/////////////////////////////////////////////////
// Include subroutines

/* ドキュメントルートからの絶対パス設定に変更
if (! defined('LIB_DIR')) define('LIB_DIR', '');
*/
if (! defined('LIB_DIR')) {
	define('LIB_DIR', DATA_HOME . 'lib/');
}

require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');
require(LIB_DIR . 'backup.php');
require(LIB_DIR . 'convert_html.php');
require(LIB_DIR . 'make_link.php');
require(LIB_DIR . 'diff.php');
require(LIB_DIR . 'config.php');
require(LIB_DIR . 'link.php');
require(LIB_DIR . 'auth.php');

// 外部呼び出しEXIT要求以外の場合は読み込み
if (! defined('EXTERNAL_CALL_REQUIRE_EXIT')) {
	require(LIB_DIR . 'proxy.php');
}

if (! extension_loaded('mbstring')) {
	require(LIB_DIR . 'mbstring.php');
}

// Defaults
$notify = 0;

// Load *.ini.php files and init PukiWiki
require(LIB_DIR . 'init.php');

// Load optional libraries
if ($notify) {
	require(LIB_DIR . 'mail.php'); // Mail notification
}

// URL短縮ライブラリロード
require(LIB_DIR . 'shorturl.php');

// ページ名上書きセット
$vars['page'] = get_pagename_from_short_url($vars['page']);

/////////////////////////////////////////////////
// Main
if (manage_page_redirect()) {
	exit;
}

$retvars = array();
$is_cmd = FALSE;
if (isset($vars['cmd'])) {
	$is_cmd  = TRUE;
	$plugin = & $vars['cmd'];
} else if (isset($vars['plugin'])) {
	$plugin = & $vars['plugin'];
} else {
	$plugin = '';
}
if ($plugin != '') {
	ensure_valid_auth_user();
	if (exist_plugin_action($plugin)) {
		// Found and exec
		$retvars = do_plugin_action($plugin);
		if ($retvars === FALSE) exit; // Done

		if ($is_cmd) {
			$base = isset($vars['page'])  ? $vars['page']  : '';
		} else {
			$base = isset($vars['refer']) ? $vars['refer'] : '';
		}
	} else {
		// Not found
		$msg = 'plugin=' . htmlsc($plugin) .
			' is not implemented.';
		$retvars = array('msg'=>$msg,'body'=>$msg);
		$base    = & $defaultpage;
	}
}

$title = htmlsc(strip_bracket($base));
$page  = make_search($base);
if (isset($retvars['msg']) && $retvars['msg'] != '') {
	$title = str_replace('$1', $title, $retvars['msg']);
	$page  = str_replace('$1', $page,  $retvars['msg']);
}

if (isset($retvars['body']) && $retvars['body'] != '') {
	$body = & $retvars['body'];
} else {
	if ($base == '' || ! is_page($base)) {
		check_readable($defaultpage, true, true);
		$base  = & $defaultpage;
		$title = htmlsc(strip_bracket($base));
		$page  = make_search($base);
	}

	$vars['cmd']  = 'read';
	$vars['page'] = & $base;

	prepare_display_materials();
	/* 外部呼び出しEXIT要求以外の場合はHTMLコンバート
	$body  = convert_html(get_source($base));
	*/
	if (! defined('EXTERNAL_CALL_REQUIRE_EXIT')) {
		$body  = convert_html(get_source($base));
	}

}

// Output
catbody($title, $page, $body);
