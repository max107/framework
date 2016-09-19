<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 23:43
 */

namespace Mindy\Migration\Commands;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use function Mindy\app;
use Symfony\Component\Console\Input\InputInterface;

class ModuleConfigurationHelper extends ConfigurationHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * ConfigurationHelper constructor.
     * @param Connection|null $connection
     * @param Configuration|null $configuration
     */
    public function __construct(Connection $connection = null, Configuration $configuration = null)
    {
        $this->connection    = $connection;
        $this->configuration = $configuration;
    }

    /**
     * @param InputInterface $input
     * @param OutputWriter $outputWriter
     * @return Configuration
     */
    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter)
    {
        $configuration = new Configuration($this->connection);
        $moduleName = $input->getOption('module');

        if (app()->hasModule($moduleName) === false) {
            throw new \Exception('Unknown module');
        }

        $module = app()->getModule($moduleName);

        $dir = $module->getBasePath() . '/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $configuration->setName($module->getId());
        $configuration->setMigrationsTableName(strtolower($moduleName) . '_migrations');
        $configuration->setMigrationsNamespace(strtr('Modules\{id}\Migrations', ['{id}' => $module->getId()]));
        $configuration->setMigrationsDirectory($dir);
        return $configuration;
    }
}