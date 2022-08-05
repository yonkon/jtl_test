<?php

namespace JTL\Update;

use DbInterface;
use JTL\Helpers\Text;
use JTL\Shop;
use stdClass;

/**
 * Class DBMigrationHelper
 * @package JTL\Update
 */
class DBMigrationHelper
{
    public const IN_USE  = 'in_use';
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';

    public const MIGRATE_NONE    = 0x0000;
    public const MIGRATE_INNODB  = 0x0001;
    public const MIGRATE_UTF8    = 0x0002;
    public const MIGRATE_TEXT    = 0x0004;
    public const MIGRATE_C_UTF8  = 0x0010;
    public const MIGRATE_TINYINT = 0x0020;
    public const MIGRATE_TABLE   = self::MIGRATE_INNODB | self::MIGRATE_UTF8;
    public const MIGRATE_COLUMN  = self::MIGRATE_C_UTF8 | self::MIGRATE_TEXT | self::MIGRATE_TINYINT;

    /**
     * @return stdClass
     */
    public static function getMySQLVersion(): stdClass
    {
        static $versionInfo = null;

        if ($versionInfo !== null) {
            return $versionInfo;
        }
        $db            = Shop::Container()->getDB();
        $versionInfo   = new stdClass();
        $innodbSupport = $db->getSingleObject(
            "SELECT `SUPPORT`
                FROM information_schema.ENGINES
                WHERE `ENGINE` = 'InnoDB'"
        );
        $utf8Support   = $db->getSingleObject(
            "SELECT `IS_COMPILED` FROM information_schema.COLLATIONS
                WHERE `COLLATION_NAME` RLIKE 'utf8(mb3)?_unicode_ci'"
        );
        $innodbPath    = $db->getSingleObject('SELECT @@innodb_data_file_path AS path');
        $innodbSize    = 'auto';
        if ($innodbPath && \mb_stripos($innodbPath->path, 'autoextend') === false) {
            $innodbSize = 0;
            $paths      = \explode(';', $innodbPath->path);
            foreach ($paths as $path) {
                if (\preg_match('/:([\d]+)([MGTKmgtk]+)/', $path, $hits)) {
                    switch (\mb_convert_case($hits[2], \MB_CASE_UPPER)) {
                        case 'T':
                            $innodbSize += $hits[1] * 1024 * 1024 * 1024 * 1024;
                            break;
                        case 'G':
                            $innodbSize += $hits[1] * 1024 * 1024 * 1024;
                            break;
                        case 'M':
                            $innodbSize += $hits[1] * 1024 * 1024;
                            break;
                        case 'K':
                            $innodbSize += $hits[1] * 1024;
                            break;
                        default:
                            $innodbSize += $hits[1];
                    }
                }
            }
        }

        $versionInfo->server = $db->getServerInfo();
        $versionInfo->innodb = new stdClass();

        $versionInfo->innodb->support = $innodbSupport && \in_array($innodbSupport->SUPPORT, ['YES', 'DEFAULT'], true);
        $versionInfo->innodb->version = $db->getSingleObject("SHOW VARIABLES LIKE 'innodb_version'")->Value;
        $versionInfo->innodb->size    = $innodbSize;
        $versionInfo->collation_utf8  = $utf8Support && \mb_convert_case(
            $utf8Support->IS_COMPILED,
            \MB_CASE_LOWER
        ) === 'yes';

        return $versionInfo;
    }

