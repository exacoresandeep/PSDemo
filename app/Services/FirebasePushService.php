<?php

namespace App\Services;
use App\Models\Activity;
use App\Models\Dealer;
use App\Models\Employee;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebasePushService
{
    protected $credentialsPath;
    protected $projectId;

    public function __construct()
    {
        $this->credentialsPath = storage_path('app/firebase/ps-steel-ae50c-firebase-adminsdk-fbsvc-ad4b6cd8a0.json');
        $json = json_decode(file_get_contents($this->credentialsPath), true);
        $this->projectId = $json['project_id'];
    }

    protected function getAccessToken()
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $credentials = new ServiceAccountCredentials(
            $scopes,
            $this->credentialsPath
        );

        return $credentials->fetchAuthToken()['access_token'];
    }

    public function sendNotification($deviceToken, $title, $body,$table, $data = [])
    {
        $accessToken = $this->getAccessToken();

        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ]
            ]
	];
	if($table=="employees"){
		$user=Employee::where("fcm_token",$deviceToken)->first();
		if($user){
			$ActCount=Activity::where('employee_id', $user->id)
    ->where('notification_status', 'pending')
    ->count();
			$data["count"]=$ActCount;
		}
	}
	if($table=="dealers"){
		 $data["count"]=12;

	}

	if (!empty($data) && is_array($data) && array_keys($data) !== range(0, count($data) - 1)) {
	//	$message['message']['data'] = $data;
//		$message['message']['data'] = array_map('strval', $data);
    }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $response = Http::withToken($accessToken)
            ->post($url, $message);
        return $response->json();
    }
}

