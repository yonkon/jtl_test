<?php declare(strict_types=1);

namespace JTL\Console\Command\Compile;

use Exception;
use JTL\Console\Command\Command;
use JTL\Console\ConsoleIO;
use JTL\Filesystem\LocalFilesystem;
use JTL\Shop;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SASSCommand
 * @package JTL\Console\Command\Compile
 */
class SASSCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('compile:sass')
            ->setDescription('Compile all theme specific sass files')
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
        $cacheDir         = \PFAD_ROOT . \PFAD_COMPILEDIR . 'tpleditortmp';
        $templateDir      = $templateDirParam === null
            ? \PFAD_TEMPLATES .'NOVA/themes/'
            : \PFAD_TEMPLATES . \rtrim($templateDirParam, '/') . '/themes/';
        $fileSystem       = Shop::Container()->get(LocalFilesystem::class);
        $themeFolders     = $fileSystem->listContents($templateDir, false);
        if ($themeParam !== null) {
            $this->compile($themeParam, $templateDir, $cacheDir, $io);
        } else {
            $compiled = 0;
            foreach ($themeFolders as $themeFolder) {
                if (!$this->compile(\basename($themeFolder->path()), $templateDir, $cacheDir, $io)) {
                    return Command::FAILURE;
                }
                ++$compiled;
            }
            if ($compiled === 0) {
                $io->writeln('<info>No files were compiled.</info>');

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param string    $themeFolderName
     * @param string    $templateDir
     * @param string    $cacheDir
     * @param ConsoleIO $io
     * @return bool
     */
    private function compile(string $themeFolderName, string $templateDir, string $cacheDir, ConsoleIO $io): bool
    {
        if ($themeFolderName === 'base') {
            return true;
        }
        $theme      = $themeFolderName;
        $directory  = \realpath(\PFAD_ROOT . $templateDir . $theme) . '/';
        $compareDir = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, \realpath(\PFAD_ROOT . \PFAD_TEMPLATES));
        if (\strpos($directory, $compareDir) !== 0) {
            $io->error('Theme does not exist. ');

            return false;
        }
        if (\defined('THEME_COMPILE_CACHE') && \THEME_COMPILE_CACHE === true) {
            if (\file_exists($cacheDir)) {
                \array_map('\unlink', \glob($cacheDir . '/lessphp*'));
            } elseif (!\mkdir($cacheDir, 0777) && !\is_dir($cacheDir)) {
                throw new \RuntimeException(\sprintf('Directory "%s" was not created', $cacheDir));
            }
        }
        $input = $directory . 'sass/' . $theme . '.scss';
        if (!\file_exists($input)) {
            $io->error("Theme scss file: $input does not exist. ");

            return false;
        }
        try {
            $this->compileSass($input, $directory . $theme . '.css', $directory);
            $critical = $input = $directory . 'sass/' . $theme . '_crit.scss';
            if (\file_exists($critical)) {
                $this->compileSass($critical, $directory . $theme . '_crit.css', $directory);
                $io->writeln('<info>' . $theme . '_crit.css was compiled successfully.</info>');
            }
            $io->writeln('<info>' . $theme . '.css was compiled successfully.</info>');

            return true;
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return false;
        }
    }

    /**
     * @param string $file
     * @param string $target
     * @param string $directory
     */
    private function compileSass(string $file, string $target, string $directory): void
    {
        $baseDir  = $directory . 'sass/';
        $critical = \strpos($file, '_crit') !== false;
        $compiler = new Compiler();
        $compiler->setSourceMap($critical ? Compiler::SOURCE_MAP_NONE : Compiler::SOURCE_MAP_FILE);
        $compiler->setSourceMapOptions([
            'sourceMapURL'      => \basename($target) . '.map',
            'sourceMapBasepath' => $directory,
        ]);
        $compiler->addImportPath($baseDir);
        $result = $compiler->compileString(\file_get_contents($file));
        \file_put_contents($target, $result->getCss());
        if (!$critical) {
            \file_put_contents($target . '.map', $result->getSourceMap());
        }
    }
}
