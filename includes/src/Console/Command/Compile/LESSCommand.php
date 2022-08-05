<?php declare(strict_types=1);

namespace JTL\Console\Command\Compile;

use JTL\Console\Command\Command;
use JTL\Console\ConsoleIO;
use JTL\Filesystem\LocalFilesystem;
use JTL\Shop;
use Less_Parser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LESSCommand
 * @package JTL\Console\Command\Compile
 */
class LESSCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('compile:less')
            ->setDescription('Compile all theme specific less files')
            ->addOption('theme', null, InputOption::VALUE_OPTIONAL, 'Single theme name to compile')
            ->addOption('templateDir', null, InputOption::VALUE_OPTIONAL, 'Template directory to compile from');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io               = $this->getIO();
        $themeParam       = $this->getOption('theme');
        $templateDirParam = $this->getOption('templateDir');
        $templateDir      = $templateDirParam === null
            ? \PFAD_TEMPLATES . 'Evo/themes/'
            : \PFAD_TEMPLATES . \rtrim($templateDirParam, '/') . '/themes/';
        if ($themeParam === null) {
            $compiled   = 0;
            $fileSystem = Shop::Container()->get(LocalFilesystem::class);
            foreach ($fileSystem->listContents($templateDir) as $themeFolder) {
                if (\basename($themeFolder->path()) === 'base') {
                    continue;
                }
                if (!$this->compileLess(\PFAD_ROOT . $themeFolder->path(), \basename($themeFolder->path()), $io)) {
                    return Command::FAILURE;
                }
                ++$compiled;
            }
            if ($compiled > 0) {
                $io->writeln('...');
                $io->writeln('<info>Theme files were compiled successfully.</info>');
            } else {
                $io->writeln('<info>No files were compiled.</info>');
            }
        } elseif ($this->compileLess(\PFAD_ROOT . $templateDir . $themeParam, $themeParam, $io)) {
            $io->writeln('...');
            $io->writeln('<info>Theme ' . $themeParam . ' was compiled successfully.</info>');
        } else {
            $io->writeln('<info>Theme ' . $themeParam . ' could not be compiled.</info>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param string    $path
     * @param string    $themeName
     * @param ConsoleIO $io
     * @return bool
     */
    private function compileLess(string $path, string $themeName, ConsoleIO $io): bool
    {
        $parser = new Less_Parser();
        try {
            $parser->parseFile($path . '/less/theme.less', '/');
            $css = $parser->getCss();
            \file_put_contents($path . '/bootstrap.css', $css);
            $io->writeln('<info>compiled ' . $themeName . ' theme </info>');
            unset($parser);

            return true;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return false;
        }
    }
}
