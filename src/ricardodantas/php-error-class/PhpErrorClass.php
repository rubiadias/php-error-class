<?php

use Intouch\Newrelic\Newrelic;

namespace ricardodantas\PhpErrorClass;

class PhpErrorClass{

    protected $settings = array();
    protected $send_to = 'email';
    protected $error_msg = null;
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
                                        'content' => null
                                    );


    public static function register($error_msg, $action, $config = array()){

        if(!$error_msg)
            exit('[PhpErrorClass] Error message could not be empty.');
        else
            $this->error_msg = $error_msg;

        if(!$action)
            exit('[PhpErrorClass] You must set the action (email, new_relic or zendesk) to be performed.');
        else
            $this->sendTo = $action;

        if(!$config)
            exit('[PhpErrorClass] You must set the settings for this action.');
        else
            $this->settings = $config;

        $this->sendTo();

    }


    protected function sendToEmail(){

        try{

            $this->email_configs = $this->settings;

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
            $mail->Body    = $this->error_msg;
            $mail->AltBody = $this->error_msg;

            if(!$mail->Send())
                throw new \ErrorException('PHPMailer Error: ' . $mail->ErrorInfo);

        }catch(Exception $e){

            exit($e->getMessage());

        }

    }

    protected function sendToNewRelic(){


        $newrelic = new Newrelic( true );
        $newrelic->setAppName( $this->settings['app_name'], $this->settings['license'] );
        $newrelic->noticeError( $this->error_msg );

    }

    protected function sendToZendesk(){

    }

    protected function sendTo(){

        switch ($this->send_to){
            case 'new_relic':
                $this->sendToNewRelic();
            break;

            case 'zendesk':
                $this->sendToZendesk();
            break;

            default:
                self::sendToEmail();
        }

    }


}