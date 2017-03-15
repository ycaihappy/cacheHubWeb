<?php

/**
  　* 数据库PDO操作
  　 */
class PDODB {

    public $PDOStatement = null;

    /**
     * 数据库的连接参数配置
     *
     * @var array
     * @access public
     *
     */
    public $config = array();

    /**
     * 是否使用永久连接
     *
     * @var bool
     * @access public
     *
     */
    public $pconnect = false;

    /**
     * 错误信息
     *
     * @var string
     * @access public
     *
     */
    public $error = '';

    /**
     * 单件模式,保存Pdo类唯一实例,数据库的连接资源
     *
     * @var object
     * @access public
     *
     */
    protected $link;

    /**
     * 是否已经连接数据库
     *
     * @var bool
     * @access public
     *
     */
    public $connected = false;

    /**
     * 数据库版本
     *
     * @var string
     * @access public
     *
     */
    public $dbVersion = null;

    /**
     * 当前SQL语句
     *
     * @var string
     * @access public
     *
     */
    public $queryStr = '';

    /**
     * 最后插入记录的ID
     *
     * @var integer
     * @access public
     *
     */
    public $lastInsertId = null;

    /**
     * 返回影响记录数
     *
     * @var integer
     * @access public
     *
     */
    public $numRows = 0;
    // 事务指令数
    public $transTimes = 0;
    public $db_name;

    /**
     * 构造函数，
     *
     * @param $dbconfig 数据库连接相关信息，array('ServerName',
     *        	'UserName', 'Password', 'DefaultDb', 'DB_Port', 'DB_TYPE')
     *
     */
    public static $instances;

    public static function getInstance($config = '', $encrypt = false, $pconnect = true) {
        $config_key = md5(json_encode($config));
        if (empty(self::$instances[$config_key])) {
            $obj = new self();
            if (!class_exists('PDO')) {
//				SRCLog::error("have no class : PDO", __FILE__, __LINE__);
                return false;
            }
            if (!isset($config['dbtype']) || empty($config['dbtype'])) {
                $config['dbtype'] = "mysql";
            }
            if (empty($config)) {
//				SRCLog::error("db config is empty", __FILE__, __LINE__);
                return false;
            }
            if (isset($config['pconnect'])) {
                $pconnect = $config['pconnect'];
            }
            if (isset($config['encrypt'])) {
                $encrypt = $config['encrypt'];
            }
            if ($encrypt) {
                if (isset($config['password_key']) && !empty($config['password_key'])) {
                    $password_key = $config['password_key'];
                } else {
                    $password_key = "zencart";
                }
                $config['password'] = $obj->zen_passport_decrypt($config['password'], $password_key);
            }
            if (!isset($config['charset']) || empty($config['charset'])) {
                $config['charset'] = "utf8";
            }
            if (isset($config['dbname']) && !empty($config['dbname'])) {
                $config['database'] = $config['dbname'];
            }
            if (!isset($config['dsn']) || empty($config['dsn'])) {
                $config['dsn'] = $config['dbtype'] . ":host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['database'];
            }
            $obj->config = $config;
            if (empty($obj->config ['params'])) {
                $obj->config ['params'] = array();
            }
            if (!isset($obj->link)) {
                if (!$obj->createPdoLink($pconnect)) {
//					SRCLog::error("create link failure", __FILE__, __LINE__);
                    return false;
                }
            }
            self::$instances[$config_key] = $obj;
        }
        return self::$instances[$config_key];
    }

    private function createPdoLink($pconnect = true) {
        $configs = $this->config;
        if ($pconnect) {
            $configs ['params'] [constant('PDO::ATTR_PERSISTENT')] = true;
        }
        try {
            $this->link = new PDO($configs ['dsn'], $configs ['username'], $configs ['password'], $configs ['params']);
        } catch (PDOException $e) {
//			SRCLog::error("PDO CONNECT ERROR, ".$e->getMessage (), __FILE__, __LINE__, $configs);
            return false;
        }
        if (!$this->link) {
//			SRCLog::error('PDO CONNECT ERROR', __FILE__, __LINE__, $configs);
            return false;
        }
        $this->link->exec('SET NAMES ' . $configs['charset']);
        $this->dbVersion = $this->link->getAttribute(constant("PDO::ATTR_SERVER_INFO"));
        // 标记连接成功
        $this->connected = true;
        // 注销数据库连接配置信息
        unset($configs);
        return true;
    }

