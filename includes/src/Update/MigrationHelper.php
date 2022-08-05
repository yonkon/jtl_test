<?php

namespace JTL\Update;

use DateTime;
use Exception;
use JTL\Filesystem\LocalFilesystem;
use JTL\Shop;
use JTL\Smarty\ContextType;
use JTL\Smarty\JTLSmarty;
use Throwable;

/**
 * Class MigrationHelper
 * @package JTL\Update
 */
class MigrationHelper
{
    /**
     * @var string
     */
    public const DATE_FORMAT = 'YmdHis';

    /**
     * @var string
     */
    public const MIGRATION_CLASS_NAME_PATTERN = '/^Migration_(\d+)$/i';

    /**
     * @var string
     */
    public const MIGRATION_FILE_NAME_PATTERN = '/^(\d+)_([\w_]+).php$/i';

    /**
     * @return string
     */
    public static function getMigrationPath(): string
    {
        return \PFAD_ROOT . \PFAD_UPDATE . 'migrations/';
    }

    /**
     * @return array
     */
    public static function getExistingMigrationClassNames(): array
    {
        $classNames = [];
        $path       = static::getMigrationPath();
        $phpFiles   = \glob($path . '*.php');
        foreach ($phpFiles as $filePath) {
            if (\preg_match(static::MIGRATION_FILE_NAME_PATTERN, \basename($filePath))) {
                $classNames[] = static::mapFileNameToClassName(\basename($filePath));
            }
        }

        return $classNames;
    }

    /**
     * Get the id from a file name.
     *
     * @param string $fileName File Name
     * @return string|null
     */
    public static function getIdFromFileName(string $fileName): ?string
    {
        $matches = [];

        return \preg_match(static::MIGRATION_FILE_NAME_PATTERN, \basename($fileName), $matches)
            ? $matches[1]
            : null;
    }

    /**
     * Get the info from a file name.
     *
     * @param string $fileName
     * @return string|null
     */
    public static function getInfoFromFileName(string $fileName): ? string
    {
        $matches = [];
        if (\preg_match(static::MIGRATION_FILE_NAME_PATTERN, \basename($fileName), $matches)) {
            return \preg_replace_callback(
                '/(^|_)([a-z])/',
                static function ($m) {
                    return (\mb_strlen($m[1]) ? ' ' : '') . \mb_convert_case($m[2], \MB_CASE_UPPER);
                },
                $matches[2]
            );
        }

        return null;
    }

    /**
     * Returns names like 'Migration_12345678901234'.
     *
     * @param string $fileName File Name
     * @return string
     */
    public static function mapFileNameToClassName(string $fileName): string
    {
        return 'Migration_' . static::getIdFromFileName($fileName);
    }

