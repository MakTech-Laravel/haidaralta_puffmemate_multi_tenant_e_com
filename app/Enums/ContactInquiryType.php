<?php

namespace App\Enums;

enum ContactInquiryType: string
{
    case GeneralInquiry = 'general_inquiry';
    case OrderIssue = 'order_issue';
    case DeliveryComplaint = 'delivery_complaint';
    case ProductFeedback = 'product_feedback';
    case PaymentIssue = 'payment_issue';
    case Others = 'others';

    public function label(): string
    {
        return match ($this) {
            self::GeneralInquiry => 'General inquiry',
            self::OrderIssue => 'Order issue',
            self::DeliveryComplaint => 'Delivery complaint',
            self::ProductFeedback => 'Product feedback',
            self::PaymentIssue => 'Payment issue',
            self::Others => 'Others',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