    public function zen_passport_encrypt($txt, $key) {
        srand((double) microtime() * 1000000);
        $encrypt_key = md5(rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr] . ($txt[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode($this->zen_passport_key($tmp, $key));
    }

    public function zen_passport_decrypt($txt, $key) {
        $txt = $this->zen_passport_key(base64_decode($txt), $key);
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $md5 = $txt[$i];
            $tmp .= $txt[++$i] ^ $md5;
        }
        return $tmp;
    }

    public function zen_passport_key($txt, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }

    /**
     * 释放查询结果
     *
     * @access function
     *
     */
    public function free() {
        $this->PDOStatement = null;
    }

    /**
     * ******************************************************************************************************
     */
    /* 数据库操作 */
    /**
     * ******************************************************************************************************
     */

    /**
     * 获得所有的查询数据
     *
     * @access function
     * @return array
     *
     */
    public function getAll($sql = null, $params = array()) {
        $this->query($sql, $params);
        // 返回数据集
        $result = $this->PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
        return $result;
    }

    public function getAllWithColumnIndex($sql, $indexColumn = '', $params = array()) {
        $result = $this->getAll($sql, $params);
        $returnIndexedData = array();
        if ($indexColumn) {
            foreach ($result as $row) {
                $returnIndexedData[$row[$indexColumn]] = $row;
            }
        }
        return $returnIndexedData;
    }

    /**
     * 查询获得一列结果
     * $column列名
     *
     * @param string $sql
     */
    public function getColumn($sql, $columnName, $params = array()) {
        $columnData = array();
        $result = $this->getAll($sql, $params);
        if ($result) {
            foreach ($result as $row) {
                $columnData[] = $row[$columnName];
            }
        }
        return $columnData;
    }

    /**
     * 获得一条查询结果
     *
     * @access function
     * @param string $sql
     *        	SQL指令
     * @param integer $seek
     *        	指针位置
     * @return array
     *
     */
    public function getRow($sql = null, $params=array()) {
        $this->query($sql, $params);
        // 返回数组集
        $result = $this->PDOStatement->fetch(constant('PDO::FETCH_ASSOC'), constant('PDO::FETCH_ORI_NEXT'));
        return $result;
    }

    /**
     * 执行sql语句，自动判断进行查询或者执行操作
     *
     * @access function
     * @param string $sql
     *        	SQL指令
     * @return mixed
     *
     */
    public function doSql($sql = '') {
        if ($this->isMainIps($sql)) {
            return $this->execute($sql);
        } else {
            return $this->getAll($sql);
        }
    }

    /**
     * 根据指定ID查找表中记录(仅用于单表操作)
     *
     * @access function
     * @param integer $priId
     *        	主键ID
     * @param string $tables
     *        	数据表名
     * @param string $fields
     *        	字段名
     * @return ArrayObject 表记录
     *
     */
    public function findById($tabName, $priId, $fields = '*') {
        $sql = 'SELECT %s FROM %s WHERE id=%d';
        return $this->getRow(sprintf($sql, $this->parseFields($fields), $tabName, $priId));
    }

    /**
     * 查找记录
     *
     * @access function
     * @param string $tables
     *        	数据表名
     * @param mixed $where
     *        	查询条件
     * @param string $fields
     *        	字段名
     * @param string $order
     *        	排序
     * @param string $limit
     *        	取多少条数据
     * @param string $group
     *        	分组
     * @param string $having
     * @param boolean $lock
     *        	是否加锁
     * @return ArrayObject
     *
     */
    public function find($tables, $where = "", $fields = '*', $order = null, $limit = null, $group = null, $having = null) {
        $sql = 'SELECT ' . $this->parseFields($fields) . ' FROM ' . $tables . $this->parseWhere($where) . $this->parseGroup($group) . $this->parseHaving($having) . $this->parseOrder($order) . $this->parseLimit($limit);
        $dataAll = $this->getAll($sql);
        if (count($dataAll) == 1) {
            $rlt = $dataAll [0];
        } else {
            $rlt = $dataAll;
        }
        return $rlt;
    }

    /**
     * 插入（单条）记录
     *
     * @access function
     * @param mixed $data
     *        	数据
     * @param string $table
     *        	数据表名
     * @return false | integer
     *
     */
    public function add($data, $table) {
        // 过滤提交数据
        $data = $this->filterPost($table, $data);
        foreach ($data as $key => $val) {
            if (is_array($val) && strtolower($val [0]) == 'exp') {
                $val = $val [1]; // 使用表达式 ???
            } elseif (is_scalar($val)) {
                $val = $this->fieldFormat($val);
            } else {
                // 去掉复合对象
                continue;
            }
            $data [$key] = $val;
        }
        $fields = array_keys($data);
        array_walk($fields, array(
            $this,
            'addSpecialChar'
        ));
        $fieldsStr = implode(',', $fields);
        $values = array_values($data);
        $valuesStr = implode(',', $values);
        $sql = 'INSERT INTO ' . $table . ' (' . $fieldsStr . ') VALUES (' . $valuesStr . ')';
        return $this->execute($sql);
    }

    public function replace($data, $table) {
        // 过滤提交数据
        $data = $this->filterPost($table, $data);
        foreach ($data as $key => $val) {
            if (is_array($val) && strtolower($val [0]) == 'exp') {
                $val = $val [1]; // 使用表达式 ???
            } elseif (is_scalar($val)) {
                $val = $this->fieldFormat($val);
            } else {
                // 去掉复合对象
                continue;
            }
            $data [$key] = $val;
        }
        $fields = array_keys($data);
        array_walk($fields, array(
            $this,
            'addSpecialChar'
        ));
        $fieldsStr = implode(',', $fields);
        $values = array_values($data);
        $valuesStr = implode(',', $values);
        $sql = 'REPLACE INTO ' . $table . ' (' . $fieldsStr . ') VALUES (' . $valuesStr . ')';
        return $this->execute($sql);
    }

    /**
     * 更新记录
     *
     * @access function
     * @param mixed $sets
     *        	数据
     * @param string $table
     *        	数据表名
     * @param string $where
     *        	更新条件
     * @param string $limit
     * @param string $order
     * @return false | integer
     *
     */
    public function update($sets, $table, $where, $limit = 0, $order = '') {
        $sets = $this->filterPost($table, $sets);
        $sql = 'UPDATE ' . $table . ' SET ' . $this->parseSets($sets) . $this->parseWhere($where) . $this->parseOrder($order) . $this->parseLimit($limit);
        return $this->execute($sql);
    }

    /**
     * 保存某个字段的值
     *
     * @access function
     * @param string $field
     *        	要保存的字段名
     * @param string $value
     *        	字段值
     * @param string $table
     *        	数据表
     * @param string $where
     *        	保存条件
     * @param boolean $asString
     *        	字段值是否为字符串
     * @return void
     *
     */
    public function setField($field, $value, $table, $condition = "", $asString = false) {
        // 如果有'(' 视为 SQL指令更新 否则 更新字段内容为纯字符串
        if (false === strpos($value, '(') || $asString)
            $value = '"' . $value . '"';
        $sql = 'UPDATE ' . $table . ' SET ' . $field . '=' . $value . $this->parseWhere($condition);
        return $this->execute($sql);
    }

    /**
     * 删除记录
     *
     * @access function
     * @param mixed $where
     *        	为条件Map、Array或者String
     * @param string $table
     *        	数据表名
     * @param string $limit
     * @param string $order
     * @return false | integer
     *
     */
    public function remove($where, $table, $limit = '', $order = '') {
        $sql = 'DELETE FROM ' . $table . $this->parseWhere($where) . $this->parseOrder($order) . $this->parseLimit($limit);
        return $this->execute($sql);
    }

    /**
     * +----------------------------------------------------------
     * 修改或保存数据(仅用于单表操作)
     * 有主键ID则为修改，无主键ID则为增加
     * 修改记录：
     * +----------------------------------------------------------
     *
     * @access function
     *         +----------------------------------------------------------
     * @param $tabName 表名
     * @param $aPost 提交表单的
     *        	$_POST
     * @param $priId 主键ID
     * @param $aNot 要排除的一个字段或数组
     * @param $aCustom 自定义的一个数组，附加到数据库中保存
     * @param $isExits 是否已经存在
     *        	存在：true, 不存在：false
     *        	+----------------------------------------------------------
     * @return Boolean 修改或保存是否成功
     *         +----------------------------------------------------------
     *
     */
    public function saveOrUpdate($tabName, $aPost, $priId = "", $aNot = "", $aCustom = "", $isExits = false) {
        if (empty($tabName) || !is_array($aPost) || is_int($aNot))
            return false;
        if (is_string($aNot) && !empty($aNot))
            $aNot = array(
                $aNot
            );
        if (is_array($aNot) && is_int(key($aNot)))
            $aPost = array_diff_key($aPost, array_flip($aNot));
        if (is_array($aCustom) && is_string(key($aCustom)))
            $aPost = array_merge($aPost, $aCustom);
        if (empty($priId) && !$isExits) { // 新增
            $aPost = array_filter($aPost, array(
                $this,
                'removeEmpty'
            ));
            return $this->add($aPost, $tabName);
        } else { // 修改
            return $this->update($aPost, $tabName, "id=" . $priId);
        }
    }

    /**
     * 获取最近一次查询的sql语句
     *
     * @access function
     * @param
     *
     * @return String 执行的SQL
     *
     */
    public function getLastSql() {
        $link = $this->link;
        if (!$link)
            return false;
        return $this->queryStr;
    }

    /**
     * 获取最后插入的ID
     *
     * @access function
     * @param
     *
     * @return integer 最后插入时的数据ID
     *
     */
    public function getLastInsId() {
        $link = $this->link;
        if (!$link)
            return false;
        return $this->lastInsertId;
    }

    /**
     * 获取DB版本
     *
     * @access function
     * @param
     *
     * @return string
     *
     */
    public function getDbVersion() {
        $link = $this->link;
        if (!$link)
            return false;
        return $this->dbVersion;
    }

    /**
     * 取得数据库的表信息
     *
     * @access function
     * @return array
     *
     */
    public function getTables() {
        $info = array();
        if ($this->query("SHOW TABLES")) {
            $result = $this->getAll();
            foreach ($result as $key => $val) {
                $info [$key] = current($val);
            }
        }
        return $info;
    }

    /**
     * 取得数据表的字段信息
     *
     * @access function
     * @return array
     *
     */
    public function getFields($tableName) {
        // 获取数据库联接
        $link = $this->link;
        $sql = "SELECT
ORDINAL_POSITION ,COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, 
IF(ISNULL(CHARACTER_MAXIMUM_LENGTH), (NUMERIC_PRECISION + NUMERIC_SCALE), CHARACTER_MAXIMUM_LENGTH) AS MAXCHAR, 
IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA, COLUMN_COMMENT 
FROM 
INFORMATION_SCHEMA.COLUMNS 
WHERE 
TABLE_NAME = :tabName";
        $this->queryStr = sprintf($sql, $tableName);
        $sth = $link->prepare($sql);
        $sth->bindParam(':tabName', $tableName);
        $sth->execute();
        $result = $sth->fetchAll(constant('PDO::FETCH_ASSOC'));
        $info = array();
        foreach ($result as $key => $val) {
            $info [$val ['COLUMN_NAME']] = array(
                'postion' => $val ['ORDINAL_POSITION'],
                'name' => $val ['COLUMN_NAME'],
                'type' => $val ['COLUMN_TYPE'],
                'd_type' => $val ['DATA_TYPE'],
                'length' => $val ['MAXCHAR'],
                'notnull' => (strtolower($val ['IS_NULLABLE']) == "no"),
                'default' => $val ['COLUMN_DEFAULT'],
                'primary' => (strtolower($val ['COLUMN_KEY']) == 'pri'),
                'autoInc' => (strtolower($val ['EXTRA']) == 'auto_increment'),
                'comment' => $val ['COLUMN_COMMENT']
            );
        }
        // 有错误则抛出异常
        $this->haveErrorThrowException();
        return $info;
    }

    /**
     * 关闭数据库
     *
     * @access function
     *
     */
    public function close() {
        $this->link = null;
    }

    /**
     * SQL指令安全过滤
     *
     * @access function
     * @param string $str
     *        	SQL指令
     * @return string
     *
     */
    public function escape_string($str) {
        return addslashes($str);
    }

    /**
     * ******************************************************************************************************
     */
    /* 内部操作方法 */
    /**
     * ******************************************************************************************************
     */

    /**
     * 有出错抛出异常
     *
     * @access function
     * @return
     *
     *
     */
    public function haveErrorThrowException() {
        $obj = empty($this->PDOStatement) ? $this->link : $this->PDOStatement;
        $arrError = $obj->errorInfo();
        if (count($arrError) > 1 && $arrError[0] != '00000') { // 有错误信息
            // $this->rollback();
            $this->error = $arrError [2];
            // throw_exception($this->error);
            throw new Exception($this->error);
            return false;
        }
        // 主要针对execute()方法抛出异常
        if ($this->queryStr == '')
            throw new Exception('Query was empty<br/><br/>[ SQL语句 ] :');
    }

    /**
     * where分析
     *
     * @access function
     * @param mixed $where
     *        	查询条件
     * @return string
     *
     */
    public function parseWhere($where) {
        $whereStr = '';
        if (is_string($where) || is_null($where)) {
            $whereStr = $where;
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * order分析
     *
     * @access function
     * @param mixed $order
     *        	排序
     * @return string
     *
     */
    public function parseOrder($order) {
        $orderStr = '';
        if (is_array($order))
            $orderStr .= ' ORDER BY ' . implode(',', $order);
        else if (is_string($order) && !empty($order))
            $orderStr .= ' ORDER BY ' . $order;
        return $orderStr;
    }

    /**
     * limit分析
     *
     * @access function
     * @param string $limit
     * @return string
     *
     */
    public function parseLimit($limit) {
        $limitStr = '';
        if (is_array($limit)) {
            if (count($limit) > 1)
                $limitStr .= ' LIMIT ' . $limit [0] . ' , ' . $limit [1] . ' ';
            else
                $limitStr .= ' LIMIT ' . $limit [0] . ' ';
        } else if (is_string($limit) && !empty($limit)) {
            $limitStr .= ' LIMIT ' . $limit . ' ';
        }
        return $limitStr;
    }

    /**
     * group分析
     *
     * @access function
     * @param mixed $group
     * @return string
     *
     */
    public function parseGroup($group) {
        $groupStr = '';
        if (is_array($group))
            $groupStr .= ' GROUP BY ' . implode(',', $group);
        else if (is_string($group) && !empty($group))
            $groupStr .= ' GROUP BY ' . $group;
        return empty($groupStr) ? '' : $groupStr;
    }

    /**
     * having分析
     *
     * @access function
     * @param string $having
     * @return string
     *
     */
    public function parseHaving($having) {
        $havingStr = '';
        if (is_string($having) && !empty($having))
            $havingStr .= ' HAVING ' . $having;
        return $havingStr;
    }

    /**
     * fields分析
     *
     * @access function
     * @param mixed $fields
     * @return string
     *
     */
    public function parseFields($fields) {
        if (is_array($fields)) {
            array_walk($fields, array(
                $this,
                'addSpecialChar'
            ));
            $fieldsStr = implode(',', $fields);
        } else if (is_string($fields) && !empty($fields)) {
            if (false === strpos($fields, '`')) {
                $fields = explode(',', $fields);
                array_walk($fields, array(
                    $this,
                    'addSpecialChar'
                ));
                $fieldsStr = implode(',', $fields);
            } else {
                $fieldsStr = $fields;
            }
        } else
            $fieldsStr = '*';
        return $fieldsStr;
    }

    /**
     * sets分析,在更新数据时调用
     *
     * @access function
     * @param mixed $values
     * @return string
     *
     */
    private function parseSets($sets) {
        $setsStr = '';
        if (is_array($sets)) {
            foreach ($sets as $key => $val) {
                $key = $this->addSpecialChar($key);
                $val = $this->fieldFormat($val);
                $setsStr .= "$key = " . $val . ",";
            }
            $setsStr = substr($setsStr, 0, - 1);
        } else if (is_string($sets)) {
            $setsStr = $sets;
        }
        return $setsStr;
    }

    /**
     * 字段格式化
     *
     * @access function
     * @param mixed $value
     * @return mixed
     *
     */
    public function fieldFormat(&$value) {
        if (is_int($value)) {
            $value = intval($value);
        } else if (is_float($value)) {
            $value = floatval($value);
        } elseif (preg_match('/^\(\w*(\+|\-|\*|\/)?\w*\)$/i', $value)) {
            // 支持在字段的值里面直接使用其它字段
            // 例如 (score+1) (name) 必须包含括号
            $value = $value;
        } else if (is_string($value)) {
            $value = '\'' . $this->escape_string($value) . '\'';
        }
        return $value;
    }

    /**
     * 字段和表名添加` 符合
     * 保证指令中使用关键字不出错 针对mysql
     *
     * @access function
     * @param mixed $value
     * @return mixed
     *
     */
    public function addSpecialChar(&$value) {
        if ('*' == $value || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos($value, '`')) {
            // 如果包含* 或者 使用了sql方法 则不作处理
        } elseif (false === strpos($value, '`')) {
            $value = '`' . trim($value) . '`';
        }
        return $value;
    }

    /**
     * +----------------------------------------------------------
     * 去掉空元素
     * +----------------------------------------------------------
     *
     * @access function
     *         +----------------------------------------------------------
     * @param mixed $value
     *        	+----------------------------------------------------------
     * @return mixed +----------------------------------------------------------
     *
     */
    public function removeEmpty($value) {
        return !empty($value);
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     *
     * @access function
     * @param string $sql
     *        	sql指令
     * @return mixed
     *
     */
    public function query($sql = '', $params = array()) {
        // 获取数据库联接
        $bol = false;
        try {
            $link = $this->link;
            if (!$link)
                return false;
            $this->queryStr = $sql;
            // 释放前次的查询结果
            if (!empty($this->PDOStatement))
                $this->free();
            $this->PDOStatement = $link->prepare($this->queryStr);
            if (!empty($params)) {
                foreach($params as $paramName => $paramValue) {
                    $type = isset($paramValue[1]) ? $paramValue[1] : null;
                    $this->PDOStatement->bindValue($paramName, $paramValue[0], $type); 
                }
            }
            $bol = $this->PDOStatement->execute();
            // 有错误则抛出异常
            $this->haveErrorThrowException();
        } catch (Exception $e) {
            if ($e->getMessage() == 'MySQL server has gone away') {
                $this->connected = false;
                $this->createPdoLink();
                if ($this->connected) {
                    return $this->query($sql);
                }
            } else {
                throw $e;
            }
        }

        return $bol;
    }

    /**
     * 数据库操作方法
     *
     * @access function
     * @param string $sql
     *        	执行语句
     * @param boolean $lock
     *        	是否锁定(默认不锁定)
     * @return void public function execute($sql='',$lock=false) {
     *         if(empty($sql)) $sql = $this->queryStr;
     *         return $this->_execute($sql);
     *         }
     */

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     *
     * @access function
     * @param string $sql
     *        	sql指令
     * @return integer
     *
     */
    public function execute($sql = '') {
        // 获取数据库联接
        $link = $this->link;
        if (!$link)
            return false;
        $this->queryStr = $sql;
        // 释放前次的查询结果
        if (!empty($this->PDOStatement))
            $this->free();
        $result = $link->exec($this->queryStr);
        // 有错误则抛出异常
        $this->haveErrorThrowException();
        if (false === $result) {
            return false;
        } else {
            $this->numRows = $result;
            $this->lastInsertId = $link->lastInsertId();
            return $this->numRows;
        }
    }

    /**
     * 是否为数据库更改操作
     *
     * @access private
     * @param string $query
     *        	SQL指令
     * @return boolen 如果是查询操作返回false
     *
     */
    public function isMainIps($query) {
        $queryIps = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK';
        if (preg_match('/^\s*"?(' . $queryIps . ')\s+/i', $query)) {
            return true;
        }
        return false;
    }

    /**
     * 过滤POST提交数据
     *
     * @access private
     * @param mixed $data
     *        	POST提交数据
     * @param string $table
     *        	数据表名
     * @return mixed $newdata
     *
     */
    public function filterPost($table, $data) {
        $table_column = $this->getFields($table);
        $newdata = array();
        foreach ($table_column as $key => $val) {
            if (array_key_exists($key, $data) && ($data [$key]) !== '') {
                $newdata [$key] = $data [$key];
            }
        }
        return $newdata;
    }

    /**
     * 启动事务
     *
     * @access function
     * @return void
     *
     */
    public function startTrans() {
        // 数据rollback 支持
        $link = $this->link;
        if (!$link)
            return false;
        if ($this->transTimes == 0) {
            $link->beginTransaction();
        }
        $this->transTimes ++;
        return;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @access function
     * @return boolen
     *
     */
    public function commit() {
        $link = $this->link;
        if (!$link)
            return false;
        if ($this->transTimes > 0) {
            $result = $link->commit();
            $this->transTimes = 0;
            if (!$result) {
                throw new Exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     * 事务回滚
     *
     * @access function
     * @return boolen
     *
     */
    public function rollback() {
        $link = $this->link;
        if (!$link)
            return false;
        if ($this->transTimes > 0) {
            $result = $link->rollback();
            $this->transTimes = 0;
            if (!$result) {
                throw new Exception($this->error());
                return false;
            }
        }
        return true;
    }

}
