<?php

/*
 * This file is part of the Crossover Demo package.
 *
 * (c) Berk BADEM <berkbadem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service used to manage sending e-mails via PHPMailer.
 *
 * @author Berk BADEM <berkbadem@gmail.com>
 */
class Mailer
{
    /**
     * @var PHPMailer
     *
     * PHPMailer object
     */
    private $mail;

    /**
     * Mailer constructor, it populated by symfony service and variables supplied from parameters.yml
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param bool $smtpsecure
     */
    public function __construct($host, $port, $username, $password, $smtpsecure)
    {
        $this->mail = new PHPMailer(true);
        $this->mail->Host = $host;
        $this->mail->Username = $username;
        $this->mail->Password = $password;
        $this->mail->SMTPSecure = $smtpsecure;
        $this->mail->Port = $port;
        $this->mail->SMTPAuth = true;
        $this->mail->isSMTP();
        $this->mail->isHtml(true);
        $this->mail->setFrom($username);
    }

    /**
     * Sets subject of email
     *
     * @param string $subject
     *
     * @return Mailer
     */
    public function setSubject($subject)
    {
        $this->mail->Subject = $subject;

        return $this;
    }

    /**
     * Sets content of email
     *
     * @param string $content
     *
     * @return Mailer
     */
    public function setContent($content)
    {
        $this->mail->msgHTML($content);

        return $this;
    }

    /**
     * Sets adress of email
     *
     * @param string $address
     *
     * @return Mailer
     */
    public function setAddress($address)
    {
        $this->mail->addAddress($address);

        return $this;
    }

    /**
     * Sends email via given parameters
     *
     * @return bool
     */
    public function send()
    {
        try {
            $this->mail->send();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}