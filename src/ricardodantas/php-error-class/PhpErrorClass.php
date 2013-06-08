<?php

use Intouch\Newrelic\Newrelic;


namespace ricardodantas;

class PhpErrorClass{

    protected $settings = array();
    protected $send_to = 'email';
    protected $error_msg = null;

    protected $zendesk_configs = array(
                                    "subject" => null,
                                    "description" => null,
                                    "recipient_email" => null,
                                    "requester_name" => null,
                                    "requester_email" => null
                                );

    protected $new_relic_configs = array(
                                        'app_name'=>null,
                                        'license'=>null
                                    );

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

        $this->new_relic_configs = $this->settings;

        $newrelic = new Newrelic( true );
        $newrelic->setAppName( $this->new_relic_configs['app_name'], $this->new_relic_configs['license'] );
        if($newrelic->noticeError( $this->error_msg ) == false)
            exit('[PhpErrorClass] - [New Relic] It is not possible to execute this action.');

    }

    protected function sendToZendesk(){

        $this->zendesk_configs = $this->settings;


        $create  = json_encode(
                array(
                        'ticket' => array(
                            'subject' => $this->zendesk_configs['subject'],
                            'description' => $this->zendesk_configs['description'],
                            'requester' => array('name' => $this->zendesk_configs['requester_name'],
                            'email' => $this->zendesk_configs['requester_email']
                        )
                    )
                ),
            JSON_FORCE_OBJECT
        );

        $zendesk = new Zendesk();
        $zendesk->call("/tickets", $create, "POST");

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
                $this->sendToEmail();
        }

    }


}




/**
  * A minimal Zendesk API PHP implementation
  * @package Zendesk
  * @author  Julien Renouard <renouard.julien@gmail.com> (deeply inspired by Darren Scerri <darrenscerri@gmail.com> Mandrill's implemetation)
  * @version 1.0
  */
class Zendesk
{
    /**
     * API Constructor. If set to test automatically, will return an Exception if the ping API call fails
     * @param string $apiKey API Key.
     * @param string $user Username on Zendesk.
     * @param string $subDomain Your subdomain on zendesk, without https:// nor trailling dot.
     * @param string $suffix .json by default.
     * @param bool $test=true Whether to test API connectivity on creation.
     */
    public function __construct($apiKey, $user, $subDomain, $suffix = '.json', $test = false)
    {
        $this->api_key = $apiKey;
        $this->user    = $user;
        $this->base    = 'https://' . $subDomain . '.zendesk.com/api/v2';
        $this->suffix  = $suffix;
        if ($test === true && !$this->test())
        {
            throw new \Exception('Cannot connect or authentice with the Zendesk API');
        }
    }

    /**
     * Perform an API call.
     * @param string $url='/tickets' Endpoint URL. Will automatically add the suffix you set if necessary (both '/tickets.json' and '/tickets' are valid)
     * @param array $json=array() An associative array of parameters
     * @param string $action Action to perform POST/GET/PUT
     * @return mixed Automatically decodes JSON responses. If the response is not JSON, the response is returned as is
     */
    public function call($url, $json, $action)
    {
        if (substr_count($url, $this->suffix) == 0)
        {
            $url .= '.json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt($ch, CURLOPT_URL, $this->base.$url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user."/token:".$this->api_key);
        switch($action){
            case "POST":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            default:
                break;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'Accept: application/json'));
        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $output = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($output, true);

        return is_null($decoded) ? $output : $decoded;
    }

    /**
     * Tests the API using /users/ping
     * @return bool Whether connection and authentication were successful
     */
    public function test()
    {
        return $this->call('/tickets', '', 'GET');
    }
}