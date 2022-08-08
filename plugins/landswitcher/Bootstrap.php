<?php

declare(strict_types=1);

namespace Plugin\landswitcher;

use JTL\Backend\AdminIO;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;

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
            /** @var  AdminIO $io */
            $io = $args['io'];

            $io->register('Landswitcher.create', [$handler, 'onCreate']);
            $io->register('Landswitcher.getOne', [$handler, 'onGetOne']);
            $io->register('Landswitcher.getList', [$handler, 'onGetList']);
            $io->register('Landswitcher.update', [$handler, 'onUpdate']);
            $io->register('Landswitcher.delete', [$handler, 'onDelete']);
        });
    }



}
