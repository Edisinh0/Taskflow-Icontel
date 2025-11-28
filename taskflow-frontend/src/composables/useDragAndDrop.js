import { onMounted, onUnmounted } from 'vue'
import Sortable from 'sortablejs'

export function useDragAndDrop(elementRef, options = {}) {
  let sortableInstance = null

  onMounted(() => {
    if (!elementRef.value) return

    sortableInstance = new Sortable(elementRef.value, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.drag-handle', // Solo arrastrar desde este elemento
      disabled: options.disabled || false,
      onEnd: (evt) => {
        if (options.onEnd) {
          options.onEnd(evt)
        }
      }
    })
  })

  onUnmounted(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
  })

  return {
    sortableInstance
  }
}