    /**
     * Returns names like '12345678901234'.
     *
     * @param string $className File Name
     * @return string|null
     */
    public static function mapClassNameToId(string $className): ?string
    {
        $matches = [];
        if (\preg_match(static::MIGRATION_CLASS_NAME_PATTERN, $className, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if a migration file name is valid.
     *
     * @param string $fileName File Name
     * @return bool|int
     */
    public static function isValidMigrationFileName(string $fileName)
    {
        $matches = [];

        return \preg_match(static::MIGRATION_FILE_NAME_PATTERN, $fileName, $matches);
    }

    /**
     * Check database integrity
     */
    public static function verifyIntegrity(): void
    {
        if (Shop::Container()->getDB()->getSingleObject(
                "SELECT `table_name` 
                    FROM information_schema.tables 
                    WHERE `table_type` = 'base table'
                        AND `table_schema` = :sma
                        AND `table_name` = :tn",
                ['sma' => DB_NAME, 'tn' => 'tmigration']
            ) === null
        ) {
            Shop::Container()->getDB()->query(
                "CREATE TABLE IF NOT EXISTS tmigration 
            (
                kMigration bigint(14) NOT NULL, 
                nVersion int(3) NOT NULL, 
                dExecuted datetime NOT NULL,
                PRIMARY KEY (kMigration)
            ) ENGINE=InnoDB CHARACTER SET='utf8' COLLATE='utf8_unicode_ci'"
            );
            Shop::Container()->getDB()->query(
                "CREATE TABLE IF NOT EXISTS tmigrationlog 
            (
                kMigrationlog int(10) NOT NULL AUTO_INCREMENT, 
                kMigration bigint(20) NOT NULL, 
                cDir enum('up','down') NOT NULL, 
                cState varchar(6) NOT NULL, 
                cLog text NOT NULL, 
                dCreated datetime NOT NULL, 
                PRIMARY KEY (kMigrationlog)
            ) ENGINE=InnoDB CHARACTER SET='utf8' COLLATE='utf8_unicode_ci'"
            );
        }
    }

    /**
     * @param string $idxTable
     * @param string $idxName
     * @return array
     */
    public static function indexColumns(string $idxTable, string $idxName): array
    {
        return Shop::Container()->getDB()->getObjects(
            "SHOW INDEXES FROM `$idxTable` WHERE Key_name = :idxName",
            ['idxName' => $idxName]
        );
    }

    /**
     * @param string      $idxTable
     * @param array       $idxColumns
     * @param string|null $idxName
     * @param bool        $idxUnique
     * @return bool
     */
    public static function createIndex(string $idxTable, array $idxColumns, $idxName = null, $idxUnique = false): bool
    {
        if (empty($idxName)) {
            $idxName = \implode('_', $idxColumns) . '_' . ($idxUnique ? 'UQ' : 'IDX');
        }

        if (\count(self::indexColumns($idxTable, $idxName)) === 0 || self::dropIndex($idxTable, $idxName)) {
            $ddl = 'CREATE' . ($idxUnique ? ' UNIQUE' : '')
                . ' INDEX `' . $idxName . '` ON `' . $idxTable . '` '
                . '(`' . \implode('`, `', $idxColumns) . '`)';

            return !Shop::Container()->getDB()->query($ddl) ? false : true;
        }

        return false;
    }

    /**
     * @param string $idxTable
     * @param string $idxName
     * @return bool
     */
    public static function dropIndex(string $idxTable, string $idxName): bool
    {
        if (\count(self::indexColumns($idxTable, $idxName)) > 0) {
            return !Shop::Container()->getDB()->query(
                'DROP INDEX `' . $idxName . '` ON `' . $idxTable . '` '
            ) ? false : true;
        }

        return true;
    }

    /**
     * @param string $description
     * @param string $author
     * @return string
     * @throws Exception
     */
    public static function create(string $description, string $author): string
    {
        $datetime      = new DateTime('NOW');
        $timestamp     = $datetime->format('YmdHis');
        $asFilePath    = static function ($text) {
            $text = \preg_replace('/\W/', '_', $text);
            $text = \preg_replace('/_+/', '_', $text);

            return \strtolower($text);
        };
        $filePath      = \implode(
            '_',
            \array_filter([$timestamp, $asFilePath($description)])
        );
        $relPath       = 'update/migrations';
        $migrationPath = $relPath . '/' . $filePath . '.php';
        $fileSystem    = Shop::Container()->get(LocalFilesystem::class);
        try {
            $fileSystem->createDirectory($relPath);
        } catch (Throwable $e) {
            throw new Exception('Cannot create migrations path!');
        }

        $smartyCli  = Shop::Smarty(true, ContextType::CLI);
        $smartyCli->setCaching(JTLSmarty::CACHING_OFF);
        $content = $smartyCli->assign('description', $description)
            ->assign('author', $author)
            ->assign('created', $datetime->format(DateTime::RSS))
            ->assign('timestamp', $timestamp)
            ->fetch(\PFAD_ROOT . 'includes/src/Console/Command/Migration/Template/migration.class.tpl');

        $fileSystem->write($migrationPath, $content);

        return $migrationPath;
    }
}
