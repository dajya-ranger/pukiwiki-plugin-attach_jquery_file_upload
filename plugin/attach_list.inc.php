<?php
/**
 * attach_list.inc.php
 *
 * 添付ファイルリスト出力プラグイン
 *
 * @author		オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @copyright	Copyright © 2020, dajya-ranger.com
 * @link		https://dajya-ranger.com/pukiwiki/attach-jquery-file-upload/
 * @example		#attach_list([ページ名], [full|del],[nodel],[newpage])
 * @license		Apache License 2.0
 * @version		0.1.1
 * @since 		0.1.1 2020/08/21 デグレードしていた＆細かいバグを除去
 * @since 		0.1.0 2020/08/14 暫定初公開（独自拡張）
 *
 */

// ソートテーブルヘッダ行バックグラウンドカラー
define('PLUGIN_ATTACH_LIST_HEAD_COLOR', '#f0f0f0');
// ソートテーブル奇数行バックグラウンドカラー
define('PLUGIN_ATTACH_LIST_ODD_COLOR', '#ffffff');
// ソートテーブル偶数行バックグラウンドカラー
define('PLUGIN_ATTACH_LIST_EVEN_COLOR', '#f6f9fb');
// ソートテーブルヘッダ行折返し禁止指定（'nowrap' or ''）
define('PLUGIN_ATTACH_LIST_HEAD_NOWRAP', 'nowrap');

