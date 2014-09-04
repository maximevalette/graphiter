<?php

namespace Graphiter\Alerter;

use Swift_Message, Swift_Mailer, Swift_SmtpTransport, Swift_MailTransport, Swift_Attachment;

/**
 * Class EmailAlerter
 *
 * @package Graphiter\Alerter
 */
class EmailAlerter implements AlerterInterface
{
    protected $options;
    protected $config;
    protected $type;

    /**
     * @param array  $options
     * @param string $type
     */
    public function __construct(array $options, $type)
    {
        $this->options = $options;
        $this->config  = $options[$type];
        $this->type    = strtoupper($type);
    }

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed|void
     */
    public function trigger($key, array $args)
    {
        if ($this->options === false) {
            return;
        }

        $args['type']   = $this->type;
        $args['action'] = 'TRIGGERED';

        foreach ($args as $k => $data) {
            $args['{' . $k . '}'] = $data;
            unset($args[$k]);
        }

        $subject = strtr($this->options['subject'], $args);
        $msg     = strtr($this->config['trigger'], $args);

        $this->mail($subject, $msg, $args['url']);
    }

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed|void
     */
    public function resolve($key, array $args)
    {
        if ($this->options === false) {
            return;
        }

        $args['type']   = $this->type;
        $args['action'] = 'RESOLVED';

        foreach ($args as $k => $data) {
            $args['{' . $k . '}'] = $data;
            unset($args[$k]);
        }

        $subject = strtr($this->options['subject'], $args);
        $msg     = strtr($this->config['resolve'], $args);

        $this->mail($subject, $msg, $args['url']);
    }

    /**
     * @param string $subject
     * @param string $msg
     */
    protected function mail($subject, $msg, $url)
    {
        /** @var Swift_Message $message */
        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($this->options['from'])->setTo($this->options['to'])->setBody($msg);
        $message->attach(Swift_Attachment::newInstance(file_get_contents($url), 'graph.png'));

        if (is_array($this->options['smtp'])) {
            $transport = Swift_SmtpTransport::newInstance($this->options['smtp']['host'], $this->options['smtp']['port']);
        } else {
            $transport = Swift_MailTransport::newInstance();
        }

        $mailer = Swift_Mailer::newInstance($transport);
        $mailer->send($message);
    }
}
