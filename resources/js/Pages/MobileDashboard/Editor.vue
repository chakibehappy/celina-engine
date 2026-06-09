<template>
  <div class="app">
    <div class="palette-panel">
      <div class="toolbar">
        <h2>Palette</h2>
      </div>
      <div class="palette-group">
        <h3>Containers (Dropzones)</h3>
        <div 
          v-for="type in structuralTypes" 
          :key="type"
          class="palette-item"
          draggable="true"
          @dragstart="handlePaletteDragStart($event, type)"
        >
          <span class="material-symbols-outlined icon">chips</span>
          {{ type }}
        </div>

        <h3>Base Widgets</h3>
        <div 
          v-for="type in widgetTypes" 
          :key="type"
          class="palette-item"
          draggable="true"
          @dragstart="handlePaletteDragStart($event, type)"
        >
          <span class="material-symbols-outlined icon">widgets</span>
          {{ type }}
        </div>
      </div>
    </div>

    <div class="editor-panel">
      <div class="toolbar">
        <h2>Celina Engine Vue Runtime</h2>
        <button @click="reloadRuntime">
          Reload
        </button>
      </div>

      <textarea
        v-model="jsonText"
        class="json-editor"
      />
    </div>

    <div class="preview-panel">
      <div class="phone-frame">
        <div
          class="phone-screen"
          :style="{
            background: parsedData?.theme?.bg || '#FFFFFF'
          }"
          @dragover.prevent
          @drop.stop="handleRootDrop"
        >
          <template v-if="parsedData">
            <div class="layer-background">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'bg-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'background'"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>

            <div class="screen-scroll">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'main-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) == null"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>

            <div class="layer-root">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'root-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'root'"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>

            <div class="layer-header">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'header-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'header'"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>

            <div class="layer-floating">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'floating-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'floating'"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>

            <div class="layer-footer">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'footer-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'footer'"
                  :element="getElement(item)"
                  :path="[index]"
                />
              </template>
            </div>
          </template>

          <div
            v-else
            class="invalid-json"
          >
            Invalid JSON
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  reactive,
  computed,
  defineComponent,
  h,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| DRAG & DROP CENTRAL STATE
|--------------------------------------------------------------------------
*/
const structuralTypes = ['box-h', 'box-v', 'box-stack', 'box-banner', 'grid', 'card', 'items-scroller-h', 'data-form']
const widgetTypes = ['text', 'image', 'button', 'icon', 'input', 'image-picker', 'spacer', 'gesture', 'tab-menu']

// Holds references to what is currently flying mid-air during drag
const currentDragType = ref(null)      // 'new' or 'move'
const draggedPayload = ref(null)     // string (component type) or Array (source target json node index path)

function handlePaletteDragStart(event, type) {
  currentDragType.value = 'new'
  draggedPayload.value = type
  event.dataTransfer.effectAllowed = 'copy'
}

function handleNodeDragStart(event, path) {
  currentDragType.value = 'move'
  draggedPayload.value = path
  event.dataTransfer.effectAllowed = 'move'
}

// Global Injectable handlers to catch nesting actions triggered deep within child elements
provide('builderDragStart', handleNodeDragStart)
provide('builderDropNode', handleDropTarget)

function createBlueprintNode(type) {
  const node = {
    type: type,
    props: {},
    styles: { p: 12, gap: 8 }
  }

  // Set structural fallbacks
  if (structuralTypes.includes(type) || type === 'tab-menu') {
    node.children = []
  }
  // Setup sensible presentation defaults
  if (type === 'text') node.props.value = 'Text Node Item'
  if (type === 'button') node.props.value = 'Click Action'
  if (type === 'input') node.props.placeholder = 'Type here...'
  if (type === 'icon') node.props.name = 'home'
  if (type === 'image') node.props.url = 'https://picsum.photos/200/100'
  
  return node
}

