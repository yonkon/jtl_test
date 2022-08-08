<?php
declare(strict_types=1);

namespace Plugin\landswitcher;

use Illuminate\Support\Collection;
use JTL\Backend\AdminIO;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;

class Bootstrap extends Bootstrapper
{

    /**
     * @inheritDoc
     */
    public function boot(Dispatcher $dispatcher)
    {
        parent::boot($dispatcher);


        $dispatcher->listen('shop.hook.' . HOOK_IO_HANDLE_REQUEST_ADMIN, function ($args) {
            $handler = new Handler($this->getPlugin(), $this->getDB());
            extract($args, EXTR_OVERWRITE);
            /** @var  AdminIO $io */
            /** @var  mixed $request */
            $io->register('Landswitcher.create', [$handler, 'onCreate']);
            $io->register('Landswitcher.getOne', [$handler, 'onGetOne']);
            $io->register('Landswitcher.getList', [$handler, 'onGetList']);
            $io->register('Landswitcher.update', [$handler, 'onUpdate']);
            $io->register('Landswitcher.delete', [$handler, 'onDelete']);
        });
    }

    public function onSettingsUpdate($args)
    {
        extract($args, EXTR_OVERWRITE);
        /** @var  \JTL\Plugin\Plugin $plugin */
        /** @var  bool $hasError */
        /** @var  string $msg */
        /** @var  string $error */
        /** @var  Collection $options */
    }


}
