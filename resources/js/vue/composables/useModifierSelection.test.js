import { describe, it, expect, beforeEach } from 'vitest';
import { ref } from 'vue';
import { useModifierSelection } from './useModifierSelection';

const pizzaGroups = () => [
    { id: 'g-size', name: 'Размер', type: 'radio', min: 1, max: 1, options: [
        { id: 'o-small', name: 'Маленькая', price: 0, is_default: true },
        { id: 'o-big', name: 'Большая', price: 50, is_default: false },
    ] },
    { id: 'g-top', name: 'Топпинг', type: 'checkbox', min: 0, max: 2, options: [
        { id: 'o-cheese', name: 'Сыр', price: 30, is_default: false },
        { id: 'o-mush', name: 'Грибы', price: 25, is_default: false },
        { id: 'o-bacon', name: 'Бекон', price: 40, is_default: false },
    ] },
    { id: 'g-sauce', name: 'Соус', type: 'counter', min: 0, max: 3, options: [
        { id: 'o-ketchup', name: 'Кетчуп', price: 5, is_default: false },
    ] },
];

describe('useModifierSelection', () => {
    let groups;
    let sel;

    beforeEach(() => {
        groups = ref(pizzaGroups());
        sel = useModifierSelection(groups);
        sel.reset();
    });

    const g = (id) => groups.value.find((x) => x.id === id);
    const o = (group, id) => g(group).options.find((x) => x.id === id);

    it('starts with the default options chosen', () => {
        expect(sel.chosenModifiers.value.map((m) => m.name)).toEqual(['Маленькая']);
        expect(sel.isValid.value).toBe(true);
    });

    it('a required radio group without a default is invalid until one is chosen', () => {
        groups.value[0].options[0].is_default = false;
        sel.reset();

        expect(sel.isValid.value).toBe(false);
        expect(sel.groupError(g('g-size'))).toBe(true);

        sel.toggle(g('g-size'), o('g-size', 'o-big'));
        expect(sel.isValid.value).toBe(true);
    });

    it('choosing in a radio group switches the choice, and a required one cannot be cleared', () => {
        sel.toggle(g('g-size'), o('g-size', 'o-big'));
        expect(sel.chosenModifiers.value.map((m) => m.name)).toEqual(['Большая']);

        sel.toggle(g('g-size'), o('g-size', 'o-big'));
        expect(sel.chosenModifiers.value.map((m) => m.name)).toEqual(['Большая']);
    });

    it('an optional radio group is cleared by choosing the same option again', () => {
        groups.value[0].min = 0;
        sel.toggle(g('g-size'), o('g-size', 'o-small'));

        expect(sel.chosenModifiers.value).toEqual([]);
    });

    it('a checkbox group stops at its maximum and frees up when one is removed', () => {
        const top = g('g-top');
        sel.toggle(top, o('g-top', 'o-cheese'));
        sel.toggle(top, o('g-top', 'o-mush'));

        expect(sel.canAddPortion(top)).toBe(false);
        expect(sel.isDisabled(top, o('g-top', 'o-bacon'))).toBe(true);
        expect(sel.isDisabled(top, o('g-top', 'o-cheese'))).toBe(false);

        sel.toggle(top, o('g-top', 'o-bacon')); // ignored, the limit is reached
        expect(sel.portions(top, o('g-top', 'o-bacon'))).toBe(0);

        sel.toggle(top, o('g-top', 'o-cheese'));
        expect(sel.isDisabled(top, o('g-top', 'o-bacon'))).toBe(false);
    });

    it('a counter counts portions up to the group maximum', () => {
        const sauce = g('g-sauce');
        const ketchup = o('g-sauce', 'o-ketchup');

        sel.setPortions(sauce, ketchup, 2);
        sel.setPortions(sauce, ketchup, 5); // over the limit: stops at the maximum
        expect(sel.portions(sauce, ketchup)).toBe(3);
        expect(sel.canAddPortion(sauce)).toBe(false);

        sel.setPortions(sauce, ketchup, 0);
        expect(sel.chosenModifiers.value.find((m) => m.option_id === 'o-ketchup')).toBeUndefined();
    });

    it('a group that requires several is invalid until enough are chosen', () => {
        groups.value[1].min = 2;
        sel.reset();

        expect(sel.isValid.value).toBe(false);
        sel.toggle(g('g-top'), o('g-top', 'o-cheese'));
        expect(sel.isValid.value).toBe(false);
        sel.toggle(g('g-top'), o('g-top', 'o-mush'));
        expect(sel.isValid.value).toBe(true);
    });

    it('gives the cart the chosen options with their group, price and portions', () => {
        sel.toggle(g('g-size'), o('g-size', 'o-big'));
        sel.toggle(g('g-top'), o('g-top', 'o-cheese'));
        sel.setPortions(g('g-sauce'), o('g-sauce', 'o-ketchup'), 2);

        expect(sel.chosenModifiers.value).toEqual([
            { group_id: 'g-size', option_id: 'o-big', group: 'Размер', name: 'Большая', price: 50, quantity: 1 },
            { group_id: 'g-top', option_id: 'o-cheese', group: 'Топпинг', name: 'Сыр', price: 30, quantity: 1 },
            { group_id: 'g-sauce', option_id: 'o-ketchup', group: 'Соус', name: 'Кетчуп', price: 5, quantity: 2 },
        ]);
        // 50 + 30 + 2 × 5
        expect(sel.modifiersTotal.value).toBe(90);
    });

    it('a dish without groups is always valid', () => {
        const none = useModifierSelection(ref([]));
        none.reset();

        expect(none.isValid.value).toBe(true);
        expect(none.chosenModifiers.value).toEqual([]);
    });
});
