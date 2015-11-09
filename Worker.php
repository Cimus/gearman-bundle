<?php
namespace Cimus\GearmanBundle;

use LogicException;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Базовый класс для воркеров
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
abstract class Worker extends ContainerAware 
{
    /**
     * @return Doctrine\Bundle\DoctrineBundle\Registry
     * @throws LogicException If DoctrineBundle is not available
     */
    public function getDoctrine()
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
    public function has($id)
    {
        return $this->container->has($id);
    }

    
    /**
     * @param string $id
     * @return object
     */
    public function get($id)
    {
        return $this->container->get($id);
    }
    
}
