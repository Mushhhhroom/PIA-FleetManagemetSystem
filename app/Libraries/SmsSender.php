<?php

namespace App\Libraries;

class SmsSender
{
    protected ?string $apiKey;
    protected string $senderName;

    public function __construct()
    {
        $this->apiKey = env('SEMAPHORE_KEY', null);
        $this->senderName = env('SEMAPHORE_SENDER', 'PIA-Motorpool');
    }

    /**
     * Send SMS or log simulated transmission
     */
    public function send(string $phoneNumber, string $message): array
    {
        // If API key is configured, post to Semaphore
        if (!empty($this->apiKey) && $this->apiKey !== 'dummy_key') {
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->post('https://api.semaphore.co/api/v4/messages', [
                    'form_params' => [
                        'apikey'     => $this->apiKey,
                        'number'     => $phoneNumber,
                        'message'    => $message,
                        'sendername' => $this->senderName,
                    ]
                ]);
                return [
                    'status' => 'sent',
                    'code'   => $response->getStatusCode(),
                    'live'   => true,
                ];
            } catch (\Exception $e) {
                log_message('error', 'Semaphore SMS error: ' . $e->getMessage());
            }
        }

        // Development/Test simulated transmission log
        log_message('info', "[SIMULATED SMS] To: {$phoneNumber} | Message: {$message}");
        return [
            'status'    => 'simulated',
            'recipient' => $phoneNumber,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
