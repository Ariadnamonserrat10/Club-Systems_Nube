import * as bootstrap from 'bootstrap'

export default defineNuxtPlugin(() => {
  window.bootstrap = bootstrap
})

declare global {
  interface Window {
    bootstrap: typeof bootstrap
  }
}
