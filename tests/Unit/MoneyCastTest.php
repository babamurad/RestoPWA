<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Casts\MoneyCast;
use App\Domains\Menu\Models\Product;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    private MoneyCast $cast;
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new MoneyCast();
        $this->model = new TestModel();
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

    public function test_set_converts_integer_to_cents(): void
    {
        $result = $this->cast->set($this->model, 'price', 100, []);

        $this->assertSame(10000, $result);
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
