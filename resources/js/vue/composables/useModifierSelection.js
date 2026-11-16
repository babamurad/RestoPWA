import { computed, ref } from 'vue';

/**
 * The customer's choice from a dish's modifier groups.
 *
 * groups: ref/computed list of { id, name, type: 'radio'|'checkbox'|'counter', min, max,
 * options: [{ id, name, price, is_default }] } as the menu API returns it.
 * The choice is held as { [groupId]: { [optionId]: portions } }.
 */
export function useModifierSelection(groups) {
  const selection = ref({});

  const portions = (group, option) => selection.value[group.id]?.[option.id] || 0;

  const groupCount = (group) => Object.values(selection.value[group.id] || {}).reduce((sum, n) => sum + n, 0);

  // Start from the options the restaurant marked as default
  const reset = () => {
    const initial = {};
    groups.value.forEach((group) => {
      const defaults = group.options.filter((option) => option.is_default);
      const chosen = group.type === 'radio' ? defaults.slice(0, 1) : defaults.slice(0, group.max || defaults.length);
      initial[group.id] = Object.fromEntries(chosen.map((option) => [option.id, 1]));
    });
    selection.value = initial;
  };

  const canAddPortion = (group) => group.max === null || group.max === undefined || groupCount(group) < group.max;

  // A checkbox whose limit is reached cannot take one more (a radio simply switches)
  const isDisabled = (group, option) => group.type === 'checkbox'
    && portions(group, option) === 0
    && !canAddPortion(group);

  const toggle = (group, option) => {
    const current = { ...(selection.value[group.id] || {}) };

    if (group.type === 'radio') {
      // A required radio keeps one chosen; an optional one is cleared by choosing it again
      selection.value = { ...selection.value, [group.id]: current[option.id] && !(group.min > 0) ? {} : { [option.id]: 1 } };
      return;
    }

    if (current[option.id]) {
      delete current[option.id];
    } else if (canAddPortion(group)) {
      current[option.id] = 1;
    }
    selection.value = { ...selection.value, [group.id]: current };
  };

  const setPortions = (group, option, count) => {
    let wanted = Math.max(0, Math.min(99, count));
    // Never more portions than the group's maximum leaves room for
    if (group.max !== null && group.max !== undefined) {
      wanted = Math.min(wanted, portions(group, option) + Math.max(0, group.max - groupCount(group)));
    }

    const current = { ...(selection.value[group.id] || {}) };
    if (wanted === 0) {
      delete current[option.id];
    } else {
      current[option.id] = wanted;
    }
    selection.value = { ...selection.value, [group.id]: current };
  };

  // Fewer chosen than the group requires
  const groupError = (group) => groupCount(group) < (group.min || 0);

  const isValid = computed(() => groups.value.every((group) => !groupError(group)));

  // The chosen options as the cart and the order API want them
  const chosenModifiers = computed(() => groups.value.flatMap((group) => group.options
    .filter((option) => portions(group, option) > 0)
    .map((option) => ({
      group_id: group.id,
      option_id: option.id,
      group: group.name,
      name: option.name,
      price: option.price,
      quantity: portions(group, option),
    }))));

  const modifiersTotal = computed(() => chosenModifiers.value.reduce((sum, m) => sum + m.price * m.quantity, 0));

  return {
    selection, portions, groupCount, reset, canAddPortion, isDisabled, toggle, setPortions,
    groupError, isValid, chosenModifiers, modifiersTotal,
  };
}
