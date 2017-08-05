<?php
/**
 * User: nine
 * Date: 2017/8/4
 * Time: 上午10:25
 */

require_once __DIR__ . '/Binlog.php';
require_once __DIR__ . '/Conf.php';
require_once __DIR__ . '/Util.php';

$binlog = new \Binlog2sql\Binlog();
$binlog->start();

