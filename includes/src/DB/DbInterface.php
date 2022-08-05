<?php

namespace JTL\DB;

use Illuminate\Support\Collection;
use PDOStatement;
use stdClass;

/**
 * Interface DbInterface
 * @package JTL\DB
 */
interface DbInterface extends \Serializable
{
    /**
     * Database configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * avoid destructer races with object cache
     *
     * @return $this
     */
    public function reInit(): DbInterface;

    /**
     * close db connection
     *
     * @return bool
     */
    public function close(): bool;

    /**
     * check if connected
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * get server version information
     *
     * @return string
     */
    public function getServerInfo(): string;

    /**
     * get server stats
     *
     * @return string
     */
    public function getServerStats(): string;

    /**
     * @return \PDO
     */
    public function getPDO(): \PDO;

    /**
     * insert row into db
     *
     * @param string $tableName - table name
     * @param object $object - object to insert
     * @param bool   $echo - true -> print statement
     * @return int - 0 if fails, PrimaryKeyValue if successful
     */
    public function insertRow(string $tableName, $object, bool $echo = false): int;

    /**
     * @param string $tableName
     * @param object $object
     * @param bool   $echo
     * @return int
     */
    public function insert(string $tableName, $object, bool $echo = false): int;

    /**
     * update table row
     *
     * @param string           $tableName - table name
     * @param string|array     $keyname - Name of Key which should be compared
     * @param int|string|array $keyvalue - Value of Key which should be compared
     * @param object           $object - object to update with
     * @param bool             $echo - true -> print statement
     * @return int - -1 if fails, number of affected rows if successful
     */
    public function updateRow(string $tableName, $keyname, $keyvalue, $object, bool $echo = false): int;

    /**
     * @param string           $tableName
     * @param string|array     $keyname
     * @param string|int|array $keyvalue
     * @param object           $object
     * @param bool             $echo
     * @return int
     */
    public function update(string $tableName, $keyname, $keyvalue, $object, bool $echo = false): int;

    /**
     * @param string $tableName
     * @param object $object
     * @param array  $excludeUpdate
     * @param bool   $echo
     * @return int - -1 if fails, 0 if update, PrimaryKeyValue if successful inserted
     */
    public function upsert(string $tableName, $object, array $excludeUpdate = [], bool $echo = false): int;

    /**
     * selects all (*) values in a single row from a table - gives just one row back!
     *
     * @param string           $tableName - Tabellenname
     * @param string|array     $keyname - Name of Key which should be compared
     * @param string|int|array $keyvalue - Value of Key which should be compared
     * @param string|null      $keyname1 - Name of Key which should be compared
     * @param string|int|null  $keyvalue1 - Value of Key which should be compared
     * @param string|null      $keyname2 - Name of Key which should be compared
     * @param string|int|null  $keyvalue2 - Value of Key which should be compared
     * @param bool             $echo - true -> print statement
     * @param string           $select - the key to select
     * @return null|object - null if fails, resultObject if successful
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
    );

    /**
     * @param string            $tableName
     * @param string|array      $keyname
     * @param string|int|array  $keyvalue
     * @param string|null       $keyname1
     * @param string|int|null   $keyvalue1
     * @param string|array|null $keyname2
     * @param string|int|null   $keyvalue2
     * @param bool              $echo
     * @param string            $select
     * @return mixed
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
    );

    /**
     * @param string           $tableName
     * @param string|array     $keys
     * @param string|array|int $values
     * @param string           $select
     * @param string           $orderBy
     * @param string|int       $limit
     * @return array
     * @throws \InvalidArgumentException
     */
    public function selectArray(
        string $tableName,
        $keys,
        $values,
        string $select = '*',
        string $orderBy = '',
        $limit = ''
    );

    /**
     * @param string           $tableName
     * @param string|array     $keys
     * @param string|int|array $values
     * @param string           $select
     * @param string           $orderBy
     * @param string|int       $limit
     * @return array
     */
    public function selectAll(
        string $tableName,
        $keys,
        $values,
        string $select = '*',
        string $orderBy = '',
        $limit = ''
    );

    /**
     * executes query and returns misc data
     *
     * @param string        $stmt - Statement to be executed
     * @param int           $return - what should be returned.
     * 1  - single fetched object
     * 2  - array of fetched objects
     * 3  - affected rows
     * 7  - last inserted id
     * 8  - fetched assoc array
     * 9  - array of fetched assoc arrays
     * 10 - result of querysingle
     * 11 - fetch both arrays
     * @param bool          $echo print current stmt
     * @param callable|null $fnInfo statistic callback
     * @return array|object|int - 0 if fails, 1 if successful or LastInsertID if specified
     * @throws \InvalidArgumentException
     */
    public function executeQuery(string $stmt, int $return = ReturnType::DEFAULT, bool $echo = false, $fnInfo = null);

