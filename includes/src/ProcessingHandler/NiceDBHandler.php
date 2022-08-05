<?php declare(strict_types=1);

namespace JTL\ProcessingHandler;

use JTL\DB\DbInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class NiceDBHandler
 * @package JTL\ProcessingHandler
 */
class NiceDBHandler extends AbstractProcessingHandler
{
    /**
     * @var DbInterface
     */
    private $db;

    /**
     * NiceDBHandler constructor.
     * @param DbInterface $db
     * @param int         $level
     * @param bool        $bubble
     */
    public function __construct(DbInterface $db, int $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->db = $db;
        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void
    {
        $context = isset($record['context'][0]) && \is_numeric($record['context'][0])
            ? (int)$record['context'][0]
            : 0;

        $this->db->insert(
            'tjtllog',
            (object)[
                'cKey'      => $record['channel'],
                'nLevel'    => $record['level'],
                'cLog'      => $record['formatted'],
                'kKey'      => $context,
                'dErstellt' => $record['datetime']->format('Y-m-d H:i:s'),
            ]
        );
    }
}
