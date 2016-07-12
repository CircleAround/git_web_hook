# git_web_hook

## .htaccess
.htaccess に環境変数を設定する
```
SetEnv SECRET hogehoge # github webhook の secretに入れた値
SetEnv MODE normal # debug にするとdebug モード
SetEnv BRANCH_NAME master # push先のブランチ名(基本はmaster)
SetEnv COMMAND "sudo -u hoge sh fuga.sh" webhookで実行したいコマンド
SetEnv DEBUG_COMMAND "sudo -u hoge sh fuga1.sh" debug時に実行したいコマンド
```
## apacheユーザでPHPからsudo権限でシェルを叩く
git pull 専用ユーザー deployer(名前は任意)を作成している前提で書きます。

http://blog.ousaan.com/index.cgi/links/20120504.html

```
$ sudo visudo 
```
```
apache ALL=(deployer) NOPASSWD: /bin/sh # deployer の権限でshコマンドを実行する時
Defaults:apache !requiretty # apacheだけ端末デバイス(Terminal)以外からのアクセスを許す
```

## pull.sh
```
#!/bin/sh
cd /path/to/file
git pull origin master
```

## /path/to/file 以下の権限を変更

- chown でdeployerに変更する


## .git をWebからアクセスできなくする

http://stackoverflow.com/questions/6142437/make-git-directory-web-inaccessible
