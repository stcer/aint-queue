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

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WorkerStopCommand extends AbstractCommand
{
    protected static $defaultName = 'worker:stop';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Stop listening the queue.')
            ->setHelp('This Command allows you to stop listening the queue.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channel = $input->getOption('channel');
        if (!$this->manager->isRunning()) {
            $output->writeln("The master-process of {$channel}-queue is not running!\n");

            return;
        }

        $io = new SymfonyStyle($input, $output);

        if ($io->confirm("Are you sure to stop listening the $channel-queue?", false)) {
            $pid = (int) file_get_contents($this->manager->getPidFile());
            Process::kill($pid, SIGTERM);
            $io->writeln('Success to stop!');
        }

        return 0;
    }
}
