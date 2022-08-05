<?php

namespace JTL\DB;

use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JTL\Exceptions\InvalidEntityNameException;
use JTL\Profiler;
use JTL\Shop;
use PDO;
use PDOException;
use PDOStatement;
use stdClass;

/**
 * Class NiceDB
 * @package JTL\DB
 */
class NiceDB implements DbInterface
{
    /**
     * @var pdo
     */
    protected $db;

    /**
     * @var bool
     */
    protected $isConnected = false;

    /**
     * @var bool
     */
    public $logErrors = false;

    /**
     * debug mode
     *
     * @var bool
     */
    private $debug = false;

    /**
     * debug level, 0 no debug, 1 normal, 2 verbose, 3 very verbose with backtrace
     *
     * @var int
     */
    private $debugLevel = 0;

    /**
     * @var NiceDB
     */
    private static $instance;

    /**
     * @var PDO|null
     */
    private $pdo;

    /**
     * @var string
     */
    public $state = 'instanciated';

    /**
     * @var array
     */
    private $config;

    /**
     * @var int
     */
    private $transactionCount = 0;

    /**
     * @deprecated Use ReturnType::SINGLE_OBJECT instead
     */
    public const RET_SINGLE_OBJECT = ReturnType::SINGLE_OBJECT;
    /**
     * @deprecated Use ReturnType::ARRAY_OF_OBJECTS instead
     */
    public const RET_ARRAY_OF_OBJECTS = ReturnType::ARRAY_OF_OBJECTS;
    /**
     * @deprecated Use ReturnType::AFFECTED_ROWS instead
     */
    public const RET_AFFECTED_ROWS = ReturnType::AFFECTED_ROWS;
    /**
     * @deprecated Use ReturnType::LAST_INSERTED_ID instead
     */
    public const RET_LAST_INSERTED_ID = ReturnType::LAST_INSERTED_ID;
    /**
     * @deprecated Use ReturnType::SINGLE_ASSOC_ARRAY instead
     */
    public const RET_SINGLE_ASSOC_ARRAY = ReturnType::SINGLE_ASSOC_ARRAY;
    /**
     * @deprecated Use ReturnType::SINGLE_ASSOC_ARRAY instead
     */
    public const RET_ARRAY_OF_ASSOC_ARRAYS = ReturnType::ARRAY_OF_ASSOC_ARRAYS;
    /**
     * @deprecated Use ReturnType::QUERYSINGLE instead
     */
    public const RET_QUERYSINGLE = ReturnType::QUERYSINGLE;
    /**
     * @deprecated Use ReturnType::ARRAY_OF_BOTH_ARRAYS instead
     */
    public const RET_ARRAY_OF_BOTH_ARRAYS = ReturnType::ARRAY_OF_BOTH_ARRAYS;

    /**
     * create DB Connection with default parameters
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db
     * @param bool   $forceDebug
     */
    public function __construct(string $host, string $user, string $pass, string $db, bool $forceDebug = false)
    {
        $dsn          = 'mysql:dbname=' . $db;
        $this->config = [
            'driver'   => 'mysql',
            'host'     => $host,
            'database' => $db,
            'username' => $user,
            'password' => $pass,
            'charset'  => \DB_CHARSET,
        ];
        if (\defined('DB_SOCKET')) {
            $dsn .= ';unix_socket=' . \DB_SOCKET;
        } else {
            $dsn .= ';host=' . $host;
        }
        $this->pdo = new PDO($dsn, $user, $pass, $this->getOptions());
        if (\DB_DEFAULT_SQL_MODE !== true) {
            $this->pdo->exec("SET SQL_MODE=''");
        }
        $this->initDebugging($forceDebug);
        $this->isConnected = true;
        self::$instance    = $this;
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        $options = [];
        if (\defined('DB_SSL_KEY') && \defined('DB_SSL_CERT') && \defined('DB_SSL_CA')) {
            $options = [
                PDO::MYSQL_ATTR_SSL_KEY  => \DB_SSL_KEY,
                PDO::MYSQL_ATTR_SSL_CERT => \DB_SSL_CERT,
                PDO::MYSQL_ATTR_SSL_CA   => \DB_SSL_CA
            ];
        }
        if (\defined('DB_PERSISTENT_CONNECTIONS') && \is_bool(\DB_PERSISTENT_CONNECTIONS)) {
            $options[PDO::ATTR_PERSISTENT] = \DB_PERSISTENT_CONNECTIONS;
        }
        if (\defined('DB_CHARSET')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '" . \DB_CHARSET . "'" . (\defined('DB_COLLATE')
                    ? " COLLATE '" . \DB_COLLATE . "'"
                    : '');
        }
        // this was added for compatibility with 5.1.2 and php8.1
        if (\PHP_VERSION_ID >= 80100) {
            $options[PDO::ATTR_STRINGIFY_FETCHES] = true;
        }

