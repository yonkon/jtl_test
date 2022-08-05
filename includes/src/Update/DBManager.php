<?php

namespace JTL\Update;

use JTL\Shop;
use stdClass;

/**
 * Class DBManager
 * @package JTL\Update
 */
class DBManager
{
    /**
     * @return array
     */
    public static function getTables(): array
    {
        $tables = [];
        $rows   = Shop::Container()->getDB()->getObjects(
            "SHOW FULL TABLES 
                WHERE Table_type='BASE TABLE'"
        );
        foreach ($rows as $row) {
            $tables[] = \current($row);
        }

        return $tables;
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getColumns(string $table): array
    {
        $list    = [];
        $table   = Shop::Container()->getDB()->escape($table);
        $columns = Shop::Container()->getDB()->getObjects(
            "SHOW FULL COLUMNS 
                FROM `{$table}`"
        );
        foreach ($columns as $column) {
            $column->Type_info    = self::parseType($column->Type);
            $list[$column->Field] = $column;
        }

        return $list;
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getIndexes(string $table): array
    {
        $list    = [];
        $table   = Shop::Container()->getDB()->escape($table);
        $indexes = Shop::Container()->getDB()->getObjects(
            "SHOW INDEX 
                FROM `{$table}`"
        );
        foreach ($indexes as $index) {
            $container = (object)[
                'Index_type' => 'INDEX',
                'Columns'    => []
            ];

            if (!isset($list[$index->Key_name])) {
                $list[$index->Key_name] = $container;
            }

            $list[$index->Key_name]->Columns[$index->Column_name] = $index;
        }
        foreach ($list as $key => $item) {
            if (\count($item->Columns) > 0) {
                $column = \reset($item->Columns);
                if ($column->Key_name === 'PRIMARY') {
                    $list[$key]->Index_type = 'PRIMARY';
                } elseif ($column->Index_type === 'FULLTEXT') {
                    $list[$key]->Index_type = 'FULLTEXT';
                } elseif ((int)$column->Non_unique === 0) {
                    $list[$key]->Index_type = 'UNIQUE';
                }
            }
        }

        return $list;
    }

    /**
     * @param string      $database
     * @param string|null $table
     * @return array|stdClass
     */
    public static function getStatus(string $database, ?string $table = null)
    {
        $database = Shop::Container()->getDB()->escape($database);

        if ($table !== null) {
            return Shop::Container()->getDB()->getSingleObject(
                "SHOW TABLE STATUS 
                    FROM `{$database}` 
                    WHERE name = :tbl",
                ['tbl' => $table]
            );
        }

        $list   = [];
        $status = Shop::Container()->getDB()->getObjects(
            "SHOW TABLE STATUS 
                FROM `{$database}`"
        );
        foreach ($status as $s) {
            $list[$s->Name] = $s;
        }

        return $list;
    }

    /**
     * @param string $type
     * @return object
     */
    public static function parseType($type)
    {
        $result = (object)[
            'Name'     => null,
            'Size'     => null,
            'Unsigned' => false
        ];
        $types  = \explode(' ', $type);

        if (isset($types[1]) && $types[1] === 'unsigned') {
            $result->Unsigned = true;
        }

        if (\preg_match('/([a-z]+)(?:\((.*)\))?/', $types[0], $m)) {
            $result->Size = 0;
            $result->Name = $m[1];
            if (isset($m[2])) {
                $size         = \explode(',', $m[2]);
                $size         = \count($size) === 1 ? $size[0] : $size;
                $result->Size = $size;
            }
        }

        return $result;
    }
}
