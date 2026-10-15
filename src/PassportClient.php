<?php

namespace Nitron\Passport;

use Nitron\Passport\Exceptions\PassportException;
use Nitron\Passport\Exceptions\PassportValidationException;
use InvalidArgumentException;

class PassportClient
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://sms.nitron.pro/api',
        int $timeout = 10
    ) {
        $this->apiKey = trim($apiKey);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('API Key cannot be empty.');
        }
    }

    /**
     * ارسال پیامک اعتبارسنجی (OTP)
     *
     * @param string $mobile شماره موبایل مقصد
     * @param string $templateCode کد الگوی تایید شده در پنل
     * @param array $parameters مقادیر متغیرهای الگو مانند ['code' => '123456']
     * @param string|null $clientReference شناسه پیگیری اختیاری سمت شما
     * @return array پاسخ دیکود شده سرور
     * @throws PassportException
     */
    public function sendOtp(
        string $mobile,
        string $templateCode,
        array $parameters,
        ?string $clientReference = null
    ): array {
        $payload = [
            'mobile'           => $this->normalizeMobile($mobile),
            'template_code'    => trim($templateCode),
            'parameters'       => (object) $parameters,
            'client_reference' => $clientReference,
        ];

        return $this->request('POST', '/v1/otp/send', $payload);
    }

    /**
     * استعلام وضعیت پیامک ارسالی با UUID
     *
     * @param string $uuid
     * @return array
     * @throws PassportException
     */
    public function getOtpStatus(string $uuid): array
    {
        return $this->request('GET', '/v1/otp/' . trim($uuid));
    }

    /**
     * دریافت لیست الگوهای تایید شده در پنل
     *
     * @return array
     * @throws PassportException
     */
    public function getTemplates(): array
    {
        return $this->request('GET', '/v1/templates');
    }

    /**
     * دریافت موجودی حساب
     *
     * @return array
     * @throws PassportException
     */
    public function getBalance(): array
    {
        return $this->request('GET', '/v1/account/balance');
    }

    /**
     * ارسال درخواست HTTP به سرور
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @return array
     * @throws PassportException
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        ];

        if ($method === 'POST' && !empty($data)) {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
            $options[CURLOPT_POSTFIELDS] = $jsonData;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new PassportException("Connection error: {$curlError}");
        }

        $decoded = json_decode($response, true) ?? [];

        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded;
        }

        $message = $decoded['message'] ?? "Request failed with HTTP status {$httpCode}";
        $errors = $decoded['errors'] ?? [];

        if ($httpCode === 422) {
            throw new PassportValidationException($message, 422, $errors);
        }

        $exceptionMessage = !empty($errors) ? $message . ' - ' . json_encode($errors, JSON_UNESCAPED_UNICODE) : $message;
        throw new PassportException($exceptionMessage, $httpCode);
    }

    /**
     * استانداردسازی و تبدیل شماره موبایل به فرمت 09xxxxxxxxx
     *
     * @param string $mobile
     * @return string
     */
    public function normalizeMobile(string $mobile): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $num     = range(0, 9);
        $mobile  = str_replace($persian, $num, $mobile);
        $mobile  = str_replace($arabic, $num, $mobile);

        $mobile = preg_replace('/[^0-9+]/', '', trim($mobile));

        if (strpos($mobile, '+98') === 0) {
            $mobile = '0' . substr($mobile, 3);
        } elseif (strpos($mobile, '98') === 0 && strlen($mobile) === 12) {
            $mobile = '0' . substr($mobile, 2);
        } elseif (strpos($mobile, '9') === 0 && strlen($mobile) === 10) {
            $mobile = '0' . $mobile;
        }

        return $mobile;
    }
}
