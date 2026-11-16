<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    private MoneyCast $cast;

    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new MoneyCast;
        $this->model = new TestModel;
    }

    public function test_get_returns_float_from_cents(): void
    {
        $result = $this->cast->get($this->model, 'price', '1999', []);

        $this->assertSame(19.99, $result);
        $this->assertIsFloat($result);
    }

    public function test_get_returns_zero_from_null(): void
    {
        $result = $this->cast->get($this->model, 'price', null, []);

        $this->assertSame(0.0, $result);
    }

    public function test_get_handles_integer_cents(): void
    {
        $result = $this->cast->get($this->model, 'price', 10050, []);

        $this->assertSame(100.5, $result);
    }

    public function test_set_converts_float_to_cents(): void
    {
        $result = $this->cast->set($this->model, 'price', 19.99, []);

        $this->assertSame(1999, $result);
        $this->assertIsInt($result);
    }

    public function test_set_treats_an_integer_as_currency_units_like_a_float(): void
    {
        // 100 means 100 TMT, exactly as 100.0 does: the type of a number never changes its meaning
        $this->assertSame(10000, $this->cast->set($this->model, 'price', 100, []));
        $this->assertSame(10000, $this->cast->set($this->model, 'price', 100.0, []));
        $this->assertSame(10000, $this->cast->set($this->model, 'price', '100', []));
    }

    public function test_set_and_get_round_trip_for_every_numeric_form(): void
    {
        foreach ([20, 20.0, '20', '20.00', 19.99, '19.99', 0, 0.0, '0'] as $amount) {
            $stored = $this->cast->set($this->model, 'price', $amount, []);

            $this->assertSame((float) $amount, $this->cast->get($this->model, 'price', $stored, []), var_export($amount, true));
        }
    }

    public function test_set_treats_an_empty_string_as_zero(): void
    {
        $this->assertSame(0, $this->cast->set($this->model, 'price', '', []));
    }

    public function test_set_rejects_what_is_not_a_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->cast->set($this->model, 'price', 'ten', []);
    }

    public function test_minor_unit_helpers(): void
    {
        $this->assertSame(19.99, MoneyCast::fromMinor(1999));
        $this->assertSame(19.99, MoneyCast::fromMinor('1999'));
        $this->assertSame(1999, MoneyCast::toMinor(19.99));
        // 0.1 + 0.2 is 0.30000000000000004 in floats: still 30 tyiyn
        $this->assertSame(30, MoneyCast::toMinor(0.1 + 0.2));
    }

    public function test_set_converts_string_to_cents(): void
    {
        $result = $this->cast->set($this->model, 'price', '25.50', []);

        $this->assertSame(2550, $result);
    }

    public function test_set_returns_zero_from_null(): void
    {
        $result = $this->cast->set($this->model, 'price', null, []);

        $this->assertSame(0, $result);
    }

    public function test_rounds_correctly(): void
    {
        $result = $this->cast->set($this->model, 'price', 19.999, []);

        $this->assertSame(2000, $result);
    }
}

class TestModel extends Model
{
    protected $table = 'test';
}
