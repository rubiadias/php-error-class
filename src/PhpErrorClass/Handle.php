<?php

use Intouch\Newrelic\Newrelic;


namespace PhpErrorClass;

class Handle{

    protected static $settings = array('api_key' => null ,'options' => array()), $via = 'email', $error_msg = null;

    protected static $zendesk_configs = array(
                                    "subject" => null,
                                    "description" => null,
                                    "recipient_email" => null,
                                    "requester_name" => null,
                                    "requester_email" => null
                                );

    protected static $new_relic_configs = array(
                                        'app_name'=>null,
                                        'license'=>null
                                    );

    protected static $email_configs = array(
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


    public static function register($error_msg = null, $action = null, $config = array()){


        if(is_null($error_msg))
            exit('[PhpErrorClass] Error message could not be empty.');
        else
            self::$error_msg = $error_msg;

        if(is_null($action))
            exit('[PhpErrorClass] You must set the action (email, new_relic or zendesk) to be performed.');
        else
            self::$via = $action;

        if( is_array($config) AND count($config) <= 0)
            exit('[PhpErrorClass] You must set the settings for this action ('.$action.').');
        else
            self::$settings = $config;

        self::notifyError();

    }


    protected static function sendToEmail(){

        try{

            self::$email_configs = self::$settings;

            $mail = new \PHPMailer;

            $mail->IsSMTP();
            $mail->Host = self::$email_configs['smtp'];
            $mail->SMTPAuth = self::$email_configs['smtp_auth'];
            $mail->Username = self::$email_configs['username'];
            $mail->Password = self::$email_configs['password'];

            if(self::$email_configs['smtp_secure'] !== false)
                $mail->SMTPSecure = self::$email_configs['smtp_secure'];

            $mail->From = self::$email_configs['from_email'];
            $mail->FromName = self::$email_configs['from_name'];

            $mail->AddAddress(self::$email_configs['to_email']);

            $mail->IsHTML(false);

            $mail->Subject = self::$email_configs['subject'];
            $mail->Body    = self::$error_msg;
            $mail->AltBody = self::$error_msg;

            if(!$mail->Send())
                throw new \Exception('[PhpErrorClass][PHPMailer] '. $mail->ErrorInfo);

        }catch(\Exception $e){

            exit($e->getMessage());

        }

    }

    protected static function sendToNewRelic(){

        self::$new_relic_configs = self::$settings;

        $newrelic = new Newrelic( true );
        $newrelic->setAppName( self::$new_relic_configs['app_name'], self::$new_relic_configs['license'] );
        if($newrelic->noticeError( self::$error_msg ) == false)
            exit('[PhpErrorClass] - [New Relic] It is not possible to execute this action.');

    }

    protected static function sendToZendesk(){

        self::$zendesk_configs = self::$settings;

        $create  = json_encode(
                array(
                        'ticket' => array(
                            'subject' => self::$zendesk_configs['subject'],
                            'description' => self::$zendesk_configs['description'],
                            'requester' => array('name' => self::$zendesk_configs['requester_name'],
                            'email' => self::$zendesk_configs['requester_email']
                        )
                    )
                ),
            JSON_FORCE_OBJECT
        );

        $zendesk = new Zendesk\Zendesk();
        $zendesk->call("/tickets", $create, "POST");

    }

    protected static function sendToAirbrake(){

        $apiKey  = self::$settings['api_key'];
        $options = self::$settings['options'];


        $config = new \Airbrake\Configuration($apiKey, $options);
        $client = new \Airbrake\Client($config);

        $client->notifyOnError(self::$error_msg);

    }


    protected static function notifyError(){

        switch (self::$via){

            case 'new_relic':
                self::sendToNewRelic();
            break;

            case 'zendesk':
                self::sendToZendesk();
            break;

            case 'airbrake':
                self::sendToAirbrake();
            break;

            case 'email':
                self::sendToEmail();
            break;

            default:
                exit('[PhpErrorClass] Invalid option to notify. Pleas try on of these (email,new_relic,zendesk).');
        }

    }


}


?>
