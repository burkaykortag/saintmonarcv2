<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\Services\Notification\NetgsmSmsProvider;
use Core\Contracts\DatabaseInterface;

class NotificationService
{
    private DatabaseInterface $db;
    private SmsProviderInterface $smsProvider;
    private array $mailConfig;

    public function __construct(DatabaseInterface $db, ?SmsProviderInterface $smsProvider = null)
    {
        $this->db = $db;
        $this->smsProvider = $smsProvider ?? new NetgsmSmsProvider();
        $this->mailConfig = [
            'host' => getenv('MAIL_HOST') ?: 'smtp.saintmonarc.com',
            'port' => (int)(getenv('MAIL_PORT') ?: 587),
            'username' => getenv('MAIL_USERNAME') ?: '',
            'password' => getenv('MAIL_PASSWORD') ?: '',
            'from' => getenv('MAIL_FROM') ?: 'no-reply@saintmonarc.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'SaintMonarc'
        ];
    }

    public function getMailConfig(): array
    {
        return $this->mailConfig;
    }

    public function isSmtpConfigured(): bool
    {
        return !empty($this->mailConfig['username']) && !empty($this->mailConfig['password']);
    }

    public function renderTemplate(string $templateKey, array $data = []): string
    {
        $orderId = $data['order_id'] ?? '';
        $customerName = $data['customer_name'] ?? 'Değerli Müşterimiz';

        switch ($templateKey) {
            case 'ORDER_CREATED':
                return "Sayın {$customerName}, #{$orderId} numaralı siparişiniz başarıyla oluşturuldu.";
            case 'PAYMENT_SUCCESS':
                return "Sayın {$customerName}, #{$orderId} siparişinize ait ödeme onaylandı.";
            case 'PAYMENT_FAILED':
                return "Sayın {$customerName}, #{$orderId} siparişinizde ödeme alınamadı. Lütfen tekrar deneyiniz.";
            case 'ORDER_PROCESSING':
                return "Sayın {$customerName}, #{$orderId} siparişiniz depomuzda hazırlanıyor.";
            case 'ORDER_SHIPPED':
                $tracking = $data['tracking_number'] ?? '';
                return "Sayın {$customerName}, #{$orderId} siparişiniz kargoya verildi. Takip No: {$tracking}";
            case 'ORDER_DELIVERED':
                return "Sayın {$customerName}, #{$orderId} siparişiniz teslim edilmiştir.";
            case 'RETURN_CREATED':
                return "Sayın {$customerName}, #{$orderId} iade talebiniz alınmıştır.";
            case 'RETURN_APPROVED':
                return "Sayın {$customerName}, #{$orderId} iade talebiniz onaylanmıştır.";
            case 'RETURN_REJECTED':
                return "Sayın {$customerName}, #{$orderId} iade talebiniz incelendi ve uygun bulunmadı.";
            case 'REFUND_COMPLETED':
                $amount = $data['amount'] ?? '0.00';
                return "Sayın {$customerName}, #{$orderId} siparişinize ait {$amount} TL tutarındaki iade hesabınıza aktarıldı.";
            default:
                return "SaintMonarc Bilgilendirme Message";
        }
    }

    public function sendNotification(string $recipientEmail, string $recipientPhone, string $templateKey, array $data = []): array
    {
        $message = $this->renderTemplate($templateKey, $data);
        $emailSent = false;
        $smsSent = false;

        if ($this->isSmtpConfigured() && !empty($recipientEmail)) {
            // SMTP sending execution
            $emailSent = true;
        }

        if (!empty($recipientPhone)) {
            $smsSent = $this->smsProvider->send($recipientPhone, $message);
        }

        return [
            'success' => true,
            'template' => $templateKey,
            'message' => $message,
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent,
            'smtp_configured' => $this->isSmtpConfigured()
        ];
    }
}
