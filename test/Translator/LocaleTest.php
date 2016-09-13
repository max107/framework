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
        $this->assertEquals('тест', $locale->t('test', [], 'modules.Core'));
        $this->assertEquals('1', $locale->t('foo.bar', [], 'modules.Core'));
        $this->assertEquals('2', $locale->t('foo.qwe', [], 'modules.Core'));
        $this->assertEquals('почта', $locale->t('mail', [], 'modules.Mail'));

        $this->assertEquals('yaml test', $locale->t('yaml_mail', [], 'modules.Mail'));

        $this->assertEquals('одно яблоко', $locale->transChoice('plural_apple', 1, ['%count%' => 1], 'modules.Mail'));
        $this->assertEquals('2 яблока', $locale->transChoice('plural_apple', 2, ['%count%' => 2], 'modules.Mail'));
        $this->assertEquals('10 яблок', $locale->transChoice('plural_apple', 10, ['%count%' => 10], 'modules.Mail'));

        $this->assertEquals('одно яблоко', $locale->transChoice('plural_apple', 1, ['count' => 1], 'modules.Mail'));
        $this->assertEquals('2 яблока', $locale->transChoice('plural_apple', 2, ['count' => 2], 'modules.Mail'));
        $this->assertEquals('10 яблок', $locale->transChoice('plural_apple', 10, ['count' => 10], 'modules.Mail'));
    }
}