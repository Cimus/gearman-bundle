<?php
namespace Cimus\GearmanBundle\Command;

use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\Table;


/**
 * Description of ListWorkerCommand
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class ListWorkerCommand extends ContainerAwareCommand
{
    private $workers = [];
    
    
    
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('cimus:gearman:list')
            ->addOption('watch', null, InputOption::VALUE_REQUIRED, 'Check for changes every n seconds set in option or one by default')
            ->setDescription('List gearman workers')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command list gearman workers
<info>php %command.full_name%</info>
EOF
        );
    }
    
    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle)
        {
            if(is_dir($cmdDir = $bundle->getPath().'/Worker'))
            {
                $finder = new Finder;
                $finder->files()->in($cmdDir)->name('*Worker.php');
                $prefix = $bundle->getNamespace() . '\\Worker';
                
                foreach($finder as $file)
                {
                    $ns = $prefix;
                    if($relativePath = $file->getRelativePath())
                    {
                        $ns .= '\\' . strtr($relativePath, '/', '\\');
                    }
                    $class = $ns . '\\' . $file->getBasename('.php');
                    $workerName = $bundle->getName() .  ':' . substr($file->getBasename('.php'), 0, -6);
                    
                    $r = new ReflectionClass($class);
                    
                    if(!$r->isAbstract() && $r->isSubclassOf('Cimus\\GearmanBundle\\Worker'))
                    {
                        $this->workers[$workerName] = new $class;
                        $this->workers[$workerName]->setContainer($this->getContainer());
                    }
                }
            }
        }
        
        if(!$this->workers)
        {
            throw new \InvalidArgumentException('Could not find workers');
        }
        
        $annotationReader = $this->getContainer()->get('annotation_reader');
        
        
        $jobs = [];
        
        foreach($this->workers as $workerName => $worker) 
        {
            $reflection = new \ReflectionObject($worker);
            $reflectionMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach($reflectionMethods as $method)
            {
                foreach ($annotationReader->getMethodAnnotations($method) as $annotation)
                {
                    if($annotation instanceof \Cimus\GearmanBundle\Annotation\Gearman)
                    {
                        if(!$annotation->name) $annotation->name = $method->getName();
                        
                        $jobs[]=[
                            $workerName,
                            $annotation->name,
                            $annotation->description,
                            $annotation->retry,
                        ];
                    }
                }
            }
        }

        $table = new Table($output);
        
        $table = new Table($output);
        $table
                ->setHeaders(['Worker Name', 'Job Name', 'Job Description', 'Is Retry'])
                ->setRows($jobs);
        $table->render($output);
        
    }
    
}
