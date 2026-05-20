import { ref, readonly } from 'vue'
import apiClient from '../api/client'

/**
 * Composable for delivery zone check and reverse geocoding.
 * Both calls are debounced to avoid hammering the API on every map move.
 */
export function useZoneCheck() {
  const zoneStatus   = ref('idle')   // idle | loading | inside | outside | zone_missing | error
  const zoneMessage  = ref(null)
  const reverseAddr  = ref(null)     // human-readable address from reverse geocode
  const confidence   = ref(null)     // high | medium | low | null

  let zoneTimer   = null
  let reverseTimer = null

  /**
   * Check delivery zone with debounce (default 600ms).
   * @param {string} vendorId
   * @param {number} lat
   * @param {number} lon
   * @param {number} debounceMs
   */
  function checkZone(vendorId, lat, lon, debounceMs = 600) {
    clearTimeout(zoneTimer)

    if (!vendorId || lat == null || lon == null) return

    zoneTimer = setTimeout(async () => {
      zoneStatus.value  = 'loading'
      zoneMessage.value = null

      try {
        const { data } = await apiClient.post('/geo/zone-check', { vendor_id: vendorId, lat, lon })
        const payload = data?.data ?? data

        zoneStatus.value  = payload.status  ?? (payload.in_zone ? 'inside' : 'outside')
        zoneMessage.value = payload.message ?? null
      } catch {
        zoneStatus.value  = 'error'
        zoneMessage.value = 'Не удалось проверить зону доставки.'
      }
    }, debounceMs)
  }

  /**
   * Reverse geocode coordinates with debounce (default 800ms).
   * @param {number} lat
   * @param {number} lon
   * @param {number} debounceMs
   */
  function reverseGeocode(lat, lon, debounceMs = 800) {
    clearTimeout(reverseTimer)

    if (lat == null || lon == null) return

    reverseTimer = setTimeout(async () => {
      try {
        const { data } = await apiClient.post('/geo/reverse', { lat, lon })
        const payload = data?.data ?? data

        reverseAddr.value = payload.address  ?? null
        confidence.value  = payload.confidence ?? null
      } catch {
        reverseAddr.value = null
        confidence.value  = null
      }
    }, debounceMs)
  }

  function reset() {
    clearTimeout(zoneTimer)
    clearTimeout(reverseTimer)
    zoneStatus.value  = 'idle'
    zoneMessage.value = null
    reverseAddr.value = null
    confidence.value  = null
  }

  return {
    zoneStatus:  readonly(zoneStatus),
    zoneMessage: readonly(zoneMessage),
    reverseAddr: readonly(reverseAddr),
    confidence:  readonly(confidence),
    checkZone,
    reverseGeocode,
    reset,
  }
}
