<template>
  <div>
    <div ref="mapContainer" style="height: 450px; width: 100%; border-radius: 0.75rem; border: 1px solid rgba(156,163,175,0.3);"></div>
    <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
      <button @click="startDrawing" type="button" class="btn btn-warning" style="background-color: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; border: none; cursor: pointer;">Нарисовать зону</button>
      <button @click="editPoints" type="button" class="btn btn-gray" style="background-color: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; border: none; cursor: pointer;">Редактировать</button>
      <button @click="clearMap" type="button" class="btn btn-danger" style="background-color: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; border: none; cursor: pointer;">Очистить</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps(['initialValue'])
const emit = defineEmits(['update:modelValue'])

const mapContainer = ref(null)
let map = null
let polygon = null
let drawingManager = null

onMounted(() => {
  if (typeof ymaps === 'undefined') {
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=${window.YANDEX_MAPS_KEY}`
    script.onload = initMap
    document.head.appendChild(script)
  } else {
    initMap()
  }
})

onBeforeUnmount(() => {
  if (map) map.destroy()
})

function initMap() {
  ymaps.ready(() => {
    map = new ymaps.Map(mapContainer.value, {
      center: [39.0886, 63.5593],
      zoom: 12,
      controls: ['zoomControl', 'fullscreenControl']
    })

    if (props.initialValue) {
      loadFromState(props.initialValue)
    }
  })
}

function createPolygon(coords) {
  const p = new ymaps.Polygon(coords, { hintContent: 'Зона доставки' }, {
    fillColor: '#FF6B3555',
    strokeColor: '#FF6B35',
    strokeWidth: 3,
    editorDrawingCursor: 'crosshair'
  })

  // ПАТЧ YANDEX MAPS:
  // Предотвращаем краш `o.calculate is not a function` при добавлении пустого полигона.
  if (p.geometry && p.geometry.getBounds) {
    const origGetBounds = p.geometry.getBounds;
    p.geometry.getBounds = function() {
      const c = this.getCoordinates();
      if (!c || c.length === 0 || !c[0] || c[0].length === 0) {
        return null;
      }
      return origGetBounds.apply(this, arguments);
    };
  }

  p.events.add('geometrychange', () => {
    const allContours = p.geometry.getCoordinates()
    if (!allContours?.[0] || allContours[0].length < 3) {
      emit('update:modelValue', null)
      return
    }
    const coordinates = allContours[0]
    const geojsonCoords = coordinates.map(pt => [pt[1], pt[0]])
    const first = geojsonCoords[0]
    const last = geojsonCoords[geojsonCoords.length - 1]
    if (first[0] !== last[0] || first[1] !== last[1]) {
      geojsonCoords.push([first[0], first[1]])
    }
    emit('update:modelValue', JSON.stringify({
      type: 'MultiPolygon',
      coordinates: [[geojsonCoords]]
    }))
  })

  return p
}

function loadFromState(state) {
  try {
    const data = typeof state === 'string' ? JSON.parse(state) : state
    let coords = []
    if (data?.type === 'MultiPolygon' && data.coordinates?.[0]) {
      coords = data.coordinates[0][0].map(p => [p[1], p[0]])
    } else if (data?.type === 'Polygon' && data.coordinates?.[0]) {
      coords = data.coordinates[0].map(p => [p[1], p[0]])
    }
    if (coords.length > 0) {
      polygon = createPolygon([coords])
      map.geoObjects.add(polygon)
      setTimeout(() => {
        const bounds = polygon.geometry.getBounds()
        if (bounds) map.setBounds(bounds)
      }, 100)
    }
  } catch (e) {
    console.error('Failed to parse delivery zone', e)
  }
}

function startDrawing() {
  if (!map) return

  // Полностью удаляем старый полигон
  if (polygon) {
    try { polygon.editor.stopDrawing() } catch(e) {}
    try { polygon.editor.stopEditing() } catch(e) {}
    if (polygon.getMap()) map.geoObjects.remove(polygon)
  }

  // Создаем чистый полигон для рисования
  polygon = createPolygon([])
  map.geoObjects.add(polygon)
  
  // Даем Yandex Maps время на инициализацию проекции
  setTimeout(() => {
    if (polygon) {
      polygon.editor.startDrawing()
    }
  }, 150)
}

function editPoints() {
  if (polygon?.getMap()) {
    polygon.editor.startEditing()
  }
}

function clearMap() {
  if (!confirm('Очистить зону доставки?')) return
  if (polygon) {
    try { polygon.editor.stopDrawing() } catch(e) {}
    try { polygon.editor.stopEditing() } catch(e) {}
    if (polygon.getMap()) map.geoObjects.remove(polygon)
    polygon = null
  }
  if (drawingManager) {
    try { drawingManager.stopDrawing() } catch(e) {}
    drawingManager = null
  }
  emit('update:modelValue', null)
}
</script>
