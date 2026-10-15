# 🛂 Nitron Passport PHP SDK

<p align="center">
  <img src="assets/logo/logo-512.png" alt="Nitron Passport Logo" width="180">
</p>

کتابخانه رسمی PHP برای اتصال به درگاه احراز هویت و ارسال هوشمند کد اعتبارسنجی (OTP) **Nitron Passport**.

---

## 🚀 نصب (Installation)

با استفاده از کامپوزر:

```bash
composer require nitron/passport-php-sdk
```

---

## ⚡ نحوه استفاده سریع (Quick Start)

```php
use Nitron\Passport\PassportClient;
use Nitron\Passport\Exceptions\PassportValidationException;
use Nitron\Passport\Exceptions\PassportException;

$client = new PassportClient('YOUR_API_KEY');

try {
    // ارسال کد OTP
    $response = $client->sendOtp(
        mobile: '09121234567',
        templateCode: 'LOGIN',
        parameters: ['code' => '482913']
    );

    $uuid = $response['data']['uuid'];
    echo "ارسال موفق! شناسه پیامک: " . $uuid;

} catch (PassportValidationException $e) {
    // خطای اعتبارسنجی ورودی‌ها (کد ۴۲۲)
    echo "خطای ورودی: " . $e->getMessage();
    print_r($e->getErrors());
} catch (PassportException $e) {
    // سایر خطاهای ارتباطی یا سرویس
    echo "خطا در برقراری ارتباط: " . $e->getMessage();
}
```

---

## 📋 متدهای کلاینت

### ۱. ارسال OTP
```php
$client->sendOtp(string $mobile, string $templateCode, array $parameters, ?string $clientReference = null): array
```

### ۲. استعلام وضعیت پیامک
```php
$status = $client->getOtpStatus(string $uuid): array
```

### ۳. دریافت موجودی حساب
```php
$balance = $client->getBalance(): array
```

### ۴. دریافت لیست قالب‌های تایید شده
```php
$templates = $client->getTemplates(): array
```
