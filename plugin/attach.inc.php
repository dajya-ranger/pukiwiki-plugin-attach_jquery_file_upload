<?php
// PukiWiki - Yet another WikiWikiWeb clone
// attach.inc.php
// Copyright
//   2003-2020 PukiWiki Development Team
//   2002-2003 PANDA <panda@arino.jp> http://home.arino.jp/
//   2002      Y.MASUI <masui@hisec.co.jp> http://masui.net/pukiwiki/
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// File attach plugin

/**
 * 修正情報
 *
 * PukiWiki attach.inc.php
 * ドラッグ＆ドロップファイルアップロード対応attachプラグイン
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

// 添付ファイルをドラッグ＆ドロップでアップロードできるようにする
// FALSE:従来オペレーション・TRUE:ドラッグ＆ドロップアップロード
define('PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP', TRUE);
// ファイルを上書きでアップロードする（ドラッグ＆ドロップのみ有効）
// FALSE:ファイル存在チェック（推奨）・TRUE:常にファイル上書きアップロード
define('PLUGIN_ATTACH_UPLOAD_OVERWRITE', FALSE); // FALSE or TRUE
// 新規作成ページでもドラッグ＆ドロップでアップロードする
// TRUE:ドラッグ＆ドロップでアップロード可（非推奨）
// FALSE:ページの新規作成時のみドラッグ＆ドロップでアップロード不可（推奨）
define('PLUGIN_ATTACH_UPLOAD_NEW_PAGE', FALSE); // FALSE or TRUE
// ドラッグ＆ドロップアップロードツールフォルダ
define('PLUGIN_ATTACH_UPLOAD_TOOL_DIR', '/attach_dndtool/');
// 添付ファイルリスト出力プラグイン名
define('PLUGIN_ATTACH_UPLOAD_FILE_LIST', 'attach_list');
// 添付ファイル削除モジュール
define('PLUGIN_ATTACH_UPLOAD_FILE_DELETE', 'attach_file_delete.php');

// 添付ファイルリスト出力プラグインファイル読み込み
require_once(PLUGIN_DIR . PLUGIN_ATTACH_UPLOAD_FILE_LIST . '.inc.php');

// NOTE (PHP > 4.2.3):
//    This feature is disabled at newer version of PHP.
//    Set this at php.ini if you want.
// Max file size for upload on PHP (PHP default: 2MB)
ini_set('upload_max_filesize', '30M');

// Max file size for upload on script of PukiWikiX_FILESIZE
define('PLUGIN_ATTACH_MAX_FILESIZE', ((1024 * 1024)*30)); // default: 1MB

// 管理者だけが添付ファイルをアップロードできるようにする
define('PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY', TRUE); // FALSE or TRUE

// 管理者だけが添付ファイルを削除できるようにする
define('PLUGIN_ATTACH_DELETE_ADMIN_ONLY', TRUE); // FALSE or TRUE

// 管理者が添付ファイルを削除するときは、バックアップを作らない
// PLUGIN_ATTACH_DELETE_ADMIN_ONLY=TRUEのとき有効
define('PLUGIN_ATTACH_DELETE_ADMIN_NOBACKUP', TRUE); // FALSE or TRUE

// アップロード/削除時にパスワードを要求する(ADMIN_ONLYが優先)
define('PLUGIN_ATTACH_PASSWORD_REQUIRE', FALSE); // FALSE or TRUE

// 添付ファイル名を変更できるようにする
define('PLUGIN_ATTACH_RENAME_ENABLE', TRUE); // FALSE or TRUE

// ファイルのアクセス権
define('PLUGIN_ATTACH_FILE_MODE', 0644);
//define('PLUGIN_ATTACH_FILE_MODE', 0604); // for XREA.COM

// File icon image
define('PLUGIN_ATTACH_FILE_ICON', '<img src="' . IMAGE_DIR .  'file.png"' .
	' width="20" height="20" alt="file"' .
	' style="border-width:0" />');

// mime-typeを記述したページ
define('PLUGIN_ATTACH_CONFIG_PAGE_MIME', 'plugin/attach/mime-type');

//-------- convert
function plugin_attach_convert()
{
	global $vars;

	$page = isset($vars['page']) ? $vars['page'] : '';

	$nolist = $noform = FALSE;
	if (func_num_args() > 0) {
		foreach (func_get_args() as $arg) {
			$arg = strtolower($arg);
			$nolist |= ($arg == 'nolist');
			$noform |= ($arg == 'noform');
		}
	}

	$ret = '';
	if (! $nolist) {
		$obj  = new AttachPages($page);
		$ret .= $obj->toString($page, TRUE);
	}
	if (! $noform) {
		if (PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP) {
			// ドラッグ＆ドロップフォーム
			$ret .= attach_form_dnd($page);
		} else {
			// 従来フォーム
			$ret .= attach_form($page);
		}
	}

	return $ret;
}

//-------- action
function plugin_attach_action()
{
	global $vars, $_attach_messages;

	// Backward compatible
	if (isset($vars['openfile'])) {
		$vars['file'] = $vars['openfile'];
		$vars['pcmd'] = 'open';
	}
	if (isset($vars['delfile'])) {
		$vars['file'] = $vars['delfile'];
		$vars['pcmd'] = 'delete';
	}

	$pcmd  = isset($vars['pcmd'])  ? $vars['pcmd']  : '';
	$refer = isset($vars['refer']) ? $vars['refer'] : '';
	$pass  = isset($vars['pass'])  ? $vars['pass']  : NULL;
	$page  = isset($vars['page'])  ? $vars['page']  : '';

	if ($refer === '' && $page !== '') {
		$refer = $page;
	}
	if ($refer != '' && is_pagename($refer)) {
		if(in_array($pcmd, array('info', 'open', 'list'))) {
			check_readable($refer);
		} else {
			check_editable($refer);
		}
	}

	// Dispatch
	if (isset($_FILES['attach_file'])) {
		// 従来フォームアップロード
		return attach_upload($_FILES['attach_file'], $refer, $pass);
	} else {
		switch ($pcmd) {
		case 'delete':	/*FALLTHROUGH*/
		case 'freeze':
		case 'unfreeze':
			if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');
		}
		switch ($pcmd) {
		case 'info'     : return attach_info();
		case 'delete'   : return attach_delete();
		case 'open'     : return attach_open();
		case 'list'     : return attach_list();
		case 'freeze'   : return attach_freeze(TRUE);
		case 'unfreeze' : return attach_freeze(FALSE);
		case 'rename'   : return attach_rename();
		case 'upload'   : return attach_showform();
		}
		if ($page == '' || ! is_page($page)) {
			return attach_list();
		} else {
			return attach_showform();
		}
	}
}

