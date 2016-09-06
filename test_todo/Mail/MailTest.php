<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 17/05/14.05.2014 13:58
 */

class MailTest extends \Tests\TestCase
{
    public function testMail()
    {
        $mail = $this->mail;

        $mail
            ->compose('hello world')
            ->setFrom('admin@admin.com')
            ->setTo('admin@admin.com')
            ->setSubject('qwe')
            ->send();
        $this->assertEquals(1, count($mail->out));
        $message = $mail->out[0];
        $this->assertEquals('qwe', $message->getSubject());
        $this->assertEquals(['admin@admin.com' => null], $message->getTo());
        $this->assertEquals(['admin@admin.com' => null], $message->getFrom());
    }
}