function handleRootDrop(event) {
  if (!parsedData.value) return
  const data = JSON.parse(JSON.stringify(parsedData.value))
  
  if (currentDragType.value === 'new') {
    const newNode = createBlueprintNode(draggedPayload.value)
    data.content.push(newNode)
  } else if (currentDragType.value === 'move') {
    const sourcePath = draggedPayload.value
    const nodeToMove = removeNodeByPath(data.content, sourcePath)
    if (nodeToMove) {
      data.content.push(nodeToMove)
    }
  }
  
  jsonText.value = JSON.stringify(data, null, 2)
  clearDragState()
}

function handleDropTarget(targetPath, event) {
  if (!parsedData.value) return
  const data = JSON.parse(JSON.stringify(parsedData.value))

  let nodeToInsert = null

  if (currentDragType.value === 'new') {
    nodeToInsert = createBlueprintNode(draggedPayload.value)
  } else if (currentDragType.value === 'move') {
    const sourcePath = draggedPayload.value
    // Prevent dragging an element into itself or its own recursive subtree lineage
    if (isAncestorPath(sourcePath, targetPath)) {
      clearDragState()
      return
    }
    nodeToInsert = removeNodeByPath(data.content, sourcePath)
  }

  if (nodeToInsert) {
    insertNodeByPath(data.content, targetPath, nodeToInsert)
  }

  jsonText.value = JSON.stringify(data, null, 2)
  clearDragState()
}

function clearDragState() {
  currentDragType.value = null
  draggedPayload.value = null
}

/* Helper mutations to parse structural layout path routing array configurations */
function removeNodeByPath(tree, path) {
  let current = tree
  for (let i = 0; i < path.length - 1; i++) {
    current = current[path[i]].children
  }
  const indexToRemove = path[path.length - 1]
  return current.splice(indexToRemove, 1)[0]
}

function insertNodeByPath(tree, path, node) {
  let current = tree
  // Drill into structural wrapper container target node configurations
  for (let i = 0; i < path.length; i++) {
    if (i === path.length - 1) {
      if (!current[path[i]].children) current[path[i]].children = []
      current[path[i]].children.push(node)
      return
    }
    current = current[path[i]].children
  }
}

function isAncestorPath(source, target) {
  if (source.length > target.length) return false
  return source.every((val, idx) => target[idx] === val)
}

/*
|--------------------------------------------------------------------------
| GLOBAL STATES
|--------------------------------------------------------------------------
*/
const formValues = reactive({})
const overrideMap = reactive({})
const globalStates = reactive({})

/*
|--------------------------------------------------------------------------
| ICON MAP
|--------------------------------------------------------------------------
*/
const iconMap = {
  home: 'home', chat: 'chat', person: 'person', globe: 'public', menu: 'menu', back: 'arrow_back',
  chevron_right: 'chevron_right', expand_more: 'expand_more', qr: 'qr_code_scanner', wallet: 'account_balance_wallet',
  cart: 'shopping_cart', 'cart-outline': 'shopping_cart', store: 'storefront', receipt: 'receipt',
  inventory: 'inventory_2', sell: 'sell', visibility: 'visibility', visibility_off: 'visibility_off',
  payments: 'payments', smartphone: 'smartphone', badge: 'badge', notifications: 'notifications',
  search: 'search', edit_note: 'edit_note', account_tree: 'account_tree', people: 'groups',
  calendar: 'calendar_today', description: 'description', assignment: 'assignment', chart: 'bar_chart',
  campaign: 'campaign', sticky_note: 'sticky_note_2', settings: 'settings', article: 'article',
  help: 'help', shopping_bag: 'shopping_bag', add_box: 'add_box', confirmation_number: 'confirmation_number',
  add_circle: 'add_circle', delete: 'delete', close: 'close', tunai: 'payments', qr_code: 'qr_code',
  account_balance: 'account_balance', more_horiz: 'more_horiz', image: 'image'
}

/*
|--------------------------------------------------------------------------
| JSON
|--------------------------------------------------------------------------
*/
const jsonText = ref(`{
  "theme": {
    "bg": "#FFFFFF"
  },
  "content": [
    {
      "type": "box-v",
      "styles": { "p": 20, "bg": "#f1f5f9" },
      "children": [
        { "type": "text", "props": { "value": "Drag components here!" } }
      ]
    }
  ]
}`)

