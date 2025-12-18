import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

export function initializeEcho(authToken) {
  if (echoInstance) {
    return echoInstance
  }

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: 'taskflow-key',
    cluster: 'mt1',
    wsHost: 'localhost',
    wsPort: 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'http://localhost:8080/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${authToken}`,
        Accept: 'application/json'
      }
    }
  })

  console.log('✅ Echo initialized successfully')
  return echoInstance
}

export function getEcho() {
  return echoInstance
}

export function disconnectEcho() {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
    console.log('🔌 Echo disconnected')
  }
}

export default {
  install(app) {
    app.config.globalProperties.$echo = echoInstance
    app.provide('echo', echoInstance)
  }
}
