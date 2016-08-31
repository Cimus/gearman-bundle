<?php
namespace Cimus\GearmanBundle;

use LogicException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Базовый класс для воркеров
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
abstract class Worker
{
    use ContainerAwareTrait;
    
    public function init()
    {
        
    }






    protected function getWorkload(\GearmanJob $job)
    {
        return unserialize($job->workload());
    }
    
    /**
     * 
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }


    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     * @throws LogicException If DoctrineBundle is not available
     */
    protected function getDoctrine()
    {
        if (!$this->container->has('doctrine')) {
            throw new LogicException('The DoctrineBundle is not registered in your application.');
        }
        return $this->container->get('doctrine');
    }
    
    
    

    /**
     * @param string $id
     * @return boolean
     */
    protected function has($id)
    {
        return $this->container->has($id);
    }

    
    /**
     * @param string $id
     * @return object
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }
    
    
    
    /**
     * 
     * @return \Cimus\GearmanBundle\Services\GearmanClient
     */
    protected function getGearmanClient()
    {
        return $this->container->get('cimus.gearman');
    }
    
    
    
    
    
    
    
}
