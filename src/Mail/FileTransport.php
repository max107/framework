<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 06/09/16
 * Time: 22:02
 */

namespace Mindy\Mail;

use Swift_Events_EventListener;
use Swift_Mime_Message;

class FileTransport implements \Swift_Transport
{
    /**
     * @var string the directory where the email messages are saved when [[useFileTransport]] is true.
     */
    public $savePath;
    /**
     * @var callable a PHP callback that will be called by [[send()]] when [[useFileTransport]] is true.
     * The callback should return a file name which will be used to save the email message.
     * If not set, the file name will be generated based on the current timestamp.
     *
     * The signature of the callback is:
     *
     * ~~~
     * function ($mailer, $message)
     * ~~~
     */
    public $callback;

    /**
     * FileTransport constructor.
     * @param string $savePath
     * @param callable|null $callback
     */
    public function __construct(string $savePath, callable $callback = null)
    {
        $this->savePath = $savePath;
        $this->callback = $callback;
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message $message
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        return $this->saveMessage($message);
    }

    /**
     * Saves the message as a file under [[fileTransportPath]].
     * @param \Swift_Message|Swift_Mime_Message $message
     * @return bool whether the message is saved successfully
     * @throws \Exception
     */
    protected function saveMessage($message)
    {
        $path = $this->savePath;
        if ($path === null) {
            throw new \Exception('Missing savePath in ' . __CLASS__);
        } else if ($path && !is_dir(($path))) {
            mkdir($path, 0777, true);
        }
        if ($this->callback !== null) {
            $file = $path . '/' . call_user_func($this->callback, $this, $message);
        } else {
            $file = $path . '/' . $this->generateMessageFileName();
        }
        file_put_contents($file, $message->toString());

        return true;
    }

    /**
     * @return string the file name for saving the message when [[useFileTransport]] is true.
     */
    public function generateMessageFileName()
    {
        $time = microtime(true);

        return date('Ymd-His-', $time) . sprintf('%04d', (int)(($time - (int)$time) * 10000)) . '-' . sprintf('%04d', mt_rand(0, 10000)) . '.eml';
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
    }
}