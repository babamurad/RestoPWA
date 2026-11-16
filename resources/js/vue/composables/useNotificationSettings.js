import { ref } from 'vue';
import notificationsApi from '../api/notifications';

export const MARKETING_CHANNELS = ['push', 'sms', 'email'];

/**
 * Marketing notification choices of the customer. Order notifications are not here on purpose:
 * they cannot be switched off. A switch moves at once and goes back if the server refuses.
 */
export function useNotificationSettings() {
  const marketing = ref({ push: false, sms: false, email: false });
  const loaded = ref(false);
  const error = ref(false);
  const savingChannel = ref(null);

  const load = async () => {
    try {
      const settings = await notificationsApi.getSettings();
      marketing.value = { ...marketing.value, ...(settings.marketing || {}) };
      error.value = false;
    } catch (e) {
      error.value = true;
    } finally {
      loaded.value = true;
    }
  };

  const toggle = async (channel) => {
    if (savingChannel.value || !MARKETING_CHANNELS.includes(channel)) return false;

    const previous = marketing.value[channel];
    marketing.value = { ...marketing.value, [channel]: !previous };
    savingChannel.value = channel;
    error.value = false;

    try {
      const settings = await notificationsApi.updateMarketing({ [channel]: !previous });
      marketing.value = { ...marketing.value, ...(settings.marketing || {}) };
      return true;
    } catch (e) {
      marketing.value = { ...marketing.value, [channel]: previous };
      error.value = true;
      return false;
    } finally {
      savingChannel.value = null;
    }
  };

  return { marketing, loaded, error, savingChannel, load, toggle };
}
