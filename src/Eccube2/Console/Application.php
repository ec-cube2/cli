<?php

namespace Eccube2\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class Application extends BaseApplication
{
    protected static $configPaths = array();

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        define('SAFE', true);

        // 標準コマンドをインポート
        self::prependConfigPath(realpath(__DIR__ . '/../../../config'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addCompilerPass(new RegisterListenersPass());
        $containerBuilder->addCompilerPass(new AddConsoleCommandPass());
        $containerBuilder->register('event_dispatcher', 'Symfony\Component\EventDispatcher\EventDispatcher');
        foreach (self::$configPaths as $configPath) {
            $loader = new YamlFileLoader($containerBuilder, new FileLocator($configPath));
            $loader->load('services.yaml');
        }
        $containerBuilder->compile();

        $this->setCommandLoader($containerBuilder->get('console.command_loader'));
    }

    static public function appendConfigPath($path)
    {
        if (!is_dir($path)) {
            throw new \Exception($path . ' は存在しません');
        }

        self::$configPaths[] = $path;
    }

    static public function prependConfigPath($path)
    {
        if (!is_dir($path)) {
            throw new \Exception($path . ' は存在しません');
        }

        array_unshift(self::$configPaths, $path);
    }
}