    /**
     * @return stdClass[]
     */
    public static function getTablesNeedMigration(): array
    {
        return Shop::Container()->getDB()->getObjects(
            "SELECT t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`, t.`TABLE_COMMENT`
                , COUNT(IF(c.DATA_TYPE = 'text', c.COLUMN_NAME, NULL)) TEXT_FIELDS
                , COUNT(IF(c.DATA_TYPE = 'tinyint', c.COLUMN_NAME, NULL)) TINY_FIELDS
                , COUNT(IF(c.COLLATION_NAME RLIKE 'utf8(mb3)?_unicode_ci', NULL, c.COLLATION_NAME)) FIELD_COLLATIONS
                FROM information_schema.TABLES t
                LEFT JOIN information_schema.COLUMNS c 
                    ON c.TABLE_NAME = t.TABLE_NAME
                    AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
                    AND (c.DATA_TYPE = 'text'
                             OR c.DATA_TYPE = 'tinyint'
                             OR c.COLLATION_NAME NOT RLIKE 'utf8(mb3)?_unicode_ci'
                        )
                WHERE t.`TABLE_SCHEMA` = :schema
                    AND t.`TABLE_NAME` NOT LIKE 'xplugin_%'
                    AND (t.`ENGINE` != 'InnoDB' 
                           OR t.`TABLE_COLLATION` NOT RLIKE 'utf8(mb3)?_unicode_ci'
                           OR c.COLLATION_NAME NOT RLIKE 'utf8(mb3)?_unicode_ci'
                           OR c.DATA_TYPE = 'text'
                           OR (c.DATA_TYPE = 'tinyint' AND SUBSTRING(c.COLUMN_NAME, 1, 1) = 'k')
                    )
                GROUP BY t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`, t.`TABLE_COMMENT`
                ORDER BY t.`TABLE_NAME`",
            ['schema' => Shop::Container()->getDB()->getConfig()['database']]
        );
    }

    /**
     * @param DbInterface $db
     * @param string[]    $excludeTables
     * @return stdClass|null
     */
    public static function getNextTableNeedMigration(DbInterface $db, array $excludeTables = []): ?stdClass
    {
        $excludeStr = \implode("','", Text::filterXSS($excludeTables));

        return $db->getSingleObject(
            "SELECT t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`, t.`TABLE_COMMENT`
                , COUNT(IF(c.DATA_TYPE = 'text', c.COLUMN_NAME, NULL)) TEXT_FIELDS
                , COUNT(IF(c.DATA_TYPE = 'tinyint', c.COLUMN_NAME, NULL)) TINY_FIELDS
                , COUNT(IF(c.COLLATION_NAME RLIKE 'utf8(mb3)?_unicode_ci', NULL, c.COLLATION_NAME)) FIELD_COLLATIONS
                FROM information_schema.TABLES t
                LEFT JOIN information_schema.COLUMNS c 
                    ON c.TABLE_NAME = t.TABLE_NAME
                    AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
                    AND (c.DATA_TYPE = 'text'
                             OR c.DATA_TYPE = 'tinyint'
                             OR c.COLLATION_NAME NOT RLIKE 'utf8(mb3)?_unicode_ci'
                        )
                WHERE t.`TABLE_SCHEMA` = :schema
                    AND t.`TABLE_NAME` NOT LIKE 'xplugin_%'
                    " . (!empty($excludeStr) ? "AND t.`TABLE_NAME` NOT IN ('" . $excludeStr . "')" : '') . "
                    AND (t.`ENGINE` != 'InnoDB' 
                        OR t.`TABLE_COLLATION` NOT RLIKE 'utf8(mb3)?_unicode_ci'
                        OR c.COLLATION_NAME NOT RLIKE 'utf8(mb3)?_unicode_ci'
                        OR c.DATA_TYPE = 'text'
                        OR (c.DATA_TYPE = 'tinyint' AND SUBSTRING(c.COLUMN_NAME, 1, 1) = 'k')
                    )
                GROUP BY t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`
                ORDER BY t.`TABLE_NAME` LIMIT 1",
            ['schema' => $db->getConfig()['database']]
        );
    }

    /**
     * @param string $table
     * @return stdClass|null
     */
    public static function getTable(string $table): ?stdClass
    {
        return Shop::Container()->getDB()->getSingleObject(
            "SELECT t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`, t.`TABLE_COMMENT`
                , COUNT(IF(c.DATA_TYPE = 'text', c.COLUMN_NAME, NULL)) TEXT_FIELDS
                , COUNT(IF(c.DATA_TYPE = 'tinyint', c.COLUMN_NAME, NULL)) TINY_FIELDS
                , COUNT(IF(c.COLLATION_NAME RLIKE 'utf8(mb3)?_unicode_ci', NULL, c.COLLATION_NAME)) FIELD_COLLATIONS
                FROM information_schema.TABLES t
                LEFT JOIN information_schema.COLUMNS c 
                    ON c.TABLE_NAME = t.TABLE_NAME
                    AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
                    AND (c.DATA_TYPE = 'text'
                        OR (c.DATA_TYPE = 'tinyint' AND SUBSTRING(c.COLUMN_NAME, 1, 1) = 'k')
                        OR c.COLLATION_NAME NOT RLIKE 'utf8(mb3)?_unicode_ci'
                    )
                WHERE t.`TABLE_SCHEMA` = :schema
                    AND t.`TABLE_NAME` = :table
                GROUP BY t.`TABLE_NAME`, t.`ENGINE`, t.`TABLE_COLLATION`, t.`TABLE_COMMENT`
                ORDER BY t.`TABLE_NAME` LIMIT 1",
            ['schema' => Shop::Container()->getDB()->getConfig()['database'], 'table' => $table,]
        );
    }

