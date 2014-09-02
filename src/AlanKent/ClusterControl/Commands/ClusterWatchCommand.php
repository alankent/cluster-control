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
            )->addArgument(
                'cluster',
                InputArgument::REQUIRED,
                'Cluster name to watch.'
            )->addArgument(
                'index',
                InputArgument::REQUIRED,
                'Wait index to start watching from.'
            )->addArgument(
                'exec',
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
        $waitIndex = $input->getArgument('index');
        $command = $input->getArgument('exec');
        $arguments = $input->getArgument('argument');
        $debug = $output->isVerbose();

        // Form command to execute when configuration is changed.
        $exec = escapeshellcmd($command);
        foreach ($arguments as $argument) {
            $exec .= ' ' . escapeshellarg($argument);
        }

        $clusterControl = new ClusterControl($conf, $debug);

        $output->writeln('<info>Starting to watch cluster "'.$cluster.'".</info>');

        // Unfortunately, there seems to be no way to ask all the entries at
        // the previous wait version. It tells you additions and removals,
        // not the whole directory. So the first little change will always
        // trigger rewriting the cluster membership.
        $previousMembers = [];

        // Wait for something to change.
        $clusterControl->waitClusterMembers($cluster, $waitIndex);

        // Loop reading the cluster members and writing to config file.
        while ($resp = $clusterControl->readClusterMembers($cluster)) {

            $waitIndex = $resp['index'];
            $clusterMembers = $resp['members'];

            // Only rewrite the file if the contents has changed. Heartbeats for
            // example may mark directory as changed so returned from 'watch', but
            // there is no change in membership.
            if ($clusterMembers != $previousMembers) {

                // Membership has changed.
                $output->writeln('<info>Membership of "'.$cluster.'" has changed - writing new configuration file.</info>');
                $clusterControl->writeClusterConfig($cluster, $clusterMembers);
                $previousMembers = $clusterMembers;

                // Run command to tell server about the change (e.g. restart server).
                $output->writeln('<info>Telling service to load new members: '.$exec.'</info>');
                passthru($exec);
            }

            // Wait for something to change.
            $clusterControl->waitClusterMembers($cluster, $waitIndex);
        }

        // Something went wrong reading the cluster. That should never happen.
        return 1;
    }
}
