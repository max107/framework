<?php

namespace Mindy\Tests\Mail;

use Mindy\Mail\FileTransport;
use Mindy\Mail\Mailer;
use Mindy\Mail\Message;
use PHPUnit_Framework_TestCase;
use Swift_NullTransport;
use Swift_SpoolTransport;
use Symfony\Component\Finder\Finder;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 17/05/14.05.2014 13:58
 */
class MailTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $this->clean();
    }

    protected function clean()
    {
        foreach ((new Finder)->in(__DIR__ . '/saved') as $fileInfo) {
            unlink($fileInfo->getRealPath());
        }

        foreach ((new Finder)->in(__DIR__ . '/spool') as $fileInfo) {
            unlink($fileInfo->getRealPath());
        }
    }

    public function testInit()
    {
        $mailer = new Mailer([
            'transport' => ['class' => Swift_NullTransport::class],
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => 'noreply@mydomain.com',
                'bcc' => 'developer@mydomain.com',
            ]
        ]);
        $this->assertInstanceOf(Swift_NullTransport::class, $mailer->getTransport());
        $this->assertEquals(['noreply@mydomain.com' => null], $mailer->createMessage()->getFrom());
    }

    public function testCreateMessageFromMailer()
    {
        $mailer = new Mailer([
            'transport' => ['class' => Swift_NullTransport::class],
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => 'noreply@mydomain.com',
                'bcc' => 'developer@mydomain.com',
            ]
        ]);

        $message = $mailer
            ->createMessage()
            ->setFrom('foo@bar.com')
            ->setTo('foo@bar.com')
            ->setSubject('foo-bar-com')
            ->setTextBody('text body')
            ->setHtmlBody('html body');

        $this->assertEquals(['foo@bar.com' => null], $message->getFrom());
        $this->assertEquals(['foo@bar.com' => null], $message->getTo());
        $this->assertEquals('foo-bar-com', $message->getSubject());
        $mailer->send($message);
        $this->assertEquals(1, count($mailer->out));
    }

    public function testCreateMessage()
    {
        $mailer = new Mailer([
            'transport' => ['class' => Swift_NullTransport::class],
        ]);

        $message = (new Message())
            ->setFrom('foo@bar.com')
            ->setTo('foo@bar.com')
            ->setSubject('foo-bar-com')
            ->setTextBody('text body')
            ->setHtmlBody('html body');

        $this->assertEquals(['foo@bar.com' => null], $message->getFrom());
        $this->assertEquals(['foo@bar.com' => null], $message->getTo());
        $this->assertEquals('foo-bar-com', $message->getSubject());
        $mailer->send($message);
        $this->assertEquals(1, count($mailer->out));
    }

    public function testFileTransport()
    {
        $mailer = new Mailer([
            'useSpoolTransport' => false,
            'transport' => function () {
                return new FileTransport(__DIR__ . '/saved');
            },
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => 'noreply@mydomain.com',
                'bcc' => 'developer@mydomain.com',
            ]
        ]);
        $message = $mailer->createMessage()->setFrom('foo@bar.com')->setTo('foo@bar.com');
        $mailer->send($message);
        $this->assertEquals(1, count($mailer->out));
        $this->assertEquals(1, count((new Finder())->in(__DIR__ . '/saved')));
    }

    public function testSpool()
    {
        // https://knpuniversity.com/screencast/question-answer-day/swiftmailer-spooling
        $mailer = new Mailer([
            'useSpoolTransport' => true,
            'spoolTransport' => function () {
                return new Swift_SpoolTransport(new \Swift_FileSpool(__DIR__ . '/spool'));
            },
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => 'noreply@mydomain.com',
                'bcc' => 'developer@mydomain.com',
            ]
        ]);
        $this->assertInstanceOf(Swift_SpoolTransport::class, $mailer->getSpoolTransport());

        $this->assertEquals(['noreply@mydomain.com' => null], $mailer->createMessage()->getFrom());

        $message = $mailer->createMessage()->setFrom('foo@bar.com')->setTo('foo@bar.com');
        $mailer->send($message);
        $this->assertEquals(1, count($mailer->out));

        // 2 because bcc
        $this->assertEquals(2, $mailer->sendSpool());
    }
}