const parsedData = computed(() => {
  try { return JSON.parse(jsonText.value) }
  catch (e) { return null }
})

function reloadRuntime() {
  jsonText.value = jsonText.value
}
function getElement(item) {
  return item.portrait || item
}
function getLayer(element) {
  return element?.props?.layer || null
}

function injectData(node, data) {
  if (!node) return
  if (node.props) {
    Object.keys(node.props).forEach(key => {
      node.props[key] = String(node.props[key]).replace(/\{\{(.*?)\}\}/g, (_, k)=> data[k.trim()] || '')
    })
  }
  if (node.children) {
    node.children.forEach(child => injectData(child, data))
  }
}

/*
|--------------------------------------------------------------------------
| STYLE ENGINE
|--------------------------------------------------------------------------
*/
function styleObject(styles = {}) {
  const obj = {}
  if (styles.w) {
    if (styles.w === 'fill') obj.width = '100%'
    else if (String(styles.w).includes('%')) obj.width = styles.w
    else obj.width = styles.w + 'px'
  }
  if (styles.h) {
    if (styles.h === 'fill') obj.height = '100%'
    else if (String(styles.h).includes('%')) obj.height = styles.h
    else obj.height = styles.h + 'px'
  }
  if (styles.bg) obj.background = styles.bg
  if (styles.bgImage) {
    obj.backgroundImage = `url(${styles.bgImage})`; obj.backgroundSize = 'cover'; obj.backgroundPosition = 'center'
  }
  if (styles.color) obj.color = styles.color
  if (styles.fontSize) obj.fontSize = styles.fontSize + 'px'
  if (styles.bold === 'true') obj.fontWeight = '700'
  if (styles.lineHeight) obj.lineHeight = styles.lineHeight + 'px'

  if (styles.align === 'center') { obj.alignItems = 'center'; obj.textAlign = 'center' }
  if (styles.align === 'right') { obj.alignItems = 'flex-end'; obj.textAlign = 'right' }
  if (styles.align === 'left') { obj.alignItems = 'flex-start'; obj.textAlign = 'left' }

  if (styles.arrangement === 'center') obj.justifyContent = 'center'
  if (styles.arrangement === 'between') obj.justifyContent = 'space-between'
  if (styles.arrangement === 'around') obj.justifyContent = 'space-around'
  if (styles.arrangement === 'evenly') obj.justifyContent = 'space-evenly'

  if (styles.p) obj.padding = styles.p + 'px'
  if (styles.pt) obj.paddingTop = styles.pt + 'px'
  if (styles.pb) obj.paddingBottom = styles.pb + 'px'
  if (styles.pl) obj.paddingLeft = styles.pl + 'px'
  if (styles.pr) obj.paddingRight = styles.pr + 'px'

  if (styles.mt) obj.marginTop = styles.mt + 'px'
  if (styles.mb) obj.marginBottom = styles.mb + 'px'
  if (styles.ml) obj.marginLeft = styles.ml + 'px'
  if (styles.mr) obj.marginRight = styles.mr + 'px'

  if (styles.gap) obj.gap = styles.gap + 'px'
  if (styles.radius) obj.borderRadius = styles.radius + 'px'
  if (styles.border) obj.border = '1px solid ' + styles.border
  if (styles.weight) obj.flex = styles.weight

  if (styles.elevation) {
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }
  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0; const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }
  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') obj.overflowY = 'auto'

  if (styles.maxLines) {
    obj.display = '-webkit-box'; obj.webkitLineClamp = styles.maxLines; obj.webkitBoxOrient = 'vertical'; obj.overflow = 'hidden'
  }
  if (styles.absolute === 'true') {
    obj.position = 'absolute'
    if (styles.top != null) obj.top = styles.top + 'px'
    if (styles.left != null) obj.left = styles.left + 'px'
    if (styles.right != null) obj.right = styles.right + 'px'
    if (styles.bottom != null) obj.bottom = styles.bottom + 'px'
  }
  return obj
}

