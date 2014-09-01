<?php

namespace Graphiter\Alerter;

use Swift_Message, Swift_Mailer, Swift_SmtpTransport, Swift_MailTransport;

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

        $subject = str_replace(array_keys($args), array_values($args), $this->options['subject']);
        $msg     = str_replace(array_keys($args), array_values($args), $this->config['trigger']);

        $this->mail($subject, $msg);
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

        $subject = str_replace(array_keys($args), array_values($args), $this->options['subject']);
        $msg     = str_replace(array_keys($args), array_values($args), $this->config['resolve']);

        $this->mail($subject, $msg);
    }

    /**
     * @param string $subject
     * @param string $msg
     */
    protected function mail($subject, $msg)
    {
        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($this->options['from'])->setTo($this->options['to'])->setBody($msg);

        if (is_array($this->options['smtp'])) {
            $transport = Swift_SmtpTransport::newInstance($this->options['smtp']['host'], $this->options['smtp']['port']);
        } else {
            $transport = Swift_MailTransport::newInstance();
        }

        $mailer = Swift_Mailer::newInstance($transport);
        $mailer->send($message);
    }
}
