<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:23
 */

namespace Mindy\Auth\UserProvider;

abstract class AbstractUserProvider implements UserProviderInterface
{
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @param array $config
     */
    protected function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }
}