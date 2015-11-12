<?php
namespace Cimus\GearmanBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use TweeGearmanStat\Queue\Gearman as GearmanTelnetMonitor;
use Symfony\Component\Console\Helper\Table;

/**
 * Description of MonitorCommand
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class MonitorCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('cimus:gearman:monitor')
            ->addOption('watch', null, InputOption::VALUE_REQUIRED, 'Check for changes every n seconds set in option or one by default')
            ->setDescription('Run monitor on gearman queue')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command monitors gearman queue
<info>php %command.full_name%</info>
<info>php %command.full_name% --watch=1 --env=prod</info>
EOF
        );
    }
    
    
    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = new GearmanTelnetMonitor($this->getGearmanServers());
        $watch = $input->getOption('watch');
        $once = true;
        $table = new Table($output);
        while($once || $watch)
        {
            $status = $adapter->status();
            foreach($status as $server => $queues)
            {
                
                $output->writeln("<info>Status for Server {$server}</info>");
                $output->writeln("");
                if($this->getHelperSet()->has('table'))
                {
                    $table = new Table($output);
                    $table
                            ->setHeaders(['Queue', 'Jobs', 'Workers working', 'Workers total'])
                            ->setRows($queues);
                    $table->render($output);
                }
                else
                {
                    foreach($queues as $queue)
                    {
                        $str = "    <comment>{$queue['name']}</comment> Jobs: {$queue['queue']}";
                        $str .= " Workers: {$queue['running']} / {$queue['workers']}";
                        $output->writeln($str);
                    }
                }
            }
            
            $once = false;
            if($watch)
            {
                sleep(intval($watch));
            }
        }
    }
    
    
    
    
    /**
     * Formats servers as argument for GearmanTelnetMonitor
     *
     * @return array
     */
    private function getGearmanServers()
    {
        $servers = $this->getContainer()->getParameter('cimus.gearman.servers.original');
        
        $return = [];
        
        foreach($servers as $name => $server)
        {
            $return[$name] = [
                'host' => $server['host'],
                'port' => intval($server['port']),
                'timeout' => 1
            ];
        }
        
        return $return;
    }
}