        return $options;
    }

    /**
     * @param bool $debugOverride
     */
    private function initDebugging(bool $debugOverride = false): void
    {
        if ($debugOverride === false && \PROFILE_QUERIES !== false) {
            $this->debugLevel = \DEBUG_LEVEL;
            if (\PROFILE_QUERIES === true) {
                $this->debug = true;
            }
        }
        if (\ES_DB_LOGGING === true) {
            $this->logErrors = true;
        }
        if (\ES_DB_LOGGING === true || \NICEDB_EXCEPTION_BACKTRACE === true) {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * @param null|string $host
     * @param null|string $user
     * @param null|string $pass
     * @param null|string $db
     * @return NiceDB
     * @throws Exception
     * @deprecated since 5.0.0 use Shop::Container()->getDB() instead
     */
    public static function getInstance(
        string $host = null,
        string $user = null,
        string $pass = null,
        string $db = null
    ): DbInterface {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        return self::$instance ?? new self($host, $user, $pass, $db);
    }

    /**
     * descructor for debugging purposes and closing db connection
     */
    public function __destruct()
    {
        $this->state = 'destructed';
        if ($this->isConnected) {
            $this->close();
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function reInit(): DbInterface
    {
        $dsn = 'mysql:dbname=' . $this->config['database'];
        if (\defined('DB_SOCKET')) {
            $dsn .= ';unix_socket=' . \DB_SOCKET;
        } else {
            $dsn .= ';host=' . $this->config['host'];
        }
        $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
        if (\defined('DB_CHARSET')) {
            $this->pdo->exec(
                "SET NAMES '" . \DB_CHARSET . "'" . (\defined('DB_COLLATE')
                    ? " COLLATE '" . \DB_COLLATE . "'"
                    : '')
            );
        }

        return $this;
    }

    /**
     * @param string     $stmt
     * @param array|null $assigns
     * @param array|null $named
     * @param float      $time
     * @return DbInterface
     */
    private function analyzeQuery(
        string $stmt,
        array $assigns = null,
        array $named = null,
        float $time = 0
    ): DbInterface {
        if ($this->debug !== true
            || \mb_strpos($stmt, 'tprofiler') !== false
            || \mb_stripos($stmt, 'create table') !== false
        ) {
            return $this;
        }
        $backtrace = $this->debugLevel > 2 ? \debug_backtrace() : null;
        $explain   = 'EXPLAIN ' . $stmt;
        try {
            if ($named !== null) {
                $res = $this->pdo->prepare($explain);
                foreach ($named as $k => $v) {
                    $this->_bind($res, $k, $v);
                }
                $res->execute();
            } elseif ($assigns !== null) {
                $res = $this->pdo->prepare($explain);
                $res->execute($assigns);
            } else {
                $res = $this->pdo->query($explain);
            }
        } catch (PDOException $e) {
            $this->handleException($e, $stmt, $assigns);

            return $this;
        }
        if ($res === false) {
            return $this;
        }
        $backtrace = $this->getBacktrace($backtrace);
        while (($row = $res->fetchObject()) !== false) {
            if (!empty($row->table)) {
                $tableData            = new stdClass();
                $tableData->type      = $row->select_type ?? '???';
                $tableData->table     = $row->table;
                $tableData->count     = 1;
                $tableData->time      = $time;
                $tableData->hash      = \md5($stmt);
                $tableData->statement = null;
                $tableData->backtrace = null;
                if ($this->debugLevel > 1) {
                    $tableData->statement = \preg_replace('/\s\s+/', ' ', \mb_substr($stmt, 0, \NICEDB_DEBUG_STMT_LEN));
                    $tableData->backtrace = $backtrace;
                }
                Profiler::setSQLProfile($tableData);
            } elseif ($this->debugLevel > 1 && isset($row->Extra)) {
                $tableData            = new stdClass();
                $tableData->type      = $row->select_type ?? '???';
                $tableData->message   = $row->Extra;
                $tableData->statement = \preg_replace('/\s\s+/', ' ', $stmt);
                $tableData->backtrace = $backtrace;
                Profiler::setSQLError($tableData);
            }
        }

        return $this;
    }

    /**
     * @param array|null $backtrace
     * @return array|null
     */
    private function getBacktrace(?array $backtrace = null): ?array
    {
        if (!\is_array($backtrace)) {
            return null;
        }
        $stripped = [];
        foreach ($backtrace as $bt) {
            $bt['class']    = $bt['class'] ?? '';
            $bt['function'] = $bt['function'] ?? '';
            if (isset($bt['file'])
                && !($bt['class'] === __CLASS__ && $bt['function'] === '__call')
                && \mb_strpos($bt['file'], 'NiceDB.php') === false
            ) {
                $stripped[] = [
                    'file'     => $bt['file'],
                    'line'     => $bt['line'],
                    'class'    => $bt['class'],
                    'function' => $bt['function']
                ];
            }
        }

        return $stripped;
    }

    /**
     * @inheritdoc
     */
    public function close(): bool
    {
        $this->pdo = null;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * @inheritdoc
     */
    public function getServerInfo(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * @return string
     * @deprecated since 5.1.0
     */
    public function info(): string
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use getServerInfo() instead.', \E_USER_DEPRECATED);
        return $this->getServerInfo();
    }

    /**
     * @inheritdoc
     */
    public function getServerStats(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    }

    /**
     * @return string
     * @deprecated since 5.1.0
     */
    public function stats(): string
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use getServerStats() instead.', \E_USER_DEPRECATED);
        return $this->getServerStats();
    }

    /**
     * get db object
     *
     * @return PDO
     * @deprecated since 5.1.0
     */
    public function DB(): PDO
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use getPDO() instead.', \E_USER_DEPRECATED);
        return $this->pdo;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * @inheritdoc
     * @throws InvalidEntityNameException
     * @throws InvalidArgumentException
     */
    public function insertRow(string $tableName, $object, bool $echo = false): int
    {
        $start = \microtime(true);
        $this->validateEntityName($tableName);
        $this->validateDbObject($object);
        $arr     = \get_object_vars($object);
        $keys    = []; //column names
        $values  = []; //column values - either sql statement like "now()" or prepared like ":my-var-name"
        $assigns = []; //assignments from prepared var name to values, will be inserted in ->prepare()
        foreach ($arr as $col => $val) {
            $keys[] = '`' . $col . '`';
            if ($val === '_DBNULL_') {
                $val = null;
            } elseif ($val === null) {
                $val = '';
            }
            $lc = \mb_convert_case((string)$val, \MB_CASE_LOWER);
            if ($lc === 'now()' || $lc === 'current_timestamp') {
                $values[] = $val;
            } else {
                $values[]            = ':' . $col;
                $assigns[':' . $col] = $val;
            }
        }
        $stmt = 'INSERT INTO ' . $tableName .
            ' (' . \implode(', ', $keys) . ') VALUES (' . \implode(', ', $values) . ')';
        if ($echo) {
            echo $stmt;
        }
        try {
            $s   = $this->pdo->prepare($stmt);
            $res = $s->execute($assigns);
        } catch (PDOException $e) {
            $this->handleException($e, $stmt, $assigns);

            return 0;
        }
        if (!$res) {
            $this->logError($stmt);

            return 0;
        }
        $id = $this->pdo->lastInsertId();
        if (\mb_strpos($tableName, 'tprofiler') !== 0) {
            $this->analyzeQuery($stmt, $assigns, null, \microtime(true) - $start);
        }

        return $id > 0 ? (int)$id : 1;
    }

    /**
     * @inheritdoc
     */
    public function insert(string $tableName, $object, bool $echo = false): int
    {
        return $this->insertRow($tableName, $object, $echo);
    }

    /**
     * @inheritdoc
     */
    public function updateRow(string $tableName, $keyname, $keyvalue, $object, bool $echo = false): int
    {
        $start = \microtime(true);
        $this->validateEntityName($tableName);
        foreach ((array)$keyname as $x) {
            $this->validateEntityName($x);
        }
        $this->validateDbObject($object);
        $arr     = \get_object_vars($object);
        $updates = []; //list of "<column name>=?" or "<column name>=now()" strings
        $assigns = []; //list of values to insert as param for ->prepare()
        if (!\is_array($arr) || !$keyname || !$keyvalue) {
            return -1;
        }
        foreach ($arr as $_key => $_val) {
            if ($_val === '_DBNULL_') {
                $_val = null;
            } elseif ($_val === null) {
                $_val = '';
            }
            $lc = \mb_convert_case((string)$_val, \MB_CASE_LOWER);
            if ($lc === 'now()' || $lc === 'current_timestamp') {
                $updates[] = '`' . $_key . '`=' . $_val;
            } else {
                $updates[] = '`' . $_key . '`=?';
                $assigns[] = $_val;
            }
        }
        if (\is_array($keyname) && \is_array($keyvalue)) {
            $keynamePrepared = \array_map(static function ($_v) {
                return '`' . $_v . '`=?';
            }, $keyname);
            $where           = ' WHERE ' . \implode(' AND ', $keynamePrepared);
            foreach ($keyvalue as $_v) {
                $assigns[] = $_v;
            }
        } else {
            $assigns[] = $keyvalue;
            $where     = ' WHERE `' . $keyname . '`=?';
        }
        $stmt = 'UPDATE ' . $tableName . ' SET ' . \implode(',', $updates) . $where;
        if ($echo) {
            echo $stmt;
        }
        try {
            $s   = $this->pdo->prepare($stmt);
            $res = $s->execute($assigns);
        } catch (PDOException $e) {
            $this->handleException($e, $stmt, $assigns);

            return -1;
        }
        if (!$res) {
            $this->logError($stmt);
            $ret = -1;
        } else {
            $ret = $s->rowCount();
        }
        $this->analyzeQuery($stmt, $assigns, null, \microtime(true) - $start);

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function update(string $tableName, $keyname, $keyvalue, $object, bool $echo = false): int
    {
        return $this->updateRow($tableName, $keyname, $keyvalue, $object, $echo);
    }

    /**
     * @inheritdoc
     * @throws InvalidEntityNameException
     */
    public function upsert(string $tableName, $object, array $excludeUpdate = [], bool $echo = false): int
    {
        $start = \microtime(true);
        $this->validateEntityName($tableName);
        $this->validateDbObject($object);
        $insData = [];
        $updData = [];
        $assigns = [];
        foreach ($object as $column => $value) {
            if ($value === '_DBNULL_') {
                $value = null;
            } elseif ($value === null) {
                $value = '';
            }
            $lc = \mb_convert_case((string)$value, \MB_CASE_LOWER);
            if ($lc === 'now()' || $lc === 'current_timestamp') {
                $insData['`' . $column . '`'] = $value;
                if (!\in_array($column, $excludeUpdate, true)) {
                    $updData[] = '`' . $column . '` = ' . $value;
                }
            } else {
                $insData['`' . $column . '`'] = ':' . $column;
                $assigns[':' . $column]       = $value;
                if (!\in_array($column, $excludeUpdate, true)) {
                    $updData[] = '`' . $column . '` = :' . $column;
                }
            }
        }

        $sql = 'INSERT' . (\count($updData) > 0 ? ' ' : ' IGNORE ') . 'INTO ' . $tableName
            . '(' . \implode(', ', \array_keys($insData)) . ')
                    VALUES (' . \implode(', ', $insData) . ')' . (\count($updData) > 0 ? ' ON DUPLICATE KEY
                    UPDATE ' . \implode(', ', $updData) : '');
        if ($echo) {
            echo $sql;
        }
        $stmt = $this->pdo->prepare($sql);
        try {
            $res = $stmt->execute($assigns);
        } catch (PDOException $e) {
            $this->handleException($e, $sql, $assigns);

            return -1;
        }

        if (!$res) {
            $this->logError($sql);

            return -1;
        }

        $lastID = $this->pdo->lastInsertId();
        $this->analyzeQuery($sql, $assigns, null, \microtime(true) - $start);

        return (int)$lastID;
    }

    /**
     * @inheritdoc
     * @throws InvalidEntityNameException
     */
    public function selectSingleRow(
        string $tableName,
        $keyname,
        $keyvalue,
        $keyname1 = null,
        $keyvalue1 = null,
        $keyname2 = null,
        $keyvalue2 = null,
        bool $echo = false,
        string $select = '*'
    ) {
        $start = \microtime(true);
        $this->validateEntityName($tableName);
        foreach ((array)$keyname as $x) {
            $this->validateEntityName($x);
        }
        if ($keyname1 !== null) {
            $this->validateEntityName($keyname1);
        }
        if ($keyname2 !== null) {
            $this->validateEntityName($keyname2);
        }
        $keys    = \is_array($keyname) ? $keyname : [$keyname, $keyname1, $keyname2];
        $values  = \is_array($keyvalue) ? $keyvalue : [$keyvalue, $keyvalue1, $keyvalue2];
        $assigns = [];
        $i       = 0;
        foreach ($keys as &$_key) {
            if ($_key !== null) {
                $_key      = '`' . $_key . '`=?';
                $assigns[] = $values[$i];
            } else {
                unset($keys[$i]);
            }
            ++$i;
        }
        unset($_key);
        $stmt = 'SELECT ' . $select .
            ' FROM ' . $tableName .
            ((\count($keys) > 0)
                ? (' WHERE ' . \implode(' AND ', $keys))
                : ''
            );
        if ($echo) {
            echo $stmt;
        }
        try {
            $s   = $this->pdo->prepare($stmt);
            $res = $s->execute($assigns);
        } catch (PDOException $e) {
            $this->handleException($e, $stmt, $assigns);

            return null;
        }
        if (!$res) {
            $this->logError($stmt);

            return null;
        }
        $ret = $s->fetchObject();
        $this->analyzeQuery($stmt, $assigns, null, \microtime(true) - $start);

        return $ret !== false ? $ret : null;
    }

    /**
     * @inheritdoc
     */
    public function select(
        string $tableName,
        $keyname,
        $keyvalue,
        $keyname1 = null,
        $keyvalue1 = null,
        $keyname2 = null,
        $keyvalue2 = null,
        bool $echo = false,
        string $select = '*'
    ) {
        return $this->selectSingleRow(
            $tableName,
            $keyname,
            $keyvalue,
            $keyname1,
            $keyvalue1,
            $keyname2,
            $keyvalue2,
            $echo,
            $select
        );
    }

    /**
     * @inheritdoc
     */
    public function selectArray(
        string $tableName,
        $keys,
        $values,
        string $select = '*',
        string $orderBy = '',
        $limit = ''
    ) {
        $this->validateEntityName($tableName);
        foreach ((array)$keys as $key) {
            $this->validateEntityName($key);
        }

        $keys   = \is_array($keys) ? $keys : [$keys];
        $values = \is_array($values) ? $values : [$values];
        $kv     = [];
        if (\count($keys) !== \count($values)) {
            throw new InvalidArgumentException('Number of keys must be equal to number of given keys. Got ' .
                \count($keys) . ' key(s) and ' . \count($values) . ' value(s).');
        }
        foreach ($keys as $_key) {
            $kv[] = '`' . $_key . '`=:' . $_key;
        }
        $stmt = 'SELECT ' . $select . ' FROM ' . $tableName .
            ((\count($keys) > 0) ?
                (' WHERE ' . \implode(' AND ', $kv)) :
                ''
            ) .
            (!empty($orderBy) ? (' ORDER BY ' . $orderBy) : '') .
            (!empty($limit) ? (' LIMIT ' . $limit) : '');

        $res = $this->_execute(1, $stmt, \array_combine($keys, $values), ReturnType::ARRAY_OF_OBJECTS);

        if (\is_array($res)) {
            return $res;
        }

        throw new InvalidArgumentException(
            'The queried table "' . $tableName . '" or one of its columns "' . $select . '" might not exist.'
        );
    }

    /**
     * @inheritdoc
     */
    public function selectAll(
        string $tableName,
        $keys,
        $values,
        string $select = '*',
        string $orderBy = '',
        $limit = ''
    ) {
        return $this->selectArray($tableName, $keys, $values, $select, $orderBy, $limit);
    }

    /**
     * @inheritdoc
     */
    public function executeQuery(string $stmt, int $return = ReturnType::DEFAULT, bool $echo = false, $fnInfo = null)
    {
        return $this->_execute(0, $stmt, [], $return, $echo, $fnInfo);
    }

    /**
     * @inheritdoc
     */
    public function executeQueryPrepared(
        string $stmt,
        array $params,
        int $return = ReturnType::DEFAULT,
        bool $echo = false,
        $fnInfo = null
    ) {
        return $this->_execute(1, $stmt, $params, $return, $echo, $fnInfo);
    }

    /**
     * @inheritdoc
     */
    public function queryPrepared(
        string $stmt,
        array $params,
        int $return = ReturnType::DEFAULT,
        bool $echo = false,
        $fnInfo = null
    ) {
        return $this->_execute(1, $stmt, $params, $return, $echo, $fnInfo);
    }

    /**
     * @inheritdoc
     */
    public function getArrays(string $stmt, array $params = []): array
    {
        return $this->_execute(1, $stmt, $params, ReturnType::ARRAY_OF_ASSOC_ARRAYS);
    }

    /**
     * @inheritdoc
     */
    public function getObjects(string $stmt, array $params = []): array
    {
        return $this->_execute(1, $stmt, $params, ReturnType::ARRAY_OF_OBJECTS);
    }

    /**
     * @inheritdoc
     */
    public function getCollection(string $stmt, array $params = []): Collection
    {
        return $this->_execute(1, $stmt, $params, ReturnType::COLLECTION);
    }

    /**
     * @inheritdoc
     */
    public function getSingleObject(string $stmt, array $params = []): ?stdClass
    {
        $res = $this->_execute(1, $stmt, $params, ReturnType::SINGLE_OBJECT);

        return $res !== false ? $res : null;
    }

    /**
     * @inheritdoc
     */
    public function getSingleArray(string $stmt, array $params = []): ?array
    {
        return $this->_execute(1, $stmt, $params, ReturnType::SINGLE_ASSOC_ARRAY);
    }

    /**
     * @inheritdoc
     */
    public function getAffectedRows(string $stmt, array $params = []): int
    {
        return $this->_execute(1, $stmt, $params, ReturnType::AFFECTED_ROWS);
    }

    /**
     * @inheritdoc
     */
    public function getPDOStatement(string $stmt, array $params = []): PDOStatement
    {
        return $this->_execute(1, $stmt, $params, ReturnType::QUERYSINGLE);
    }

    /**
     * executes query and returns misc data
     *
     * @param int           $type - Type [0 => query, 1 => prepared]
     * @param string        $stmt - Statement to be executed
     * @param array|null    $params - An array of values with as many elements as there are bound parameters
     * @param int           $return - what should be returned.
     * @param bool          $echo print current stmt
     * @param null|callable $fnInfo
     * 1  - single fetched object
     * 2  - array of fetched objects
     * 3  - affected rows
     * 7  - last inserted id
     * 8  - fetched assoc array
     * 9  - array of fetched assoc arrays
     * 10 - result of querysingle
     * 11 - fetch both arrays
     * @return array|object|int - 0 if fails, 1 if successful or LastInsertID if specified
     * @throws InvalidArgumentException
     */
    protected function _execute(int $type, string $stmt, $params, int $return, bool $echo = false, $fnInfo = null)
    {
        $params = \is_array($params) ? $params : [];
        if (!\in_array($type, [0, 1], true)) {
            throw new InvalidArgumentException('$type parameter must be 0 or 1, "' . $type . '" given');
        }
        if ($return <= 0 || $return > 12) {
            throw new InvalidArgumentException('$return parameter must be between 1 - 12, "' . $return . '" given');
        }
        if ($fnInfo !== null && !\is_callable($fnInfo)) {
            throw new InvalidArgumentException('$fnInfo parameter is not callable, given: ' . \gettype($fnInfo));
        }

        if ($echo) {
            echo $stmt;
        }

        $start = \microtime(true);
        try {
            if ($type === 0) {
                $res = $this->pdo->query($stmt);
            } else {
                $res = $this->pdo->prepare($stmt);
                foreach ($params as $k => $v) {
                    $this->_bind($res, $k, $v);
                }
                if ($res->execute() === false) {
                    return $this->failExecute($return);
                }
            }
        } catch (PDOException $e) {
            $this->handleException($e, $this->readableQuery($stmt, $params));
            if ($this->transactionCount > 0) {
                throw $e;
            }

            return $this->failExecute($return);
        }

        if ($fnInfo !== null) {
            $info = [
                'mysqlerrno' => $this->pdo->errorCode(),
                'statement'  => $stmt,
                'time'       => \microtime(true) - $start
            ];
            $fnInfo($info);
        }

        if (!$res) {
            $this->logError($this->readableQuery($stmt, $params));

            return $this->failExecute($return);
        }

        $ret = $this->getQueryResult($return, $res);
        $this->analyzeQuery($stmt, null, $type === 0 ? null : $params, \microtime(true) - $start);

        return $ret;
    }

    /**
     * @param int $returnType
     * @return array|bool|Collection|int|PDOStatement|stdClass
     */
    private function failExecute(int $returnType)
    {
        switch ($returnType) {
            case ReturnType::COLLECTION:
                return new Collection();
            case ReturnType::ARRAY_OF_OBJECTS:
            case ReturnType::ARRAY_OF_ASSOC_ARRAYS:
            case ReturnType::ARRAY_OF_BOTH_ARRAYS:
            case ReturnType::SINGLE_ASSOC_ARRAY:
                return [];
            case ReturnType::SINGLE_OBJECT:
                return new stdClass();
            case ReturnType::QUERYSINGLE:
                return new PDOStatement();
            case ReturnType::DEFAULT:
                return true;
            default:
                return 0;
        }
    }

    /**
     * @param int          $type
     * @param PDOStatement $statement
     * @return array|Collection|int|mixed|string
     */
    private function getQueryResult(int $type, PDOStatement $statement)
    {
        switch ($type) {
            case ReturnType::SINGLE_OBJECT:
                try {
                    $result = $statement->fetchObject();
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                    $result = false;
                }
                break;
            case ReturnType::ARRAY_OF_OBJECTS:
                $result = [];
                try {
                    while (($row = $statement->fetchObject()) !== false) {
                        $result[] = $row;
                    }
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                }
                break;
            case ReturnType::COLLECTION:
                $result = new Collection();
                try {
                    while (($row = $statement->fetchObject()) !== false) {
                        $result->push($row);
                    }
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                }
                break;
            case ReturnType::AFFECTED_ROWS:
                try {
                    $result = $statement->rowCount();
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                    $result = 0;
                }
                break;
            case ReturnType::LAST_INSERTED_ID:
                $id     = $this->pdo->lastInsertId();
                $result = ($id > 0) ? $id : 1;
                break;
            case ReturnType::SINGLE_ASSOC_ARRAY:
                try {
                    $result = $statement->fetchAll(PDO::FETCH_NAMED);
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                    $result = null;
                }
                if (\is_array($result) && isset($result[0])) {
                    $result = $result[0];
                } else {
                    $result = null;
                }
                break;
            case ReturnType::ARRAY_OF_ASSOC_ARRAYS:
                try {
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                    $result = [];
                }
                break;
            case ReturnType::QUERYSINGLE:
                $result = $statement;
                break;
            case ReturnType::ARRAY_OF_BOTH_ARRAYS:
                try {
                    $result = $statement->fetchAll(PDO::FETCH_BOTH);
                } catch (Exception $e) {
                    $this->logError($statement->queryString, $e);
                    $result = [];
                }
                break;
            default:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws InvalidEntityNameException
     */
    public function deleteRow(string $tableName, $keyname, $keyvalue, bool $echo = false): int
    {
        $this->validateEntityName($tableName);
        foreach ((array)$keyname as $i) {
            $this->validateEntityName($i);
        }
        $start   = \microtime(true);
        $assigns = [];
        if (\is_array($keyname) && \is_array($keyvalue)) {
            $keyname = \array_map(static function ($_v) {
                return '`' . $_v . '`=?';
            }, $keyname);
            $where   = \implode(' AND ', $keyname);
            foreach ($keyvalue as $_v) {
                $assigns[] = $_v;
            }
        } else {
            $assigns[] = $keyvalue;
            $where     = '`' . $keyname . '`=?';
        }

        $stmt = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;

        if ($echo) {
            echo $stmt;
        }
        try {
            $s   = $this->pdo->prepare($stmt);
            $res = $s->execute($assigns);
        } catch (PDOException $e) {
            $this->handleException($e, $stmt);

            return -1;
        }
        if (!$res) {
            $this->logError($stmt);

            return -1;
        }
        $ret = $s->rowCount();
        $this->analyzeQuery($stmt, $assigns, null, \microtime(true) - $start);

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $tableName, $keyname, $keyvalue, bool $echo = false): int
    {
        return $this->deleteRow($tableName, $keyname, $keyvalue, $echo);
    }

    /**
     * @inheritdoc
     */
    public function executeExQuery($stmt)
    {
        try {
            $res = $this->pdo->query($stmt);
        } catch (PDOException $e) {
            $this->handleException($e, $stmt);

            return 0;
        }
        if (!$res) {
            $res = 0;
            $this->logError($stmt);
        }

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function query(string $stmt, int $return = ReturnType::DEFAULT, bool $echo = false)
    {
        return $this->_execute(0, $stmt, [], $return, $echo);
    }

    /**
     * @param string $stmt
     * @return PDOStatement|int
     * @deprecated since 5.1.0
     */
    public function exQuery($stmt)
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use executeExQuery() instead.', \E_USER_DEPRECATED);
        return $this->executeExQuery($stmt);
    }

    /**
     * @param mixed $res
     * @return bool
     */
    protected function isPdoResult($res): bool
    {
        return \is_object($res) && $res instanceof PDOStatement;
    }

    /**
     * @inheritdoc
     */
    public function quote($string): string
    {
        if (\is_bool($string)) {
            $string = $string ?: '0';
        }

        return $this->pdo->quote((string)$string);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string
     * @return string
     */
    public function escape($string): string
    {
        // remove outer single quotes
        return \preg_replace('/^\'(.*)\'$/', '$1', $this->quote($string));
    }

    /**
     * @param string $string
     * @return string
     * @deprecated since 5.1.0
     */
    public function pdoEscape($string): string
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use escape() instead.', \E_USER_DEPRECATED);
        return $this->escape($string);
    }

    /**
     * @param string $string
     * @return string
     * @deprecated since 5.1.0
     */
    public function realEscape($string): string
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use escape() instead.', \E_USER_DEPRECATED);
        return $this->escape($string);
    }

    /**
     * @param string $entry
     * @return DbInterface
     */
    public function writeLog(string $entry): DbInterface
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        Shop::Container()->getLogService()->error($entry);

        return $this;
    }

    /**
     * @return mixed
     * @deprecated since 5.1.0
     */
    public function _getErrorCode()
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        return $this->getErrorCode();
    }

    /**
     * @inheritdoc
     */
    public function getErrorCode()
    {
        $errorCode = $this->pdo->errorCode();

        return $errorCode !== '00000' ? $errorCode : 0;
    }

    /**
     * @return array
     * @deprecated since 5.1.0
     */
    public function _getError(): array
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use getError() instead.', \E_USER_DEPRECATED);
        return $this->getError();
    }

    /**
     * @inheritdoc
     */
    public function getError(): array
    {
        return $this->pdo->errorInfo();
    }

    /**
     * @return string
     * @deprecated since 5.1.0
     */
    public function _getErrorMessage(): string
    {
        \trigger_error(__METHOD__ . ' is deprecated. Use getErrorMessage() instead.', \E_USER_DEPRECATED);
        return $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage(): string
    {
        $error = $this->getError();
        if (\is_array($error) && isset($error[2])) {
            return \is_string($error[2]) ? $error[2] : '';
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function beginTransaction(): bool
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->transactionCount++ <= 0) {
            return $this->pdo->beginTransaction();
        }

        return $this->transactionCount >= 0;
    }

    /**
     * @inheritdoc
     */
    public function commit(): bool
    {
        if ($this->transactionCount-- === 1) {
            return $this->pdo->commit();
        }
        if (\NICEDB_EXCEPTION_BACKTRACE === false) {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }

        return $this->transactionCount <= 0;
    }

    /**
     * @inheritdoc
     */
    public function rollback(): bool
    {
        $result = false;
        if ($this->transactionCount >= 0) {
            $result = $this->pdo->rollBack();
        }
        $this->transactionCount = 0;

        return $result;
    }

    /**
     * @param PDOStatement $stmt
     * @param string       $parameter
     * @param mixed        $value
     * @param int|null     $type
     */
    protected function _bind(PDOStatement $stmt, $parameter, $value, $type = null): void
    {
        $parameter = $this->_bindName($parameter);

        if ($type === null) {
            switch (true) {
                case \is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case \is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case $value === null:
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
                    break;
            }
        }

        $stmt->bindValue($parameter, $value, $type);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function _bindName($name)
    {
        return \is_string($name)
            ? (':' . \ltrim($name, ':'))
            : $name;
    }

    /**
     * @inheritdoc
     */
    public function readableQuery($query, $params)
    {
        $keys   = [];
        $values = [];
        foreach ($params as $key => $value) {
            $key    = \is_string($key)
                ? $this->_bindName($key)
                : '[?]';
            $keys[] = '/' . $key . '/';
            $value  = \is_int($value)
                ? $value
                : $this->quote($value);

            $values[] = $value;
        }

        return \preg_replace($keys, $values, $query, 1, $count);
    }

    /**
     * Verifies that a database entity name matches the preconditions. Those preconditions are enforced to prevent
     * SQL-Injection through not preparable sql command components.
     *
     * @param string $name
     * @return bool
     */
    protected function isValidEntityName(string $name): bool
    {
        return \preg_match('/^[a-z_0-9]+$/i', \trim($name)) === 1;
    }

    /**
     * Verifies db entity names and throws an exception if it does not match the preconditions
     *
     * @param string $name
     * @throws InvalidEntityNameException
     */
    protected function validateEntityName(string $name): void
    {
        if (!$this->isValidEntityName($name)) {
            throw new InvalidEntityNameException($name);
        }
    }

    /**
     * This method shall prevent SQL-Injection through the member names of objects because they are not preparable.
     *
     * @param object $obj
     * @throws InvalidEntityNameException
     * @throws InvalidArgumentException
     */
    protected function validateDbObject($obj): void
    {
        if (!\is_object($obj)) {
            $type = \gettype($obj);
            throw new InvalidArgumentException('Got var of type ' . $type . ' where object was expected');
        }
        foreach ($obj as $key => $value) {
            if (!$this->isValidEntityName($key)) {
                throw new InvalidEntityNameException($key);
            }
        }
    }

    /**
     * @param PDOException $e
     * @param string       $stmt
     * @param array|null   $assigns
     */
    private function handleException(PDOException $e, string $stmt, array $assigns = null): void
    {
        if (\NICEDB_EXCEPTION_ECHO === true) {
            Shop::dbg($stmt, false, 'NiceDB exception executing sql: ');
            if ($assigns !== null) {
                Shop::dbg($assigns, false, 'Bound params:');
            }
            Shop::dbg($e->getMessage());
            if (\NICEDB_EXCEPTION_BACKTRACE === true) {
                Shop::dbg(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS), false, 'Backtrace:');
            }
        }
        $this->logError($stmt, $e);
    }

    /**
     * @param string         $stmt
     * @param Exception|null $e
     */
    private function logError(string $stmt, ?Exception $e = null): void
    {
        if ($this->logErrors) {
            $errorMessage = $e === null
                ? $this->getErrorCode() . ': ' . $this->getErrorMessage()
                : $e->getMessage();
            Shop::Container()->getLogService()->error('Error executing query: ' . $stmt . "\n" . $errorMessage);
        }
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
    }
}
