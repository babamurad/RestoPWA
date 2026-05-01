<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PIIMasker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PIIMaskerTest extends TestCase
{
    #[Test]
    public function mask_phone_keeps_country_code_and_last_two_digits(): void
    {
        $this->assertEquals('+993******78', PIIMasker::maskPhone('+99312345678'));
    }

    #[Test]
    public function mask_phone_handles_short_numbers(): void
    {
        $this->assertEquals('***', PIIMasker::maskPhone('+12'));
    }

    #[Test]
    public function mask_name_keeps_first_character_of_each_part(): void
    {
        $this->assertEquals('И*** И*****', PIIMasker::maskName('Иван Иванов'));
    }

    #[Test]
    public function mask_name_handles_single_name(): void
    {
        $this->assertEquals('А***', PIIMasker::maskName('Анна'));
    }

    #[Test]
    public function mask_address_keeps_only_street_name(): void
    {
        $this->assertEquals(
            'ул. Ахалтекинская, ***',
            PIIMasker::maskAddress('ул. Ахалтекинская, д. 10, кв. 5')
        );
    }

    #[Test]
    public function mask_address_returns_full_if_no_comma(): void
    {
        $this->assertEquals(
            'ул. Ахалтекинская',
            PIIMasker::maskAddress('ул. Ахалтекинская')
        );
    }

    #[Test]
    public function mask_comment_truncates_to_max_visible(): void
    {
        $comment = 'Это очень длинный комментарий который нужно замаскировать';
        $masked = PIIMasker::maskComment($comment, 20);

        $this->assertStringContainsString('...', $masked);
        $this->assertLessThanOrEqual(24, mb_strlen($masked));
    }

    #[Test]
    public function mask_comment_returns_full_if_short(): void
    {
        $comment = 'Коротко';
        $this->assertEquals($comment, PIIMasker::maskComment($comment));
    }

    #[Test]
    public function mask_order_payload_masks_all_pii_fields(): void
    {
        $payload = [
            'vendor_id' => 'v1',
            'address' => [
                'name' => 'Иван Иванов',
                'phone' => '+99312345678',
                'address' => 'ул. Тестовая, д. 1',
                'comment' => 'Длинный комментарий который не должен быть полностью виден в логах',
            ],
            'comment' => 'Длинный комментарий к заказу который нужно сократить',
            'total' => 5000,
        ];

        $masked = PIIMasker::maskOrderPayload($payload);

        $this->assertEquals('И*** И*****', $masked['address']['name']);
        $this->assertStringContainsString('***', $masked['address']['phone']);
        $this->assertEquals('ул. Тестовая, ***', $masked['address']['address']);
        $this->assertStringContainsString('...', $masked['address']['comment']);
        $this->assertStringContainsString('...', $masked['comment']);
        $this->assertEquals(5000, $masked['total']);
        $this->assertEquals('v1', $masked['vendor_id']);
    }

    #[Test]
    public function masking_checklist_contains_expected_fields(): void
    {
        $checklist = PIIMasker::getMaskingChecklist();

        $this->assertContains('address.name', $checklist['must_mask']);
        $this->assertContains('address.phone', $checklist['must_mask']);
        $this->assertContains('vendor_id', $checklist['safe_to_log']);
        $this->assertContains('trace_id', $checklist['safe_to_log']);
    }
}
