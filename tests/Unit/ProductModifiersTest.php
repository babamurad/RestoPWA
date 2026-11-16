<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Menu\Services\ProductModifiers;
use PHPUnit\Framework\TestCase;

class ProductModifiersTest extends TestCase
{
    private function stored(): array
    {
        return [
            ['name' => 'Размер', 'type' => 'radio', 'required' => true, 'options' => [
                ['name' => 'Маленькая', 'price' => 0, 'is_default' => true],
                ['name' => 'Большая', 'price' => 50],
            ]],
            ['name' => 'Топпинг', 'type' => 'checkbox', 'min' => 0, 'max' => 2, 'options' => [
                ['name' => 'Сыр', 'price' => 30],
                ['name' => 'Грибы', 'price' => 25],
                ['name' => 'Бекон', 'price' => 40],
            ]],
            ['name' => 'Соус', 'type' => 'counter', 'max' => 3, 'options' => [
                ['name' => 'Кетчуп', 'price' => 5],
            ]],
        ];
    }

    private function id(array $groups, string $group, string $option): string
    {
        foreach ($groups as $g) {
            foreach ($g['options'] as $o) {
                if ($g['name'] === $group && $o['name'] === $option) {
                    return $o['id'];
                }
            }
        }
        $this->fail("No option {$group}/{$option}");
    }

    public function test_groups_get_stable_ids_and_normalised_rules(): void
    {
        $groups = ProductModifiers::groups($this->stored());

        $this->assertSame(['radio', 'checkbox', 'counter'], array_column($groups, 'type'));
        $this->assertSame([1, 0, 0], array_column($groups, 'min'));
        $this->assertSame([1, 2, 3], array_column($groups, 'max'));
        $this->assertSame([true, false, false], array_column($groups, 'required'));
        // The same stored data always gives the same ids (carts keep them)
        $this->assertSame($groups, ProductModifiers::groups($this->stored()));
        $this->assertNotSame($groups[0]['id'], $groups[1]['id']);
    }

    public function test_a_radio_group_always_allows_one_and_a_checkbox_cannot_exceed_its_options(): void
    {
        $groups = ProductModifiers::groups([
            ['name' => 'A', 'type' => 'radio', 'min' => 5, 'max' => 9, 'options' => [['name' => 'x', 'price' => 0], ['name' => 'y', 'price' => 0]]],
            ['name' => 'B', 'type' => 'checkbox', 'max' => 10, 'options' => [['name' => 'x', 'price' => 0], ['name' => 'y', 'price' => 0]]],
        ]);

        $this->assertSame([1, 1], [$groups[0]['min'], $groups[0]['max']]);
        $this->assertSame(2, $groups[1]['max']);
    }

    public function test_a_flat_legacy_modifier_is_an_optional_single_option_group(): void
    {
        $groups = ProductModifiers::groups([['id' => 'm1', 'name' => 'Двойная порция', 'price' => 20]]);

        $this->assertCount(1, $groups);
        $this->assertSame('m1', $groups[0]['options'][0]['id']);
        $this->assertFalse($groups[0]['required']);

        $result = ProductModifiers::resolve($groups, ['m1']);
        $this->assertSame(20.0, $result['price']);
        $this->assertSame([], $result['violations']);
    }

    public function test_price_comes_from_the_stored_options_and_counters_multiply(): void
    {
        $groups = ProductModifiers::groups($this->stored());

        $result = ProductModifiers::resolve($groups, [
            $this->id($groups, 'Размер', 'Большая'),
            ['option_id' => $this->id($groups, 'Топпинг', 'Сыр'), 'price' => 0.01],
            ['option_id' => $this->id($groups, 'Соус', 'Кетчуп'), 'quantity' => 2],
        ]);

        // 50 + 30 + 2 × 5; the price the client sent for the cheese is ignored
        $this->assertSame(90.0, $result['price']);
        $this->assertSame([], $result['violations']);
        $this->assertSame(['Большая', 'Сыр', 'Кетчуп'], array_column($result['selected'], 'name'));
        $this->assertSame(2, $result['selected'][2]['quantity']);
    }

    public function test_unknown_options_are_dropped_and_duplicates_count_once(): void
    {
        $groups = ProductModifiers::groups($this->stored());
        $small = $this->id($groups, 'Размер', 'Маленькая');

        $result = ProductModifiers::resolve($groups, [$small, $small, 'made-up', ['option_id' => 'nope', 'price' => 0], 42, null]);

        $this->assertSame(0.0, $result['price']);
        $this->assertCount(1, $result['selected']);
        $this->assertSame([], $result['violations']);
    }

    public function test_required_group_must_be_chosen(): void
    {
        $groups = ProductModifiers::groups($this->stored());

        $result = ProductModifiers::resolve($groups, []);

        $this->assertSame([['group' => 'Размер', 'rule' => 'min', 'limit' => 1]], $result['violations']);
    }

    public function test_too_many_options_in_a_group_is_a_violation(): void
    {
        $groups = ProductModifiers::groups($this->stored());

        $result = ProductModifiers::resolve($groups, [
            $this->id($groups, 'Размер', 'Маленькая'),
            $this->id($groups, 'Размер', 'Большая'), // radio: one only
            $this->id($groups, 'Топпинг', 'Сыр'),
            $this->id($groups, 'Топпинг', 'Грибы'),
            $this->id($groups, 'Топпинг', 'Бекон'), // max 2
            ['option_id' => $this->id($groups, 'Соус', 'Кетчуп'), 'quantity' => 4], // max 3 portions
        ]);

        $this->assertSame(
            [['Размер', 'max', 1], ['Топпинг', 'max', 2], ['Соус', 'max', 3]],
            array_map(fn ($v) => [$v['group'], $v['rule'], $v['limit']], $result['violations']),
        );
    }

    public function test_a_counter_is_capped_and_the_old_name_form_still_works(): void
    {
        $groups = ProductModifiers::groups([['name' => 'Соус', 'type' => 'counter', 'options' => [['name' => 'Кетчуп', 'price' => 1]]]]);

        $result = ProductModifiers::resolve($groups, [['option_id' => $groups[0]['options'][0]['id'], 'quantity' => 100000]]);
        $this->assertSame(99.0, $result['price']);

        $byName = ProductModifiers::resolve($groups, [['name' => 'Кетчуп']]);
        $this->assertSame(1.0, $byName['price']);

        // An old client sends an id the dish never had together with the name
        $legacy = ProductModifiers::resolve($groups, [['id' => 'size_large', 'name' => 'Кетчуп', 'price' => 999900]]);
        $this->assertSame(1.0, $legacy['price']);
    }

    public function test_garbage_input_is_harmless(): void
    {
        $this->assertSame([], ProductModifiers::groups('nonsense'));
        $this->assertSame([], ProductModifiers::groups([['name' => '', 'options' => []], 'x', ['name' => 'Пустая', 'type' => 'radio', 'options' => []]]));
        $this->assertSame(0.0, ProductModifiers::resolve([], 'nonsense')['price']);
    }
}
