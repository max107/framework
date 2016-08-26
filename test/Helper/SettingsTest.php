<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 17:04
 */

namespace Mindy\Helper\Tests;

use Mindy\Helper\Settings;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function testSettings()
    {
        $data = [
            'components' => [
                'db' => [
                    'dsn' => 'unknown'
                ]
            ]
        ];

        $result = [
            'components' => [
                'db' => [
                    'dsn' => 'foobar',
                    'persist' => true
                ]
            ]
        ];

        $this->assertEquals($result, Settings::override($data, [
            'components' => [
                'db' => [
                    'dsn' => 'foobar',
                    'persist' => true
                ]
            ]
        ]));
    }
}