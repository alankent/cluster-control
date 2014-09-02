<?php

namespace AlanKent\ClusterControl\Commands;

use AlanKent\ClusterControl\ClusterControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HeartbeatCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cc:heartbeat')
            ->setDescription(
                'Periodically update etcd entry with a TTL to indicate we are still alive.'
            )->addOption(
                'conf',
                null,
                InputOption::VALUE_REQUIRED,
                'Configuration file',
                ClusterControl::DEFAULT_CONFIG_FILE
            );
    }

    /**
     * Loop forever sending heartbeat updates to the key.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Returns 0 when container key is deleted (external shutdown request).
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getOption('conf');
        $debug = $output->isVerbose();
        $clusterControl = new ClusterControl($conf, $debug);

        // First time we set the key because it should not exist.
        $count = 0;
        if ($debug) $output->write("<debug>Heartbeat 0</debug>", true);
        $clusterControl->setKey($count);

        // Keep refreshing the key, exiting if someone else deleted the key.
        do {
            sleep($clusterControl->heartbeatInterval());
            $count = $count + 1;
            if ($debug) $output->write("<debug>Heartbeat $count</debug>", true);
        } while ($clusterControl->updateKey($count));

        // Graceful exit.
        if ($debug) $output->write("<debug>Heartbeat exit</debug>", true);
        return 0;
    }
}
