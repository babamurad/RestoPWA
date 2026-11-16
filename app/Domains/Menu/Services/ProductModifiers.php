<?php

declare(strict_types=1);

namespace App\Domains\Menu\Services;

use Illuminate\Support\Collection;

/**
 * Modifier groups of a dish ("size", "toppings", "sauce") and the customer's choice from them.
 *
 * A group has a type — radio (one option), checkbox (several), counter (several, each with a
 * quantity) — and the rules: required, min and max number of chosen options (for a counter:
 * of chosen portions). The price of a choice is always computed here from the stored options;
 * names and prices the client sends are never trusted.
 *
 * Old data is understood too: a flat modifier without options ({id, name, price}) is an
 * optional one-option group.
 */
final class ProductModifiers
{
    public const TYPE_RADIO = 'radio';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_COUNTER = 'counter';

    /** A counter portion is capped, so a typo cannot produce a 10 000-portion order */
    public const MAX_PORTIONS = 99;

    /**
     * Stored modifiers → groups with stable ids and the rules filled in.
     *
     * @return list<array{id: string, name: string, type: string, required: bool, min: int, max: ?int, options: list<array{id: string, name: string, price: float, is_default: bool}>}>
     */
    public static function groups(mixed $stored): array
    {
        $raw = $stored instanceof Collection ? $stored->all() : (is_array($stored) ? $stored : []);
        $groups = [];

        foreach ($raw as $entry) {
            if (! is_array($entry) || ($entry['name'] ?? '') === '') {
                continue;
            }

            $name = (string) $entry['name'];

            // Flat modifier: an optional extra with a single option
            if (! isset($entry['options']) || ! is_array($entry['options'])) {
                $entry = [
                    'name' => $name,
                    'type' => self::TYPE_CHECKBOX,
                    'options' => [['id' => $entry['id'] ?? null, 'name' => $name, 'price' => $entry['price'] ?? 0]],
                ];
            }

            $type = in_array($entry['type'] ?? null, [self::TYPE_RADIO, self::TYPE_CHECKBOX, self::TYPE_COUNTER], true)
                ? $entry['type']
                : self::TYPE_CHECKBOX;

            $options = [];
            foreach ($entry['options'] as $option) {
                if (! is_array($option) || ($option['name'] ?? '') === '') {
                    continue;
                }
                $options[] = [
                    'id' => (string) ($option['id'] ?? 'o'.substr(sha1($name.'|'.$option['name']), 0, 10)),
                    'name' => (string) $option['name'],
                    'price' => round((float) ($option['price'] ?? 0), 2),
                    'is_default' => (bool) ($option['is_default'] ?? false),
                ];
            }
            if ($options === []) {
                continue;
            }

            $required = (bool) ($entry['required'] ?? false);
            $min = max($required ? 1 : 0, (int) ($entry['min'] ?? 0));
            $max = isset($entry['max']) && $entry['max'] !== '' && (int) $entry['max'] > 0 ? (int) $entry['max'] : null;

            if ($type === self::TYPE_RADIO) {
                $min = min($min, 1);
                $max = 1;
            } elseif ($type === self::TYPE_CHECKBOX) {
                // More options than the group has cannot be chosen
                $max = min($max ?? count($options), count($options));
                $min = min($min, count($options));
            }
            if ($max !== null) {
                $min = min($min, $max);
            }

            $groups[] = [
                'id' => (string) ($entry['id'] ?? 'g'.substr(sha1($name), 0, 10)),
                'name' => $name,
                'type' => $type,
                'required' => $min > 0,
                'min' => $min,
                'max' => $max,
                'options' => $options,
            ];
        }

        return $groups;
    }

    /**
     * Prices and checks a choice.
     *
     * $selection is what the client sent: option ids ("o1a2b…"), or entries
     * {option_id, quantity} / {id}; the old name-based form {name} is still accepted.
     * Options the dish does not have are dropped.
     *
     * @param  list<array<string, mixed>>  $groups  from groups()
     * @return array{
     *     price: float,
     *     selected: list<array{id: string, group_id: string, group: string, name: string, price: float, quantity: int}>,
     *     violations: list<array{group: string, rule: 'min'|'max', limit: int}>
     * }
     */
    public static function resolve(array $groups, mixed $selection): array
    {
        $chosen = []; // option id => quantity

        foreach (is_array($selection) ? $selection : [] as $entry) {
            $optionId = is_string($entry) ? $entry : null;
            $quantity = 1;

            if (is_array($entry)) {
                $optionId = isset($entry['option_id']) ? (string) $entry['option_id'] : (isset($entry['id']) ? (string) $entry['id'] : null);
                $quantity = (int) ($entry['quantity'] ?? 1);
            }

            $found = self::findOption($groups, $optionId, is_array($entry) ? ($entry['name'] ?? null) : null);
            if ($found === null) {
                continue;
            }

            [$group, $option] = $found;
            $portions = $group['type'] === self::TYPE_COUNTER ? max(1, min(self::MAX_PORTIONS, $quantity)) : 1;
            $key = $group['id'].'|'.$option['id'];

            // The same option sent twice counts once, except a counter, which adds up
            $chosen[$key] = $group['type'] === self::TYPE_COUNTER
                ? min(self::MAX_PORTIONS, ($chosen[$key] ?? 0) + $portions)
                : 1;
        }

        $price = 0.0;
        $selected = [];
        $violations = [];

        foreach ($groups as $group) {
            $count = 0;

            foreach ($group['options'] as $option) {
                $quantity = $chosen[$group['id'].'|'.$option['id']] ?? 0;
                if ($quantity === 0) {
                    continue;
                }

                $count += $quantity;
                $price += $option['price'] * $quantity;
                $selected[] = [
                    'id' => $option['id'],
                    'group_id' => $group['id'],
                    'group' => $group['name'],
                    'name' => $option['name'],
                    'price' => $option['price'],
                    'quantity' => $quantity,
                ];
            }

            if ($count < $group['min']) {
                $violations[] = ['group' => $group['name'], 'rule' => 'min', 'limit' => $group['min']];
            } elseif ($group['max'] !== null && $count > $group['max']) {
                $violations[] = ['group' => $group['name'], 'rule' => 'max', 'limit' => $group['max']];
            }
        }

        return ['price' => round($price, 2), 'selected' => $selected, 'violations' => $violations];
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>}|null */
    private static function findOption(array $groups, ?string $optionId, mixed $name): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['options'] as $option) {
                if ($optionId !== null && $option['id'] === $optionId) {
                    return [$group, $option];
                }
            }
        }

        // Old clients identified an option by its name (and sent an id the dish never had)
        if (is_string($name) && $name !== '') {
            foreach ($groups as $group) {
                foreach ($group['options'] as $option) {
                    if ($option['name'] === $name) {
                        return [$group, $option];
                    }
                }
            }
        }

        return null;
    }
}
