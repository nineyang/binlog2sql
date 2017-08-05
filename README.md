## 介绍
这是一个可以帮助我们把我们的binlog文件的内容转换为可识别的sql语句的工具包。

## 安装
1. composer 安装
```
composer require nine/binlog2sql 
```

2. 直接下载
clone 下来即可。

## 使用
首先在`Conf.php`中配置自己的`MySql`信息:
```php
const __DATABASE__ = 'test';
const __TABLE__ = 'student';
const __USER__ = 'root';
const __PASSWORD__ = 123456;
const __HOST__ = '127.0.0.1';
const __PORT__ = 3309;
```

调用`Binlog`的`start`方法:
```php
$binlog = new \Binlog2sql\Binlog();
$binlog->start();
```
其中`start`方法可以写入一些参数，比如设置起始时间和起始`position`，或者指定的表。