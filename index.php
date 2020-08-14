<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: index.php,v 1.9 2006/05/13 07:39:49 henoheno Exp $
// Copyright (C) 2001-2006 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version

/**
 * 修正情報
 *
 * @author	オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @since 	2020/08/14 ドキュメントルートからの絶対パス設定に変更
 *
 */

// Error reporting
//error_reporting(0); // Nothing

error_reporting(E_ERROR | E_PARSE); // Avoid E_WARNING, E_NOTICE, etc

//error_reporting(E_ALL); // Debug purpose

// Special
//define('PKWK_READONLY',  1);
//define('PKWK_SAFE_MODE', 1);
//define('PKWK_OPTIMISE',  1);
//define('TDIARY_THEME',   'digital_gadgets');

// Directory definition
// (Ended with a slash like '../path/to/pkwk/', or '')

/* ドキュメントルートからの絶対パス設定に変更
define('DATA_HOME',	'');
define('LIB_DIR',	'lib/');
*/
define('DATA_HOME',	$_SERVER['DOCUMENT_ROOT'] . '/');
define('LIB_DIR', DATA_HOME . 'lib/');

require(LIB_DIR . 'pukiwiki.php');
