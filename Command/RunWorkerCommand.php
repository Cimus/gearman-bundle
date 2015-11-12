<?php
namespace Cimus\GearmanBundle\Command;

use GearmanJob;
use GearmanWorker;
use ReflectionClass;

use Cimus\GearmanBundle\Worker;
use Cimus\GearmanBundle\Exception\RetryException;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Команда для запуска воркеров
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class RunWorkerCommand extends ContainerAwareCommand
{
    
    private $workers = [];
    private $shouldStop = false;

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * 
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        declare(ticks = 1);
        
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('cimus:gearman:worker:run')
            ->setDescription('Run given worker.')
            ->addOption('worker', 'w', InputOption::VALUE_OPTIONAL, 'Worker name')
      ;
    }
    
    /**
     * Запуск воркеров
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $this->initWorkers($input->getOption('worker'));

            
        if(!$this->workers)
        {
            throw new \InvalidArgumentException('Could not find workers');
        }
        
        
        $gmWorker = $this->getGearmanWorker();
        //Максимальное время ожидания задания (в миллисекундах)
        $gmWorker->setTimeout(1000);
        $gmWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING); 
        
        foreach($this->workers as $workerName => $worker)
        {
            $this->registerWorker($workerName, $worker, $gmWorker, $output);
        }
        
        
        while(!$this->shouldStop && ($gmWorker->work() || ($gmWorker->returnCode() == GEARMAN_IO_WAIT) || ($gmWorker->returnCode() == GEARMAN_NO_JOBS)))
        {
            if($gmWorker->returnCode() == GEARMAN_SUCCESS)
                continue;
            $gmWorker->wait();
        }
    }
    
    /**
     * Инициализирует воркеров
     * 
     * @param string $workerName
     */
    private function initWorkers($workerName = null)
    {
        if($workerName)
            $this->initSingleWorker($workerName);
        else
            $this->initAllWorkers();
        
    }
    
    /**
     * Инициализирует всех воркеров
     */
    private function initAllWorkers()
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
    }


    
    /**
     * Инициализирует определённый воркер
     * 
     * @param string $workerName
     * @throws \InvalidArgumentException
     */
    private function initSingleWorker($workerName)
    {
        if(strpos($workerName, ':') === false)
            throw new \InvalidArgumentException("Invalid worker name ($workerName)");
        
        
        list($bundleName, $className) = explode(':', $workerName, 2);
        
        $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);
        
        if(is_dir($cmdDir = $bundle->getPath().'/Worker'))
        {
            $prefix = $bundle->getNamespace() . '\\Worker';
            $finder = new Finder;
            $finder->files()->in($cmdDir)->name($className. 'Worker.php');

            foreach($finder as $file)
            {
                $ns = $prefix;
                if($relativePath = $file->getRelativePath())
                {
                    $ns .= '\\' . strtr($relativePath, '/', '\\');
                }
                $class = $ns . '\\' . $file->getBasename('.php');

                $r = new ReflectionClass($class);

                if(!$r->isAbstract() && $r->isSubclassOf('Cimus\\GearmanBundle\\Worker'))
                {
                    $this->workers[$workerName] = new $class;
                    $this->workers[$workerName]->setContainer($this->getContainer());
                }
            }
        }
    }

    
    
    public function stopCommand()
    {
        $this->shouldStop = true;
    }
    
    
    
    /**
     * 
     * @return GearmanWorker
     */
    private function getGearmanWorker()
    {
        return $this->getContainer()->get('cimus.gearman.worker');
    }
    
    /**
     * Регистрирует воркеров
     * 
     * @param string $workerName
     * @param Worker $worker
     * @param GearmanWorker $gmWorker
     * @param OutputInterface $output
     */
    private function registerWorker($workerName, Worker $worker, GearmanWorker $gmWorker, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Registered worker %s</info>', $workerName));
        
        $annotationReader = $this->getContainer()->get('annotation_reader');
        
        $reflection = new \ReflectionObject($worker);
        $reflectionMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $jobs = [];
        
        foreach($reflectionMethods as $method)
        {
            foreach ($annotationReader->getMethodAnnotations($method) as $annotation)
            {
                if($annotation instanceof \Cimus\GearmanBundle\Annotation\Gearman)
                {
                    if(!$annotation->name) $annotation->name = $method->getName();
                        
                    $this->registerJob($annotation->name, $method->getName(), $worker, $gmWorker, $output, $annotation);
                }
            }
        }
    }
    
    /**
     * Регистрирует обработчиков
     * 
     * @param string $name
     * @param string $metod
     * @param Worker $worker
     * @param GearmanWorker $gmWorker
     * @param OutputInterface $output
     */
    private function registerJob($name, $metod, Worker $worker, GearmanWorker $gmWorker, OutputInterface $output, \Cimus\GearmanBundle\Annotation\Gearman $annotation)
    {
        $gmWorker->addFunction($name, function (GearmanJob $job) use ($name, $metod, $worker, $output, $annotation){
            
            try {
                $result = call_user_func_array([$worker, $metod ], [$job, $output]);
                $job->sendComplete($result);
                return true;
            }
            catch(RetryException $ex)
            {
                $output->writeln("<error>Failed:</error> " . $ex->getMessage() . ": <info>{$name}</info>");
                
                //Если нужно повторить таск и его разрешено повторять в аннотациях
                if($annotation->retry)
                {
                    sleep(3);//Подождём немного, может чуть погодя таск нормально пройдёт

                    $this->getContainer()->get('cimus.gearman')->doBackground($job->functionName(), unserialize($job->workload()));
                }
                else
                {
                    throw $ex;
                }

                return false;
            }
        });
        
        
        $output->writeln(date('Y-m-d H:i:s') . " -> Registering job: <comment>{$name}</comment>");
    }
}
