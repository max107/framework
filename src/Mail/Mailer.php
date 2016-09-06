<?php

namespace Mindy\Mail;

use Mindy\Exception\InvalidConfigException;
use Mindy\Helper\Alias;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Helper\Traits\RenderTrait;

/**
 * Mailer implements a mailer based on SwiftMailer.
 *
 * To use Mailer, you should configure it in the application configuration like the following,
 *
 * ~~~
 * 'components' => [
 *     ...
 *     'mail' => [
 *         'class' => 'yii\swiftmailer\Mailer',
 *         'transport' => [
 *             'class' => 'Swift_SmtpTransport',
 *             'host' => 'localhost',
 *             'username' => 'username',
 *             'password' => 'password',
 *             'port' => '587',
 *             'encryption' => 'tls',
 *         ],
 *     ],
 *     ...
 * ],
 * ~~~
 *
 * You may also skip the configuration of the [[transport]] property. In that case, the default
 * PHP `mail()` function will be used to send emails.
 *
 * You specify the transport constructor arguments using 'constructArgs' key in the config.
 * You can also specify the list of plugins, which should be registered to the transport using
 * 'plugins' key. For example:
 *
 * ~~~
 * 'transport' => [
 *     'class' => 'Swift_SmtpTransport',
 *     'constructArgs' => ['localhost', 25]
 *     'plugins' => [
 *         [
 *             'class' => 'Swift_Plugins_ThrottlerPlugin',
 *             'constructArgs' => [20],
 *         ],
 *     ],
 * ],
 * ~~~
 *
 * To send an email, you may use the following code:
 *
 * ~~~
 * Yii::$app->mail->compose('contact/html', ['contactForm' => $form])
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->send();
 * ~~~
 *
 * @see http://swiftmailer.org
 *
 * @property array|\Swift_Mailer $swiftMailer Swift mailer instance or array configuration. This property is
 * read-only.
 * @property array|\Swift_Transport $transport This property is read-only.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 * @package Mindy\Mail
 */
class Mailer implements MailerInterface
{
    use Accessors, Configurator, RenderTrait;

    /**
     * @var array the configuration that should be applied to any newly created
     * email message instance by [[createMessage()]] or [[compose()]]. Any valid property defined
     * by [[MessageInterface]] can be configured, such as `from`, `to`, `subject`, `textBody`, `htmlBody`, etc.
     *
     * For example:
     *
     * ~~~
     * [
     *     'charset' => 'UTF-8',
     *     'from' => 'noreply@mydomain.com',
     *     'bcc' => 'developer@mydomain.com',
     * ]
     * ~~~
     */
    public $messageConfig = [];
    /**
     * @var bool whether to save email messages as files under Swift_SpoolTransport
     */
    public $useSpoolTransport = true;
    /**
     * @var string message default class name.
     */
    public $messageClass = '\Mindy\Mail\Message';
    /**
     * @var \Swift_Mailer Swift mailer instance.
     */
    private $_swiftMailer;
    /**
     * @var \Swift_Transport|array Swift transport instance or its array configuration.
     */
    private $_transport = [];
    /**
     * @var \Swift_SpoolTransport|array Swift transport instance or its array configuration.
     */
    private $_spool_transport = [];

    /**
     * @var \Mindy\Mail\Message[] Outcoming messages
     */
    public $out = [];

    /**
     * @return array|\Swift_Mailer Swift mailer instance or array configuration.
     */
    public function getSwiftMailer()
    {
        if ($this->_swiftMailer === null) {
            $this->_swiftMailer = $this->createSwiftMailer();
        }
        return $this->_swiftMailer;
    }

    /**
     * @param array|\Swift_Transport $transport
     * @throws InvalidConfigException on invalid argument.
     */
    public function setTransport($transport)
    {
        if ($transport instanceof \Closure) {
            $transport = $transport();
        } else if (!is_array($transport) && !is_object($transport)) {
            throw new InvalidConfigException('"' . get_class($this) . '::transport" should be either object or array, "' . gettype($transport) . '" given.');
        }
        $this->_transport = $transport;
    }

    /**
     * @return array|\Swift_Transport
     */
    public function getTransport()
    {
        if (!is_object($this->_transport)) {
            $this->_transport = $this->createTransport($this->_transport);
        }

        return $this->_transport;
    }

    /**
     * @param array|\Swift_Transport $transport
     * @throws InvalidConfigException on invalid argument.
     */
    public function setSpoolTransport($transport)
    {
        if ($transport instanceof \Closure) {
            $transport = $transport();
        } else if (!is_array($transport) && !is_object($transport)) {
            throw new InvalidConfigException('"' . get_class($this) . '::transport" should be either object or array, "' . gettype($transport) . '" given.');
        }
        $this->_spool_transport = $transport;
    }

    /**
     * @return array|\Swift_Transport
     */
    public function getSpoolTransport()
    {
        if (!is_object($this->_spool_transport)) {
            $this->_spool_transport = $this->createTransport($this->_spool_transport);
        }

        return $this->_spool_transport;
    }

    /**
     * @param $message MessageInterface|Message
     * @return bool
     */
    protected function sendMessage($message)
    {
        /*
        $address = $message->getTo();
        if (is_array($address)) {
            $address = implode(', ', array_keys($address));
        }
        $this->getLogger()->info('Sending email "' . $message->getSubject() . '" to "' . $address . '"', [], __METHOD__);
        */
        $this->out[] = $message;
        return $this->getSwiftMailer()->send($message->getSwiftMessage()) > 0;
    }

    /**
     * Creates Swift mailer instance.
     * @return \Swift_Mailer mailer instance.
     */
    protected function createSwiftMailer()
    {
        $transport = $this->useSpoolTransport ? $this->getSpoolTransport() : $this->getTransport();
        return new \Swift_Mailer($transport);
    }

