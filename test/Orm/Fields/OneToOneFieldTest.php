<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/07/16
 * Time: 07:34
 */

namespace Mindy\Tests\Orm\Fields;

use Exception;
use Mindy\Tests\Orm\Models\Member;
use Mindy\Tests\Orm\Models\MemberProfile;
use Mindy\Tests\Orm\OrmDatabaseTestCase;

abstract class OneToOneFieldTest extends OrmDatabaseTestCase
{
    protected function getModels()
    {
        return [new Member, new MemberProfile];
    }

    public function testOneToOneKey()
    {
        $member = new Member();
        $this->assertTrue($member->hasField('id'));
        $this->assertTrue($member->hasField('profile'));
        $this->assertTrue($member->hasAttribute('profile_id'));
        $this->assertEquals('id', $member->getPrimaryKeyName());
        $this->assertTrue($member->hasField('pk'));
        $this->assertTrue($member->save());
        $this->assertEquals(1, $member->pk);

        $profile = new MemberProfile();
        $profile->user = $member;
        $this->assertTrue($profile->hasField('user'));
        $this->assertTrue($profile->hasAttribute('user_id'));
        $this->assertEquals(1, $profile->user_id);
        $this->assertTrue($profile->save());
        $this->assertEquals(1, $profile->getAttribute('user_id'));
        $this->assertEquals(1, $profile->user_id);
        $this->assertEquals(1, $profile->pk);
        $this->assertEquals(1, $member->pk);
        $this->assertEquals(1, MemberProfile::objects()->filter(['user_id' => $member->id])->count());
        $this->assertEquals(1, $member->profile->pk);
        $profile->delete();
        $this->assertNull($member->profile);
    }

    public function testOneToOne()
    {
        $member = new Member();
        $this->assertTrue($member->save());
        $this->assertEquals(1, $member->pk);

        $profile = new MemberProfile();
        $profile->user = $member;
        $this->assertTrue($profile->isValid());
        $profile->save();

        $profile2 = new MemberProfile();
        $profile2->user = $member;
        $this->assertFalse($profile2->isValid());
        $this->assertEquals(['user_id' => ['The value must be unique']], $profile2->getErrors());

        $this->assertEquals(1, MemberProfile::objects()->count());
        $this->assertEquals(1, Member::objects()->count());
    }

    public function testOneToOneReverseException()
    {
        $member = new Member();
        $this->assertTrue($member->save());
        $this->assertEquals(1, $member->pk);

        $member2 = new Member();
        $this->assertTrue($member2->save());
        $this->assertEquals(2, $member2->pk);

        $profile = new MemberProfile();
        $profile->user = $member;
        $this->assertTrue($profile->save());
        $this->assertEquals(1, $profile->pk);

        $member2->profile = $profile;
        $this->assertFalse($member2->isValid());
        $this->assertEquals(['profile_id' => ['The value must be unique']], $member2->getErrors());
    }

    public function testOneToOneKeyInt()
    {
        $member = new Member();
        $this->assertTrue($member->save());
        $this->assertEquals(1, $member->id);
        $this->assertEquals(1, $member->pk);

        $profile = new MemberProfile();
        $profile->user_id = 1;
        $profile->save();
        $this->assertTrue($profile->save());
        $this->assertEquals(1, $profile->user_id);
        $this->assertEquals(1, $profile->pk);

        $this->assertEquals(1, Member::objects()->count());
        $this->assertEquals(1, MemberProfile::objects()->filter(['user' => $member->id])->count());

        $this->assertInstanceOf(Member::class, $profile->user);

        $this->assertInstanceOf(MemberProfile::class, $member->profile);
        $this->assertEquals(1, $member->profile->pk);
        $profile->delete();
        $this->assertNull($member->profile);
    }
}