/*
|--------------------------------------------------------------------------
| RENDERER (DRAG & DROP READY COMPONENT WITH NESTING HIGHLIGHTS)
|--------------------------------------------------------------------------
*/
const Renderer = defineComponent({
  name: 'Renderer',
  props: {
    element: Object,
    form: Object,
    overrides: Object,
    parentActive: Boolean,
    path: Array // NEW: Array representing structural path inside JSON structure tree
  },
  setup(props, { attrs }) {
    const localForm = props.form || formValues
    const localOverride = props.overrides || overrideMap

    // Setup visual highlights on draggable item layers
    const isDragOver = ref(false)
    const onGlobalDragStart = inject('builderDragStart')
    const onGlobalDrop = inject('builderDropNode')

    function mergedProps() {
      const name = props.element.props?.name
      const override = name ? localOverride[name] : null
      return { ...(props.element.props || {}), ...(override?.props || {}) }
    }

    function mergedStyles() {
      const name = props.element.props?.name
      const override = name ? localOverride[name] : null
      return {
        ...(props.element.styles || {}),
        ...(props.parentActive ? props.element.activeStyles || {} : {}),
        ...(override?.styles || {})
      }
    }

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) => h(Renderer, {
          key: index,
          element: child,
          form: localForm,
          overrides: localOverride,
          parentActive: extra.parentActive,
          path: [...props.path, index] // Increments path structure down to children nesting trees
        })
      )
    }

    function applyControlElements(tabId) {
      const controls = props.element['control-elements']
      if (!controls) return
      controls.forEach(control => {
        const target = control['target-name']
        const config = control['on-values']?.[tabId]
        if (config) localOverride[target] = config
      })
    }

    const dynamicItems = ref([])
    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') return null

      // Injection of global properties ensuring drop highlights and drag event routing triggers work properly
      const dragAttributes = {
        class: [
          'draggable-node', 
          isDragOver.value ? 'drag-over-active' : ''
        ],
        draggable: 'true',
        onDragstart: (e) => {
          e.stopPropagation()
          onGlobalDragStart(e, props.path)
        },
        onDragover: (e) => {
          e.preventDefault()
          e.stopPropagation()
          isDragOver.value = true
        },
        onDragleave: (e) => {
          e.preventDefault()
          isDragOver.value = false
        },
        onDrop: (e) => {
          e.preventDefault()
          e.stopPropagation()
          isDragOver.value = false
          onGlobalDrop(props.path, e)
        }
      }

      /*
      |--------------------------------------------------------------------------
      | LAYOUT TEMPLATE IMPLEMENTATION MAPPINGS
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return h('div', {
          ...dragAttributes,
          style: { display: 'flex', flexDirection: 'row', width: '100%', boxSizing: 'border-box', ...styleObject(s) }
        }, renderChildren())
      }

      if (props.element.type === 'box-v') {
        return h('div', {
          ...dragAttributes,
          style: {
            display: 'flex', flexDirection: 'column',
            width: s.w === 'fill' ? '100%' : undefined,
            height: s.h === 'fill' ? '100%' : undefined,
            minHeight: s.h === 'fill' ? '100%' : undefined,
            boxSizing: 'border-box',
            position: s.absolute === 'true' ? 'absolute' : 'relative',
            ...styleObject(s)
          }
        }, renderChildren())
      }

      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return h('div', {
          ...dragAttributes,
          style: {
            position: 'relative', display: 'flex', flexDirection: 'column',
            width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
            height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
            minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
            backgroundImage: p.bgImage ? `url(${p.bgImage})` : undefined,
            backgroundSize: 'cover', backgroundPosition: 'center', backgroundRepeat: 'no-repeat', overflow: 'hidden',
            ...styleObject({ ...s, bgImage: null })
          }
        }, renderChildren())
      }

      if (props.element.type === 'box-banner') {
        return h('div', {
          ...dragAttributes,
          style: { position: 'relative', overflow: 'hidden', width: '100%', ...styleObject({ ...s, bgImage: p.bgImage }) }
        }, renderChildren())
      }

      if (props.element.type === 'data-form') {
        return h('div', {
          ...dragAttributes,
          style: { width: '100%', ...styleObject(s) }
        }, renderChildren())
      }

      if (props.element.type === 'text') {
        return h('div', {
          ...dragAttributes,
          style: { boxSizing: 'border-box', ...styleObject(s) }
        }, p.value || '')
      }

      if (props.element.type === 'image') {
        return h('img', {
          ...dragAttributes,
          src: p.url,
          style: { width: '100%', display: 'block', objectFit: 'cover', ...styleObject(s) }
        })
      }

      if (props.element.type === 'image-picker') {
        return h('label', {
          ...dragAttributes,
          style: { display: 'block', cursor: 'pointer' }
        }, [
          h('input', {
            type: 'file', accept: 'image/*', style: { display: 'none' },
            onChange: e => {
              const file = e.target.files[0]
              if (file) localForm[p.name] = URL.createObjectURL(file)
            }
          }),
          localForm[p.name] ? h('img', { src: localForm[p.name], style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' } })
          : h('div', { style: { height: '200px', border: '2px dashed #CBD5E1', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' } }, 'Tap to upload')
        ])
      }

      if (props.element.type === 'input') {
        return h('input', {
          ...dragAttributes,
          type: p.keyboardType === 'password' ? 'password' : 'text',
          value: localForm[p.name] || p.value || '',
          placeholder: p.placeholder || '',
          onInput: e => { localForm[p.name] = e.target.value },
          style: { border: 'none', outline: 'none', width: '100%', boxSizing: 'border-box', ...styleObject(s) }
        })
      }

      if (props.element.type === 'button') {
        return h('button', {
          ...dragAttributes,
          style: { border: 'none', cursor: 'pointer', ...styleObject(s) },
          onClick: () => {
            if (p.state_key) localForm[p.state_key] = p.set_value
            if (props.element.action?.target) alert('Navigate : ' + props.element.action.target)
          }
        }, p.value || 'Button')
      }

      if (props.element.type === 'icon') {
        return h('span', {
          ...dragAttributes,
          class: 'material-symbols-outlined',
          style: { fontSize: (s.size || 24) + 'px', display: 'flex', alignItems: 'center', justifyContent: 'center', ...styleObject(s) }
        }, iconMap[(p.name || '').toLowerCase()] || 'flash_on')
      }

      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return h('div', {
          ...dragAttributes,
          style: { display: 'grid', gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`, gap: (s.gapV || 8) + 'px', width: '100%' }
        }, renderChildren())
      }

      if (props.element.type === 'spacer') {
        return h('div', { ...dragAttributes, style: { ...styleObject(s) } })
      }

      if (props.element.type === 'card') {
        return h('div', {
          ...dragAttributes,
          style: { width: '100%', boxSizing: 'border-box', ...styleObject(s) }
        }, renderChildren())
      }

      if (props.element.type === 'items-scroller-h') {
        return h('div', {
          ...dragAttributes,
          style: { display: 'flex', overflowX: 'auto', overflowY: 'hidden', width: '100%', boxSizing: 'border-box', ...styleObject(s) }
        }, renderChildren())
      }

      if (props.element.type === 'gesture') {
        return h('div', {
          ...dragAttributes,
          style: { cursor: 'pointer', position: 'relative' },
          onClick: () => { if (p.state_key) localForm[p.state_key] = p.set_value }
        }, renderChildren())
      }

      if (props.element.type === 'tab-menu') {
        const stateKey = p.state_key || 'tab'
        if (!globalStates[stateKey]) {
          globalStates[stateKey] = p.initial_tab
          applyControlElements(p.initial_tab)
        }
        return h('div', {
          ...dragAttributes,
          style: { display: 'flex', width: '100%', ...styleObject(s) }
        }, props.element.children.map(child => {
          const tabId = child.props?.tab_id
          return h('div', {
            style: { flex: 1, cursor: 'pointer' },
            onClick: () => { globalStates[stateKey] = tabId; applyControlElements(tabId) }
          }, [
            h(Renderer, {
              element: child, form: localForm, overrides: localOverride,
              parentActive: globalStates[stateKey] === tabId,
              path: props.path
            })
          ])
        }))
      }

      if (props.element.type === 'bottom-drawer') {
        const isOpen = localForm[p.state_key] === 'true'
        if (!isOpen) return null
        return h('div', {
          ...dragAttributes,
          style: {
            position: 'absolute', left: 0, right: 0, bottom: 0, zIndex: 999, background: '#FFF',
            borderTopLeftRadius: '24px', borderTopRightRadius: '24px', boxShadow: '0 -10px 40px rgba(0,0,0,0.2)', ...styleObject(s)
          }
        }, renderChildren())
      }

      return h('div', { style: { color: 'red', fontSize: '12px', padding: '4px' } }, `UNKNOWN TYPE : ${props.element.type}`)
    }
  }
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
}

.app {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: #0f172a;
  font-family: Inter, sans-serif;
}

/* NEW: PALETTE LAYOUT STYLES */
.palette-panel {
  width: 260px;
  min-width: 260px;
  height: 100%;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
  background: #090d16;
}

