<?php

namespace ricardodantas\PhpErrorClass;

class PhpErrorClass{

    protected static $registered = false;
    protected static $old_error_reporting = null;
    protected static $old_display_error = null;

    protected $send_to = 'email';
    protected $email_configs = array(
                                        'smtp' => null,
                                        'smtp_auth' => true,
                                        'username' => null,
                                        'password' => null,
                                        'ssl' => true,
                                        'tls'=>false,
                                        'from_email' => null,
                                        'from_name' => null,
                                        'to_email' => null,
                                        'subject' => '[Error Report]',
                                        'content' => self::error_message
                                    );

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
        $mail->Host = self::email_configs[''];                    // Specify main and backup server
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'jswan';                            // SMTP username
        $mail->Password = 'secret';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

        $mail->From = 'from@example.com';
        $mail->FromName = 'Mailer';

        $mail->AddAddress('ellen@example.com');               // Name is optional

        $mail->IsHTML(false);                                  // Set email format to HTML

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