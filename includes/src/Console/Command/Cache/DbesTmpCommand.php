<?php declare(strict_types=1);

namespace JTL\Console\Command\Cache;

use JTL\Console\Command\Command;
use JTL\Filesystem\LocalFilesystem;
use JTL\Shop;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class DbesTmpCommand
 * @package JTL\Console\Command\Cache
 */
class DbesTmpCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('cache:dbes:delete')
            ->setDescription('Delete dbeS cache');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();
        $fs = Shop::Container()->get(LocalFilesystem::class);
        try {
            /** @var Filesystem $fs */
            foreach ($fs->listContents('dbeS/tmp/')->toArray() as $item) {
                /** @var FileAttributes $item */
                if ($item->isDir()) {
                    $fs->deleteDirectory($item->path());
                } else {
                    $fs->delete($item->path());
                }
            }
            $io->success('dbeS tmp cache deleted.');

            return 0;
        } catch (Throwable $e) {
            $io->warning('Could not delete: ' . $e->getMessage());

            return 1;
        }
    }
}
