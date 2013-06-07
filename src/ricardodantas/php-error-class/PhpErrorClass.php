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
                                        'smtp_secure' => false, // ssl or tsl
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

        $mail->IsSMTP();
        $mail->Host = $this->email_configs['smtp'];
        $mail->SMTPAuth = $this->email_configs['smtp_auth'];
        $mail->Username = $this->email_configs['username'];
        $mail->Password = $this->email_configs['password'];

        if($this->email_configs['smtp_secure'] !== false)
            $mail->SMTPSecure = $this->email_configs['smtp_secure'];

        $mail->From = $this->email_configs['from_email'];
        $mail->FromName = $this->email_configs['from_name'];

        $mail->AddAddress($this->email_configs['to_email']);

        $mail->IsHTML(false);

        $mail->Subject = $this->email_configs['subject'];
        $mail->Body    = $this->email_configs['content'];
        $mail->AltBody = $this->email_configs['content'];

        if(!$mail->Send()) {
           echo 'Message could not be sent.';
           echo 'Mailer Error: ' . $mail->ErrorInfo;
           exit;
        }

    }

    protected function sendTo(){

        switch ($this->send_to){
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