<template>
  <div class="app">

    <div class="palette-panel">
      <div class="palette-header">
        <h3>Component Palette</h3>
        <span>Drag items into the preview canvas</span>
      </div>
      <div class="palette-list">
        <div 
          v-for="comp in paletteComponents" 
          :key="comp.type"
          class="palette-item"
          draggable="true"
          @dragstart="onPaletteDragStart($event, comp.type)"
        >
          <span class="material-symbols-outlined palette-icon">{{ comp.icon }}</span>
          <div class="palette-item-info">
            <span class="palette-item-name">{{ comp.label }}</span>
            <span class="palette-item-desc">{{ comp.desc }}</span>
          </div>
        </div>
      </div>

      <div class="clipboard-status" v-if="clipboardNode">
        <div class="status-indicator">
          <span class="material-symbols-outlined">assignment_turned_in</span>
          <div>
            <p>Component Copied</p>
            <span>Type: <b>{{ clipboardNode.type }}</b></span>
          </div>
        </div>
        <button class="clear-clip-btn" @click="clipboardNode = null">Clear</button>
      </div>
    </div>

    <div class="editor-panel">
      <div class="toolbar">
        <h2>Celina Engine Vue Runtime</h2>
        <button @click="reloadRuntime">Reload</button>
      </div>
      <textarea
        v-model="jsonText"
        class="json-editor"
        spellcheck="false"
        placeholder="{ ... }"
      />
    </div>

    <div class="preview-panel">
      <div class="phone-frame">
        <div
          class="phone-screen"
          :style="{ background: parsedData?.theme?.bg || '#FFFFFF' }"
        >
          <template v-if="parsedData">

            <div 
              class="layer-background"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'background')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'bg-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'background'"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

            <div 
              class="screen-scroll"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, null)"
            >
              <template v-for="(item, index) in parsedData.content" :key="'main-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) == null"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

            <div 
              class="layer-root"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'root')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'root-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'root'"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

            <div 
              class="layer-header"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'header')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'header-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'header'"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

            <div 
              class="layer-floating"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'floating')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'floating-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'floating'"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

            <div 
              class="layer-footer"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'footer')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'footer-' + index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'footer'"
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

          </template>

          <div v-else class="invalid-json">
            Invalid JSON Architecture
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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| DRAGGABLE COMPONENTS CONFIGURATIONS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-v', label: 'Vertical Box', icon: 'view_stream', desc: 'Linear column layout container' },
  { type: 'box-h', label: 'Horizontal Box', icon: 'view_column', desc: 'Linear row container layout' },
  { type: 'box-stack', label: 'Stack Box', icon: 'layers', desc: 'Relative overlap layer canvas' },
  { type: 'text', label: 'Text Field', icon: 'text_fields', desc: 'Custom configured label view' },
  { type: 'image', label: 'Image Content', icon: 'image', desc: 'Standard absolute web image block' },
  { type: 'button', label: 'Interactive Action', icon: 'smart_button', desc: 'Trigger event handler action item' },
  { type: 'icon', label: 'Material Symbol', icon: 'star', desc: 'Vector inline typography asset' },
  { type: 'input', label: 'Input Capture', icon: 'input', desc: 'Form field text capturing frame' },
  { type: 'spacer', label: 'Layout Spacer', icon: 'space_bar', desc: 'Elastic gap filler block helper' }
]

function makeDefaultComponentObject(type) {
  const node = { type, props: {}, styles: {} }
  if (['box-v', 'box-h', 'box-stack'].includes(type)) {
    node.children = []
    node.styles.p = 16
    node.styles.gap = 10
    node.styles.bg = 'rgba(255, 255, 255, 0.05)'
  } else if (type === 'text') {
    node.props.value = 'Custom Dynamic Text Node'
    node.styles.color = '#1e293b'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Action Item'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 12
    node.styles.radius = 8
    node.styles.align = 'center'
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
    node.styles.color = '#2563eb'
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 140
    node.styles.radius = 8
  } else if (type === 'input') {
    node.props.placeholder = 'Type input data...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 10
    node.styles.border = '#CBD5E1'
    node.styles.radius = 8
    node.styles.bg = '#ffffff'
  } else if (type === 'spacer') {
    node.styles.h = 20
  }
  return node
}

