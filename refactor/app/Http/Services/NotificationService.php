<?php

namespace DTApi\Http\Services;

class NotificationService
{
    public $key;

    public function __construct() {
        /*
         * Multiple Environment Variables shouldn't be used, it should be single for DEV only DevID and for PROD only PRODID, this is not a good practice
         * also it requires additional checks
         * */
        if (env('APP_ENV') == 'prod') {
            $this->key = sprintf("Authorization: Basic %s", config('app.prodOnesignalApiKey'));
        } else {
            $this->key = sprintf("Authorization: Basic %s", config('app.devOnesignalApiKey'));
        }
    }
    public function sendNotification($fields, $jobId, $logger) {
        /*
         * Any Specific Reason for using cURL ?
         * Guzzle should be used for HTTP client requests
         * */

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $this->key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        $logger->addInfo('Push send for job ' . $job_id . ' curl answer', [$response]);
        curl_close($ch);
    }
}