    /**
     * @param string|null $table
     * @return stdClass[]
     */
    public static function getFulltextIndizes(?string $table = null): array
    {
        $params = ['schema' => Shop::Container()->getDB()->getConfig()['database']];
        $filter = "AND `INDEX_NAME` NOT IN ('idx_tartikel_fulltext', 'idx_tartikelsprache_fulltext')";

        if (!empty($table)) {
            $params['table'] = $table;
            $filter          = 'AND `TABLE_NAME` = :table';
        }

        return Shop::Container()->getDB()->getObjects(
            "SELECT DISTINCT `TABLE_NAME`, `INDEX_NAME`
                FROM information_schema.STATISTICS
                WHERE `TABLE_SCHEMA` = :schema
                    {$filter}
                    AND `INDEX_TYPE` = 'FULLTEXT'",
            $params
        );
    }

    /**
     * @param string|stdClass $table
     * @return int
     */
    public static function isTableNeedMigration($table): int
    {
        $result = self::MIGRATE_NONE;

        if (\is_string($table)) {
            $table = self::getTable($table);
        }

        if (\is_object($table)) {
            if ($table->ENGINE !== 'InnoDB') {
                $result |= self::MIGRATE_INNODB;
            }
            if (!\in_array($table->TABLE_COLLATION, ['utf8_unicode_ci', 'utf8mb3_unicode_ci'])) {
                $result |= self::MIGRATE_UTF8;
            }
            if (isset($table->TEXT_FIELDS) && (int)$table->TEXT_FIELDS > 0) {
                $result |= self::MIGRATE_TEXT;
            }
            if (isset($table->TINY_FIELDS) && (int)$table->TINY_FIELDS > 0) {
                $result |= self::MIGRATE_TINYINT;
            }
            if (isset($table->FIELD_COLLATIONS) && (int)$table->FIELD_COLLATIONS > 0) {
                $result |= self::MIGRATE_C_UTF8;
            }
        }

        return $result;
    }

    /**
     * @param DbInterface $db
     * @param string      $table
     * @return bool
     */
    public static function isTableInUse(DbInterface $db, $table): bool
    {
        $mysqlVersion = self::getMySQLVersion();
        if (\version_compare($mysqlVersion->innodb->version, '5.6', '<')) {
            $tableInfo = self::getTable($table);

            return $tableInfo !== null && \mb_strpos($tableInfo->TABLE_COMMENT, ':Migrating') !== false;
        }

        $tableStatus = $db->getSingleObject(
            'SHOW OPEN TABLES
                WHERE `Database` LIKE :schema
                    AND `Table` LIKE :table',
            ['schema' => $db->getConfig()['database'], 'table' => $table,]
        );

        return $tableStatus !== null && (int)$tableStatus->In_use > 0;
    }

    /**
     * @param string $table
     * @return stdClass[]
     */
    public static function getColumnsNeedMigration(string $table): array
    {
        return Shop::Container()->getDB()->getObjects(
            "SELECT `COLUMN_NAME`, `DATA_TYPE`, `COLUMN_TYPE`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `EXTRA`
                FROM information_schema.COLUMNS
                WHERE `TABLE_SCHEMA` = :schema
                    AND `TABLE_NAME` = :table
                    AND ((`CHARACTER_SET_NAME` IS NOT NULL AND `CHARACTER_SET_NAME` NOT RLIKE 'utf8(mb3)?')
                        OR `COLLATION_NAME` NOT RLIKE 'utf8(mb3)?_unicode_ci'
                        OR DATA_TYPE = 'text'
                        OR (DATA_TYPE = 'tinyint' AND SUBSTRING(COLUMN_NAME, 1, 1) = 'k')
                    )
                ORDER BY `ORDINAL_POSITION`",
            ['schema' => Shop::Container()->getDB()->getConfig()['database'], 'table' => $table]
        );
    }

