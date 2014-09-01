<?php

namespace AlanKent\ClusterControl\Commands;

use AlanKent\ClusterControl\ClusterControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchKeyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cc:watchkey')
            ->setDescription(
                'Watch the containers key, returning when key is deleted.'
            )
            ->addOption(
                'conf',
                null,
                InputOption::VALUE_REQUIRED,
                'Configuration file',
                ClusterControl::DEFAULT_CONFIG_FILE
            );
    }

    /**
     * Watch the container's key, returning as soon as the key disappears.
     * This means the container has been requested to exit.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Returns 0 when container key is deleted (external shutdown request).
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getOption('conf');
        $clusterControl = new ClusterControl($conf);

        // Keep fetching the key until we fail.
        $clusterControl->watchKey();

        // Graceful exit.
        return 0;
    }
}
