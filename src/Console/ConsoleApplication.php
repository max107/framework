<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 14:16
 */

namespace Mindy\Console;

use Exception;
use Mindy\Helper\Text;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ConsoleApplication extends Application
{
    /**
     * Find console commands in modules and create
     * @param array $modules
     * @return array|ConsoleCommand[]
     */
    public function findModulesCommands(array $modules = [])
    {
        $commands = [];
        foreach ($modules as $id => $module) {
            foreach ($module->getConsoleCommands() as $fileInfo) {
                $command = $this->createCommandFromFile($fileInfo);
                if ($command) {
                    $command->setModuleId(Text::toUnderscore($id));
                    $commands[] = $command;
                }
            }
        }
        return $commands;
    }

    /**
     * @param SplFileInfo $fileInfo
     * @return null|ConsoleCommand
     * @throws Exception
     */
    protected function createCommandFromFile(SplFileInfo $fileInfo)
    {
        $className = $this->getClassFromCode($fileInfo->getContents());
        if ($className) {
            $command = new $className;
            if ($command instanceof ConsoleCommand) {
                return $command;
            }
            return null;
        }

        throw new Exception('Classes not found in: ' . $fileInfo->getPath());
    }

    /**
     * @param $modulesPath
     * @return array
     * @throws Exception
     */
    public function findCommands($path) : array
    {
        $commands = [];

        $finder = new Finder();
        $finder->files()->in($path)->ignoreUnreadableDirs()->name('*Command.php');

        foreach ($finder as $fileInfo) {
            $command = $this->createCommandFromFile($fileInfo);
            if ($command) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * @param $code string
     * @return string
     * @throws Exception
     */
    public function getClassFromCode($code) : string
    {
        $class = null;
        $namespace = '';

        $tokens = token_get_all($code);
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $namespace .= '\\' . $tokens[$j][1];
                    } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === '{') {
                        if (empty($namespace)) {
                            $class = $tokens[$i + 2][1];
                        } else {
                            $class = $namespace . '\\' . $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        return $class;
    }
}