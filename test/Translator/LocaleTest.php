<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 14:38
 */

namespace Mindy\Tests\Translator;

use Mindy\Translator\Locale;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class LocaleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testModulesPath()
    {
        new Locale();
    }

    public function testInit()
    {
        $locale = new Locale([
            'modulesPath' => __DIR__ . '/Modules',
            'locale' => 'ru_RU'
        ]);
        $translator = $locale->getTranslator();
        $this->assertInstanceOf(Translator::class, $translator);
        $this->assertEquals('ru_RU', $locale->locale);
        $this->assertEquals(__DIR__ . '/Modules', $locale->getModulesPath());
    }

    public function testTranslate()
    {
        $locale = new Locale([
            'modulesPath' => __DIR__ . '/Modules',
            'locale' => 'ru_RU',
            'loaders' => [
                'php' => ['class' => PhpFileLoader::class],
                'yml' => ['class' => YamlFileLoader::class]
            ]
        ]);
        $this->assertEquals('тест', $locale->t('modules.Core', 'test'));
        $this->assertEquals('1', $locale->t('modules.Core', 'foo.bar'));
        $this->assertEquals('2', $locale->t('modules.Core', 'foo.qwe'));
        $this->assertEquals('почта', $locale->t('modules.Mail', 'mail'));

        $this->assertEquals('yaml test', $locale->t('modules.Mail', 'yaml_mail'));

        $this->assertEquals('одно яблоко', $locale->transChoice('modules.Mail', 'plural_apple', 1, ['%count%' => 1]));
        $this->assertEquals('2 яблока', $locale->transChoice('modules.Mail', 'plural_apple', 2, ['%count%' => 2]));
        $this->assertEquals('10 яблок', $locale->transChoice('modules.Mail', 'plural_apple', 10, ['%count%' => 10]));

        $this->assertEquals('одно яблоко', $locale->transChoice('modules.Mail', 'plural_apple', 1, ['count' => 1]));
        $this->assertEquals('2 яблока', $locale->transChoice('modules.Mail', 'plural_apple', 2, ['count' => 2]));
        $this->assertEquals('10 яблок', $locale->transChoice('modules.Mail', 'plural_apple', 10, ['count' => 10]));
    }
}