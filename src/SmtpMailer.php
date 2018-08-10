<?php
//
// +---------------------------------------------------------------------+
// | CODE INC. SOURCE CODE                                               |
// +---------------------------------------------------------------------+
// | Copyright (c) 2017 - Code Inc. SAS - All Rights Reserved.           |
// | Visit https://www.codeinc.fr for more information about licensing.  |
// +---------------------------------------------------------------------+
// | NOTICE:  All information contained herein is, and remains the       |
// | property of Code Inc. SAS. The intellectual and technical concepts  |
// | contained herein are proprietary to Code Inc. SAS are protected by  |
// | trade secret or copyright law. Dissemination of this information or |
// | reproduction of this material is strictly forbidden unless prior    |
// | written permission is obtained from Code Inc. SAS.                  |
// +---------------------------------------------------------------------+
//
// Author:   Joan Fabrégat <joan@codeinc.fr>
// Date:     2018-03-30
// Time:     12:00
// Project:  SmtpMailer
//
namespace CodeInc\SmtpMailer;
use CodeInc\Mailer\Interfaces\EmailInterface;
use CodeInc\Mailer\Interfaces\MailerInterface;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Class SmtpMailer
 *
 * @package CodeInc\SmtpMailer
 * @author Joan Fabrégat <joan@codeinc.fr>
 */
class SmtpMailer implements MailerInterface
{
    /**
     * @var PHPMailer
     */
    private $phpMailer;

    /**
     * @var PHPMailer|null
     */
    private $lastSentEmail;

    /**
     * SmtpMailer constructor.
     *
     * @param PHPMailer $phpMailer
     */
    private function __construct(PHPMailer $phpMailer)
    {
        $this->phpMailer = $phpMailer;
    }

    /**
     * @param string $host
     * @param null|string $username
     * @param null|string $password
     * @param int|null $port
     * @param null|string $secure
     * @param bool|null $autoTls
     * @return SmtpMailer
     */
    public static function fromSmtpConf(string $host, ?string $username = null,
        ?string $password = null, ?int $port = null, ?string $secure = null, ?bool $autoTls = null):self
    {
        $phpMailer = new PHPMailer();
        $phpMailer->Mailer = 'smtp';
        $phpMailer->Host = $host;
        if ($username) {
            $phpMailer->SMTPAuth = true;
            $phpMailer->Username = $username;
            if ($password) {
                $phpMailer->Password = $password;
            }
        }
        if ($port) {
            $phpMailer->Port = $port;
        }
        if ($secure) {
            $phpMailer->SMTPSecure = $secure;
            if ($autoTls !== null) {
                $phpMailer->SMTPAutoTLS = $autoTls;
            }
        }
        return new self($phpMailer);
    }

    /**
     * @inheritdoc
     * @param EmailInterface $email
     * @throws \PHPMailer\PHPMailer\Exception
     */
	public function send(EmailInterface $email):void
    {
        $phpMailer = clone $this->phpMailer;

        // Configuration de l'email
        $phpMailer->From = $email->getSender()->getAddress();
        $phpMailer->FromName = $email->getSender()->getName();
        $phpMailer->Subject = $email->getSubject();
        $phpMailer->CharSet = $email->getCharset();

        if ($htmlBody = $email->getHtmlBody()) {
            $phpMailer->Body = $htmlBody;
            $phpMailer->isHTML(true);
        }
        elseif ($textBody = $email->getTextBody()) {
            $phpMailer->Body = $textBody;
            $phpMailer->isHTML(false);
        }

        foreach ($email->getRecipients() as $recipient) {
            $phpMailer->addAddress($recipient->getAddress(), $recipient->getName() ?? '');
        }

        $this->lastSentEmail = $phpMailer;
		if (!$phpMailer->send()) {
			throw new \RuntimeException(sprintf("Error while sending the email via SMTP and PHPMailer : %s",
                $phpMailer->ErrorInfo));
		}
	}

    /**
     * @return null|PHPMailer
     */
    public function getLastSentEmail():?PHPMailer
    {
        return $this->lastSentEmail;
    }
}