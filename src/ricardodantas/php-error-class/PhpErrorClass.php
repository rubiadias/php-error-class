<?php

namespace ricardodantas\PhpErrorClass;

class PhpErrorClass{
    protected static $registered = false;
    protected static $old_error_reporting = null;
    protected static $old_display_error = null;
    protected static $send_to = 'email';

    static public function handler($code, $string, $file, $line){
        switch ($code) {
            case E_DEPRECATED: // ignores new DEPRECATED error to allow developers to use third party libraries
                return;
            case E_WARNING:
                throw new \ErrorException($string, $code, $code,$file,$line);
            default:
                throw new \ErrorException($string, $code, $code,$file,$line);
        }
    }

    protected function sendToEmail(){

        $mail = new PHPMailer;

        $mail->IsSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup server
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'jswan';                            // SMTP username
        $mail->Password = 'secret';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

        $mail->From = 'from@example.com';
        $mail->FromName = 'Mailer';
        $mail->AddAddress('josh@example.net', 'Josh Adams');  // Add a recipient
        $mail->AddAddress('ellen@example.com');               // Name is optional
        $mail->AddReplyTo('info@example.com', 'Information');
        $mail->AddCC('cc@example.com');
        $mail->AddBCC('bcc@example.com');

        $mail->WordWrap = 50;                                 // Set word wrap to 50 characters
        $mail->AddAttachment('/var/tmp/file.tar.gz');         // Add attachments
        $mail->AddAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->IsHTML(true);                                  // Set email format to HTML

        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if(!$mail->Send()) {
           echo 'Message could not be sent.';
           echo 'Mailer Error: ' . $mail->ErrorInfo;
           exit;
        }

    }

    protected function sendTo(){

        switch (self::send_to){
            case 'new_relic':
                self::sendToNewRelic();
            break;

            case 'zendesk':
                self::sendToZendesk();
            break;

            default:
                self::sendToEmail();
        }

    }

    static public function register(){
        if (!self::$registered){
            // saves old error reporting
            self::$old_error_reporting = error_reporting();
            self::$old_display_error = ini_get('display_errors');

            // set new error reporting configuration
            ini_set('display_errors','1');
            error_reporting(E_ALL);

            // set error handling
            set_error_handler(array(__CLASS__,'handler'), E_ALL);
            self::$registered = true;
        }

    }

    static public function unregister(){
        if (self::$registered){
            // restore old configuration values
            ini_set('display_errors',self::$old_display_error);
            error_reporting(self::$old_error_reporting);

            restore_error_handler();
            self::$registered = false;
        }
    }
}