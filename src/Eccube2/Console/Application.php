<?php

namespace Eccube2\Console;

use Eccube2\Init;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application extends BaseApplication
{
    protected static $configPaths = array();

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        define('SAFE', true);

        Init::init();

        // 標準コマンドをインポート
        self::prependConfigPath(realpath(__DIR__ . '/../../../config'));

        $container = new ContainerBuilder();
        $container->addCompilerPass(new AddConsoleCommandPass());
        foreach (self::$configPaths as $configPath) {
            $loader = new YamlFileLoader($container, new FileLocator($configPath));
            $loader->load('services.yaml');
        }
        $container->compile();

        $this->setCommandLoader($container->get('console.command_loader'));

        try {
            $objQuery = \SC_Query_Ex::getSingletonInstance();
            if ($objQuery->isError()) {
                throw new \Exception();
            }

            $objPlugin = \SC_Helper_Plugin_Ex::getSingletonInstance(true);
            if ($objPlugin instanceof \SC_Helper_Plugin_Ex) {
                $objPlugin->doAction('Eccube2_Console_Application::__construct', array($this));
            }
        } catch (\Exception $e) {
        }
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