function plugin_attach_list_convert() {
	global $vars, $date_format, $time_format, $weeklabels;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$args = array();
	$matches = array();
	$files = array();
	$status = array('count'=>array(0), 'age'=>'', 'pass'=>'', 'freeze'=>FALSE);

	// 引数チェック
	$num = func_num_args();
	$args = func_get_args();
	// 新規ページ作成指定セット
	$is_new_page = in_array('newpage', $args);
	if ($num < 1) {
		// 引数が存在しない場合
		$operation = FALSE;
	} else {
		if ($num > 4) {
			// 引数の数が不正の場合
			return '#attach_list: 引数の数が不正です';
		} else {
			if ($num == 1) {
				if (! is_page($args[0]) && ! $is_new_page) {
					// 第1引数が有効なページ名ではない
					if ((! strtolower($args[0]) == 'full') ||
						(! strtolower($args[0]) == 'del')) {
						// ファイル操作オプションでもない
						return '#attach_list: 第1引数が不正です';
					} else {
						$operation = $args[0];
					}
				} else {
					// 第1引数のページ名を採用する
					$page = $args[0];
				}
			} else {
				$temp_page = trim(strip_htmltag($args[0]));
				if ((! is_page($temp_page) && ! $is_new_page) ||
					!(strtolower($args[1]) == 'full' ||
					  strtolower($args[1]) == 'del')) {
					// 第1引数が有効なページ名でないか
					// 第2引数がファイル操作オプションではない
					return '#attach_list: 正しいページ名とファイル操作オプションを設定して下さい';
				}
				$page = $temp_page;
				$operation = $args[1];
				if ($num == 3) {
					// 第3引数がある場合
					if ((! strtolower($args[2]) == 'nodel') ||
						(! strtolower($args[2]) == 'newpage')) {
						// 第3引数が削除ボタン抑止オプション
						// または新規作成ページ指定オプションじゃない場合
						return '#attach_list: 第3引数が不正です';
					} else {
						$nodel = (strtolower($args[2]) == 'nodel') ? TRUE : FALSE;
					}
				}
			}
		}
	}

	// ページ有効チェック
	if (! is_page($page) && ! $is_new_page) {
		// ページ名が有効ではなく、新規作成ページでもない場合
		return '#attach_list No such page: ' . $page;
	}

	// ページ添付ファイル取得
	$od = @opendir(UPLOAD_DIR);
	while ($filename = readdir($od)) {
		if (preg_match("/^((?:[0-9A-F]{2})+)_((?:[0-9A-F]{2})+)$/", $filename, $matches)) {
			if ($page == decode($matches[1])) {
				// 指定ページ名の場合のみ添付ファイル名を取得する
				$files[] = decode($matches[2]);
			}
		}
	}
	closedir($od);

	// 添付ファイル数チェック
	if (count($files) == 0) return;

	// 取得した添付ファイル一覧をソートテーブルで編集
	$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
	$script = $protocol . $_SERVER['HTTP_HOST'] . '/';
	if ($nodel || (PLUGIN_ATTACH_LIST_HEAD_NOWRAP != '')) {
		// ソートテーブルヘッダ行折返し禁止指定の場合
		$nowrap = "width=100,nowrap){{\n";
	} else {
		$nowrap = "width=100){{\n";
	}
	if ($operation) {
		// ファイル操作あり
		$body  = "#sortable_table(String|Date|Number|String,autonum,";
		$body .= "head=" . PLUGIN_ATTACH_LIST_HEAD_COLOR . ",";
		$body .= "odd=" . PLUGIN_ATTACH_LIST_ODD_COLOR . ",";
		$body .= "even=" . PLUGIN_ATTACH_LIST_EVEN_COLOR . ",";
		$body .= $nowrap;
		$body .= "|~ファイル名|~更新日時|~サイズ|~ファイル操作|h\n";
		$body .= "|LEFT:|CENTER:|RIGHT:|CENTER:|c\n";
	} else {
		// ファイル操作なし
		$body  = "#sortable_table(String|Date|Number,autonum,head=#f0f0f0,";
		$body .= "head=" . PLUGIN_ATTACH_LIST_HEAD_COLOR . ",";
		$body .= "odd=" . PLUGIN_ATTACH_LIST_ODD_COLOR . ",";
		$body .= "even=" . PLUGIN_ATTACH_LIST_EVEN_COLOR . ",";
		$body .= $nowrap;
		$body .= "|~ファイル名|~更新日時|~サイズ|h\n";
		$body .= "|LEFT:|CENTER:|RIGHT:|c\n";
	}
	// ファイル名でソート
	sort($files);
	$count = 0;
	foreach($files as $filename) {
		$coutn .= ++$count;
		// ファイル情報編集
		$basename = UPLOAD_DIR . encode($page) . '_' . encode($filename);
		$logname = $basename . '.log';
		$filedate = filemtime($basename);
		$filedate = date($date_format, $filedate) . ' (' . $weeklabels[date('w', $filedate)] . ') ' . date($time_format, $filedate);
		// テーブル内容編集
		$body .= "|&ref($page/$filename,noimg,$filename);";
		$body .= "|" . $filedate;
		$body .= "|" . sprintf('%01.1f', round(filesize($basename) / 1024, 1)) . 'KB';
		if (file_exists($logname)) {
			// ログファイルが存在する場合、ファイルのステータス取得
			$data = file($logname);
			foreach ($status as $key=>$value) {
				$status[$key] = chop(array_shift($data));
			}
			$status['count'] = explode(',', $status['count']);
		} else {
			// ログファイルが存在しない場合は凍結状態をクリア
			$status['freeze'] = FALSE;
		}
		// リンクパラメータ編集
		$param = '&file=' . urlencode($filename) . '&refer=' . urlencode($page);
		switch (strtolower($operation)) {
		case 'full':
			// ファイル操作「フル」
			if ($status['freeze']) {
				// ファイルが凍結されている場合
				$body .= "|[[詳細（解凍）>$script?plugin=attach&pcmd=info$param]]";
			} else {
				$body .= "|[[詳細（削除・凍結・名変）>$script?plugin=attach&pcmd=info$param]]";
				if (! $nodel) {
					// 削除ボタン抑止オプション指定がない場合
					$body .= " <button class=\"del_button\" type=\"button\" name=\"button$count\" value=\"$basename\">削除</button>";
				}
			}
			break;
		case 'del':
			// ファイル操作「削除」のみ
			if ($status['freeze']) {
				// ファイルが凍結されている場合
				$body .= "|凍結ファイル";
			} else {
				$body .= "|";
				if (! $nodel) {
					// 削除ボタン抑止オプション指定がない場合
					$body .= "<button class=\"del_button\" type=\"button\" name=\"button$count\" value=\"$basename\">削除</button>";
				}
			}
			break;
		}
		$body .= "|\n";
	}
	$body .= "}}\n";

	// HTMLに変換して返却
	$body = convert_html($body);
	$search = array('&lt;', '&gt;', '&quot;');
	$replace = array('<', '>', '"');
	$body = str_replace($search, $replace, $body);
	return $body;
}
