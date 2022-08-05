<?php declare(strict_types=1);

namespace JTL\Console\Command\Cache;

use JTL\Console\Command\Command;
use JTL\Filesystem\LocalFilesystem;
use JTL\Shop;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class DeleteFileCacheCommand
 * @package JTL\Console\Command\Cache
 */
class DeleteFileCacheCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('cache:file:delete')
            ->setDescription('Delete file cache');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();
        $fs = Shop::Container()->get(LocalFilesystem::class);
        try {
            $fs->deleteDirectory('/templates_c/filecache/');
            $io->success('File cache deleted.');

            return 0;
        } catch (Throwable $e) {
            $io->warning('Could not delete: ' . $e->getMessage());

            return 1;
        }
    }
}
