<?php

declare(strict_types=1);

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Littlesqx\AintQueue\Console\Command;

use Littlesqx\AintQueue\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerListenCommand extends AbstractCommand
{
    protected static $defaultName = 'worker:listen';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Listen the queue.')
            ->setHelp('This Command allows you to run a process to listen the queue.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->manager->isRunning()) {
            throw new RuntimeException(\sprintf('[Error] Listener for queue %s has been started.', $input->getOption('channel')));
        }
        // This statement will fork several sub-process to consumer the message.
        // Although it's non-blocking, master process is required to do as few things as possible.
        $this->manager->listen();

        return 0;
    }
}
