import { describe, it, expect, beforeEach, vi } from 'vitest';

vi.mock('../api/client', () => ({
    default: { get: vi.fn(), put: vi.fn(), defaults: { headers: { common: {} } } },
}));

vi.mock('../api/notifications', () => ({
    default: { getSettings: vi.fn(), updateMarketing: vi.fn() },
}));

import notificationsApi from '../api/notifications';
import { useNotificationSettings } from './useNotificationSettings';

describe('useNotificationSettings', () => {
    let settings;

    beforeEach(() => {
        vi.clearAllMocks();
        settings = useNotificationSettings();
    });

    it('loads what the customer has chosen; everything is off until then', async () => {
        expect(settings.marketing.value).toEqual({ push: false, sms: false, email: false });

        notificationsApi.getSettings.mockResolvedValue({ marketing: { push: false, sms: true, email: true } });
        await settings.load();

        expect(settings.marketing.value).toEqual({ push: false, sms: true, email: true });
        expect(settings.loaded.value).toBe(true);
        expect(settings.error.value).toBe(false);
    });

    it('reports a failed load without breaking the switches', async () => {
        notificationsApi.getSettings.mockRejectedValue(new Error('Network Error'));

        await settings.load();

        expect(settings.error.value).toBe(true);
        expect(settings.loaded.value).toBe(true);
        expect(settings.marketing.value.push).toBe(false);
    });

    it('sends only the switched channel and keeps the server answer', async () => {
        notificationsApi.updateMarketing.mockResolvedValue({ marketing: { push: false, sms: true, email: false } });

        expect(await settings.toggle('sms')).toBe(true);

        expect(notificationsApi.updateMarketing).toHaveBeenCalledWith({ sms: true });
        expect(settings.marketing.value.sms).toBe(true);
        expect(settings.savingChannel.value).toBeNull();
    });

    it('moves the switch at once and puts it back when the server refuses', async () => {
        let refuse;
        notificationsApi.updateMarketing.mockReturnValue(new Promise((_, reject) => { refuse = reject; }));

        const pending = settings.toggle('email');
        expect(settings.marketing.value.email).toBe(true);
        expect(settings.savingChannel.value).toBe('email');

        refuse(new Error('500'));
        expect(await pending).toBe(false);

        expect(settings.marketing.value.email).toBe(false);
        expect(settings.error.value).toBe(true);
    });

    it('ignores a second tap while saving and a channel that does not exist', async () => {
        notificationsApi.updateMarketing.mockReturnValue(new Promise(() => {}));

        settings.toggle('push');
        expect(await settings.toggle('sms')).toBe(false);
        expect(await settings.toggle('fax')).toBe(false);

        expect(notificationsApi.updateMarketing).toHaveBeenCalledTimes(1);
    });
});