    /**
     * @param string $table
     * @return stdClass[]
     */
    public static function getFKDefinitions(string $table): array
    {
        return Shop::Container()->getDB()->getObjects(
            'SELECT rc.`CONSTRAINT_NAME`, rc.`TABLE_NAME`, rc.`UPDATE_RULE`, rc.`DELETE_RULE`,
                    rk.`COLUMN_NAME`, rk.`REFERENCED_TABLE_NAME`, rk.`REFERENCED_COLUMN_NAME`
                FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                INNER JOIN information_schema.KEY_COLUMN_USAGE rk
                    ON rk.`CONSTRAINT_SCHEMA` = rc.`CONSTRAINT_SCHEMA`
                        AND rk.`CONSTRAINT_NAME` = rc.`CONSTRAINT_NAME`
                WHERE rc.`CONSTRAINT_SCHEMA` = :schema
                    AND rc.`REFERENCED_TABLE_NAME` = :table',
            ['schema' => Shop::Container()->getDB()->getConfig()['database'], 'table'  => $table]
        );
    }

    /**
     * @param stdClass $table
     * @return string
     */
    public static function sqlAddLockInfo($table): string
    {
        $mysqlVersion = self::getMySQLVersion();

        return \version_compare($mysqlVersion->innodb->version, '5.6', '<')
            ? "ALTER TABLE `{$table->TABLE_NAME}` COMMENT = '{$table->TABLE_COMMENT}:Migrating'"
            : '';
    }

    /**
     * @param stdClass $table
     * @return string
     */
    public static function sqlClearLockInfo($table): string
    {
        $mysqlVersion = self::getMySQLVersion();

        return \version_compare($mysqlVersion->innodb->version, '5.6', '<')
            ? "ALTER TABLE `{$table->TABLE_NAME}` COMMENT = '{$table->TABLE_COMMENT}'"
            : '';
    }

    /**
     * @param string $table
     * @return object - dropFK: Array with SQL to drop associated foreign keys,
     *                  createFK: Array with SQL to recreate them
     */
    public static function sqlRecreateFKs(string $table): object
    {
        $fkDefinitions = self::getFKDefinitions($table);
        $result        = (object)[
            'dropFK'   => [],
            'createFK' => [],
        ];

        if (\count($fkDefinitions) === 0) {
            return $result;
        }

        foreach ($fkDefinitions as $fkDefinition) {
            $result->dropFK[]   = 'ALTER TABLE `' . $fkDefinition->TABLE_NAME . '`'
                . ' DROP FOREIGN KEY `' . $fkDefinition->CONSTRAINT_NAME . '`';
            $result->createFK[] = 'ALTER TABLE `' . $fkDefinition->TABLE_NAME . '`'
                . ' ADD FOREIGN KEY `' . $fkDefinition->CONSTRAINT_NAME . '` (`' . $fkDefinition->COLUMN_NAME . '`)'
                . ' REFERENCES `' . $fkDefinition->REFERENCED_TABLE_NAME . '`'
                    . '(`' . $fkDefinition->REFERENCED_COLUMN_NAME . '`)'
                    . ' ON DELETE ' . $fkDefinition->DELETE_RULE
                    . ' ON UPDATE ' . $fkDefinition->UPDATE_RULE;
        }

        return $result;
    }

