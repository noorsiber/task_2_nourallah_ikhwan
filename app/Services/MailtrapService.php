<?php namespace App\Services;

use Illuminate\Support\Facades\Http;

class MailtrapService
{

    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('MAILTRAP_BASE_URL', 'https://sandbox.api.mailtrap.io/api/send');
        $this->apiKey = env('MAILTRAP_API_KEY');
    }
    public function sendEmail(array $data)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Api-Token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/inbox_id", $data);

        if ($response->failed()) {
            return 'Error: ' . $response->body();
        }

        return $response->body();
    }
}
?>