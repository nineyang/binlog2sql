<?php

/**
 * User: nine
 * Date: 2017/8/4
 * Time: 上午10:00
 */

namespace Binlog2sql;

use \PDO;
use \Exception;

/**
 * Class Binlog
 * @package Binlog2sql
 */
class Binlog
{
    /**
     * @var
     */
    protected $_start_position;

    /**
     * @var
     */
    protected $_stop_position;

    /**
     * @var
     */
    protected $_start_time;

    /**
     * @var
     */
    protected $_stop_time;

    /**
     * @var
     */
    protected $_binlog_file;

    /**
     * binlog所在的位置
     * @var
     */
    protected $_binlog_basename;

    /**
     * @var
     */
    protected $_pdo;

    /**
     * mysql binlog_format type
     * @var
     */
    protected $_type;

    /**
     * @var
     */
    protected $_table;

    /**
     * @var
     */
    protected $_tableColumns = [];

    /**
     * Binlog constructor.
     */
    public function __construct()
    {
        try {
            $this->_pdo = new PDO("mysql:host=" . Conf::__HOST__ . ";port=" . Conf::__PORT__ . ";dbname=" .
                Conf::__DATABASE__, Conf::__USER__, Conf::__PASSWORD__);
        } catch (Exception $e) {
            Util::dd($e->getMessage());
        }
    }

    /**
     * @param null $startPosition
     * @param null $endPosition
     * @param null $startDate
     * @param null $endTime
     * @param null $table
     * @param null $binlogFile
     */
    public function start($startPosition = null, $endPosition = null, $startDate = null, $endTime = null, $table = null, $binlogFile = null)
    {
        $this->_table = $table;
        # get binlog info
        $binlogInfo = $this->select("show master status");
        ($binlogInfo && $binlogInfo = array_pop($binlogInfo)) || Util::dd('尚未开启binlog');
//        $this->_start_position = $startPosition ?: $binlogInfo['Position'];
        $this->_start_position = $startPosition ?: 0;
        $this->_binlog_file = $binlogFile ?: $binlogInfo['File'];

        $this->_start_time = $startDate ? date('Y-m-d H:i:s', $startDate) : $startDate;
        $this->_stop_time = $endTime ? date('Y-m-d H:i:s', $endTime) : $endTime;

        # get binlog_format
        $this->_type = array_pop($this->select("show variables like 'binlog_format'"))['Value'];

        # get binlog_basename
        $this->_binlog_basename = explode('/', array_pop($this->select("show variables like 'log_bin_basename'"))['Value']);
        array_pop($this->_binlog_basename);
        $this->_binlog_basename = implode('/', $this->_binlog_basename);

        $this->selectFromBinLog()->parseSql();
    }

    /**
     * @param $sql
     * @return array
     */
    protected function select($sql)
    {
        return $this->_pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return $this
     */
    protected function selectFromBinLog()
    {
        $fillFile = Util::getFile(__DIR__ . '/data/file.sql');
        exec("mysqlbinlog -v --database=" . Conf::__DATABASE__
            . ($this->_start_position ? " --start-position=$this->_start_position " : '')
            . ($this->_stop_position ? " --stop-position=$this->_stop_position " : '')
            . ($this->_start_time ? " --start-datetime=$this->_start_time " : '')
            . ($this->_stop_time ? " --stop-datetime=$this->_stop_time " : '')
            . " $this->_binlog_basename/$this->_binlog_file" .
            " | grep -E -i '###|UPDATE|INSERT|DELETE' >> $fillFile");
        file_put_contents($fillFile, "");
        return $this;
    }

    /**
     * @return $this
     */
    protected function parseSql()
    {
        $fillFileHandler = fopen(__DIR__ . '/data/file.sql', 'r');
        $sqlArr = [];
        if ($this->_type == 'ROW') {
            $match = NULL;
            $sqlStr = "";
            while (($sql = fgets($fillFileHandler)) !== false) {
                if (($match = preg_match('/UPDATE|INSERT|DELETE/', $sql)) || strrpos($sql, 'end_log_pos') !== false) {
                    # 如果有指定表
                    if ($match && $this->_table && strpos($sql, $this->_table) === false) continue;
                    $sqlStr == '' || array_push($sqlArr, $sqlStr);
                    $sqlStr = $match ? trim(substr($sql, 3, -1)) . " " : "";
                } elseif (strpos($sql, '@') !== false || strpos($sql, 'SET')) {
                    $sqlStr .= trim(substr($sql, 3, -1)) . " ";
                }
            }
            $sqlStr == '' || array_push($sqlArr, $sqlStr);
        } else {
            # statement 和 mixed格式一样
            while (($sql = fgets($fillFileHandler)) !== false) {
                $sql = trim($sql);
                if (preg_match('/(UPDATE|INSERT|DELETE)\s+/', $sql)) {
                    array_push($sqlArr, $sql);
                }
            }
        }
        $sqlArr = array_map(function ($value) {
            return preg_replace_callback('/(@(\d+))/', function ($matches) use ($value) {
                $parts = explode('.', $value);
                return $this->getTableColumns(explode('`', array_pop($parts))[1])[$matches[2] - 1];
            }, $value);
        }, $sqlArr);

        $mysqlFile = Util::getFile(__DIR__ . '/data/mysql.sql');

        array_map(function ($value) use ($mysqlFile) {
            file_put_contents($mysqlFile, $value . PHP_EOL, FILE_APPEND);
        }, $sqlArr);
        fclose($fillFileHandler);

        return $this;
    }

    /**
     * @param $table
     * @return array
     */
    protected function getTableColumns($table)
    {
        if (array_key_exists($table, $this->_tableColumns))
            return $this->_tableColumns[$table];
        $tableInfo = $this->select("show full columns from $table");
        if (empty($tableInfo)) Util::dd("$table 不存在");
        return $this->_tableColumns[$table] = array_column($tableInfo, 'Field');
    }

}