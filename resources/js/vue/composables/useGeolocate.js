import { ref, readonly } from 'vue'

const DEFAULT_TIMEOUT = 8000
const DEFAULT_MAX_AGE = 60000

export function useGeolocate(options = {}) {
  const timeout = options.timeout ?? DEFAULT_TIMEOUT
  const maximumAge = options.maximumAge ?? DEFAULT_MAX_AGE
  const enabled = options.enabled ?? true

  const status = ref('idle')
  const coords = ref(null)
  const accuracy = ref(null)
  const error = ref(null)

  let pending = null

  function request() {
    if (!enabled) return Promise.reject(new Error('Geolocation disabled'))
    if (!navigator.geolocation) {
      status.value = 'unavailable'
      error.value = 'Geolocation API not available'
      return Promise.reject(new Error(error.value))
    }

    if (pending) return pending

    status.value = 'loading'
    error.value = null

    pending = new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude, accuracy: acc } = position.coords
          coords.value = { lat: latitude, lon: longitude }
          accuracy.value = acc
          status.value = 'success'
          pending = null
          resolve({ lat: latitude, lon: longitude, accuracy: acc })
        },
        (err) => {
          let code = 'error'
          if (err.code === err.PERMISSION_DENIED) code = 'denied'
          else if (err.code === err.TIMEOUT) code = 'timeout'
          else if (err.code === err.POSITION_UNAVAILABLE) code = 'unavailable'

          status.value = code
          error.value = err.message
          pending = null
          reject({ code, message: err.message })
        },
        { enableHighAccuracy: true, timeout, maximumAge },
      )
    })

    return pending
  }

  function retry() {
    pending = null
    return request()
  }

  function reset() {
    status.value = 'idle'
    coords.value = null
    accuracy.value = null
    error.value = null
    pending = null
  }

  return {
    status: readonly(status),
    coords: readonly(coords),
    accuracy: readonly(accuracy),
    error: readonly(error),
    request,
    retry,
    reset,
  }
}
