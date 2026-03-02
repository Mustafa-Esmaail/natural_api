<?php


namespace App\Http\Controllers\API\v1\Dashboard\Admin;
use Illuminate\Support\Facades\Log;
use PSpell\Config;

class UltraMsgController
{


  public function sendSMS($to, $message, $image = null, $type = null,$multiConfig = null)
  {

    // $multiConfig = env('SMS_CONFIG_MULTI_ENABLED');
    if(!is_null($multiConfig)){
      $token = env('UltraMsg_token_whatsapp');
      $instance_id = env('UltraMsg_instance_whatsapp');
      Log::info('Record updated', ['table' => 'token_1', 'data' => $multiConfig]);
    }else{
      $token = env('UltraMsg_token');
      $instance_id = env('UltraMsg_instance');
      Log::info('Record updated', ['table' => 'token_2', 'data' => $multiConfig]);

    }

    if ($type == 'image') {
      $url = "https://api.ultramsg.com/{$instance_id}/messages/image";
      $params = array(
        'token' => $token,
        'to' => $to,
        'image' => $image,
        'caption' => $message,
      );
    } elseif ($type == 'video') {
      $url = "https://api.ultramsg.com/{$instance_id}/messages/video";
      $params = array(
        'token' => $token,
        'to' => $to,
        'video' => $image,
        'caption' => $message,
      );
    } else {
      $url = "https://api.ultramsg.com/{$instance_id}/messages/chat";
      $params = array(
        'token' => $token,
        'to' => $to,
        'body' => $message,
      );
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => http_build_query($params),
      CURLOPT_HTTPHEADER => array(
        "content-type: application/x-www-form-urlencoded"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    logger($response);
    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }
}