/*
|--------------------------------------------------------------------------
| LOCAL STORAGE ENGINE RUNTIME VARIABLES
|--------------------------------------------------------------------------
*/
const formValues = reactive({})
const overrideMap = reactive({})
const globalStates = reactive({})
const clipboardNode = ref(null)

const iconMap = {
  home: 'home', chat: 'chat', person: 'person', globe: 'public', menu: 'menu',
  back: 'arrow_back', chevron_right: 'chevron_right', expand_more: 'expand_more',
  qr: 'qr_code_scanner', wallet: 'account_balance_wallet', cart: 'shopping_cart',
  'cart-outline': 'shopping_cart', store: 'storefront', receipt: 'receipt',
  inventory: 'inventory_2', sell: 'sell', visibility: 'visibility',
  visibility_off: 'visibility_off', payments: 'payments', smartphone: 'smartphone',
  badge: 'badge', notifications: 'notifications', search: 'search',
  edit_note: 'edit_note', account_tree: 'account_tree', people: 'groups',
  calendar: 'calendar_today', description: 'description', assignment: 'assignment',
  chart: 'bar_chart', campaign: 'campaign', sticky_note: 'sticky_note_2',
  settings: 'settings', article: 'article', help: 'help', shopping_bag: 'shopping_bag',
  add_box: 'add_box', confirmation_number: 'confirmation_number', add_circle: 'add_circle',
  delete: 'delete', close: 'close', tunai: 'payments', qr_code: 'qr_code',
  account_balance: 'account_balance', more_horiz: 'more_horiz', image: 'image'
}

/*
|--------------------------------------------------------------------------
| COMPACT WORKSPACE DEMO DATA STRUCTURING
|--------------------------------------------------------------------------
*/
const jsonText = ref(`{
  "theme": {
    "bg": "#f8fafc"
  },
  "content": [
    {
      "type": "box-v",
      "styles": {
        "p": 16,
        "gap": 12,
        "bg": "#ffffff",
        "radius": 16,
        "mt": 20,
        "ml": 16,
        "mr": 16,
        "elevation": 2
      },
      "children": [
        {
          "type": "text",
          "props": {
            "value": "Interactive Workspace Canvas"
          },
          "styles": {
            "color": "#0f172a",
            "fontSize": 18,
            "bold": "true"
          }
        },
        {
          "type": "text",
          "props": {
            "value": "You can drag layout structures into here, rearrange them via cross-nesting, or copy/paste/delete using actions."
          },
          "styles": {
            "color": "#64748b",
            "fontSize": 12
          }
        }
      ]
    }
  ]
}`)

const parsedData = computed(() => {
  try { return JSON.parse(jsonText.value) } catch (e) { return null }
})

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  jsonText.value = JSON.stringify({ ...parsedData.value, content: updatedContent }, null, 2)
}

/*
|--------------------------------------------------------------------------
| RECURSIVE DEEP NODE MUTATORS BASED ON TREE PATHS
|--------------------------------------------------------------------------
*/
function findNodeByPath(rootArr, pathArr) {
  let layer = rootArr
  for (let i = 0; i < pathArr.length; i++) {
    const targetIdx = pathArr[i]
    if (i === pathArr.length - 1) return layer[targetIdx]
    layer = layer[targetIdx].children
  }
  return null
}

function removeNodeByPath(rootArr, pathArr) {
  const resultTree = JSON.parse(JSON.stringify(rootArr))
  let layer = resultTree
  for (let i = 0; i < pathArr.length; i++) {
    const targetIdx = pathArr[i]
    if (i === pathArr.length - 1) {
      layer.splice(targetIdx, 1)
    } else {
      layer = layer[targetIdx].children
    }
  }
  return resultTree
}

function insertNodeByPath(rootArr, pathArr, nodePayload) {
  const resultTree = JSON.parse(JSON.stringify(rootArr))
  let layer = resultTree
  for (let i = 0; i < pathArr.length; i++) {
    const targetIdx = pathArr[i]
    if (i === pathArr.length - 1) {
      layer.splice(targetIdx, 0, nodePayload)
    } else {
      layer = layer[targetIdx].children
    }
  }
  return resultTree
}

