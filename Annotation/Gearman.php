<?php
namespace Cimus\GearmanBundle\Annotation;

/**
 * @Annotation
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
final class Gearman implements \Doctrine\ORM\Mapping\Annotation
{
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $description;
    
    public $retry = false;
}
