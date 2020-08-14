# pukiwiki-plugin-attach_jquery_file_upload

ドラッグ＆ドロップファイルアップロード対応attachプラグイン

- 暫定公開版です（[PukiWiki1.5.2](https://pukiwiki.osdn.jp/?PukiWiki/Download/1.5.2)及び[PukiWiki1.5.3](https://pukiwiki.osdn.jp/?PukiWiki/Download/1.5.3)で動作確認済）
- [PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)でページにファイルを添付する際に、ドラッグ＆ドロップオペレーションで単数または複数のファイルを一度にアップロード出来るプラグインです（画面遷移することなく添付ファイルの削除も可能です）
- ページ編集画面でも添付ファイルのアップロードと削除が可能で、ページに添付されているファイルを参照しながらページの編集が行えます
- ドラッグ＆ドロップでファイルをアップロードする部分は、Sebastian Tschan氏（[blueimp](https://github.com/blueimp)）がGitHubで公開している、超多機能かつ高性能の[jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload)を利用し、独自にカスタマイズしています
- 本プラグインの導入は、[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)標準のattachプラグインを置き換える形になり、プラグインの設定で従来通りの添付オペレーションに戻すことも可能です
- 本プラグインは[PukiWiki用ソートテーブル（表）プラグイン（最新版）](https://dajya-ranger.com/sdm_downloads/sortable-table-plugin/)の導入が前提となり、必須ではありませんが、[PukiWiki用URL短縮ライブラリ（最新版）](https://dajya-ranger.com/sdm_downloads/short-url-library-pkwk153/)が導入されている前提で[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)の標準ライブラリを修正し、アーカイブに含めています（[PukiWiki1.5.2](https://pukiwiki.osdn.jp/?PukiWiki/Download/1.5.2)及び[PukiWiki1.5.3](https://pukiwiki.osdn.jp/?PukiWiki/Download/1.5.3)のどちらにも対応しています）
- 本プラグインは古いブラウザ（IE11等）には対応しておりませんので、最新のChrome・Firefox・Edge・Opera等をお使い下さい
- 設置と設定に関しては自サイトの記事「[ドラッグ＆ドロップファイルアップロード対応attachプラグインでPukiWikiに革命を起こすぜ！](https://dajya-ranger.com/pukiwiki/setting-comment-responsive/)」（執筆予定）を参照して下さい
