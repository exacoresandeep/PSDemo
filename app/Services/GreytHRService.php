<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreytHRService
{
    private $apiUser;
    private $apiKey;
    private $host;
    private $apiUrl;

    public function __construct()
    {
        $this->apiUser = config('services.greythr.api_user');
        $this->apiKey  = config('services.greythr.api_key');
        $this->host    = config('services.greythr.host');
        $this->apiUrl  = config('services.greythr.api_url');
    }

    public function getAccessToken()
    {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->apiUser}:{$this->apiKey}"),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ])->withOptions([
            'verify' => false,
        ])->post("https://{$this->host}/uas/v1/oauth2/client-token", [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error("GreytHR Token API Error:", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }
        return $response->json()['access_token'] ?? null;
    }

    public function getEmployeeDetails($token, $employeeCode)
    {
        
        $response = Http::withHeaders([
            'ACCESS-TOKEN' =>  $token,
            'x-greythr-domain' => $this->host,
        ])->withOptions([
            'verify' => false,
        ])->get("{$this->apiUrl}/employee/v2/employees");

        dd( $response->json());


        $response = Http::withHeaders([
            'Authorization'    => "Bearer ".$token, 
            'x-greythr-domain' => $this->host,
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ])->withOptions([
            'verify' => false,
        ])->get("{$this->apiUrl}/employee/v2/employees", [
            'employeeNo' => $employeeCode
        ]);
        if ($response->failed()) {
            Log::error("GreytHR Employee Fetch Error:", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }
}