/*
|--------------------------------------------------------------------------
| PALETTE & BASE LAYERS DRAG/DROP IMPLEMENTATIONS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(e, compType) {
  e.dataTransfer.setData('application/json-celina-engine', JSON.stringify({ mode: 'EXTERNAL_NEW', type: compType }))
}

function onRootLayerDrop(e, layerName) {
  try {
    const transitRaw = e.dataTransfer.getData('application/json-celina-engine')
    if (!transitRaw) return
    const packet = JSON.parse(transitRaw)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let payload = null
    if (packet.mode === 'EXTERNAL_NEW') {
      payload = makeDefaultComponentObject(packet.type)
      if (layerName) payload.props.layer = layerName
    } else if (packet.mode === 'INTERNAL_MOVE') {
      payload = findNodeByPath(mutableContent, packet.sourcePath)
      mutableContent = removeNodeByPath(mutableContent, packet.sourcePath)
      if (layerName) {
        payload.props = payload.props || {}
        payload.props.layer = layerName
      } else if (payload.props?.layer) {
        delete payload.props.layer
      }
    }

    if (payload) {
      mutableContent.push(payload)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

/*
|--------------------------------------------------------------------------
| INJECT GLOBAL PROVIDER TREE HANDLERS FOR THE NESTED RENDERERS
|--------------------------------------------------------------------------
*/
provide('celinaGlobalDragContext', {
  onNodeDragStart: (e, sourcePath) => {
    e.dataTransfer.setData('application/json-celina-engine', JSON.stringify({ mode: 'INTERNAL_MOVE', sourcePath }))
  },
  onNodeDrop: (e, destPath, alignmentZone) => {
    try {
      const transitRaw = e.dataTransfer.getData('application/json-celina-engine')
      if (!transitRaw) return
      const packet = JSON.parse(transitRaw)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))
      let payload = null

      if (packet.mode === 'EXTERNAL_NEW') {
        payload = makeDefaultComponentObject(packet.type)
      } else if (packet.mode === 'INTERNAL_MOVE') {
        const sStr = JSON.stringify(packet.sourcePath)
        const dStr = JSON.stringify(destPath)
        
        // Block cyclic containment anomalies
        if (dStr.startsWith(sStr.slice(0, -1))) {
          if (dStr === sStr || dStr.startsWith(sStr.replace(']', '') + ',')) {
            alert("Structural Constraint: A container cannot be nested into itself or its own sub-children branches.")
            return
          }
        }

        payload = findNodeByPath(mutableContent, packet.sourcePath)
        mutableContent = removeNodeByPath(mutableContent, packet.sourcePath)

        // Offset path balancing logic if source removal shifts destination placement array layers
        if (packet.sourcePath.length === destPath.length) {
          const matchingParents = packet.sourcePath.slice(0, -1).join(',') === destPath.slice(0, -1).join(',')
          if (matchingParents && packet.sourcePath[packet.sourcePath.length - 1] < destPath[destPath.length - 1]) {
            destPath[destPath.length - 1]--
          }
        }
      }

      if (payload) {
        if (alignmentZone === 'INNER_APPEND') {
          let containerNode = findNodeByPath(mutableContent, destPath)
          if (containerNode) {
            containerNode.children = containerNode.children || []
            containerNode.children.push(payload)
          }
        } else if (alignmentZone === 'BEFORE') {
          mutableContent = insertNodeByPath(mutableContent, destPath, payload)
        } else if (alignmentZone === 'AFTER') {
          const nextIndexTarget = [...destPath]
          nextIndexTarget[nextIndexTarget.length - 1]++
          mutableContent = insertNodeByPath(mutableContent, nextIndexTarget, payload)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
  },
  executeAction: (actionType, targetPath) => {
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))
    
    if (actionType === 'DELETE') {
      mutableContent = removeNodeByPath(mutableContent, targetPath)
      syncJsonTree(mutableContent)
    } else if (actionType === 'COPY') {
      const copiedTarget = findNodeByPath(mutableContent, targetPath)
      if (copiedTarget) {
        clipboardNode.value = JSON.parse(JSON.stringify(copiedTarget))
      }
    } else if (actionType === 'PASTE') {
      if (!clipboardNode.value) return
      const pastePayload = JSON.parse(JSON.stringify(clipboardNode.value))
      let targetNode = findNodeByPath(mutableContent, targetPath)
      
      if (targetNode && ['box-v', 'box-h', 'box-stack'].includes(targetNode.type)) {
        targetNode.children = targetNode.children || []
        targetNode.children.push(pastePayload)
      } else {
        const lateralPath = [...targetPath]
        lateralPath[lateralPath.length - 1]++
        mutableContent = insertNodeByPath(mutableContent, lateralPath, pastePayload)
      }
      syncJsonTree(mutableContent)
    }
  },
  canPaste: () => computed(() => !!clipboardNode.value).value
})

