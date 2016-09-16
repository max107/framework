<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 15:15
 */

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mindy\Orm\ModelInterface;

/**
 * Interface ModelFieldInterface
 * @package Mindy\Orm\Fields
 */
interface ModelFieldInterface
{
    /**
     * @param string $name
     */
    public function setName(string $name);

    /**
     * @param ModelInterface $model
     */
    public function setModel(ModelInterface $model);

    /**
     * @param $value
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();
}