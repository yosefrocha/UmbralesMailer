<?php

declare(strict_types=1);

final class SesV2Service
{
    public function send(array $settings, array $payload): array
    {
        $region = trim((string) ($settings['ses_region'] ?? ''));
        $accessKey = trim((string) ($settings['ses_key'] ?? ''));
        $secretKey = trim((string) ($settings['ses_secret'] ?? ''));

        if ($region === '' || $accessKey === '' || $secretKey === '') {
            return ['ok' => false, 'error' => 'Configuración SES incompleta.'];
        }

        $endpoint = 'https://email.' . $region . '.amazonaws.com/v2/email/outbound-emails';
        $host = 'email.' . $region . '.amazonaws.com';
        $service = 'ses';
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $body = [
            'FromEmailAddress' => sprintf('%s <%s>', $payload['from_name'], $payload['from_email']),
            'Destination' => [
                'ToAddresses' => [$payload['to_email']],
            ],
            'Content' => [
                'Simple' => [
                    'Subject' => [
                        'Data' => $payload['subject'],
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Text' => [
                            'Data' => $payload['text_body'],
                            'Charset' => 'UTF-8',
                        ],
                        'Html' => [
                            'Data' => $payload['html_body'],
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ],
        ];

        if (!empty($payload['reply_to'])) {
            $body['ReplyToAddresses'] = [$payload['reply_to']];
        }
        if (!empty($payload['configuration_set'])) {
            $body['ConfigurationSetName'] = $payload['configuration_set'];
        }

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payloadHash = hash('sha256', $jsonBody);

        $canonicalHeaders = "content-type:application/json\n" .
            "host:" . $host . "\n" .
            "x-amz-content-sha256:" . $payloadHash . "\n" .
            "x-amz-date:" . $amzDate . "\n";
        $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';
        $canonicalRequest = "POST\n/v2/email/outbound-emails\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = $algorithm . "\n" . $amzDate . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);

        $signingKey = $this->getSignatureKey($secretKey, $dateStamp, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorization = $algorithm . ' Credential=' . $accessKey . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

        $headers = [
            'Content-Type: application/json',
            'Host: ' . $host,
            'X-Amz-Date: ' . $amzDate,
            'X-Amz-Content-Sha256: ' . $payloadHash,
            'Authorization: ' . $authorization,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            AuditLogger::logFile('ses-curl', $error);
            return ['ok' => false, 'error' => $error];
        }

        $decoded = is_string($response) ? json_decode($response, true) : null;
        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'message_id' => $decoded['MessageId'] ?? ''];
        }

        $message = $decoded['message'] ?? $decoded['Message'] ?? ('HTTP ' . $status);
        AuditLogger::logFile('ses-http', $message . ' | body=' . (string) $response);
        return ['ok' => false, 'error' => $message];
    }

    private function getSignatureKey(string $key, string $dateStamp, string $regionName, string $serviceName): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $key, true);
        $kRegion = hash_hmac('sha256', $regionName, $kDate, true);
        $kService = hash_hmac('sha256', $serviceName, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
