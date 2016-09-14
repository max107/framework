<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 20:20
 */

namespace Mindy\Tests\Orm\Basic;

use Mindy\Orm\Fields\AutoField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\MetaData;
use Mindy\Tests\Orm\Models\User;

class MetaDataTest extends \PHPUnit_Framework_TestCase
{
    public function testMeta()
    {
        $meta = MetaData::getInstance(User::class);
        $this->assertEquals(['id', 'username', 'password'], $meta->getAttributes());
        $fields = $meta->getFieldsInit();
        $this->assertEquals(['id', 'username', 'password', 'groups', 'addresses'], array_keys($fields));

        $this->assertInstanceOf(AutoField::class, $fields['id']);
        $this->assertInstanceOf(CharField::class, $fields['username']);
        $this->assertInstanceOf(CharField::class, $fields['password']);
        $this->assertInstanceOf(ManyToManyField::class, $fields['groups']);
        $this->assertInstanceOf(HasManyField::class, $fields['addresses']);

        $this->assertTrue($meta->hasManyToManyField('groups'));
        $this->assertTrue($meta->hasField('username'));
        $this->assertTrue($meta->hasHasManyField('addresses'));
        $this->assertTrue($meta->hasRelatedField('addresses'));
        $this->assertFalse($meta->hasOneToOneField('unknown'));
        $this->assertFalse($meta->hasForeignField('unknown'));

        $this->assertInstanceOf(AutoField::class, $meta->getField('id'));
        $this->assertInstanceOf(AutoField::class, $meta->getField('pk'));

        $this->assertEquals(['groups'], array_keys($meta->getManyFields()));

        $this->assertInstanceOf(ManyToManyField::class, $meta->getManyToManyField('groups'));
        $this->assertInstanceOf(HasManyField::class, $meta->getHasManyField('addresses'));
    }
}