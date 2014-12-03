#!/usr/bin/env php
<?php
/**
 * Groongaクライアント
 *
 * @package         grn-cli
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 * @filesource
 */

/**
 * ヘルプの出力
 *
 * @access public
 * @return void
 */
function help() {
    echo "Usage: grn-cli [options...] [dest]\n".
         "Database creation options:\n".
         "  -n:                  create new database\n".
         "  -e, --encoding <encoding>:\n".
         "                       specify encoding for new database\n".
         "                       [none|euc|utf8|sjis|latin1|koi8r] (default: utf8)\n".
         "\n".
         "Client options:\n".
         "      --file <path>:          read commands from specified file\n".
         "      --input-fd <FD>:        read commands from specified file descriptor\n".
         "                              --file has a prioriry over --input-fd\n".
         "      --output-fd <FD>:       output response to specifid file descriptor\n".
         "  -p, --port <port number>:   specify server port number (client mode only)\n".
         "                              (default: 10043)\n".
         "\n".
         "Common options:\n".
         "      --working-directory <path>:\n".
         "                       specify working directory path\n".
         "                       (none)\n".
         "      --config-path <path>:\n".
         "                       specify config file path\n".
         "                       (default: /etc/groonga/groonga.conf)\n".
         "  -h, --help:          show usage\n".
         "      --version:       show groonga version\n".
         "dest:\n".
         "  <dest hostname> [<commands>]: in client mode (default: localhost)\n";
}

/**
 * コマンドヘルプの出力
 *
 * @access public
 * @return void
 */
function command_help() {
    echo "cache_limit     \n".
         "check           \n".
         "clearlock       \n".
         "column_create   \n".
         "column_list     \n".
         "column_remove   \n".
         "column_rename   \n".
         "define_selector \n".
         "defrag          \n".
         "delete          \n".
         "dump            \n".
         "load            \n".
         "log_level       \n".
         "log_put         \n".
         "log_reopen      \n".
         "normalize       \n".
         "normalizer_list \n".
         "quit            \n".
         "register        \n".
         "ruby_eval       \n".
         "ruby_load       \n".
         "select          \n".
         "shutdown        \n".
         "status          \n".
         "suggest         \n".
         "table_create    \n".
         "table_list      \n".
         "table_remove    \n".
         "table_tokenize  \n".
         "tokenize        \n".
         "tokenizer_list  \n".
         "truncate        \n";
}

/**
 * 初期値の取得
 *
 * @access public
 * @param integer 引数の数
 * @param array   引数の配列
 * @return array
 */
function getIni($argc, $argv) {
    $ini = array();

    if (isset($argc) && $argc > 1) {
        for ($pos=1; $pos < $argc; $pos++) {
            switch ($argv[$pos]) {
            case '-n':
                $ini['db-path'] = $argv[++$pos];
                break;
            case '-e':
            case '--encoding':
                $ini['encoding'] = $argv[++$pos];
                break;
            case '--working-directory':
                $ini['working-directory'] = $argv[++$pos];
                @chdir($argv[++$pos]);
                break;
            case '--config-path':
                $ini['config-path'] = $argv[++$pos];
                $parsed = parse_ini_file($ini['config-path']);
                $ini = array_merge($ini, $parsed);
                break;
            case '--version':
                break;
            case '-h':
            case '--help':
                help();
                exit(0);
            default:
                $ini['db-path'] = $argv[$pos];
                break;
            }
        }
    } else {
        help();
        exit(0);
    }
    return $ini;
}


/**
 * 初期設定、各種チェック
 *
 * @access public
 * @return array   設定リスト
 */
function initialize($ini) {
    if (!extension_loaded('readline')) {
        echo 'Unable to load readline.'.PHP_SHLIB_SUFFIX."\n".
             "http://jp2.php.net/manual/ja/book.readline.php\n";
        exit(0);
    }

    /* ディレクトリの変更 */
    if (empty($ini['working-directory'])) {
        @chdir($ini['working-directory']);
    }

    exec('touch ~/.grn-cli_history');
    readline_completion_function('cmp_handler');
    $home = getenv('HOME');
    $result = readline_read_history($home.'/.grn-cli_history');
	if (false === $result) {
		$history = file_get_contents($home.'/.grn-cli_history');
		$lines = explode("\n", $history);
		foreach ($lines as $line) {
			readline_add_history($line);
		}
	}
}

/**
 * 保管処理
 *
 * @access public
 * @param  string  $input 入力文字列
 * @param  integer $index ポジション
 * @return array   保管リスト
 */
function cmp_handler($input, $index) {
    readline_redisplay();
    return array(
        'cache_limit',
        'check',
        'clearlock',
        'column_create',
        'column_list',
        'column_remove',
        'column_rename',
        'define_selector',
        'defrag',
        'delete',
        'dump',
        'load',
        'log_level',
        'log_put',
        'log_reopen',
        'normalize',
        'normalizer_list',
        'quit',
        'register',
        'ruby_eval',
        'ruby_load',
        'select',
        'shutdown',
        'status',
        'suggest',
        'table_create',
        'table_list',
        'table_remove',
        'table_tokenize',
        'tokenize',
        'tokenizer_list',
        'truncate',
    );
}

/**
 * シャットダウン処理
 *
 * @access public
 * @return void
 */
register_shutdown_function(function () {
    $home = getenv('HOME');
    file_put_contents($home.'/.grn-cli_history', $GLOBALS['history']);
});

/**
 * メイン処理
 *
 * @access public
 * @param  integer $argc 入力文字列
 * @param  string  $argv ポジション
 * @return void
 */
$ini = getIni($argc, $argv);
initialize($ini);
$is_load = false;
$load    = $GLOBALS['history'] = '';

$gdb = new Groonga($ini['db-path']);

while (true) {
    $line = readline('grn-cli> ');
    if (false === $line) {
        exit("\n");
    } else {
        if ($is_load) {
            $load .= $line;
            $start = strpos($load, '[');
            $end   = strrpos($load, ']');
            if (false !== $end) {
                /* JSONデータ部分の抜き出し */
                $json = substr($load, $start, ($end - $start + 1));
                if (json_decode($json, true)) {
                    readline_add_history($load);
                    $GLOBALS['history'] .= $line."\n";
                    $load = trim($load);
                    $table = substr($load, 4, $start - 1);
                    $matches = array();
                    if (preg_match('/\-\-table\s+([^\s\[]+)/i', $table, $matches)) {
                        $table = $matches[1];
                    }
                    $load = $gdb->command('load');
                    $load->table = trim($table);
                    $load->values = $json;
                    echo $load->exec();
                    echo "\n";
                    $is_load = false;
                }
            }
        } else {
            $line = trim($line);
            if ('help' === $line) {
                command_help();
            } else if (preg_match('/^load/i', $line)) {
                $is_load = true;
                $load    = $line;
            } else {
                readline_add_history($line);
                $GLOBALS['history'] .= $line."\n";
                $result = $gdb->query($line, true);
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_BIGINT_AS_STRING);
                echo "\n";
            }
        }
    }
}