    /**
     * @param stdClass $table
     * @return string
     */
    public static function sqlMoveToInnoDB($table): string
    {
        $mysqlVersion = self::getMySQLVersion();

        if (!isset($table->Migration)) {
            $table->Migration = self::isTableNeedMigration($table);
        }

        if (($table->Migration & self::MIGRATE_TABLE) === self::MIGRATE_TABLE) {
            $sql = "ALTER TABLE `{$table->TABLE_NAME}` CHARACTER SET='utf8' COLLATE='utf8_unicode_ci' ENGINE='InnoDB'";
        } elseif (($table->Migration & self::MIGRATE_INNODB) === self::MIGRATE_INNODB) {
            $sql = "ALTER TABLE `{$table->TABLE_NAME}` ENGINE='InnoDB'";
        } elseif (($table->Migration & self::MIGRATE_UTF8) === self::MIGRATE_UTF8) {
            $sql = "ALTER TABLE `{$table->TABLE_NAME}` CHARACTER SET='utf8' COLLATE='utf8_unicode_ci'";
        } else {
            return '';
        }

        return \version_compare($mysqlVersion->innodb->version, '5.6', '<')
            ? $sql
            : $sql . ', LOCK EXCLUSIVE';
    }

    /**
     * @param stdClass $table
     * @param string   $lineBreak
     * @return string
     */
    public static function sqlConvertUTF8($table, $lineBreak = ''): string
    {
        $mysqlVersion = self::getMySQLVersion();
        $columns      = self::getColumnsNeedMigration($table->TABLE_NAME);
        $sql          = '';
        if ($columns !== false && \count($columns) > 0) {
            $sql = "ALTER TABLE `{$table->TABLE_NAME}`$lineBreak";

            $columnChange = [];
            foreach ($columns as $key => $col) {
                $characterSet = "CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'";

                /* Workaround for quoted values in MariaDB >= 10.2.7 Fix: SHOP-2593 */
                if ($col->COLUMN_DEFAULT === 'NULL' || $col->COLUMN_DEFAULT === "'NULL'") {
                    $col->COLUMN_DEFAULT = null;
                }
                if ($col->COLUMN_DEFAULT !== null) {
                    $col->COLUMN_DEFAULT = \trim($col->COLUMN_DEFAULT, '\'');
                }

                if ($col->DATA_TYPE === 'text') {
                    $col->COLUMN_TYPE = 'MEDIUMTEXT';
                }

                if ($col->DATA_TYPE === 'tinyint' && \strpos($col->COLUMN_NAME, 'k') === 0) {
                    $col->COLUMN_TYPE = 'INT(10) UNSIGNED';
                    $characterSet = '';
                }

                $columnChange[] = "    CHANGE COLUMN `{$col->COLUMN_NAME}` `{$col->COLUMN_NAME}` "
                    . "{$col->COLUMN_TYPE} $characterSet"
                    . ($col->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL')
                    . ($col->IS_NULLABLE === 'NO' && $col->COLUMN_DEFAULT === null ? '' : ' DEFAULT '
                        . ($col->COLUMN_DEFAULT === null ? 'NULL' : "'{$col->COLUMN_DEFAULT}'"))
                    . (!empty($col->EXTRA) ? ' ' . $col->EXTRA : '');
            }

            $sql .= \implode(", $lineBreak", $columnChange);

            if (\version_compare($mysqlVersion->innodb->version, '5.6', '>=')) {
                $sql .= ', LOCK EXCLUSIVE';
            }
        }

        return $sql;
    }

    /**
     * @param string $tableName
     * @return string - SUCCESS, FAILURE or IN_USE
     */
    public static function migrateToInnoDButf8(string $tableName): string
    {
        $table = self::getTable($tableName);
        $db    = Shop::Container()->getDB();
        if ($table === null) {
            return self::FAILURE;
        }
        if (self::isTableInUse($db, $table->TABLE_NAME)) {
            return self::IN_USE;
        }

        $migration = self::isTableNeedMigration($table);
        if (($migration & self::MIGRATE_TABLE) !== self::MIGRATE_NONE) {
            $sql = self::sqlMoveToInnoDB($table);
            if (!empty($sql)) {
                $fkSQLs = self::sqlRecreateFKs($tableName);
                foreach ($fkSQLs->dropFK as $fkSQL) {
                    $db->query($fkSQL);
                }
                $res = $db->query($sql);
                foreach ($fkSQLs->createFK as $fkSQL) {
                    $db->query($fkSQL);
                }

                if (!$res) {
                    return self::FAILURE;
                }
            }
        }
        if (($migration & self::MIGRATE_COLUMN) !== self::MIGRATE_NONE) {
            $sql = self::sqlConvertUTF8($table);
            if (!empty($sql) && !$db->query($sql)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
