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

use Littlesqx\AintQueue\Driver\DriverFactory;
use Littlesqx\AintQueue\Exception\InvalidArgumentException;
use Littlesqx\AintQueue\Exception\InvalidDriverException;
use Littlesqx\AintQueue\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function dirname;
use function file_exists;

abstract class AbstractCommand extends Command
{
    /**
     * @var Manager
     */
    protected $manager;

    protected function configure()
    {
        $this->addOption('channel', 't', InputOption::VALUE_REQUIRED, 'The channel of queue.', 'default');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The config file path', '');
    }

    /**
     * Initialize queue manager.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws InvalidArgumentException
     * @throws InvalidDriverException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $channel = $input->getOption('channel');
        $configFile = $input->getOption('config');

        if ($configFile) {
            $config = require $configFile;
        } else {
            $binPath = dirname($_SERVER['SCRIPT_FILENAME']);
            if (file_exists($binPath . '/../../config/aint-queue.php')) {
                $config = require $binPath . '/../../config/aint-queue.php';
            } else {
                $config = require __DIR__ . '/../../Config/config.php';
            }
        }

        if (!isset($config[$channel])) {
            throw new InvalidArgumentException(sprintf('[Error] The config of queue "%s" is not provided.', $channel));
        }

        $options = $config[$channel];

        $driverOptions = $options['driver'] ?? [];
        $driver = DriverFactory::make($channel, $driverOptions);
        $this->manager = new Manager($driver, $options);
    }
}
