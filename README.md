Groongaクライアント
======================
Command-line client of Groonga.

利用方法
------

### Groongaライブラリのインストール ###
    
    $ sudo aptitude install -y libgroonga0 libgroonga-dev

Groonga v4.0.7で追加されたC-APIを利用しています。
最新版のライブラリを利用してください。

### Proongaのインストール ###
    
    $ git clone https://github.com/Yujiro3/proonga.git
    $ cd ./proonga
    $ phpize
    $ ./configure
    $ make
    $ sudo -s
    # make install
    # cd /etc/php5/mods-available
    # echo extension=groonga.so > proonga.ini
    # cd /etc/php5/conf.d
    # ln -s ../mods-available/proonga.ini ./30-proonga.ini
    
内部でphp_json_decode_exを使用しています。

json-1.2.1を読み込んでからproongaを読み込むように
設定してください。
    
### 対話形式 ###

```
$ grn-cli db/test.db
grn-cli> status
{
    "alloc_count": 163,
    "starttime": 1417584597,
    "uptime": 3,
    "version": "4.0.7",
    "n_queries": 0,
    "cache_hit_rate": 0,
    "command_version": 1,
    "default_command_version": 1,
    "max_command_version": 2
}
```

### 標準入力にリダイレクト ###

```
$ echo status > status.grn
$ grn-cli db/test.db < ./status.grn
{
    "alloc_count": 163,
    "starttime": 1417584597,
    "uptime": 3,
    "version": "4.0.7",
    "n_queries": 0,
    "cache_hit_rate": 0,
    "command_version": 1,
    "default_command_version": 1,
    "max_command_version": 2
}
```

ライセンス
----------
Copyright &copy; 2014 Yujiro Takahashi  
Licensed under the [MIT License][MIT].  
Distributed under the [MIT License][MIT].  

[MIT]: http://www.opensource.org/licenses/mit-license.php
