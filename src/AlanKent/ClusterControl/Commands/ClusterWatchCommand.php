<?php

namespace AlanKent\ClusterControl\Commands;

use AlanKent\ClusterControl\ClusterControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClusterWatchCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cc:clusterwatch')
            ->setDescription(
                'Watch the specified cluster starting from the start watch index.'
            )->addOption(
                'conf',
                null,
                InputOption::VALUE_REQUIRED,
                'Configuration file',
                ClusterControl::DEFAULT_CONFIG_FILE
            )->addOption(
                'cluster',
                null,
                InputArgument::VALUE_REQUIRED,
                'Cluster name to watch.'
            )->addOption(
                'index',
                null,
                InputArgument::VALUE_REQUIRED,
                'Wait index to start watching from.'
            )->addArgument(
                'exec-command',
                InputArgument::REQUIRED,
                'Command to execute'
            )->addArgument(
                'argument',
                InputArgument::IS_ARRAY,
                'Command arguments'
            );
    }

    /**
     * Work out the members of the cluster and write that list to the configuration file.
     * Writes to stdout the wait index to pass to cc:clusterwatch to spot changes.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Returns 0 on success.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getOption('conf');
        $cluster = $input->getArgument('cluster');
        $waitIndex = $input->getArgument('waitindex');
        $command = $input->getArgument('exec-command');
        $arguments = $input->getArgument('argument');

        // Form command to execute when configuration is changed.
        $exec = escapeshellcmd($command);
        foreach ($arguments as $argument) {
            $exec .= ' ' . escapeshellarg($argument);
        }

        $clusterControl = new ClusterControl($conf);

        // Fetch the file the first time.
        $output->write('<info>Starting to watch cluster "'.$cluster.'".</info>');
        $resp = $clusterControl->readClusterMembers($cluster, $waitIndex);
        $previousMembers = $resp['members'];

        // Loop reading the cluster members and writing to config file.
        while ($resp) {
            $waitIndex = $resp['index'];
            $clusterMembers = $resp['members'];

            // Only rewrite the file if the contents has changed. Heartbeats for
            // example may mark directory as changed so returned from 'watch', but
            // there is no change in membership.
            if ($clusterMembers != $previousMembers) {

                // Membership has changed.
                $output->write('<info>Membership of "'.$cluster.'" has changed - writing new configuration file.</info>');
                $clusterControl->writeClusterConfig($cluster, $clusterMembers);
                $previousMembers = $clusterMembers;

                // Run command to tell server about the change (e.g. restart server).
                $output->write('<info>Tell service to load new members: '.$exec.'</info>');
                passthru($exec);
            }

            // Wait for next possible change in membership of cluster.
            $resp = $clusterControl->readClusterMembers($cluster, $waitIndex);
        }

        // Something went wrong reading the cluster. That should never happen.
        return 1;
    }
}