/*
|--------------------------------------------------------------------------
| RUNTIME RENDERING AND UTILITY HELPERS
|--------------------------------------------------------------------------
*/
function reloadRuntime() {
  jsonText.value = jsonText.value
}

function getElement(item) { return item.portrait || item }
function getLayer(element) { return element?.props?.layer || null }

function injectData(node, data) {
  if (!node) return
  if (node.props) {
    Object.keys(node.props).forEach(k => {
      node.props[k] = String(node.props[k]).replace(/\{\{(.*?)\}\}/g, (_, inner) => data[inner.trim()] || '')
    })
  }
  if (node.children) node.children.forEach(c => injectData(c, data))
}

/*
|--------------------------------------------------------------------------
| DESIGN STYLE OBJECT CONVERSION GENERATOR ENGINE
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
    obj.backgroundImage = `url(${styles.bgImage})`
    obj.backgroundSize = 'cover'
    obj.backgroundPosition = 'center'
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
    const ev = Number(styles.elevation)
    obj.boxShadow = `0 ${ev * 2}px ${ev * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    obj.transform = `translate(${styles.offsetX || 0}px,${styles.offsetY || 0}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') obj.overflowY = 'auto'

  if (styles.maxLines) {
    obj.display = '-webkit-box'
    obj.webkitLineClamp = styles.maxLines
    obj.webkitBoxOrient = 'vertical'
    obj.overflow = 'hidden'
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
| DYNAMIC RECURSIVE LAYOUT SYSTEM ENGINE CORE COMPONENT
|--------------------------------------------------------------------------
*/
const Renderer = defineComponent({
  name: 'Renderer',
  props: {
    element: Object,
    form: Object,
    overrides: Object,
    parentActive: Boolean,
    path: Array
  },
  inject: ['celinaGlobalDragContext'],
  setup(props) {
    const localForm = props.form || formValues
    const localOverride = props.overrides || overrideMap

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

    function renderChildren(extra = {}) {
      if (!props.element.children) return null
      return props.element.children.map((child, index) =>
        h(Renderer, {
          key: index,
          element: child,
          form: localForm,
          overrides: localOverride,
          parentActive: extra.parentActive,
          path: [...props.path, index]
        })
      )
    }

    function applyControlElements(tabId) {
      const controls = props.element['control-elements']
      if (!controls) return
      controls.forEach(control => {
        const target = control['target-name']
        const config = control['on-values']?.[tabId]
        if (config) { localOverride[target] = config }
      })
    }

    const dynamicItems = ref([])
    onMounted(async () => {
      if (props.element['data-source']) {
        try {
          const response = await fetch(props.element['data-source'])
          dynamicItems.value = await response.json()
        } catch (e) { console.log(e) }
      }
    })

    /*
    |--------------------------------------------------------------------------
    | NATIVE BOUNDARY DROP-ZONE ALIGNMENT CALCULATIONS
    |--------------------------------------------------------------------------
    */
    function wrapCanvasInteractiveZone(type, nodeSchema, nodeContents) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack'].includes(type)
      const hasClipboardPayload = props.celinaGlobalDragContext.canPaste()

      // Interactive Control Elements Box Overlay
      const actionBadgeControls = h('div', { class: 'canvas-element-actions' }, [
        h('span', { class: 'action-badge type-badge' }, type),
        h('button', { 
          class: 'action-btn copy-btn', 
          title: 'Copy Node Template',
          onClick: (e) => { e.stopPropagation(); props.celinaGlobalDragContext.executeAction('COPY', props.path) } 
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_copy')]),
        
        isStructureContainer ? h('button', { 
          class: `action-btn paste-btn ${!hasClipboardPayload ? 'disabled' : ''}`, 
          title: 'Paste Node Inside Content Layer',
          disabled: !hasClipboardPayload,
          onClick: (e) => { e.stopPropagation(); props.celinaGlobalDragContext.executeAction('PASTE', props.path) } 
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_paste')]) : null,
        
        h('button', { 
          class: 'action-btn delete-btn', 
          title: 'Remove Node from Blueprint',
          onClick: (e) => { e.stopPropagation(); props.celinaGlobalDragContext.executeAction('DELETE', props.path) } 
        }, [h('span', { class: 'material-symbols-outlined' }, 'delete')])
      ])

      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          onDragstart: (e) => {
            e.stopPropagation()
            props.celinaGlobalDragContext.onNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            
            const frameBox = e.currentTarget.getBoundingClientRect()
            const relativeOffsetMouseY = e.clientY - frameBox.top
            
            if (isStructureContainer) {
              if (relativeOffsetMouseY < frameBox.height * 0.20) {
                props.celinaGlobalDragContext.onNodeDrop(e, props.path, 'BEFORE')
              } else if (relativeOffsetMouseY > frameBox.height * 0.80) {
                props.celinaGlobalDragContext.onNodeDrop(e, props.path, 'AFTER')
              } else {
                props.celinaGlobalDragContext.onNodeDrop(e, props.path, 'INNER_APPEND')
              }
            } else {
              if (relativeOffsetMouseY > frameBox.height * 0.5) {
                props.celinaGlobalDragContext.onNodeDrop(e, props.path, 'AFTER')
              } else {
                props.celinaGlobalDragContext.onNodeDrop(e, props.path, 'BEFORE')
              }
            }
          }
        },
        [
          actionBadgeControls,
          h(nodeSchema.tag, nodeSchema.attrs, nodeContents)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') return null

      // Horizontal layout container
      if (props.element.type === 'box-h') {
        return wrapCanvasInteractiveZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '50px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      // Vertical layout row alignment block container
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return wrapCanvasInteractiveZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '50px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
            const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
            injectData(cloned, item)
            return h(Renderer, { element: cloned, form: localForm, overrides: localOverride, path: props.path })
          }))
        }

        return wrapCanvasInteractiveZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '50px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      // Stack overlap element rendering engine layer
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return wrapCanvasInteractiveZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '50px'),
              backgroundImage: p.bgImage ? `url(${p.bgImage})` : undefined,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
              backgroundRepeat: 'no-repeat',
              overflow: 'hidden',
              outline: '1px dashed rgba(168, 85, 247, 0.25)',
              ...styleObject({ ...s, bgImage: null })
            }
          }
        }, renderChildren())
      }

      if (props.element.type === 'box-banner') {
        return wrapCanvasInteractiveZone('box-banner', {
          tag: 'div',
          attrs: { style: { position: 'relative', overflow: 'hidden', width: '100%', ...styleObject({ ...s, bgImage: p.bgImage }) } }
        }, renderChildren())
      }

      if (props.element.type === 'data-form') {
        return wrapCanvasInteractiveZone('data-form', {
          tag: 'div',
          attrs: { style: { width: '100%', ...styleObject(s) } }
        }, renderChildren())
      }

      if (props.element.type === 'text') {
        return wrapCanvasInteractiveZone('text', {
          tag: 'div',
          attrs: { style: { boxSizing: 'border-box', minHeight: '20px', ...styleObject(s) } }
        }, p.value || '')
      }

      if (props.element.type === 'image') {
        return wrapCanvasInteractiveZone('image', {
          tag: 'img',
          attrs: { src: p.url, style: { width: '100%', display: 'block', objectFit: 'cover', minHeight: '40px', ...styleObject(s) } }
        }, null)
      }

      if (props.element.type === 'image-picker') {
        return wrapCanvasInteractiveZone('image-picker', {
          tag: 'label',
          attrs: { style: { display: 'block', cursor: 'pointer' } }
        }, [
          h('input', {
            type: 'file',
            accept: 'image/*',
            style: { display: 'none' },
            onChange: e => {
              const file = e.target.files[0]
              if (file) localForm[p.name] = URL.createObjectURL(file)
            }
          }),
          localForm[p.name]
            ? h('img', { src: localForm[p.name], style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' } })
            : h('div', { style: { height: '200px', border: '2px dashed #CBD5E1', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' } }, 'Tap to upload image file')
        ])
      }

      if (props.element.type === 'input') {
        return wrapCanvasInteractiveZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: { border: 'none', outline: 'none', width: '100%', boxSizing: 'border-box', ...styleObject(s) }
          }
        }, null)
      }

      if (props.element.type === 'button') {
        return wrapCanvasInteractiveZone('button', {
          tag: 'button',
          attrs: {
            style: { border: 'none', cursor: 'pointer', ...styleObject(s) },
            onClick: () => {
              if (p.state_key) localForm[p.state_key] = p.set_value
              if (props.element.action?.target) alert('Navigate action configuration : ' + props.element.action.target)
            }
          }
        }, p.value || 'Button')
      }

      if (props.element.type === 'icon') {
        return wrapCanvasInteractiveZone('icon', {
          tag: 'span',
          attrs: {
            class: 'material-symbols-outlined',
            style: { fontSize: (s.size || 24) + 'px', display: 'flex', alignItems: 'center', justifyContent: 'center', ...styleObject(s) }
          }
        }, iconMap[(p.name || '').toLowerCase()] || 'flash_on')
      }

      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return wrapCanvasInteractiveZone('grid', {
          tag: 'div',
          attrs: { style: { display: 'grid', gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`, gap: (s.gapV || 8) + 'px', width: '100%' } }
        }, renderChildren())
      }

      if (props.element.type === 'spacer') {
        return wrapCanvasInteractiveZone('spacer', {
          tag: 'div',
          attrs: { style: { minHeight: '10px', ...styleObject(s) } }
        }, null)
      }

      if (props.element.type === 'card') {
        return wrapCanvasInteractiveZone('card', {
          tag: 'div',
          attrs: { style: { width: '100%', boxSizing: 'border-box', ...styleObject(s) } }
        }, renderChildren())
      }

      if (props.element.type === 'items-scroller-h') {
        return wrapCanvasInteractiveZone('items-scroller-h', {
          tag: 'div',
          attrs: { style: { display: 'flex', overflowX: 'auto', overflowY: 'hidden', width: '100%', boxSizing: 'border-box', ...styleObject(s) } }
        }, renderChildren())
      }

      if (props.element.type === 'gesture') {
        return wrapCanvasInteractiveZone('gesture', {
          tag: 'div',
          attrs: {
            style: { cursor: 'pointer', position: 'relative' },
            onClick: () => { if (p.state_key) localForm[p.state_key] = p.set_value }
          }
        }, renderChildren())
      }

      if (props.element.type === 'tab-menu') {
        const stateKey = p.state_key || 'tab'
        if (!globalStates[stateKey]) {
          globalStates[stateKey] = p.initial_tab
          applyControlElements(p.initial_tab)
        }

        return wrapCanvasInteractiveZone('tab-menu', {
          tag: 'div',
          attrs: { style: { display: 'flex', width: '100%', ...styleObject(s) } }
        }, props.element.children.map(child => {
          const tabId = child.props?.tab_id
          return h('div', {
            style: { flex: 1, cursor: 'pointer' },
            onClick: () => {
              globalStates[stateKey] = tabId
              applyControlElements(tabId)
            }
          }, [
            h(Renderer, { element: child, form: localForm, overrides: localOverride, path: props.path, parentActive: globalStates[stateKey] === tabId })
          ])
        }))
      }

      if (props.element.type === 'bottom-drawer') {
        const isOpen = localForm[p.state_key] === 'true'
        if (!isOpen) return null
        return wrapCanvasInteractiveZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999, background: '#FFF',
              borderTopLeftRadius: '24px', borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      return h('div', { style: { color: 'red', fontSize: '12px', padding: '4px' } }, `UNKNOWN SYSTEM NODE TYPE : ${props.element.type}`)
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
  font-family: 'Inter', sans-serif;
}

/* LEFT COMPONENT PANEL PALETTE */
.palette-panel {
  width: 280px;
  min-width: 280px;
  background: #0b0f19;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
}

.palette-header {
  padding: 18px 16px;
  border-bottom: 1px solid #1e293b;
}

.palette-header h3 {
  margin: 0;
  color: #f8fafc;
  font-size: 14px;
  font-weight: 600;
}

.palette-header span {
  font-size: 11px;
  color: #64748b;
  margin-top: 5px;
  display: block;
}

.palette-list {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.palette-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
}

.palette-item:hover {
  background: #24354a;
  border-color: #3b82f6;
  transform: translateY(-1px);
}

.palette-item:active {
  cursor: grabbing;
}

.palette-icon {
  color: #3b82f6;
  font-size: 20px;
}

.palette-item-info {
  display: flex;
  flex-direction: column;
}

.palette-item-name {
  color: #e2e8f0;
  font-size: 12px;
  font-weight: 500;
}

.palette-item-desc {
  color: #64748b;
  font-size: 10px;
  line-height: 1.3;
}

.clipboard-status {
  padding: 12px;
  background: #020617;
  border-top: 1px solid #1e293b;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #10b981;
}

.status-indicator span {
  font-size: 20px;
}

.status-indicator p {
  margin: 0;
  font-size: 11px;
  font-weight: 600;
}

.status-indicator span b {
  color: #f8fafc;
}

.clear-clip-btn {
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  font-size: 10px;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
}

.clear-clip-btn:hover {
  background: #ef4444;
  color: white;
}

/* MID-TEXTAREA EDITOR CONFIGURATIONS */
.editor-panel {
  width: 25%;
  min-width: 240px;
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
  font-size: 12px;
  margin: 0;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 32px;
  padding: 0 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 11px;
  font-weight: 500;
}

.toolbar button:hover {
  background: #1d4ed8;
}

.json-editor {
  flex: 1;
  width: 100%;
  background: #020617;
  color: #cbd5e1;
  border: none;
  outline: none;
  resize: none;
  padding: 16px;
  font-size: 12px;
  font-family: 'Fira Code', monospace;
  line-height: 1.5;
}

/* RIGHT RUNTIME DISPLAY PREVIEW */
.preview-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: auto;
  background: radial-gradient(circle at top, #1e3a8a, #0f172a);
  padding: 20px;
}

.phone-frame {
  width: 390px;
  height: 844px;
  background: #000000;
  border-radius: 44px;
  padding: 12px;
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
}

.phone-screen {
  width: 100%;
  height: 100%;
  border-radius: 34px;
  overflow: hidden;
  position: relative;
  isolation: isolate;
}

/* CANVAS ACTION CONTROL INJECTIONS HOVER SYSTEM */
:deep(.canvas-drag-wrapper) {
  position: relative;
  transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.canvas-element-actions) {
  display: none;
  position: absolute;
  top: -10px;
  right: 10px;
  background: #1e293b;
  border: 1px solid #3b82f6;
  border-radius: 6px;
  padding: 2px;
  align-items: center;
  gap: 2px;
  z-index: 99999;
}

:deep(.canvas-drag-wrapper:hover > .canvas-element-actions) {
  display: flex;
}

:deep(.action-badge) {
  font-size: 9px;
  font-family: monospace;
  background: #2563eb;
  color: white;
  padding: 2px 6px;
  border-radius: 3px;
  margin-right: 4px;
  text-transform: uppercase;
}

:deep(.action-btn) {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.action-btn span) {
  font-size: 14px;
}

:deep(.action-btn:hover) {
  background: #334155;
  color: #f1f5f9;
}

:deep(.action-btn.delete-btn:hover) {
  background: #ef4444;
  color: white;
}

:deep(.action-btn.disabled) {
  opacity: 0.3;
  cursor: not-allowed;
}

:deep(.structure-container:hover) {
  outline: 2px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.08) !important;
  padding: 6px;
}

:deep(.structure-leaf:hover) {
  outline: 2px dashed #10b981 !important;
}

/* LAYERS SYSTEM DEFINITIONS */
.screen-scroll {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 2;
  padding: 12px;
}

.screen-scroll::-webkit-scrollbar {
  display: none;
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
  color: #ef4444;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  font-size: 13px;
  font-weight: 500;
}

.material-symbols-outlined {
  user-select: none;
  flex-shrink: 0;
}
</style>