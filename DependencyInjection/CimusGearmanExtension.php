<?php

namespace Cimus\GearmanBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 * 
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class CimusGearmanExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $servers = [];
        
        foreach($config['servers'] as $server)
        {
            $servers[] = $server['host'] . ':' . $server['port'];
        }
        
        $container->setParameter('cimus.gearman.servers', implode(',', $servers));
        $container->setParameter('cimus.gearman.servers.original', $config['servers']);
        
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
