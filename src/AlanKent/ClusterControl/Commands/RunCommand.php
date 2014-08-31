<?php

namespace AlanKent\ClusterControl\Commands;

use LinkORB\Component\Etcd\Client as EtcdClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cluster:run')
            ->setDescription(
                'Run a service, registering and deregistering in etcd while running'
            )
            ->addOption(
                'conf',
                null,
                InputOption::VALUE_REQUIRED,
                'Configuration file',
                'cluster-control.conf'
            )->addArgument(
                'exec-command',
                InputArgument::REQUIRED,
                'Command to execute'
            )->addArgument(
                'argument',
                InputArgument::IS_ARRAY,
                'Command arguments'
            )
            /*
            ->addArgument(
                'server',
                InputArgument::OPTIONAL,
                'Base url of etcd server and the default is http://127.0.0.1:4001'
            )->addOption(
                'recursive',
                null,
                InputOption::VALUE_NONE
            )->addOption(
                'after-index',
                null,
                InputOption::VALUE_OPTIONAL,
                'watch after the given index',
                0
            )*/;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $input->getOption('conf');
        $command = $input->getArgument('exec-command');
        $arguments = $input->getArgument('argument');

        $contents = file_get_contents($conf);
        $config = json_decode(utf8_encode($contents), true);

        $exec = escapeshellcmd($command);
        foreach ($arguments as $argument) {
            $exec .= ' ' . escapeshellarg($argument);
        }
        print "exec = $exec\n";

        $server = $config['etcd']['server'];
        $selfKey = $config['self']['key'];
        $ttl = $config['self']['ttl'];

        //$client = new EtcdClient($server);
        //$client->set($selfKey, '', $ttl);
        passthru($exec);
        //$client->rm($selfKey);

        //$server = $input->getArgument('server');
        //$key = $input->getArgument('key');
        //$afterIndex = $input->getOption('after-index');
        //$output->writeln("<info>Watching key `$key`</info>");
        //$client = new EtcdClient($server);
        /*
        $query = array('wait' => 'true');
        
        if ($recursive) {
            $query['recursive'] = 'true';
        }
        
        if ($afterIndex) {
            $query['waitIndex'] = $afterIndex;
        }
        
        $data = $client->get($key, $query);
        $output->writeln($data);
        */
    }
}