.palette-group {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.palette-group h3 {
  color: #64748b;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 16px 0 8px 0;
}

.palette-item {
  background: #1e293b;
  color: #e2e8f0;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 8px;
  cursor: grab;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  user-select: none;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}

.palette-item:hover {
  background: #334155;
  border-color: #3b82f6;
}

.palette-item .icon {
  font-size: 16px;
  color: #94a3b8;
}

/* DRAGGABLE ON-CANVAS INTERACTIVE HIGHLIGHT OUTLINES */
:deep(.draggable-node) {
  position: relative;
  transition: outline 0.15s dashed;
}

:deep(.draggable-node:hover) {
  outline: 1px dashed rgba(59, 130, 246, 0.5) !important;
}

:deep(.drag-over-active) {
  outline: 2px solid #3b82f6 !important;
  background-color: rgba(59, 130, 246, 0.08) !important;
}

.editor-panel {
  width: 35%;
  height: 100%;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
  background: #020617;
}

.toolbar {
  height: 60px;
  min-height: 60px;
  border-bottom: 1px solid #1e293b;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  color: white;
}

.toolbar h2 {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 38px;
  padding: 0 16px;
  border-radius: 10px;
  cursor: pointer;
}

.json-editor {
  flex: 1;
  width: 100%;
  background: #020617;
  color: #e2e8f0;
  border: none;
  outline: none;
  resize: none;
  padding: 20px;
  font-size: 13px;
  font-family: monospace;
  line-height: 1.6;
}

.preview-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: auto;
  background: radial-gradient(circle at top, #1e3a8a, #0f172a);
}

.phone-frame {
  width: 390px;
  height: 844px;
  background: black;
  border-radius: 40px;
  padding: 12px;
  box-shadow: 0 20px 80px rgba(0,0,0,0.5);
}

.phone-screen {
  width: 100%;
  height: 100%;
  border-radius: 32px;
  overflow: hidden;
  position: relative;
  isolation: isolate;
}

.screen-scroll {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  overflow-x: auto;
  z-index: 2;
}

.layer-background {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 0;
}

.layer-background > * {
  width: 100%;
  height: 100%;
}

.layer-root {
  position: absolute;
  inset: 0;
  z-index: 5;
  pointer-events: none;
}

.layer-root > * {
  pointer-events: auto;
}

.layer-header {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 10;
}

.layer-floating {
  position: absolute;
  inset: 0;
  z-index: 20;
  pointer-events: none;
}

.layer-floating > * {
  pointer-events: auto;
}

.layer-footer {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  z-index: 15;
}

.invalid-json {
  color: red;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

.material-symbols-outlined {
  user-select: none;
  flex-shrink: 0;
}

.screen-scroll::-webkit-scrollbar {
  display: none;
}

@keyframes drawerUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
</style>