    /**
     * Creates email transport instance by its array configuration.
     * @param array $config transport configuration.
     * @throws \Mindy\Exception\InvalidConfigException on invalid transport configuration.
     * @return \Swift_Transport transport instance.
     */
    protected function createTransport(array $config)
    {
        if (!isset($config['class'])) {
            $config['class'] = 'Swift_MailTransport';
        }
        if (isset($config['plugins'])) {
            $plugins = $config['plugins'];
            unset($config['plugins']);
        }
        /** @var \Swift_MailTransport $transport */
        $transport = $this->createSwiftObject($config);
        if (isset($plugins)) {
            foreach ($plugins as $plugin) {
                if (is_array($plugin) && isset($plugin['class'])) {
                    $plugin = $this->createSwiftObject($plugin);
                }
                $transport->registerPlugin($plugin);
            }
        }
        return $transport;
    }

    /**
     * Creates Swift library object, from given array configuration.
     * @param array $config object configuration
     * @return Object created object
     * @throws \Mindy\Exception\InvalidConfigException on invalid configuration.
     */
    protected function createSwiftObject(array $config)
    {
        if (isset($config['class'])) {
            $className = $config['class'];
            unset($config['class']);
        } else {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }
        if (isset($config['constructArgs'])) {
            $args = [];
            foreach ($config['constructArgs'] as $arg) {
                if (is_array($arg) && isset($arg['class'])) {
                    $args[] = $this->createSwiftObject($arg);
                } else {
                    $args[] = $arg;
                }
            }
            unset($config['constructArgs']);
            $object = Creator::createObject($className, $args);
        } else {
            $object = Creator::createObject($className);
        }
        if (!empty($config)) {
            foreach ($config as $name => $value) {
                if (property_exists($object, $name)) {
                    $object->$name = $value;
                } else {
                    $setter = 'set' . $name;
                    if (method_exists($object, $setter) || method_exists($object, '__call')) {
                        $object->$setter($value);
                    } else {
                        throw new InvalidConfigException('Setting unknown property: ' . $className . '::' . $name);
                    }
                }
            }
        }
        return $object;
    }

    /**
     * Creates a new message instance and optionally composes its body content via view rendering.
     *
     * @param string|array $view the view to be used for rendering the message body. This can be:
     *
     * - a string, which represents the view name or path alias for rendering the HTML body of the email.
     *   In this case, the text body will be generated by applying `strip_tags()` to the HTML body.
     * - an array with 'html' and/or 'text' elements. The 'html' element refers to the view name or path alias
     *   for rendering the HTML body, while 'text' element is for rendering the text body. For example,
     *   `['html' => 'contact-html', 'text' => 'contact-text']`.
     * - null, meaning the message instance will be returned without body content.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/mail/contact");
     * - a relative view name (e.g. "contact") located under [[viewPath]].
     *
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @return MessageInterface message instance.
     */
    public function compose($view = null, array $params = [])
    {
        $message = $this->createMessage();
        if ($view !== null) {
            $params['message'] = $message;
            if (is_array($view)) {
                if (isset($view['html'])) {
                    $html = $this->renderTemplate($view['html'], $params);
                }
                if (isset($view['text'])) {
                    $text = $this->renderTemplate($view['text'], $params);
                }
            } else {
                $html = $this->renderTemplate($view, $params);
            }
            if (isset($html)) {
                $message->setHtmlBody($html);
            }
            if (isset($text)) {
                $message->setTextBody($text);
            } elseif (isset($html)) {
                if (preg_match('|<body[^>]*>(.*?)</body>|is', $html, $match)) {
                    $html = $match[1];
                }
                $html = preg_replace('|<style[^>]*>(.*?)</style>|is', '', $html);
                $message->setTextBody(strip_tags($html));
            }
        }
        return $message;
    }

    /**
     * Creates a new message instance.
     * The newly created instance will be initialized with the configuration specified by [[messageConfig]].
     * If the configuration does not specify a 'class', the [[messageClass]] will be used as the class
     * of the new message instance.
     * @return MessageInterface message instance.
     */
    public function createMessage()
    {
        $config = $this->messageConfig;
        if (!array_key_exists('class', $config)) {
            $config['class'] = $this->messageClass;
        }
        $config['mailer'] = $this;
        return Creator::createObject($config);
    }

    /**
     * Sends the given email message.
     * This method will log a message about the email being sent.
     * If [[useFileTransport]] is true, it will save the email as a file under [[fileTransportPath]].
     * Otherwise, it will call [[sendMessage()]] to send the email to its recipient(s).
     * Child classes should implement [[sendMessage()]] with the actual email sending logic.
     *
     * Sends multiple messages at once.
     * The default implementation simply calls [[send()]] multiple times.
     * Child classes may override this method to implement more efficient way of
     * sending multiple messages.
     *
     * @param MessageInterface|MessageInterface[]|array $messages email message instance to be sent or list of email messages, which should be sent.
     * @return integer number of messages that are successfully sent.
     */
    public function send($messages)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $successCount = 0;
        foreach ($messages as $message) {
            if ($this->sendMessage($message)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * @param null $recoverTimeout
     * @return int
     */
    public function sendSpool($recoverTimeout = null)
    {
        $transport = $this->getSpoolTransport();
        if ($transport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $transport->getSpool();
            if ($spool instanceof \Swift_FileSpool) {
                if ($recoverTimeout) {
                    $spool->recover($recoverTimeout);
                } else {
                    $spool->recover();
                }
            }
            return $spool->flushQueue($this->getTransport());
        }

        return 0;
    }
}
