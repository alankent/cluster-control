<?php

namespace AlanKent\ClusterControl\Commands;

use AlanKent\ClusterControl\ClusterControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveKeyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cc:removekey')
            ->setDescription(
                'Remove the key for this service to tell others this service is no longer available.'
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
     * Remove the current server's key.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Returns 0 on success.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getOption('conf');
        $clusterControl = new ClusterControl($conf);
        $clusterControl->removeKey();
        return 0;
    }
}
