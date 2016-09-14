<?php

namespace Mindy\Tests\Base;

use PHPUnit_Framework_TestCase;

class ModuleTest extends PHPUnit_Framework_TestCase
{
    protected $parent;
    /**
     * @var NewModule
     */
    protected $mod;
    protected $d;

    public function setUp()
    {
        $this->parent = new NewModule(['id' => 'root']);
        $this->mod = new NewModule(['id' => 'foo']);
        $this->d = dirname(__FILE__);
    }

    public function tearDown()
    {
        unset($this->parent);
        unset($this->mod);
    }

    public function testGetId()
    {
        $this->assertEquals('foo', $this->mod->getId());
    }

    public function testGetBasePath()
    {
        $this->assertEquals($this->d, $this->mod->getBasePath());
    }
}
