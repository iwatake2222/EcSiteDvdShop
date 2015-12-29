<?php

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{
	private $EMAIL_TO;
	private $EMAIL_FROM;
	private $USER;
	private $PASSWD;

	function __construct() {
		$oCreds = json_decode(file_get_contents(__DIR__ . "/../creds/mailer.json"));
		$this->EMAIL_TO = $oCreds->EmailTo;
		$this->EMAIL_FROM = $oCreds->EmailFrom;
		$this->USER = $oCreds->User;
		$this->PASSWD = $oCreds->Password;
	}

	public function Send($sEmail, $sSubject, $sMessage) {
		$mail = new PHPMailer();

		//$mail->SMTPDebug = 3;                               // Enable verbose debug output

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = $this->USER;                 // SMTP username
		$mail->Password = $this->PASSWD;                           // SMTP password
		$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 465;                                    // TCP port to connect to

		$mail->setFrom($this->EMAIL_FROM, 'WebMaster');
		$mail->addAddress($this->EMAIL_TO, 'WebMaster');     // Add a recipient
		//$mail->addAddress('ellen@example.com');               // Name is optional
		$mail->addReplyTo($sEmail, 'Customer');
		//$mail->addCC('cc@example.com');
		//$mail->addBCC('bcc@example.com');

		//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
		//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
		$mail->isHTML(true);                                  // Set email format to HTML

		if($sSubject == null || $sSubject == '') $sSubject = 'no title';
		if($sMessage == null || $sMessage == '') $sMessage = 'no message';

		$mail->Subject = $sSubject;
		$mail->Body    = $sMessage;
		$mail->AltBody = $sMessage;

		if(!$mail->send()) {
			return false;
		} else {
			return true;
		}
	}

}
