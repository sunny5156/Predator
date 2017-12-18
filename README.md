Predator 
=====

Predator 是一款基于基于xhgui改进的图形管理界面，使用方法和xhgui完全一致。主要调整和优化的以下功能：

1、更改bytes为kb或者mb，µs改为ms或者s，日期格式改为年-月-日 时：分：秒。

2、列表项新增IP地址、显示完整访问地址。

3、增加多域名显示功能，增加登录验证功

系统运行条件
===================

Predator运行有以下需求:

 * PHP 版本大于或者等于5.5.
 * 系统支持[XHProf](http://pecl.php.net/package/xhprof),
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or
   [Tideways](https://github.com/tideways/php-profiler-extension) 这几个性能监控组件.
 * [MongoDB Extension](http://pecl.php.net/package/mongo) MongoDB PHP 扩展版本必须大于或者等于1.3.0.
 * [MongoDB](http://www.mongodb.org/) MongoDB版本必须大于或者等于 2.2.0.
 * [dom](http://php.net/manual/en/book.dom.php) If you are running the tests
   you'll need the DOM extension (which is a dependency of PHPUnit).


安装说明
============

1. 从Github上克隆Predator项目代码.

2. 服务器根目录指定到 Predator 文件夹下的 webroot目录.

3. 设置 cache 目录权限为 07777。Linux运行如下命令：chmod 0777 cache -R

4. 安装并启动MongoDB（config/config.php文件中的配置选项请根据实现情况进行调整）.

5. 使用db.collection.ensureIndex()命令为MongoDB添加索引.代码示例如下：系统默认使用
Predator数据库。代码示例如下：

 ```
   $ mongo
   > use predator
   > db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
   > db.results.ensureIndex( { 'profile.main().wt' : -1 } )
   > db.results.ensureIndex( { 'profile.main().mu' : -1 } )
   > db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
   > db.results.ensureIndex( { 'meta.url' : 1 } )
   > db.results.ensureIndex( { 'meta.simple_url' : 1 } )
   ```

6. 进入目录后使用php install.php 来安装 composer 来管理系统所需要的扩展。代码示例如下：

   ```bash
   cd path/to/xhgui
   php install.php
   ```

7. 对Web服务器进行配置。

服务器配置
=============

配置服务器重写规则
----------------------------------

建议使用Rewrite重写规则来进行配置，
XHGui prefers to have URL rewriting enabled, but will work without it.
For Apache, you can do the following to enable URL rewriting:

1. Make sure that an .htaccess override is allowed and that AllowOverride
   has the directive FileInfo set for the correct DocumentRoot.

    Example configuration for Apache 2.4:
    ```apache
    <Directory /var/www/xhgui/>
        Options Indexes FollowSymLinks
        AllowOverride FileInfo
        Require all granted
    </Directory>
    ```
2. Make sure you are loading up mod_rewrite correctly.
   You should see something like:

    ```apache
    LoadModule rewrite_module libexec/apache2/mod_rewrite.so
    ```

3. XHGui comes with a `.htaccess` file to enable the remaining rewrite rules.

For nginx and fast-cgi, you can the following snippet as a start:

```nginx
server {
    listen   80;
    server_name example.com;

    # root directive should be global
    root   /var/www/example.com/public/xhgui/webroot/;
    index  index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include /etc/nginx/fastcgi_params;
        fastcgi_pass    127.0.0.1:9000;
        fastcgi_index   index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

配置 Predator 采样率
-------------------------------

After installing XHGui, you may want to do change how frequently you
profile the host application. The `profiler.enable` configuration option
allows you to provide a callback function that specifies the requests that
are profiled. By default, XHGui profiles 1 in 100 requests.

The following example configures XHGui to only profile requests
from a specific URL path:

The following example configures XHGui to profile 1 in 100 requests,
excluding requests with the `/blog` URL path:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        $url = $_SERVER['REQUEST_URI'];
        if (strpos($url, '/blog') === 0) {
            return false;
        }
        return rand(1, 100) === 42;
    }
);
```

In contrast, the following example configured XHGui to profile *every*
request:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        return true;
    }
);
```


Configure 'Simple' URLs Creation
--------------------------------

XHGui generates 'simple' URLs for each profile collected. These URLs are
used to generate the aggregate data used on the URL view. Since
different applications have different requirements for how URLs map to
logical blocks of code, the `profile.simple_url` configuration option
allows you to provide specify the logic used to generate the simple URL.
By default, all numeric values in the query string are removed.

```php
// In config/config.php
return array(
    // Other config
    'profile.simple_url' => function($url) {
        // Your code goes here.
    }
);
```

The URL argument is the `REQUEST_URI` or `argv` value.


Profile an Application or Site
==============================

The simplest way to profile an application is to use
`external/header.php`. `external/header.php` is designed to be combined
with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-pr
epend-file) directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable `auto_prepend_file` per
virtual host.

With apache this would look like:

```apache
<VirtualHost *:80>
  php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
  DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
  ServerName site.localhost
</VirtualHost>
```
With Nginx in fastcgi mode you could use:

```nginx
server {
  listen 80;
  server_name site.localhost;
  root /Users/markstory/Sites/awesome-thing/app/webroot/;
  fastcgi_param PHP_VALUE "auto_prepend_file=/Users/markstory/Sites/xhgui/external/header.php";
}
```

Profile a CLI Script
====================

The simplest way to profile a CLI is to use
`external/header.php`. `external/header.php` is designed to be combined with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file)
directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively,
you can enable include the `header.php` at the top of your script:

```php
<?php
require '/path/to/xhgui/external/header.php';
// Rest of script.
```

You can alternatively use the `-d` flag when running php:

```bash
php -d auto_prepend_file=/path/to/xhgui/external/header.php do_work.php
```

Saving & Importing Profiles
---------------------------

If your site cannot directly connect to your MongoDB instance, you can choose
to save your data to a temporary file for a later import to XHGui's MongoDB
database.

To configure XHGui to save your data to a temporary file,
change the `save.handler` setting to `file` and define your file's
path with `save.handler.filename`.

To import a saved file to MongoDB use XHGui's provided
`external/import.php` script.

Be aware of file locking: depending on your workload, you may need to
change the `save.handler.filename` file path to avoid file locking
during the import.

The following demonstrate the use of `external/import.php`:

```bash
php external/import.php -f /path/to/file
```

**Warning**: Importing the same file twice will load twice the run datas inside
MongoDB, resulting in duplicate profiles


限制MongoDB 的磁盘使用
---------------------------

由于监控系统数量量比较大，尤其是访问量大的项目，你可以使用MongoDB自动删除以前的采集数据。

具体你们可以参考MongoDB的官方文档：[传送门](http://docs.mongodb.org/manual/core/index-ttl/).

TTL索引是一个特殊的索引，目前只支持在单个的字段上设置索引，而且该字段必须是日期类型或者
是包含日期类型的数组类型。我们可以通过createIndex方法来创建一个TTL索引，具体如下所示：.

代码示例如下（需要注意的是过期时间的字段必须使用UTC时间：example:Sun Jan 24 2016 20:52:33 GMT+0800 (CST)）：.

```
$ mongo
> use xhprof
> db.results.ensureIndex( { "meta.request_ts" : 1 }, { expireAfterSeconds : 432000 } )
```

Waterfall Display
-----------------

The goal of XHGui's waterfall display is to recognize that concurrent requests can
affect each other. Concurrent database requests, CPU-intensive
activities and even locks on session files can become relevant. With an
Ajax-heavy application, understanding the page build is far more complex than
a single load: hopefully the waterfall can help. Remember, if you're only
profiling a sample of requests, the waterfall fills you with impolite lies.

Some Notes:

 * There should probably be more indexes on MongoDB for this to be performant.
 * The waterfall display introduces storage of a new `request_ts_micro` value, as second level
   granularity doesn't work well with waterfalls.
 * The waterfall display is still very much in alpha.
 * Feedback and pull requests are welcome :)

使用 Tideways 扩展（推荐）
========================

该扩展支持PHP7+版本，具体详情请查看 [tideways extension](https://github.com/tideways/php-profiler-extension).

安装好扩展后，你可以参考以下代码修改PHP配置文件


```ini
[tideways]
extension="/path/to/tideways/tideways.so"
tideways.connection=unix:///usr/local/var/run/tidewaysd.sock
tideways.load_library=0
tideways.auto_prepend_library=0
tideways.auto_start=0
tideways.sample_rate=100
```

发布和更新
====================

你可以到这里查看有关 [Predator](https://github.com/Longjianghu/Predator) 的发布和更新信息

其它说明
=======

欢迎任何企业或者个人使用 Predator，如果你在使用过程中遇到任何问题请到 [这里](https://github.com/Longjianghu/Predator) 提交，非常感谢！