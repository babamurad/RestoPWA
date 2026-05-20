const SESSION_ID = self.crypto?.randomUUID?.() || Date.now().toString(36)

export function useTelemetry() {
  function track(event, params = {}) {
    const payload = {
      event,
      session_id: SESSION_ID,
      timestamp: new Date().toISOString(),
      ...params,
    }

    if (import.meta.env.DEV) {
      console.debug('[Telemetry]', payload)
    }

    try {
      fetch('/api/v1/telemetry', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true,
      })
    } catch {
      // silently fail
    }
  }

  return { track }
}
