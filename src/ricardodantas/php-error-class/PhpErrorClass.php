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
                                        'content' => null
                                    );

    public function __construct(){

    }

    protected function setError($error_msg){

        if($error_msg){
            return $error_msg;
        }else{
            exit('[PhpErrorClass] Error message could not be empty.');
        }

    }

    protected function sendToEmail(){

        try{
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

            if(!$mail->Send())
                throw new \ErrorException('PHPMailer Error: ' . $mail->ErrorInfo);

        }catch(Exception $e){

            exit($e->getMessage());

        }

    }

    protected function sendTo(){

        switch ($this->send_to){
            // case 'new_relic':
            //     $this->sendToNewRelic();
            // break;

            // case 'zendesk':
            //     $this->sendToZendesk();
            // break;

            default:
                self::sendToEmail();
        }

    }


}