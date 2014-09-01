<?php

namespace AlanKent\ClusterControl\Commands;

use AlanKent\ClusterControl\ClusterControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HeartBeatCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cc:heartbeat')
            ->setDescription(
                'Periodically update etcd entry with a TTL to indicate we are still alive.'
            )
            ->addOption(
                'conf',
                null,
                InputOption::VALUE_OPTIONAL,
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
        $clusterControl = new ClusterControl($conf);

        // First time we set the key because it should not exist.
        $clusterControl->setKey();
        sleep($clusterControl->heartbeatInterval());

        // Keep refreshing the key, exiting if someone else deleted the key.
        while ($clusterControl->refreshKey()) {
            sleep($clusterControl->heartbeatInterval());
        }

        // Graceful exit.
        return 0;
    }
}
