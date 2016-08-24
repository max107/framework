<?php
/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/06/14.06.2014 18:49
 */

namespace Mindy\Console;

use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * ConsoleCommand represents an executable console command.
 *
 * It works like {@link CController} by parsing command line options and dispatching
 * the request to a specific action with appropriate option values.
 *
 * Users call a console command via the following command format:
 * <pre>
 * yiic CommandName ActionName --Option1=Value1 --Option2=Value2 ...
 * </pre>
 *
 * Child classes mainly needs to implement various action methods whose name must be
 * prefixed with "action". The parameters to an action method are considered as options
 * for that specific action. The action specified as {@link defaultAction} will be invoked
 * when a user does not specify the action name in his command.
 *
 * Options are bound to action parameters via parameter names. For example, the following
 * action method will allow us to run a command with <code>yiic sitemap --type=News</code>:
 * <pre>
 * class SitemapCommand extends CConsoleCommand {
 *     public function actionIndex($type) {
 *         ....
 *     }
 * }
 * </pre>
 *
 * Since version 1.1.11 the return value of action methods will be used as application exit code if it is an integer value.
 *
 * @property string $name The command name.
 * @property ConsoleCommandRunner $commandRunner The command runner instance.
 * @property string $help The command description. Defaults to 'Usage: php entry-script.php command-name'.
 * @property array $optionHelp The command option help information. Each array element describes
 * the help information for a single action.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.console
 * @since 1.0
 */
abstract class ConsoleCommand
{
    /**
     * @var string
     */
    private $_name;
    /**
     * @var ConsoleCommandRunner
     */
    private $_runner;

    /**
     * Constructor.
     * @param string $name name of the command
     * @param ConsoleCommandRunner $runner the command runner
     */
    public function __construct($name, $runner)
    {
        $this->_name = $name;
        $this->_runner = $runner;
    }

    /**
     * Executes the command.
     * The default implementation will parse the input parameters and
     * dispatch the command request to an appropriate action with the corresponding
     * option values
     * @param array $args command line parameters for this command.
     * @return integer application exit code, which is returned by the invoked action. 0 if the action did not return anything.
     * (return value is available since version 1.1.11)
     */
    public function run($args)
    {
        list($action, $options, $args) = $this->resolveRequest($args);
        $methodName = 'action' . $action;
        if (!preg_match('/^\w+$/', $action) || !method_exists($this, $methodName)) {
            $this->usageError("Unknown action: " . $action);
        }

        $method = new ReflectionMethod($this, $methodName);
        $params = [];
        // named and unnamed options
        foreach ($method->getParameters() as $i => $param) {
            $name = $param->getName();
            if (isset($options[$name])) {
                if ($param->isArray()) {
                    $params[] = is_array($options[$name]) ? $options[$name] : array($options[$name]);
                } elseif (!is_array($options[$name])) {
                    $params[] = $options[$name];
                } else {
                    $this->usageError("Option --$name requires a scalar. Array is given.");
                }
            } elseif ($name === 'args') {
                $params[] = $args;
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                $this->usageError("Missing required option --$name.");
            }
            unset($options[$name]);
        }

        if (!empty($options)) {
            $this->usageError("Unknown options: " . implode(', ', array_keys($options)));
        }

        return $method->invokeArgs($this, $params);
    }