//-------- call from skin
function attach_filelist()
{
	global $vars, $_attach_messages;

	$page = isset($vars['page']) ? $vars['page'] : '';

	$obj = new AttachPages($page, 0);

	if (! isset($obj->pages[$page])) {
		return '';
	} else {
		return $_attach_messages['msg_file'] . ': ' .
		$obj->toString($page, TRUE) . "\n";
	}
}

//-------- 実体
// ファイルアップロード
// $pass = NULL : パスワードが指定されていない
// $pass = TRUE : アップロード許可
function attach_upload($file, $page, $pass = NULL)
{
	global $_attach_messages, $notify, $notify_subject;

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	// Check query-string
	$query = 'plugin=attach&amp;pcmd=info&amp;refer=' . rawurlencode($page) .
		'&amp;file=' . rawurlencode($file['name']);

	if (PKWK_QUERY_STRING_MAX && strlen($query) > PKWK_QUERY_STRING_MAX) {
		pkwk_common_headers();
		echo('Query string (page name and/or file name) too long');
		exit;
	} else if (! is_page($page)) {
		die_message('No such page');
	} else if ($file['tmp_name'] == '' || ! is_uploaded_file($file['tmp_name'])) {
		return array('result'=>FALSE);
	} else if ($file['size'] > PLUGIN_ATTACH_MAX_FILESIZE) {
		return array(
			'result'=>FALSE,
			'msg'=>$_attach_messages['err_exceed']);
	} else if (! is_pagename($page) || ($pass !== TRUE && ! is_editable($page))) {
		return array(
			'result'=>FALSE,'
			msg'=>$_attach_messages['err_noparm']);
	} else if (PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY && $pass !== TRUE &&
		  ($pass === NULL || ! pkwk_login($pass))) {
		return array(
			'result'=>FALSE,
			'msg'=>$_attach_messages['err_adminpass']);
	}

	$obj = new AttachFile($page, $file['name']);
	if ($obj->exist)
		return array('result'=>FALSE,
			'msg'=>$_attach_messages['err_exists']);

	if (move_uploaded_file($file['tmp_name'], $obj->filename))
		chmod($obj->filename, PLUGIN_ATTACH_FILE_MODE);

	if (is_page($page))
		pkwk_touch_file(get_filename($page));

	$obj->getstatus();
	$obj->status['pass'] = ($pass !== TRUE && $pass !== NULL) ? md5($pass) : '';
	$obj->putstatus();

	if ($notify) {
		$footer['ACTION']   = 'File attached';
		$footer['FILENAME'] = $file['name'];
		$footer['FILESIZE'] = $file['size'];
		$footer['PAGE']     = $page;

		$footer['URI']      = get_base_uri(PKWK_URI_ABSOLUTE) .
			// MD5 may heavy
			'?plugin=attach' .
				'&refer=' . rawurlencode($page) .
				'&file='  . rawurlencode($file['name']) .
				'&pcmd=info';

		$footer['USER_AGENT']  = TRUE;
		$footer['REMOTE_ADDR'] = TRUE;

		pkwk_mail_notify($notify_subject, "\n", $footer) or
			die('pkwk_mail_notify(): Failed');
	}

	return array(
		'result'=>TRUE,
		'msg'=>$_attach_messages['msg_uploaded']);
}

// 詳細フォームを表示
function attach_info($err = '')
{
	global $vars, $_attach_messages;

	foreach (array('refer', 'file', 'age') as $var)
		${$var} = isset($vars[$var]) ? $vars[$var] : '';

	$obj = new AttachFile($refer, $file, $age);
	return $obj->getstatus() ?
		$obj->info($err) :
		array('msg'=>$_attach_messages['err_notfound']);
}

// 削除
function attach_delete()
{
	global $vars, $_attach_messages;

	foreach (array('refer', 'file', 'age', 'pass') as $var)
		${$var} = isset($vars[$var]) ? $vars[$var] : '';

	if (is_freeze($refer) || ! is_editable($refer))
		return array('msg'=>$_attach_messages['err_noparm']);

	$obj = new AttachFile($refer, $file, $age);
	if (! $obj->getstatus())
		return array('msg'=>$_attach_messages['err_notfound']);
		
	return $obj->delete($pass);
}

// 凍結
function attach_freeze($freeze)
{
	global $vars, $_attach_messages;

	foreach (array('refer', 'file', 'age', 'pass') as $var) {
		${$var} = isset($vars[$var]) ? $vars[$var] : '';
	}

	if (is_freeze($refer) || ! is_editable($refer)) {
		return array('msg'=>$_attach_messages['err_noparm']);
	} else {
		$obj = new AttachFile($refer, $file, $age);
		return $obj->getstatus() ?
			$obj->freeze($freeze, $pass) :
			array('msg'=>$_attach_messages['err_notfound']);
	}
}

// リネーム
function attach_rename()
{
	global $vars, $_attach_messages;

	foreach (array('refer', 'file', 'age', 'pass', 'newname') as $var) {
		${$var} = isset($vars[$var]) ? $vars[$var] : '';
	}

	if (is_freeze($refer) || ! is_editable($refer)) {
		return array('msg'=>$_attach_messages['err_noparm']);
	}
	$obj = new AttachFile($refer, $file, $age);
	if (! $obj->getstatus())
		return array('msg'=>$_attach_messages['err_notfound']);

	return $obj->rename($pass, $newname);

}

// ダウンロード
function attach_open()
{
	global $vars, $_attach_messages;

	foreach (array('refer', 'file', 'age') as $var) {
		${$var} = isset($vars[$var]) ? $vars[$var] : '';
	}

	$obj = new AttachFile($refer, $file, $age);
	return $obj->getstatus() ?
		$obj->open() :
		array('msg'=>$_attach_messages['err_notfound']);
}

// 一覧取得
function attach_list()
{
	global $vars, $_attach_messages;

	$refer = isset($vars['refer']) ? $vars['refer'] : '';

	$obj = new AttachPages($refer);

	$msg = $_attach_messages[($refer == '') ? 'msg_listall' : 'msg_listpage'];
	$body = ($refer == '' || isset($obj->pages[$refer])) ?
		$obj->toString($refer, FALSE) :
		$_attach_messages['err_noexist'];

	return array('msg'=>$msg, 'body'=>$body);
}

// アップロードフォームを表示 (action時)
function attach_showform()
{
	global $vars, $_attach_messages;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$vars['refer'] = $page;
	if (PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP) {
		// ドラッグ＆ドロップフォーム
		$body = attach_form_dnd($page, FALSE);
	} else {
		// 従来フォーム
		$body = attach_form($page);
	}
	return array('msg'=>$_attach_messages['msg_upload'], 'body'=>$body);
}

//-------- サービス
// mime-typeの決定
function attach_mime_content_type($filename, $displayname)
{
	$type = 'application/octet-stream'; // default

	if (! file_exists($filename)) return $type;
	$pathinfo = pathinfo($displayname);
	$ext0 = $pathinfo['extension'];
	if (preg_match('/^(gif|jpg|jpeg|png|swf)$/i', $ext0)) {
		$size = @getimagesize($filename);
		if (is_array($size)) {
			switch ($size[2]) {
				case 1: return 'image/gif';
				case 2: return 'image/jpeg';
				case 3: return 'image/png';
				case 4: return 'application/x-shockwave-flash';
			}
		}
	}
	// mime-type一覧表を取得
	$config = new Config(PLUGIN_ATTACH_CONFIG_PAGE_MIME);
	$table = $config->read() ? $config->get('mime-type') : array();
	unset($config); // メモリ節約
	foreach ($table as $row) {
		$_type = trim($row[0]);
		$exts = preg_split('/\s+|,/', trim($row[1]), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($exts as $ext) {
			if (preg_match("/\.$ext$/i", $displayname)) return $_type;
		}
	}
	return $type;
}

// アップロードフォームの出力
function attach_form($page)
{
	global $vars, $_attach_messages;

	$script = get_base_uri();
	$r_page = rawurlencode($page);
	$s_page = htmlsc($page);
	$navi = <<<EOD
  <span class="small">
   [<a href="$script?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$_attach_messages['msg_list']}</a>]
   [<a href="$script?plugin=attach&amp;pcmd=list">{$_attach_messages['msg_listall']}</a>]
  </span><br />
EOD;

	if (! ini_get('file_uploads')) return '#attach(): file_uploads disabled<br />' . $navi;
	if (! is_page($page))          return '#attach(): No such page<br />'          . $navi;

	$maxsize = PLUGIN_ATTACH_MAX_FILESIZE;
	$msg_maxsize = sprintf($_attach_messages['msg_maxsize'], number_format($maxsize/1024) . 'KB');

	$pass = '';
	if (PLUGIN_ATTACH_PASSWORD_REQUIRE || PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY) {
		$title = $_attach_messages[PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY ? 'msg_adminpass' : 'msg_password'];
		$pass = '<br />' . $title . ': <input type="password" name="pass" size="8" />';
	}
	return <<<EOD
<form enctype="multipart/form-data" action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="pcmd"   value="post" />
  <input type="hidden" name="refer"  value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  $navi
  <span class="small">
   $msg_maxsize
  </span><br />
  <label for="_p_attach_file">{$_attach_messages['msg_file']}:</label> <input type="file" name="attach_file" id="_p_attach_file" />
  $pass
  <input type="submit" value="{$_attach_messages['btn_upload']}" />
 </div>
</form>
EOD;
}

//-------- クラス
// ファイル
class AttachFile
{
	var $page, $file, $age, $basename, $filename, $logname;
	var $time = 0;
	var $size = 0;
	var $time_str = '';
	var $size_str = '';
	var $status = array('count'=>array(0), 'age'=>'', 'pass'=>'', 'freeze'=>FALSE);

	function AttachFile($page, $file, $age = 0)
	{
		$this->__construct($page, $file, $age);
	}
	function __construct($page, $file, $age = 0)
	{
		$this->page = $page;
		$this->file = preg_replace('#^.*/#','',$file);
		$this->age  = is_numeric($age) ? $age : 0;

		$this->basename = UPLOAD_DIR . encode($page) . '_' . encode($this->file);
		$this->filename = $this->basename . ($age ? '.' . $age : '');
		$this->logname  = $this->basename . '.log';
		$this->exist    = file_exists($this->filename);
		$this->time     = $this->exist ? filemtime($this->filename) - LOCALZONE : 0;
	}

	function gethash()
	{
		return $this->exist ? md5_file($this->filename) : '';
	}

	// ファイル情報取得
	function getstatus()
	{
		// ログファイル取得
		if (file_exists($this->logname)) {
			$data = file($this->logname);
			foreach ($this->status as $key=>$value) {
				$this->status[$key] = chop(array_shift($data));
			}
			$this->status['count'] = explode(',', $this->status['count']);
		}
		if (! $this->exist) return FALSE;
		$this->time_str = get_date('Y/m/d H:i:s', $this->time);
		$this->size     = filesize($this->filename);
		$this->size_str = sprintf('%01.1f', round($this->size/1024, 1)) . 'KB';
		$this->type     = attach_mime_content_type($this->filename, $this->file);
		return TRUE;
	}

	// ステータス保存
	function putstatus()
	{
		$this->status['count'] = join(',', $this->status['count']);
		$fp = fopen($this->logname, 'wb') or
			die_message('cannot write ' . $this->logname);
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		foreach ($this->status as $key=>$value) {
			fwrite($fp, $value . "\n");
		}
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	// 日付の比較関数
	function datecomp($a, $b) {
		return ($a->time == $b->time) ? 0 : (($a->time > $b->time) ? -1 : 1);
	}

	function toString($showicon, $showinfo)
	{
		global $_attach_messages;

		$script = get_base_uri();
		$this->getstatus();
		$param  = '&amp;file=' . rawurlencode($this->file) . '&amp;refer=' . rawurlencode($this->page) .
			($this->age ? '&amp;age=' . $this->age : '');
		$title = $this->time_str . ' ' . $this->size_str;
		$label = ($showicon ? PLUGIN_ATTACH_FILE_ICON : '') . htmlsc($this->file);
		if ($this->age) {
			$label .= ' (backup No.' . $this->age . ')';
		}
		$info = $count = '';
		if ($showinfo) {
			$_title = str_replace('$1', rawurlencode($this->file), $_attach_messages['msg_info']);
			$info = "\n<span class=\"small\">[<a href=\"$script?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$_attach_messages['btn_info']}</a>]</span>\n";
			$count = ($showicon && ! empty($this->status['count'][$this->age])) ?
				sprintf($_attach_messages['msg_count'], $this->status['count'][$this->age]) : '';
		}
		return "<a href=\"$script?plugin=attach&amp;pcmd=open$param\" title=\"$title\" target=\"_blank\">$label</a>$count$info";
	}

	// 情報表示
	function info($err)
	{
		global $_attach_messages;

		$script = get_base_uri();
		$r_page = rawurlencode($this->page);
		$s_page = htmlsc($this->page);
		$s_file = htmlsc($this->file);
		$s_err = ($err == '') ? '' : '<p style="font-weight:bold">' . $_attach_messages[$err] . '</p>';

		$msg_rename  = '';
		if ($this->age) {
			$msg_freezed = '';
			$msg_delete  = '<input type="radio" name="pcmd" id="_p_attach_delete" value="delete" />' .
				'<label for="_p_attach_delete">' .  $_attach_messages['msg_delete'] .
				$_attach_messages['msg_require'] . '</label><br />';
			$msg_freeze  = '';
		} else {
			if ($this->status['freeze']) {
				$msg_freezed = "<dd>{$_attach_messages['msg_isfreeze']}</dd>";
				$msg_delete  = '';
				$msg_freeze  = '<input type="radio" name="pcmd" id="_p_attach_unfreeze" value="unfreeze" />' .
					'<label for="_p_attach_unfreeze">' .  $_attach_messages['msg_unfreeze'] .
					$_attach_messages['msg_require'] . '</label><br />';
			} else {
				$msg_freezed = '';
				$msg_delete = '<input type="radio" name="pcmd" id="_p_attach_delete" value="delete" />' .
					'<label for="_p_attach_delete">' . $_attach_messages['msg_delete'];
				if (PLUGIN_ATTACH_DELETE_ADMIN_ONLY || $this->age)
					$msg_delete .= $_attach_messages['msg_require'];
				$msg_delete .= '</label><br />';
				$msg_freeze  = '<input type="radio" name="pcmd" id="_p_attach_freeze" value="freeze" />' .
					'<label for="_p_attach_freeze">' .  $_attach_messages['msg_freeze'] .
					$_attach_messages['msg_require'] . '</label><br />';

				if (PLUGIN_ATTACH_RENAME_ENABLE) {
					$msg_rename  = '<input type="radio" name="pcmd" id="_p_attach_rename" value="rename" />' .
						'<label for="_p_attach_rename">' .  $_attach_messages['msg_rename'] .
						$_attach_messages['msg_require'] . '</label><br />&nbsp;&nbsp;&nbsp;&nbsp;' .
						'<label for="_p_attach_newname">' . $_attach_messages['msg_newname'] .
						':</label> ' .
						'<input type="text" name="newname" id="_p_attach_newname" size="40" value="' .
						$this->file . '" /><br />';
				}
			}
		}
		$info = $this->toString(TRUE, FALSE);
		$hash = $this->gethash();

		$retval = array('msg'=>sprintf($_attach_messages['msg_info'], htmlsc($this->file)));

		// キャンセルボタンonclick設定
		$root_url = PLUGIN_ATTACH_UPLOAD_SERVER . $_SERVER['HTTP_HOST'];
		$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		$root_url = $protocol . $_SERVER['HTTP_HOST'];		$cancel  = "location.href=";
		$cancel .= "'$root_url/?plugin=attach&amp;pcmd=upload&amp;page=";
		$cancel .= urlencode($this->page) . "'";
		// 添付ファイルリスト取得
		if (exist_plugin_convert(PLUGIN_ATTACH_UPLOAD_FILE_LIST)) {
			// ※プラグインを呼び出す際はexist_plugin_convertしないと呼び出せない
			$args = "$this->page,full,nodel";
			$attach_list = do_plugin_convert(PLUGIN_ATTACH_UPLOAD_FILE_LIST, $args);
		}

		$retval['body'] = <<< EOD
<p class="small">
 [<a href="$script?plugin=attach&amp;pcmd=list">{$_attach_messages['msg_listall']}</a>]
</p>
<dl>
 <dt>$info</dt>
 <dd>{$_attach_messages['msg_page']}:$s_page</dd>
 <dd>{$_attach_messages['msg_filename']}:{$this->filename}</dd>
 <dd>{$_attach_messages['msg_md5hash']}:$hash</dd>
 <dd>{$_attach_messages['msg_filesize']}:{$this->size_str} ({$this->size} bytes)</dd>
 <dd>Content-type:{$this->type}</dd>
 <dd>{$_attach_messages['msg_date']}:{$this->time_str}</dd>
 <dd>{$_attach_messages['msg_dlcount']}:{$this->status['count'][$this->age]}</dd>
 $msg_freezed
</dl>
<hr />
$s_err
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  $msg_delete
  $msg_freeze
  $msg_rename
  <br />
  <label for="_p_attach_password">{$_attach_messages['msg_password']}:</label>
  <input type="password" name="pass" id="_p_attach_password" size="8" />
  <input type="submit" value="{$_attach_messages['btn_submit']}" />
  <input type="button" value="キャンセル" onclick="$cancel" />
 </div>
</form>
<br />
$attach_list
EOD;
		return $retval;
	}

	function delete($pass)
	{
		global $_attach_messages, $notify, $notify_subject;

		if ($this->status['freeze']) return attach_info('msg_isfreeze');

		if (! pkwk_login($pass)) {
			if (PLUGIN_ATTACH_DELETE_ADMIN_ONLY || $this->age) {
				return attach_info('err_adminpass');
			} else if (PLUGIN_ATTACH_PASSWORD_REQUIRE &&
				md5($pass) !== $this->status['pass']) {
				return attach_info('err_password');
			}
		}

		// バックアップ
		if ($this->age ||
			(PLUGIN_ATTACH_DELETE_ADMIN_ONLY && PLUGIN_ATTACH_DELETE_ADMIN_NOBACKUP)) {
			@unlink($this->filename);
		} else {
			do {
				$age = ++$this->status['age'];
			} while (file_exists($this->basename . '.' . $age));

			if (! rename($this->basename,$this->basename . '.' . $age)) {
				// 削除失敗 why?
				return array('msg'=>$_attach_messages['err_delete']);
			}

			$this->status['count'][$age] = $this->status['count'][0];
			$this->status['count'][0] = 0;
			$this->putstatus();
		}

		if (is_page($this->page))
			touch(get_filename($this->page));

		if ($notify) {
			$footer['ACTION']   = 'File deleted';
			$footer['FILENAME'] = $this->file;
			$footer['PAGE']     = $this->page;
			$footer['URI']      = get_page_uri($this->page, PKWK_URI_ABSOLUTE);
			$footer['USER_AGENT']  = TRUE;
			$footer['REMOTE_ADDR'] = TRUE;
			pkwk_mail_notify($notify_subject, "\n", $footer) or
				die('pkwk_mail_notify(): Failed');
		}

		if (PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP) {
			// ドラッグ＆ドロップフォーム
			$body = attach_form_dnd($this->page, FALSE);
			return array('msg'=>$_attach_messages['msg_deleted'], 'body'=>$body);
		} else {
			// 従来フォーム
			return array('msg'=>$_attach_messages['msg_deleted']);
		}
	}

	function rename($pass, $newname)
	{
		global $_attach_messages, $notify, $notify_subject;

		if ($this->status['freeze']) return attach_info('msg_isfreeze');

		if (! pkwk_login($pass)) {
			if (PLUGIN_ATTACH_DELETE_ADMIN_ONLY || $this->age) {
				return attach_info('err_adminpass');
			} else if (PLUGIN_ATTACH_PASSWORD_REQUIRE &&
				md5($pass) !== $this->status['pass']) {
				return attach_info('err_password');
			}
		}
		$newbase = UPLOAD_DIR . encode($this->page) . '_' . encode($newname);
		if (file_exists($newbase)) {
			return array('msg'=>$_attach_messages['err_exists']);
		}
		if (! PLUGIN_ATTACH_RENAME_ENABLE) {
			return array('msg'=>$_attach_messages['err_rename']);
		}
		if (! rename($this->basename, $newbase)) {
			return array('msg'=>$_attach_messages['err_rename']);
		}
		// Rename primary file succeeded.
		// Then, rename backup(archive) files and log file)
		$rename_targets = array();
		$dir = opendir(UPLOAD_DIR);
		if ($dir) {
			$matches_leaf = array();
			if (preg_match('/(((?:[0-9A-F]{2})+)_((?:[0-9A-F]{2})+))$/', $this->basename, $matches_leaf)) {
				$attachfile_leafname = $matches_leaf[1];
				$attachfile_leafname_pattern = preg_quote($attachfile_leafname, '/');
				$pattern = "/^({$attachfile_leafname_pattern})(\.((\d+)|(log)))$/";
				$matches = array();
				while ($file = readdir($dir)) {
					if (! preg_match($pattern, $file, $matches))
						continue;
					$basename2 = $matches[0];
					$newbase2 = $newbase . $matches[2];
					$rename_targets[$basename2] = $newbase2;
				}
			}
			closedir($dir);
		}
		foreach ($rename_targets as $basename2=>$newbase2) {
			$basename2path = UPLOAD_DIR . $basename2;
			rename($basename2path, $newbase2);
		}

		if (PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP) {
			// ドラッグ＆ドロップフォーム
			$body = attach_form_dnd($this->page, FALSE);
			return array('msg'=>$_attach_messages['msg_renamed'], 'body'=>$body);
		} else {
			// 従来フォーム
			return array('msg'=>$_attach_messages['msg_renamed']);
		}
	}

	function freeze($freeze, $pass)
	{
		global $_attach_messages;

		if (! pkwk_login($pass)) return attach_info('err_adminpass');

		$this->getstatus();
		$this->status['freeze'] = $freeze;
		$this->putstatus();

		if (PLUGIN_ATTACH_UPLOAD_DRAG_AND_DROP) {
			// ドラッグ＆ドロップフォーム
			$body = attach_form_dnd($this->page, FALSE);
			return array('msg'=>$_attach_messages[$freeze ? 'msg_freezed' : 'msg_unfreezed'], 'body'=>$body);
		} else {
			// 従来フォーム
			return array('msg'=>$_attach_messages[$freeze ? 'msg_freezed' : 'msg_unfreezed']);
		}
	}

	function open()
	{
		$this->getstatus();
		$this->status['count'][$this->age]++;
		$this->putstatus();
		$filename = $this->file;

		// Care for Japanese-character-included file name
		$legacy_filename = mb_convert_encoding($filename, 'UTF-8', SOURCE_ENCODING);
		if (LANG == 'ja') {
			switch(UA_NAME . '/' . UA_PROFILE){
			case 'MSIE/default':
				$legacy_filename = mb_convert_encoding($filename, 'SJIS', SOURCE_ENCODING);
				break;
			}
		}
		$utf8filename = mb_convert_encoding($filename, 'UTF-8', SOURCE_ENCODING);

		ini_set('default_charset', '');
		mb_http_output('pass');

		pkwk_common_headers();
		header('Content-Disposition: inline; filename="' . $legacy_filename
			. '"; filename*=utf-8\'\'' . rawurlencode($utf8filename));
		header('Content-Length: ' . $this->size);
		header('Content-Type: '   . $this->type);
		// Disable output bufferring
		while (ob_get_level()) {
			ob_end_flush();
		}
		flush();
		@readfile($this->filename);
		exit;
	}
}

// ファイルコンテナ
class AttachFiles
{
	var $page;
	var $files = array();

	function AttachFiles($page)
	{
		$this->__construct($page);
	}
	function __construct($page)
	{
		$this->page = $page;
	}

	function add($file, $age)
	{
		$this->files[$file][$age] = new AttachFile($this->page, $file, $age);
	}

	// ファイル一覧を取得
	function toString($flat)
	{
		global $_title_cannotread;

		if (! check_readable($this->page, FALSE, FALSE)) {
			return str_replace('$1', make_pagelink($this->page), $_title_cannotread);
		} else if ($flat) {
			return $this->to_flat();
		}

		$ret = '';
		$files = array_keys($this->files);
		sort($files, SORT_STRING);

		foreach ($files as $file) {
			$_files = array();
			foreach (array_keys($this->files[$file]) as $age) {
				$_files[$age] = $this->files[$file][$age]->toString(FALSE, TRUE);
			}
			if (! isset($_files[0])) {
				$_files[0] = htmlsc($file);
			}
			ksort($_files, SORT_NUMERIC);
			$_file = $_files[0];
			unset($_files[0]);
			$ret .= " <li>$_file\n";
			if (count($_files)) {
				$ret .= "<ul>\n<li>" . join("</li>\n<li>", $_files) . "</li>\n</ul>\n";
			}
			$ret .= " </li>\n";
		}
		// 全ページの添付ファイル一覧でページ名に [詳細] リンクを追加
		//return make_pagelink($this->page) . "\n<ul>\n$ret</ul>\n";
		$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		$root_url = $protocol . $_SERVER['HTTP_HOST'];
		$page_detail = " [<a href=\"$root_url/?plugin=attach&amp;pcmd=upload&amp;page=" . urlencode($this->page) . "\">" . '詳細' . '</a>]';

		return make_pagelink($this->page) . $page_detail . "\n<ul>\n$ret</ul>\n";
	}

	// ファイル一覧を取得(inline)
	function to_flat()
	{
		$ret = '';
		$files = array();
		foreach (array_keys($this->files) as $file) {
			if (isset($this->files[$file][0])) {
				$files[$file] = & $this->files[$file][0];
			}
		}
		uasort($files, array('AttachFile', 'datecomp'));
		foreach (array_keys($files) as $file) {
			$ret .= $files[$file]->toString(TRUE, TRUE) . ' ';
		}

		return $ret;
	}
}

// ページコンテナ
class AttachPages
{
	var $pages = array();

	function AttachPages($page = '', $age = NULL)
	{
		$this->__construct($page, $age);
	}
	function __construct($page = '', $age = NULL)
	{

		$dir = opendir(UPLOAD_DIR) or
			die('directory ' . UPLOAD_DIR . ' is not exist or not readable.');

		$page_pattern = ($page == '') ? '(?:[0-9A-F]{2})+' : preg_quote(encode($page), '/');
		$age_pattern = ($age === NULL) ?
			'(?:\.([0-9]+))?' : ($age ?  "\.($age)" : '');
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";

		$matches = array();
		while (($file = readdir($dir)) !== FALSE) {
			if (! preg_match($pattern, $file, $matches)) continue;

			$_page = decode($matches[1]);
			$_file = decode($matches[2]);
			$_age  = isset($matches[3]) ? $matches[3] : 0;
			if (! isset($this->pages[$_page])) {
				$this->pages[$_page] = new AttachFiles($_page);
			}
			$this->pages[$_page]->add($_file, $_age);
		}
		closedir($dir);
	}

	function toString($page = '', $flat = FALSE)
	{
		if ($page != '') {
			if (! isset($this->pages[$page])) {
				return '';
			} else {
				return $this->pages[$page]->toString($flat);
			}
		}
		$ret = '';

		$pages = array_keys($this->pages);
		sort($pages, SORT_STRING);

		foreach ($pages as $page) {
			if (check_non_list($page)) continue;
			$ret .= '<li>' . $this->pages[$page]->toString($flat) . '</li>' . "\n";
		}
		return "\n" . '<ul>' . "\n" . $ret . '</ul>' . "\n";
	}
}

/**
 * ドラッグ＆ドロップアップロード拡張
 *
 * @author	オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @since 	2020/07/17 jQuery-File-Upload組み込み
 *
 */
function attach_form_dnd($page, $is_page_edit = FALSE) {
	global $vars, $_attach_messages;
	global $adminpass;
	global $head_tags;

	// ヒアドキュメント展開用
	$_ = function($const){return $const;};

	// ページリードオンリーチェック
	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	// ページ有効チェック
	$newpage = "";
	$is_new_page = strpos($_SERVER['REQUEST_URI'],'?cmd=read');
	if (! is_page($page)) {
		// ページが存在しない
		if (! is_new_page) {
			// 新規作成ページでもない
			die_message('No such page');
		} else {
			// 新規作成ページの場合はページ名をセットする
			$page = urldecode(substr($_SERVER['REQUEST_URI'],
				strpos($_SERVER['REQUEST_URI'],'page=') + 5,
				(strpos($_SERVER['REQUEST_URI'],'&refer=') - 
					(strpos($_SERVER['REQUEST_URI'],'page=') + 5))));
			// 添付ファイルリスト出力新規作成ページ指定オプションセット
			$newpage = ",newpage";
		}
	}

	// 管理者ユーザチェック
	$is_admin = pkwk_login($_SESSION['authenticated_password']);
	if (PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY && !($is_admin) && !($is_page_edit)) {
		// 管理者のみ添付ファイルをアップロード可で管理者ユーザでない場合
		// かつページ編集画面ではない場合
		die_message('File attachments are for admin users only');
	}
	$nodel = "";
	if (PLUGIN_ATTACH_DELETE_ADMIN_ONLY) {
		// 管理者のみ添付ファイル削除可の設定の場合
		if (! $is_admin) {
			// 管理者ユーザ以外は削除ボタンの表示を抑止する
			$nodel = ",nodel";
		}
	}

	// Bootstrapスタイル（アップロード操作ボタン＋アイコン）
	$head_tags[] = " <link rel=\"stylesheet\" href=\"{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}css/bootstrap.sub.css\" />";
	// ファイル入力フィールドボタンとBootstrapプログレスバーのCSS
	$head_tags[] = " <link rel=\"stylesheet\" href=\"{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}css/jquery.fileupload.css\" />";
	$head_tags[] = " <link rel=\"stylesheet\" href=\"{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}css/jquery.fileupload-ui.css\" />";
	// ソートテーブルプラグイン用定義（添付ファイルがない場合でも必ず出力する）
	$head_tags[] = ' <script type="text/javascript" src="' . SKIN_DIR . 'sortable-table.js"></script>';
	$head_tags[] = ' <script type="text/javascript" src="' . SKIN_DIR . 'filterable-table.js"></script>';

	// 添付ファイルドラッグ＆ドロップアップロードツール一式格納フルパス
	$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
	$tool_dir = $protocol . $_SERVER['HTTP_HOST'] . PLUGIN_ATTACH_UPLOAD_TOOL_DIR;
	// アップロードファイル最大サイズ
	$maxsize = PLUGIN_ATTACH_MAX_FILESIZE;
	$msg_maxsize = sprintf($_attach_messages['msg_maxsize'], number_format($maxsize/1024) . 'KB');
	// 添付ファイルリスト出力プラグイン外部呼び出しスクリプト設定
	$plugin = PLUGIN_ATTACH_UPLOAD_TOOL_DIR . PLUGIN_ATTACH_UPLOAD_FILE_LIST . '.php';
	// 添付ファイル削除スクリプト設定
	$delete = PLUGIN_ATTACH_UPLOAD_TOOL_DIR . PLUGIN_ATTACH_UPLOAD_FILE_DELETE;
	$operation = $is_page_edit ? 'del' : 'full';
	// 添付ファイルリスト出力（初回起動表示部分）
	if (exist_plugin_convert(PLUGIN_ATTACH_UPLOAD_FILE_LIST)) {
		// ※プラグインを呼び出す際はexist_plugin_convertしないと呼び出せない
		$args = "$page,$operation$nodel$newpage";
		$attach_list = do_plugin_convert(PLUGIN_ATTACH_UPLOAD_FILE_LIST, $args);
	}
	// ソートキーカラムセット
	preg_match_all('/\,.*?\[.*?[?=\]]/', $attach_list, $match_array);
	$sort_col = mb_substr($match_array[0][0], 1);
	if ($sort_col == "") {
		// 新規ページ作成の場合は例外的に固定でソートキーをセットしておく
		// （新規ページには添付ファイルがないのでソートテーブルが存在しない）
		$sort_col = "['Number','String','Date','Number','String']";
	}	// ヘッダ行折返し禁止指定
	$nowrap = (PLUGIN_ATTACH_LIST_HEAD_NOWRAP != '') ? "true" : "false";

	$html_all_pages = <<<EOD
	<span class="small">
		[<a href="./?plugin=attach&amp;pcmd=list">{$_attach_messages['msg_listall']}</a>]
	</span><br />
EOD;

	$html_form = <<<EOD
	<span class="small">
		$msg_maxsize
	</span><br />
	<br />
	<form id="fileupload" action="$tool_dir" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="page" value="$page" />
		<input type="hidden" name="overwrite" value="{$_(PLUGIN_ATTACH_UPLOAD_OVERWRITE)}" />
		<!-- アップロードファイル追加・全アップロード・全キャンセルボタン・進捗状況表示 -->
		<div class="row fileupload-buttonbar">
			<div class="col-lg-7">
				<span class="btn btn-success fileinput-button">
					<i class="glyphicon glyphicon-plus"></i>
					<span>ファイル追加</span>
					<input type="file" name="files[]" multiple />
				</span>
				<button type="submit" class="btn btn-primary start">
					<i class="glyphicon glyphicon-upload"></i>
					<span>全アップロード</span>
				</button>
				<button type="reset" class="btn btn-warning cancel">
					<i class="glyphicon glyphicon-ban-circle"></i>
					<span>全キャンセル</span>
				</button>
				<!-- 全体の処理状態表示 -->
				<span class="fileupload-process"></span>
			</div>
			<!-- アップロード全体の進捗状態 -->
			<div class="col-lg-5 fileupload-progress fade">
				<!-- アップロード全体のプログレスバー -->
				<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
					<div class="progress-bar progress-bar-success" style="width: 0%;"></div>
				</div>
				<!-- 全体の進行状況（拡張） -->
				<div class="progress-extended">&nbsp;</div>
			</div>
		</div>

		<!-- アップロードファイル一覧表 -->
		<table role="presentation" class="table table-striped">
			<tbody class="files"></tbody>
		</table>
	</form>

	<!-- アップロードファイルを表示するテンプレート -->
	<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="template-upload fade{%=o.options.loadImageFileTypes.test(file.type)?' image':''%}">
				<td>
					<span class="preview"></span>
				</td>
				<td>
					<p class="name">{%=file.name%}</p>
					<strong class="error text-danger"></strong>
				</td>
				<td>
					<p class="size">Processing...</p>
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<div class="progress-bar progress-bar-success" style="width:0%;">
						</div>
					</div>
				</td>
				<td>
					{% if (!i && !o.options.autoUpload) { %}
						<button class="btn btn-primary start" disabled>
							<i class="glyphicon glyphicon-upload"></i>
							<span>アップロード</span>
						</button>
					{% } %}
					{% if (!i) { %}
						<button class="btn btn-warning cancel">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span>キャンセル</span>
						</button>
					{% } %}
				</td>
			</tr>
		{% } %}
	</script>

	<!-- ダウンロードファイルを表示するテンプレート（アップロードエラーを表示） -->
	<script id="template-download" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			{% if (file.error) { %}
				<tr class="template-download fade{%=file.thumbnailUrl?' image':''%}">
					<td>
						<span class="preview">
							{% if (file.thumbnailUrl) { %}
								<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
							{% } %}
						</span>
					</td>
					<td>
						<p class="name">
							{% if (!file.url) { %}
								<span>{%=file.name%}</span>
							{% } %}
						</p>
						<div><span class="label label-danger">Error</span> {%=file.error%}</div>
					</td>
					<td>
						<span class="size">{%=o.formatFileSize(file.size)%}</span>
					</td>
					<td>
						{% if (!file.deleteUrl) { %}
							<button class="btn btn-warning cancel">
								<i class="glyphicon glyphicon-ban-circle"></i>
								<span>キャンセル</span>
							</button>
						{% } %}
					</td>
				</tr>
			{% } %}
		{% } %}
	</script>
EOD;

	$html_script = <<<EOD
	<script type="text/javascript">
		// 添付ファイルリスト取得処理
		function getAttachList() {
			$.ajax({
				url: '{$_($plugin)}',
				type: 'post',
				data: {args: '{$_($args)}'},
				dataType: 'html',
				success: function(data, textStatus) {
					// Ajaxで外部PHPプログラムから返却されたHTMLを表示
					$('#attach_list').html(data);
					// 添付ファイルリストセット
					setAttachList();
				},
				error: function(xhr, textStatus, errorThrown) {
					alert('添付ファイルリストの取得に失敗しました');
				}
			});
		}
		// 添付ファイルリストセット処理
		function setAttachList() {
			if (document.getElementById('sortable_table1') == null) {
				// ソートテーブルが存在しない場合はExit]
				return;
			}
			// タイマー＋ソートテーブルJavaScript起動用
			function sleep(milliSec) {
				return new Promise(function(resolve, reject) {
					setTimeout(function() {
						resolve('');
					}, milliSec);
				});
			}
			// 返却されたHTMLでJavaScriptは起動するが完全ではない
			sleep(100).then(function(result) {
				// 非同期処理なため、適度なインターバルをとって
				// JavaScriptを起動して完全にする必要がある
				var st = new SortableTable(
					document.getElementById('sortable_table1'),
					{$_($sort_col)},
					['{$_(PLUGIN_ATTACH_LIST_ODD_COLOR)}',
					 '{$_(PLUGIN_ATTACH_LIST_EVEN_COLOR)}'],
					$nowrap);
				// 追加したファイルを別窓で開く必要がある
				jQuery(function(){
					// ページ添付画像・PDFを別窓で開く
					jQuery('a[href$="jpg"],a[href$="jpeg"],a[href$="JPG"],a[href$="JPEG"],a[href$="png"],a[href$="PNG"],a[href$="pdf"],a[href$="PDF"]').click(function() {
						$(this).attr('target', '_blank');
					});
				});
			});
			// 削除ボタン処理
			$(".del_button").on('click', function() {
				if( confirm("ファイルを削除しますか？") ) {
					var delFile = $(this).val();
					// 添付ファイルリスト取得
					$.ajax({
						url: '{$_($delete)}',
						type: 'post',
						data: {filename: delFile},
						dataType: 'html',
						success: function(data, textStatus) {
							// 添付ファイルリスト取得
							getAttachList();
						},
						error: function(xhr, textStatus, errorThrown) {
							alert('添付ファイルの削除に失敗しました');
						}
					});
				}
			});
		}

		// 添付ファイルリスト初期セット
		setAttachList();
		// ファイルアップロード部
		$(function () {
			'use strict';
			var url = '{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}';
			$('#fileupload').fileupload({
				maxChunkSize: undefined,
				url: url,
				dataType: 'json'
			})
			.bind('fileuploadprogressall', function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#progress .progress-bar').css(
					'width',
					progress + '%'
				);
				$('.log').text(progress + '%');
			})
			.bind('fileuploaddone', function (e, data) {
				// プログレスバー非表示
				$('#progress .progress-bar').removeClass('progress-bar-striped active');
				// 添付ファイルリスト消去
				$('#attach_list').empty();
				// 添付ファイルリスト取得
				getAttachList();
			})
		});
	</script>

	<!-- Googleライブラリ -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>

	<!-- 以下、「jQuery-File-Upload」で必要なスクリプト -->

	<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/vendor/jquery.ui.widget.js"></script>
	<!-- The Templates plugin is included to render the upload/download listings -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/templates/tmpl.min.js"></script>
	<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/load_image/load-image.all.min.js"></script>
	<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.iframe-transport.js"></script>
	<!-- The basic File Upload plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload.js"></script>
	<!-- The File Upload processing plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-process.js"></script>
	<!-- The File Upload image preview & resize plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-image.js"></script>
	<!-- The File Upload audio preview plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-audio.js"></script>
	<!-- The File Upload video preview plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-video.js"></script>
	<!-- The File Upload validation plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-validate.js"></script>
	<!-- The File Upload user interface plugin -->
	<script src="{$_(PLUGIN_ATTACH_UPLOAD_TOOL_DIR)}js/jquery.fileupload-ui.js"></script>
EOD;

	// 添付ファイルリスト編集
	$attach_list = "<div id=\"attach_list\">" . $attach_list . "</div>\n";

	if ($is_page_edit) {
		// ページ編集画面の場合
		if (! PLUGIN_ATTACH_UPLOAD_NEW_PAGE && $is_new_page) {
			// 新規作成ページでドラッグ＆ドロップアップロードをしない設定
			// かつ現在が新規ページの場合
			return;
		}
		if (PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY && ! $is_admin) {
			// 管理者のみ添付ファイルをアップロード可で管理者ユーザでない場合
			$body  = $attach_list;
		} else {
			$body  = $html_form . "\n" . $attach_list . "\n" . $html_script;
			$body .= "\n";
		}
	} else {
		// ページ添付画面の場合
		$body  = $html_all_pages . "\n" . $html_form . "\n" . $attach_list;
		$body .= "\n" . $html_script . "\n";
	}
	return $body;
}
