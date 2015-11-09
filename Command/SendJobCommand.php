<?php
namespace Cimus\GearmanBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Ставит задачу (для тестов)
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class SendJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('cimus:gearman:job:send')
            ->setDescription('Send given gearman job')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('data', InputArgument::REQUIRED)
        ;
    }
    
    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = $input->getArgument('name');
        $jobData = $input->getArgument('data');

        $gmclient = $this->getContainer()->get('cimus.gearman.client');
        $gmclient->doBackground($jobName, $jobData);

        $returnCode = $gmclient->returnCode();
        if (GEARMAN_SUCCESS !== $returnCode) {
            throw new \RuntimeException(sprintf('Gearman server returned non-success code "%"', $returnCode));
        }
    }
    
    
}
