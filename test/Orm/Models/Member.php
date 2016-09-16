<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 15:32
 */

namespace Mindy\Tests\Orm\Models;

use Mindy\Orm\Fields\OneToOneField;
use Mindy\Orm\Model;

class Member extends Model
{
    public static function getFields()
    {
        return [
            'profile' => [
                'class' => OneToOneField::class,
                'modelClass' => MemberProfile::class,
                'reversed' => true,
                'to' => 'user_id'
            ],
        ];
    }
}