    /**
     * Parses the command line arguments and determines which action to perform.
     * @param array $args command line arguments
     * @return array the action name, named options (name=>value), and unnamed options
     * @throws Exception
     */
    protected function resolveRequest($args)
    {
        $options = []; // named parameters
        $params = []; // unnamed parameters
        foreach ($args as $arg) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $arg, $matches)) { // an option
                $name = $matches[1];
                $value = isset($matches[3]) ? $matches[3] : true;
                if (isset($options[$name])) {
                    if (!is_array($options[$name])) {
                        $options[$name] = [$options[$name]];
                    }
                    $options[$name][] = $value;
                } else {
                    $options[$name] = $value;
                }
            } elseif (isset($action)) {
                $params[] = $arg;
            } else {
                $action = $arg;
            }
        }
        if (!isset($action)) {
            throw new Exception('Unknown action');
        }

        return [
            $action,
            $options,
            $params
        ];
    }

    /**
     * @return string the command name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return ConsoleCommandRunner the command runner instance
     */
    public function getCommandRunner()
    {
        return $this->_runner;
    }

    /**
     * Provides the command description.
     * This method may be overridden to return the actual command description.
     * @return string the command description. Defaults to 'Usage: php entry-script.php command-name'.
     */
    public function getHelp()
    {
        $help = 'Usage: ' . $this->getCommandRunner()->getScriptName() . ' ' . $this->getName();
        $options = $this->getOptionHelp();
        if (empty($options)) {
            return $help . "\n";
        }
        if (count($options) === 1) {
            return $help . ' ' . $options[0] . "\n";
        }
        $help .= " <action>\nActions:\n";
        foreach ($options as $option) {
            $help .= '    ' . $option . "\n";
        }
        return $help;
    }

    /**
     * Provides the command option help information.
     * The default implementation will return all available actions together with their
     * corresponding option information.
     * @return array the command option help information. Each array element describes
     * the help information for a single action.
     * @since 1.1.5
     */
    public function getOptionHelp()
    {
        $options = [];
        $class = new ReflectionClass(get_class($this));
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (!strncasecmp($name, 'action', 6) && strlen($name) > 6) {
                $name = substr($name, 6);
                $name[0] = strtolower($name[0]);
                $help = $name;

                foreach ($method->getParameters() as $param) {
                    $optional = $param->isDefaultValueAvailable();
                    $defaultValue = $optional ? $param->getDefaultValue() : null;
                    if (is_array($defaultValue)) {
                        $arrOut = [];
                        foreach ($defaultValue as $key => $value) {
                            $arrOut[] = $key . ' => ' . $value;
                        }
                        $defaultValue = str_replace(array("\r\n", "\n", "\r"), "", '[' . implode(', ', $arrOut) . ']');
                    }
                    $name = $param->getName();

                    if ($name === 'args') {
                        continue;
                    }

                    if ($optional) {
                        $help .= " [--$name=$defaultValue]";
                    } else {
                        $help .= " --$name=value";
                    }
                }
                $options[] = $help;
            }
        }
        return $options;
    }

    /**
     * Displays a usage error.
     * This method will then terminate the execution of the current application.
     * @param string $message the error message
     */
    public function usageError($message)
    {
        echo "Error: $message\n\n" . $this->getHelp() . "\n";
        exit(1);
    }

    /**
     * Converts a word to its plural form.
     * @param string $name the word to be pluralized
     * @return string the pluralized word
     */
    public function pluralize($name)
    {
        $rules = array(
            '/(m)ove$/i' => '\1oves',
            '/(f)oot$/i' => '\1eet',
            '/(c)hild$/i' => '\1hildren',
            '/(h)uman$/i' => '\1umans',
            '/(m)an$/i' => '\1en',
            '/(s)taff$/i' => '\1taff',
            '/(t)ooth$/i' => '\1eeth',
            '/(p)erson$/i' => '\1eople',
            '/([m|l])ouse$/i' => '\1ice',
            '/(x|ch|ss|sh|us|as|is|os)$/i' => '\1es',
            '/([^aeiouy]|qu)y$/i' => '\1ies',
            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
            '/(shea|lea|loa|thie)f$/i' => '\1ves',
            '/([ti])um$/i' => '\1a',
            '/(tomat|potat|ech|her|vet)o$/i' => '\1oes',
            '/(bu)s$/i' => '\1ses',
            '/(ax|test)is$/i' => '\1es',
            '/s$/' => 's',
        );
        foreach ($rules as $rule => $replacement) {
            if (preg_match($rule, $name)) {
                return preg_replace($rule, $replacement, $name);
            }
        }
        return $name . 's';
    }
}