    /**
     * @param string   $stmt
     * @param int      $return
     * @param bool     $echo
     * @return int|object|array
     */
    public function query(string $stmt, int $return = ReturnType::DEFAULT, bool $echo = false);

    /**
     * executes query and returns misc data
     *
     * @param string        $stmt - Statement to be executed
     * @param array         $params - An array of values with as many elements as there
     * are bound parameters in the SQL statement being executed
     * @param int           $return - what should be returned.
     * 1  - single fetched object
     * 2  - array of fetched objects
     * 3  - affected rows
     * 7  - last inserted id
     * 8  - fetched assoc array
     * 9  - array of fetched assoc arrays
     * 10 - result of querysingle
     * 11 - fetch both arrays
     * @param bool          $echo print current stmt
     * @param callable|null $fnInfo statistic callback
     * @return array|object|int|bool - 0 if fails, 1 if successful or LastInsertID if specified
     * @throws \InvalidArgumentException
     */
    public function executeQueryPrepared(
        string $stmt,
        array $params,
        int $return = ReturnType::DEFAULT,
        bool $echo = false,
        $fnInfo = null
    );

    /**
     * @param string $stmt
     * @param array  $params
     * @param int    $return
     * @param bool   $echo
     * @param mixed  $fnInfo
     * @return int|object|array
     */
    public function queryPrepared(
        string $stmt,
        array $params,
        int $return = ReturnType::DEFAULT,
        bool $echo = false,
        $fnInfo = null
    );

    /**
     * @param string $stmt
     * @param array  $params
     * @return array[]
     * @since 5.1.0
     */
    public function getArrays(string $stmt, array $params = []): array;

    /**
     * @param string $stmt
     * @param array  $params
     * @return stdClass[]
     * @since 5.1.0
     */
    public function getObjects(string $stmt, array $params = []): array;

    /**
     * @param string $stmt
     * @param array  $params
     * @return Collection
     * @since 5.1.0
     */
    public function getCollection(string $stmt, array $params = []): Collection;

    /**
     * @param string $stmt
     * @param array  $params
     * @return stdClass|null
     * @since 5.1.0
     */
    public function getSingleObject(string $stmt, array $params = []): ?stdClass;

    /**
     * @param string $stmt
     * @param array  $params
     * @return array|null
     * @since 5.1.0
     */
    public function getSingleArray(string $stmt, array $params = []): ?array;

    /**
     * @param string   $stmt
     * @param array    $params
     * @return int
     * @since 5.1.0
     */
    public function getAffectedRows(string $stmt, array $params = []): int;

    /**
     * @param string   $stmt
     * @param array    $params
     * @return PDOStatement
     * @since 5.1.0
     */
    public function getPDOStatement(string $stmt, array $params = []): PDOStatement;

    /**
     * delete row from table
     *
     * @param string           $tableName - table name
     * @param string|array     $keyname - Name of Key which should be compared
     * @param string|int|array $keyvalue - Value of Key which should be compared
     * @param bool             $echo - true -> print statement
     * @return int - -1 if fails, #affectedRows if successful
     */
    public function deleteRow(string $tableName, $keyname, $keyvalue, bool $echo = false): int;

    /**
     * @param string           $tableName
     * @param string|array     $keyname
     * @param string|int|array $keyvalue
     * @param bool             $echo
     * @return int
     */
    public function delete(string $tableName, $keyname, $keyvalue, bool $echo = false): int;

    /**
     * executes a query and gives back the result
     *
     * @param string $stmt - Statement to be executed
     * @return PDOStatement|int
     */
    public function executeExQuery($stmt);

    /**
     * Quotes a string with outer quotes for use in a query.
     *
     * @param string|bool $string
     * @return string
     */
    public function quote($string): string;

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string
     * @return string
     */
    public function escape($string): string;

    /**
     * @return mixed
     */
    public function getErrorCode();

    /**
     * @return array
     */
    public function getError(): array;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * @return bool
     */
    public function commit(): bool;

    /**
     * @return bool
     */
    public function rollback(): bool;

    /**
     * @param string $query
     * @param array  $params
     * @return string
     */
    public function readableQuery($query, $params);
}
