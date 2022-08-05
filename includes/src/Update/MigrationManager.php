<?php

namespace JTL\Update;

use DateTime;
use Exception;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use InvalidArgumentException;
use JTL\DB\DbInterface;
use JTLShop\SemVer\Version;
use PDOException;
use stdClass;

/**
 * Class MigrationManager
 * @package JTL\Update
 */
class MigrationManager
{
    /**
     * @var IMigration[]
     */
    protected static $migrations = [];

    /**
     * @var array|null
     */
    protected $executedMigrations;

    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * MigrationManager constructor.
     * @param DbInterface $db
     */
    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Migrate the specified identifier.
     *
     * @param int|null $identifier
     * @return array
     * @throws Exception
     */
    public function migrate($identifier = null): array
    {
        $migrations         = $this->getMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        $currentId          = $this->getCurrentId();

        if (empty($executedMigrations) && empty($migrations)) {
            return [];
        }

        if ($identifier === null) {
            $identifier = \max(\array_merge($executedMigrations, \array_keys($migrations)));
        }

        $direction = $identifier > $currentId ? IMigration::UP : IMigration::DOWN;
        $executed  = [];

        $translator = new Translator();
        TranslatorFunctions::register($translator);

        try {
            if ($direction === IMigration::DOWN) {
                \krsort($migrations);
                foreach ($migrations as $migration) {
                    if ($migration->getId() <= $identifier) {
                        break;
                    }
                    if (\in_array($migration->getId(), $executedMigrations)) {
                        $executed[] = $migration;
                        $this->executeMigration($migration, IMigration::DOWN);
                    }
                }
            }
            \ksort($migrations);
            foreach ($migrations as $migration) {
                if ($migration->getId() > $identifier) {
                    break;
                }
                if (!\in_array($migration->getId(), $executedMigrations)) {
                    $executed[] = $migration;
                    $this->executeMigration($migration);
                }
            }
        } catch (PDOException $e) {
            [$code, , $message] = $e->errorInfo;
            $this->log($migration, $direction, $code, $message);
            throw $e;
        } catch (Exception $e) {
            $this->log($migration, $direction, 'JTL01', $e->getMessage());
            throw $e;
        }

        return $executed;
    }

    /**
     * Get a migration by Id.
     *
     * @param int $id MigrationId
     * @return IMigration
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getMigrationById($id): IMigration
    {
        $migrations = $this->getMigrations();
        if (!\array_key_exists($id, $migrations)) {
            throw new InvalidArgumentException(\sprintf(
                'Migration "%s" not found',
                $id
            ));
        }

        return $migrations[$id];
    }

    /**
     * @param int    $id
     * @param string $direction
     * @throws Exception
     */
    public function executeMigrationById($id, $direction = IMigration::UP): void
    {
        $this->executeMigration($this->getMigrationById($id), $direction);
    }

    /**
     * Execute a migration.
     *
     * @param IMigration $migration Migration
     * @param string     $direction Direction
     * @return void
     * @throws Exception
     */
    public function executeMigration(IMigration $migration, string $direction = IMigration::UP): void
    {
        // reset cached executed migrations
        $this->executedMigrations = null;
        $start                    = new DateTime('now');
        try {
            $this->db->beginTransaction();
            $migration->$direction();
            if ($this->db->getPDO()->inTransaction()) {
                // Transaction may be committed by DDL in migration
                $this->db->commit();
            }
            $this->migrated($migration, $direction, $start);
        } catch (Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->rollback();
            }
            $migrationFile = new \ReflectionClass($migration->getName());

            throw new Exception(
                '"' . $e->getMessage() . '" in: ' . $migrationFile->getFileName(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * Sets the database migrations.
     *
     * @param array $migrations Migrations
     * @return $this
     */
    public function setMigrations(array $migrations): self
    {
        static::$migrations = $migrations;

        return $this;
    }

    /**
     * Has valid migrations.
     *
     * @return bool
     * @throws Exception
     */
    public function hasMigrations(): bool
    {
        return \count($this->getMigrations()) > 0;
    }

    /**
     * Gets an array of the database migrations.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return IMigration[]
     */
    public function getMigrations(): array
    {
        if (!\is_array(static::$migrations) || \count(static::$migrations) === 0) {
            $migrations = [];
            $executed   = $this->_getExecutedMigrations();
            $path       = MigrationHelper::getMigrationPath();

            foreach (\glob($path . '*.php') as $filePath) {
                $baseName = \basename($filePath);
                if (MigrationHelper::isValidMigrationFileName($baseName)) {
                    $id    = MigrationHelper::getIdFromFileName($baseName);
                    $info  = MigrationHelper::getInfoFromFileName($baseName);
                    $class = MigrationHelper::mapFileNameToClassName($baseName);
                    $date  = $executed[(int)$id] ?? null;

                    require_once $filePath;

                    if (!\class_exists($class)) {
                        throw new InvalidArgumentException(\sprintf(
                            'Could not find class "%s" in file "%s"',
                            $class,
                            $filePath
                        ));
                    }

                    $migration = new $class($this->db, $info, $date);

                    if (!\is_subclass_of($migration, IMigration::class)) {
                        throw new InvalidArgumentException(\sprintf(
                            'The class "%s" in file "%s" must implement IMigration interface',
                            $class,
                            $filePath
                        ));
                    }

                    $migrations[$id] = $migration;
                }
            }
            \ksort($migrations);
            $this->setMigrations($migrations);
        }

        return static::$migrations;
    }

    /**
     * Get lastest executed migration id.
     *
     * @return int
     */
    public function getCurrentId(): int
    {
        return (int)($this->db->getSingleObject(
            'SELECT kMigration 
                FROM tmigration 
                ORDER BY kMigration DESC'
        )->kMigration ?? 0);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getExecutedMigrations(): array
    {
        $migrations = $this->_getExecutedMigrations();
        if (!\is_array($migrations)) {
            $migrations = [];
        }

        return \array_keys($migrations);
    }

    /**
     * @param bool $force
     * @return array
     * @throws Exception
     */
    public function getPendingMigrations(bool $force = false): array
    {
        static $pending = null;

        if ($force || $pending === null) {
            $executed   = $this->getExecutedMigrations();
            $migrations = \array_keys($this->getMigrations());
            $pending    = \array_udiff($migrations, $executed, static function ($a, $b) {
                return \strcmp((string)$a, (string)$b);
            });
        }

        return $pending;
    }

    /**
     * @return array|int
     * @throws Exception
     */
    protected function _getExecutedMigrations()
    {
        if ($this->executedMigrations === null) {
            $migrations = $this->db->getObjects(
                'SELECT * 
                    FROM tmigration 
                    ORDER BY kMigration ASC'
            );
            foreach ($migrations as $m) {
                $this->executedMigrations[$m->kMigration] = new DateTime($m->dExecuted);
            }
        }

        return $this->executedMigrations;
    }

    /**
     * @param IMigration $migration
     * @param string $direction
     * @param string $state
     * @param string $message
     * @throws Exception
     */
    public function log(IMigration $migration, string $direction, $state, $message): void
    {
        $ins             = new stdClass();
        $ins->kMigration = $migration->getId();
        $ins->cDir       = $direction;
        $ins->cState     = $state;
        $ins->cLog       = $message;
        $ins->dCreated   = (new DateTime('now'))->format('Y-m-d H:i:s');
        $this->db->insert('tmigrationlog', $ins);
    }

    /**
     * @param IMigration $migration
     * @param string     $direction
     * @param DateTime   $executed
     * @return $this
     */
    public function migrated(IMigration $migration, string $direction, DateTime $executed): self
    {
        if (\strcasecmp($direction, IMigration::UP) === 0) {
            $version = Version::parse(\APPLICATION_VERSION);
            $sql     = \sprintf(
                "INSERT INTO tmigration (kMigration, nVersion, dExecuted) VALUES ('%s', '%s', '%s');",
                $migration->getId(),
                \sprintf('%d%02d', $version->getMajor(), $version->getMinor()),
                $executed->format('Y-m-d H:i:s')
            );
            $this->db->query($sql);
        } else {
            $sql = \sprintf("DELETE FROM tmigration WHERE kMigration = '%s'", $migration->getId());
            $this->db->query($sql);
        }

        return $this;
    }
}
