<template>
  <div class="app">

    <div class="palette-panel">
      <div class="palette-header">
        <h3>Components</h3>
        <span>Drag into preview or layout containers</span>
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
            <p>Copied Element</p>
            <span>Type: <b>{{ clipboardNode.type }}</b></span>
          </div>
        </div>
        <button class="clear-clip-btn" @click="clipboardNode = null">Clear</button>
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
        spellcheck="false"
      />
    </div>

    <div class="preview-panel">
      <div class="phone-frame">
        <div
          class="phone-screen"
          :style="{
            background:
              parsedData?.theme?.bg
              || '#FFFFFF'
          }"
        >
          <template v-if="parsedData">

            <div 
              class="layer-background"
              @dragover.prevent
              @drop.stop="onRootLayerDrop($event, 'background')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'bg-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'background'
                  "
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
              <template
                v-for="(item,index) in parsedData.content"
                :key="'main-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) == null
                  "
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
              <template
                v-for="(item,index) in parsedData.content"
                :key="'root-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'root'
                  "
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
              <template
                v-for="(item,index) in parsedData.content"
                :key="'header-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'header'
                  "
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
              <template
                v-for="(item,index) in parsedData.content"
                :key="'floating-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'floating'
                  "
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
              <template
                v-for="(item,index) in parsedData.content"
                :key="'footer-'+index"
              >
                <Renderer
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'footer'
                  "
                  :element="getElement(item)"
                  :path="[index]"
                  @update-tree="syncJsonTree"
                />
              </template>
            </div>

          </template>

          <div
            v-else
            class="invalid-json"
          >
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
| PALETTE SCHEMA CONFIGURATIONS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-v', label: 'Vertical Box', icon: 'view_stream', desc: 'Linear column layout structure' },
  { type: 'box-h', label: 'Horizontal Box', icon: 'view_column', desc: 'Linear row layout element' },
  { type: 'box-stack', label: 'Stack Box', icon: 'layers', desc: 'Relative positioning stacking stack' },
  { type: 'text', label: 'Text Block', icon: 'text_fields', desc: 'Label displaying plain text value' },
  { type: 'image', label: 'Image Content', icon: 'image', desc: 'Custom configured absolute image block' },
  { type: 'button', label: 'Action Button', icon: 'smart_button', desc: 'Click interactive operational element' },
  { type: 'icon', label: 'Material Symbol', icon: 'star', desc: 'Vector inline typography element' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Form text data capture wrapper' },
  { type: 'spacer', label: 'Layout Spacer', icon: 'space_bar', desc: 'Elastic gap placeholder layout item' }
]

function createNodeInstance(type) {
  const base = { type, props: {}, styles: {} }
  if (['box-v', 'box-h', 'box-stack', 'card', 'data-form'].includes(type)) {
    base.children = []
    base.styles.p = "12"
    base.styles.gap = "8"
  } else if (type === 'text') {
    base.props.value = 'Dynamic Text Field Component'
    base.styles.fontSize = "14"
    base.styles.color = "#000000"
  } else if (type === 'button') {
    base.props.value = 'Click Action'
    base.styles.bg = "#2563EB"
    base.styles.color = "#FFFFFF"
    base.styles.p = "10"
    base.styles.radius = "8"
  } else if (type === 'icon') {
    base.props.name = 'home'
    base.styles.size = "24"
    base.styles.color = "#2563EB"
  } else if (type === 'image') {
    base.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    base.styles.h = "150"
  } else if (type === 'spacer') {
    base.styles.h = "20"
  }
  return base
}

/*
|--------------------------------------------------------------------------
| GLOBAL STATES
|--------------------------------------------------------------------------
*/
const formValues = reactive({})
const overrideMap = reactive({})
const globalStates = reactive({})
const clipboardNode = ref(null)

/*
|--------------------------------------------------------------------------
| ICON MAP
|--------------------------------------------------------------------------
*/
const iconMap = {
  home: 'home',
  chat: 'chat',
  person: 'person',
  globe: 'public',
  menu: 'menu',
  back: 'arrow_back',
  chevron_right: 'chevron_right',
  expand_more: 'expand_more',
  qr: 'qr_code_scanner',
  wallet: 'account_balance_wallet',
  cart: 'shopping_cart',
  'cart-outline': 'shopping_cart',
  store: 'storefront',
  receipt: 'receipt',
  inventory: 'inventory_2',
  sell: 'sell',
  visibility: 'visibility',
  visibility_off: 'visibility_off',
  payments: 'payments',
  smartphone: 'smartphone',
  badge: 'badge',
  notifications: 'notifications',
  search: 'search',
  edit_note: 'edit_note',
  account_tree: 'account_tree',
  people: 'groups',
  calendar: 'calendar_today',
  description: 'description',
  assignment: 'assignment',
  chart: 'bar_chart',
  campaign: 'campaign',
  sticky_note: 'sticky_note_2',
  settings: 'settings',
  article: 'article',
  help: 'help',
  shopping_bag: 'shopping_bag',
  add_box: 'add_box',
  confirmation_number: 'confirmation_number',
  add_circle: 'add_circle',
  delete: 'delete',
  close: 'close',
  tunai: 'payments',
  qr_code: 'qr_code',
  account_balance: 'account_balance',
  more_horiz: 'more_horiz',
  image: 'image'
}

/*
|--------------------------------------------------------------------------
| JSON INITIAL DEFAULTS
|--------------------------------------------------------------------------
*/
const jsonText = ref(`{
  "theme": {
    "bg": "#FFFFFF"
  },
  "content": []
}`)

const parsedData = computed(() => {
  try {
    return JSON.parse(jsonText.value)
  }
  catch (e) {
    return null
  }
})

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  jsonText.value = JSON.stringify({ ...parsedData.value, content: updatedContent }, null, 2)
}

/*
|--------------------------------------------------------------------------
| PATH TRAVERSAL AND MODIFICATION MUTATORS
|--------------------------------------------------------------------------
*/
function locateTargetByPath(rootArr, pathArr) {
  let activeArr = rootArr
  for (let i = 0; i < pathArr.length; i++) {
    const idx = pathArr[i]
    if (i === pathArr.length - 1) return activeArr[idx]
    activeArr = activeArr[idx].portrait ? activeArr[idx].portrait.children : activeArr[idx].children
  }
  return null
}

function removeTargetByPath(rootArr, pathArr) {
  const nextTree = JSON.parse(JSON.stringify(rootArr))
  let activeArr = nextTree
  for (let i = 0; i < pathArr.length; i++) {
    const idx = pathArr[i]
    if (i === pathArr.length - 1) {
      activeArr.splice(idx, 1)
    } else {
      activeArr = activeArr[idx].portrait ? activeArr[idx].portrait.children : activeArr[idx].children
    }
  }
  return nextTree
}

function insertTargetByPath(rootArr, pathArr, payload) {
  const nextTree = JSON.parse(JSON.stringify(rootArr))
  let activeArr = nextTree
  for (let i = 0; i < pathArr.length; i++) {
    const idx = pathArr[i]
    if (i === pathArr.length - 1) {
      activeArr.splice(idx, 0, payload)
    } else {
      activeArr = activeArr[idx].portrait ? activeArr[idx].portrait.children : activeArr[idx].children
    }
  }
  return nextTree
}

/*
|--------------------------------------------------------------------------
| BASE FRAME LAYER DROP ACTIONS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(e, type) {
  e.dataTransfer.setData('application/celina-engine-dnd', JSON.stringify({ mode: 'PALETTE_NEW', type }))
}

function onRootLayerDrop(e, targetLayer) {
  try {
    const rawData = e.dataTransfer.getData('application/celina-engine-dnd')
    if (!rawData) return
    const packet = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let payload = null
    if (packet.mode === 'PALETTE_NEW') {
      payload = createNodeInstance(packet.type)
      if (targetLayer) payload.props.layer = targetLayer
    } else if (packet.mode === 'RENDERER_MOVE') {
      payload = locateTargetByPath(mutableContent, packet.sourcePath)
      mutableContent = removeTargetByPath(mutableContent, packet.sourcePath)
      if (payload) {
        payload.props = payload.props || {}
        if (targetLayer) payload.props.layer = targetLayer; else delete payload.props.layer
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
| NESTED DEPENDENCY PROV-INJECT LAYER
|--------------------------------------------------------------------------
*/
provide('celinaDnDEngineContext', {
  handleDragStart: (e, sourcePath) => {
    e.dataTransfer.setData('application/celina-engine-dnd', JSON.stringify({ mode: 'RENDERER_MOVE', sourcePath }))
  },
  handleDropZone: (e, destinationPath, hoverZone) => {
    try {
      const rawData = e.dataTransfer.getData('application/celina-engine-dnd')
      if (!rawData) return
      const packet = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))
      let payload = null

      if (packet.mode === 'PALETTE_NEW') {
        payload = createNodeInstance(packet.type)
      } else if (packet.mode === 'RENDERER_MOVE') {
        const srcStr = JSON.stringify(packet.sourcePath)
        const dstStr = JSON.stringify(destinationPath)
        
        if (dstStr.startsWith(srcStr.slice(0, -1))) {
          if (dstStr === srcStr || dstStr.startsWith(srcStr.replace(']', '') + ',')) {
            alert("Nesting Boundary Constraint: A container element cannot be nested into itself or its down-tree children loops.")
            return
          }
        }

        payload = locateTargetByPath(mutableContent, packet.sourcePath)
        mutableContent = removeTargetByPath(mutableContent, packet.sourcePath)

        if (packet.sourcePath.length === destinationPath.length) {
          const identicalContext = packet.sourcePath.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (identicalContext && packet.sourcePath[packet.sourcePath.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (payload) {
        if (hoverZone === 'APPEND_INSIDE') {
          let containerNode = locateTargetByPath(mutableContent, destinationPath)
          if (containerNode) {
            const node = containerNode.portrait || containerNode
            node.children = node.children || []
            node.children.push(payload)
          }
        } else if (hoverZone === 'INSERT_BEFORE') {
          mutableContent = insertTargetByPath(mutableContent, destinationPath, payload)
        } else if (hoverZone === 'INSERT_AFTER') {
          const adjustedPath = [...destinationPath]
          adjustedPath[adjustedPath.length - 1]++
          mutableContent = insertTargetByPath(mutableContent, adjustedPath, payload)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
  },
  executeActionCommand: (cmd, targetPath) => {
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))
    if (cmd === 'DELETE') {
      mutableContent = removeTargetByPath(mutableContent, targetPath)
      syncJsonTree(mutableContent)
    } else if (cmd === 'COPY') {
      const match = locateTargetByPath(mutableContent, targetPath)
      if (match) clipboardNode.value = JSON.parse(JSON.stringify(match))
    } else if (cmd === 'PASTE') {
      if (!clipboardNode.value) return
      const payload = JSON.parse(JSON.stringify(clipboardNode.value))
      let targetNode = locateTargetByPath(mutableContent, targetPath)
      const elementNode = targetNode?.portrait || targetNode

      if (elementNode && ['box-v', 'box-h', 'box-stack', 'card', 'data-form', 'box-banner', 'grid'].includes(elementNode.type)) {
        elementNode.children = elementNode.children || []
        elementNode.children.push(payload)
      } else {
        const lateralPath = [...targetPath]
        lateralPath[lateralPath.length - 1]++
        mutableContent = insertTargetByPath(mutableContent, lateralPath, payload)
      }
      syncJsonTree(mutableContent)
    }
  },
  isClipboardPopulated: () => computed(() => !!clipboardNode.value).value
})

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function reloadRuntime() {
  jsonText.value = jsonText.value
}

function getElement(item) {
  return item.portrait || item
}

function getLayer(element) {
  return element?.props?.layer || null
}

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/
function injectData(node,data) {

  if (!node)
    return

  if (node.props) {

    Object.keys(node.props)
      .forEach(key => {

        node.props[key] =
          String(node.props[key])
          .replace(
            /\{\{(.*?)\}\}/g,
            (_,k)=> data[k.trim()] || ''
          )
      })
  }

  if (node.children) {

    node.children.forEach(
      child => injectData(child,data)
    )
  }
}

/*
|--------------------------------------------------------------------------
| STYLE ENGINE
|--------------------------------------------------------------------------
*/
function styleObject(styles = {}) {

  const obj = {}

  /*
  |--------------------------------------------------------------------------
  | SIZE
  |--------------------------------------------------------------------------
  */
  if (styles.w) {

    if (styles.w === 'fill') {
      obj.width = '100%'
    }
    else if (
      String(styles.w).includes('%')
    ) {
      obj.width = styles.w
    }
    else {
      obj.width = styles.w + 'px'
    }
  }

  if (styles.h) {

    if (styles.h === 'fill') {
      obj.height = '100%'
    }
    else if (
      String(styles.h).includes('%')
    ) {
      obj.height = styles.h
    }
    else {
      obj.height = styles.h + 'px'
    }
  }

  /*
  |--------------------------------------------------------------------------
  | BG
  |--------------------------------------------------------------------------
  */
  if (styles.bg)
    obj.background = styles.bg

  if (styles.bgImage) {

    obj.backgroundImage =
      `url(${styles.bgImage})`

    obj.backgroundSize = 'cover'
    obj.backgroundPosition = 'center'
  }

  /*
  |--------------------------------------------------------------------------
  | TEXT
  |--------------------------------------------------------------------------
  */
  if (styles.color)
    obj.color = styles.color

  if (styles.fontSize)
    obj.fontSize =
      styles.fontSize + 'px'

  if (styles.bold === 'true')
    obj.fontWeight = '700'

  if (styles.lineHeight)
    obj.lineHeight =
      styles.lineHeight + 'px'

  /*
  |--------------------------------------------------------------------------
  | ALIGN
  |--------------------------------------------------------------------------
  */
  if (styles.align === 'center') {
    obj.alignItems = 'center'
    obj.textAlign = 'center'
  }

  if (styles.align === 'right') {
    obj.alignItems = 'flex-end'
    obj.textAlign = 'right'
  }

  if (styles.align === 'left') {
    obj.alignItems = 'flex-start'
    obj.textAlign = 'left'
  }

  /*
  |--------------------------------------------------------------------------
  | ARRANGEMENT
  |--------------------------------------------------------------------------
  */
  if (styles.arrangement === 'center')
    obj.justifyContent = 'center'

  if (styles.arrangement === 'between')
    obj.justifyContent = 'space-between'

  if (styles.arrangement === 'around')
    obj.justifyContent = 'space-around'

  if (styles.arrangement === 'evenly')
    obj.justifyContent = 'space-evenly'

  /*
  |--------------------------------------------------------------------------
  | PADDING
  |--------------------------------------------------------------------------
  */
  if (styles.p)
    obj.padding = styles.p + 'px'

  if (styles.pt)
    obj.paddingTop = styles.pt + 'px'

  if (styles.pb)
    obj.paddingBottom = styles.pb + 'px'

  if (styles.pl)
    obj.paddingLeft = styles.pl + 'px'

  if (styles.pr)
    obj.paddingRight = styles.pr + 'px'

  /*
  |--------------------------------------------------------------------------
  | MARGIN
  |--------------------------------------------------------------------------
  */
  if (styles.mt)
    obj.marginTop = styles.mt + 'px'

  if (styles.mb)
    obj.marginBottom = styles.mb + 'px'

  if (styles.ml)
    obj.marginLeft = styles.ml + 'px'

  if (styles.mr)
    obj.marginRight = styles.mr + 'px'

  /*
  |--------------------------------------------------------------------------
  | GAP
  |--------------------------------------------------------------------------
  */
  if (styles.gap)
    obj.gap = styles.gap + 'px'

  /*
  |--------------------------------------------------------------------------
  | RADIUS
  |--------------------------------------------------------------------------
  */
  if (styles.radius)
    obj.borderRadius =
      styles.radius + 'px'

  /*
  |--------------------------------------------------------------------------
  | BORDER
  |--------------------------------------------------------------------------
  */
  if (styles.border)
    obj.border =
      '1px solid ' + styles.border

  /*
  |--------------------------------------------------------------------------
  | FLEX
  |--------------------------------------------------------------------------
  */
  if (styles.weight)
    obj.flex = styles.weight

  /*
  |--------------------------------------------------------------------------
  | SHADOW
  |--------------------------------------------------------------------------
  */
  if (styles.elevation) {

    const elevation =
      Number(styles.elevation)

    obj.boxShadow =
      `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  /*
  |--------------------------------------------------------------------------
  | OFFSET
  |--------------------------------------------------------------------------
  */
  if (
    styles.offsetX ||
    styles.offsetY
  ) {

    const x =
      styles.offsetX || 0

    const y =
      styles.offsetY || 0

    obj.transform =
      `translate(${x}px,${y}px)`
  }

  /*
  |--------------------------------------------------------------------------
  | ALPHA
  |--------------------------------------------------------------------------
  */
  if (styles.alpha)
    obj.opacity = styles.alpha
  if (styles.z)
    obj.zIndex = styles.z

  /*
  |--------------------------------------------------------------------------
  | SCROLLABLE
  |--------------------------------------------------------------------------
  */
  if (
    styles.scrollable === 'true'
  ) {

    obj.overflowY = 'auto'
  }

  /*
  |--------------------------------------------------------------------------
  | MAXLINES
  |--------------------------------------------------------------------------
  */
  if (styles.maxLines) {

    obj.display = '-webkit-box'

    obj.webkitLineClamp =
      styles.maxLines

    obj.webkitBoxOrient =
      'vertical'

    obj.overflow = 'hidden'
  }

  /*
  |--------------------------------------------------------------------------
  | POSITION
  |--------------------------------------------------------------------------
  */
  if (styles.absolute === 'true') {

    obj.position = 'absolute'

    if (styles.top != null)
      obj.top = styles.top + 'px'

    if (styles.left != null)
      obj.left = styles.left + 'px'

    if (styles.right != null)
      obj.right = styles.right + 'px'

    if (styles.bottom != null)
      obj.bottom = styles.bottom + 'px'
  }

  return obj
}

/*
|--------------------------------------------------------------------------
| RENDERER
|--------------------------------------------------------------------------
*/
const Renderer = defineComponent({

  name: 'Renderer',

  props: {
    element: Object,
    form: Object,
    overrides: Object,
    parentActive: Boolean,
    path: { type: Array, default: () => [] }
  },
  inject: ['celinaDnDEngineContext'],

  setup(props) {

    const localForm =
      props.form || formValues

    const localOverride =
      props.overrides || overrideMap

    /*
    |--------------------------------------------------------------------------
    | MERGED
    |--------------------------------------------------------------------------
    */
    function mergedProps() {

      const name =
        props.element.props?.name

      const override =
        name
          ? localOverride[name]
          : null

      return {
        ...(props.element.props || {}),
        ...(override?.props || {})
      }
    }

    function mergedStyles() {

      const name =
        props.element.props?.name

      const override =
        name
          ? localOverride[name]
          : null

      return {

        ...(props.element.styles || {}),

        ...(props.parentActive
          ? props.element.activeStyles || {}
          : {}
        ),

        ...(override?.styles || {})
      }
    }

    /*
    |--------------------------------------------------------------------------
    | CHILDREN
    |--------------------------------------------------------------------------
    */
    function renderChildren(
      extra={}
    ) {

      if (!props.element.children)
        return null

      return props.element.children.map(
        (child,index)=>
          h(Renderer,{
            key:index,
            element:child,
            form:localForm,
            overrides:localOverride,
            parentActive:
              extra.parentActive,
            path: [...props.path, index]
          })
      )
    }

    /*
    |--------------------------------------------------------------------------
    | CONTROL ELEMENT
    |--------------------------------------------------------------------------
    */
    function applyControlElements(tabId) {

      const controls =
        props.element['control-elements']

      if (!controls)
        return

      controls.forEach(control=>{

        const target =
          control['target-name']

        const config =
          control['on-values']?.[tabId]

        if (config) {
          localOverride[target] =
            config
        }
      })
    }

    /*
    |--------------------------------------------------------------------------
    | DATA SOURCE
    |--------------------------------------------------------------------------
    */
    const dynamicItems = ref([])

    onMounted(async()=>{

      if (
        props.element['data-source']
      ) {

        try {

          const response =
            await fetch(
              props.element['data-source']
            )

          dynamicItems.value =
            await response.json()
        }
        catch(e){
          console.log(e)
        }
      }
    })

    /*
    |--------------------------------------------------------------------------
    | DND WRAPPER LAYER INJECTION (PRESERVES NATIVE STYLING COMPLETELY)
    |--------------------------------------------------------------------------
    */
    function wrapInteractiveCanvas(type, structuralNode, virtualVNodeContent) {
      const isLayoutContainer = ['box-v', 'box-h', 'box-stack', 'card', 'data-form', 'box-banner', 'grid'].includes(type)
      const hasClipboardPayload = props.celinaDnDEngineContext.isClipboardPopulated()

      const controlPanelOverlay = h('div', { class: 'canvas-element-actions' }, [
        h('span', { class: 'action-badge' }, type),
        h('button', { 
          class: 'action-btn copy', 
          title: 'Copy component layout template',
          onClick: (e) => { e.stopPropagation(); props.celinaDnDEngineContext.executeActionCommand('COPY', props.path) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_copy')]),
        isLayoutContainer ? h('button', { 
          class: `action-btn paste ${!hasClipboardPayload ? 'disabled' : ''}`, 
          title: 'Paste inside container tier',
          disabled: !hasClipboardPayload,
          onClick: (e) => { e.stopPropagation(); props.celinaDnDEngineContext.executeActionCommand('PASTE', props.path) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_paste')]) : null,
        h('button', { 
          class: 'action-btn delete', 
          title: 'Delete from blueprint tree',
          onClick: (e) => { e.stopPropagation(); props.celinaDnDEngineContext.executeActionCommand('DELETE', props.path) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'delete')])
      ])

      return h('div', {
        class: `canvas-wrapper ${isLayoutContainer ? 'structure-container' : 'structure-leaf'}`,
        draggable: 'true',
        onDragstart: (e) => {
          e.stopPropagation()
          props.celinaDnDEngineContext.handleDragStart(e, props.path)
        },
        onDragover: (e) => {
          e.preventDefault()
          e.stopPropagation()
        },
        onDrop: (e) => {
          e.stopPropagation()
          e.preventDefault()

          const rect = e.currentTarget.getBoundingClientRect()
          const relativeY = e.clientY - rect.top

          if (isLayoutContainer) {
            if (relativeY < rect.height * 0.22) {
              props.celinaDnDEngineContext.handleDropZone(e, props.path, 'INSERT_BEFORE')
            } else if (relativeY > rect.height * 0.78) {
              props.celinaDnDEngineContext.handleDropZone(e, props.path, 'INSERT_AFTER')
            } else {
              props.celinaDnDEngineContext.handleDropZone(e, props.path, 'APPEND_INSIDE')
            }
          } else {
            if (relativeY > rect.height * 0.5) {
              props.celinaDnDEngineContext.handleDropZone(e, props.path, 'INSERT_AFTER')
            } else {
              props.celinaDnDEngineContext.handleDropZone(e, props.path, 'INSERT_BEFORE')
            }
          }
        }
      }, [
        controlPanelOverlay,
        h(structuralNode.tag, structuralNode.attrs, virtualVNodeContent)
      ])
    }

    return ()=> {

      const p = mergedProps()
      const s = mergedStyles()

      /*
      |--------------------------------------------------------------------------
      | VISIBILITY
      |--------------------------------------------------------------------------
      */
      if (
        p.visibility === 'off'
        ||
        p.visibility === 'false'
      ) {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'box-h'
      ) {
        return wrapInteractiveCanvas('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '28px',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | BOX V
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'box-v'
      ) {

        /*
        |--------------------------------------------------------------------------
        | DATA SOURCE
        |--------------------------------------------------------------------------
        */
        if (
          props.element['data-source']
          &&
          props.element['data-container']
        ) {
          return wrapInteractiveCanvas('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '30px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map((item, index) => {
            const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
            injectData(cloned, item)
            return h(Renderer, {
              element: cloned,
              form: localForm,
              overrides: localOverride,
              path: [...props.path, index]
            })
          }))
        }

        return wrapInteractiveCanvas('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '30px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | BOX STACK
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'box-stack'
      ) {

        const isBackgroundLayer =
          p.layer === 'background'

        return wrapInteractiveCanvas('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '35px'),
              backgroundImage: p.bgImage ? `url(${p.bgImage})` : undefined,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
              backgroundRepeat: 'no-repeat',
              overflow: 'hidden',
              ...styleObject({ ...s, bgImage: null })
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'box-banner'
      ) {
        return wrapInteractiveCanvas('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '40px',
              ...styleObject({ ...s, bgImage: p.bgImage })
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | DATA FORM
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'data-form'
      ) {
        return wrapInteractiveCanvas('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '30px',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | TEXT
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'text'
      ) {
        return wrapInteractiveCanvas('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
              minHeight: '14px',
              ...styleObject(s)
            }
          }
        }, p.value || '')
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'image'
      ) {
        return wrapInteractiveCanvas('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '24px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE PICKER
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'image-picker'
      ) {

        return wrapInteractiveCanvas('image-picker', {
          tag: 'label',
          attrs: {
            style: { display: 'block', cursor: 'pointer' }
          }
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
            : h('div', { style: { height: '200px', border: '2px dashed #CBD5E1', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' } }, 'Tap to upload')
        ])
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type === 'input'
      ) {

        return wrapInteractiveCanvas('input', {
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

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'button'
      ) {

        return wrapInteractiveCanvas('button', {
          tag: 'button',
          attrs: {
            style: { border: 'none', cursor: 'pointer', ...styleObject(s) },
            onClick: () => {
              if (p.state_key) localForm[p.state_key] = p.set_value
              if (props.element.action?.target) alert('Navigate : ' + props.element.action.target)
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'icon'
      ) {

        return wrapInteractiveCanvas('icon', {
          tag: 'span',
          attrs: {
            class: 'material-symbols-outlined',
            style: {
              fontSize: (s.size || 24) + 'px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              ...styleObject(s)
            }
          }
        }, iconMap[(p.name || '').toLowerCase()] || 'flash_on')
      }

      /*
      |--------------------------------------------------------------------------
      | GRID
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'grid'
      ) {

        const columns = Number(s.columns || 2)
        return wrapInteractiveCanvas('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%',
              minHeight: '30px'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'spacer'
      ) {

        return wrapInteractiveCanvas('spacer', {
          tag: 'div',
          attrs: {
            style: { minHeight: '8px', ...styleObject(s) }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'card'
      ) {

        return wrapInteractiveCanvas('card', {
          tag: 'div',
          attrs: {
            style: { width: '100%', boxSizing: 'border-box', minHeight: '30px', ...styleObject(s) }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'items-scroller-h'
      ) {

        return wrapInteractiveCanvas('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: { display: 'flex', overflowX: 'auto', overflowY: 'hidden', width: '100%', boxSizing: 'border-box', minHeight: '40px', ...styleObject(s) }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'gesture'
      ) {

        return wrapInteractiveCanvas('gesture', {
          tag: 'div',
          attrs: {
            style: { cursor: 'pointer', position: 'relative' },
            onClick: () => { if (p.state_key) localForm[p.state_key] = p.set_value }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | TAB MENU
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'tab-menu'
      ) {

        const stateKey = p.state_key || 'tab'
        if (!globalStates[stateKey]) {
          globalStates[stateKey] = p.initial_tab
          applyControlElements(p.initial_tab)
        }

        return wrapInteractiveCanvas('tab-menu', {
          tag: 'div',
          attrs: {
            style: { display: 'flex', width: '100%', ...styleObject(s) }
          }
        }, props.element.children.map((child, index) => {
          const tabId = child.props?.tab_id
          return h('div', {
            style: { flex: 1, cursor: 'pointer' },
            onClick: () => {
              globalStates[stateKey] = tabId
              applyControlElements(tabId)
            }
          }, [
            h(Renderer, {
              element: child,
              form: localForm,
              overrides: localOverride,
              parentActive: globalStates[stateKey] === tabId,
              path: [...props.path, index]
            })
          ])
        }))
      }

      /*
      |--------------------------------------------------------------------------
      | BOTTOM DRAWER
      |--------------------------------------------------------------------------
      */
      if (
        props.element.type ===
        'bottom-drawer'
      ) {

        const isOpen = localForm[p.state_key] === 'true'
        if (!isOpen) return null

        return wrapInteractiveCanvas('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999, background: '#FFF',
              borderTopLeftRadius: '24px', borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
              minHeight: '60px',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | UNKNOWN
      |--------------------------------------------------------------------------
      */
      return h(
        'div',
        {
          style:{ color:'red', fontSize:'12px', padding:'4px'}
        },

        `UNKNOWN TYPE : ${props.element.type}`
      )
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

/* PALETTE PANEL CONTAINER STYLE rules */
.palette-panel {
  width: 260px;
  min-width: 260px;
  background: #0b0f19;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
}

.palette-header {
  padding: 16px;
  border-bottom: 1px solid #1e293b;
}

.palette-header h3 {
  margin: 0;
  color: #f8fafc;
  font-size: 14px;
}

.palette-header span {
  font-size: 11px;
  color: #64748b;
  margin-top: 4px;
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
  gap: 10px;
  padding: 10px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 6px;
  cursor: grab;
  user-select: none;
}

.palette-item:hover {
  background: #24354a;
  border-color: #3b82f6;
}

.palette-icon {
  color: #3b82f6;
  font-size: 18px;
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
}

.clipboard-status {
  padding: 10px;
  background: #020617;
  border-top: 1px solid #1e293b;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #10b981;
}

.status-indicator span { font-size: 18px; }
.status-indicator p { margin: 0; font-size: 11px; font-weight: bold; }
.status-indicator span b { color: #fff; }

.clear-clip-btn {
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  cursor: pointer;
}

/* COMPACT EDITOR WINDOW STYLE RULES */
.editor-panel {
  width: 25%;
  min-width: 280px;
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
  font-size: 11px;
  margin: 0;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 12px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 11px;
}

.json-editor {
  flex: 1;
  width: 100%;
  background: #020617;
  color: #e2e8f0;
  border: none;
  outline: none;
  resize: none;
  padding: 16px;
  font-size: 12px;
  font-family: monospace;
  line-height: 1.5;
}

/* CANVAS DND WRAPPER AND BADGE INTERFACES */
:deep(.canvas-wrapper) {
  position: relative;
  transition: outline 0.1s ease;
}

:deep(.canvas-element-actions) {
  display: none;
  position: absolute;
  top: -12px;
  right: 4px;
  background: #1e293b;
  border: 1px solid #3b82f6;
  border-radius: 4px;
  padding: 2px;
  align-items: center;
  gap: 2px;
  z-index: 99999;
}

:deep(.canvas-wrapper:hover > .canvas-element-actions) {
  display: flex;
}

:deep(.action-badge) {
  font-size: 8px;
  font-family: monospace;
  background: #2563eb;
  color: white;
  padding: 1px 4px;
  border-radius: 2px;
  margin-right: 2px;
  text-transform: uppercase;
}

:deep(.action-btn) {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
}

:deep(.action-btn span) { font-size: 13px; }
:deep(.action-btn:hover) { background: #334155; color: #fff; }
:deep(.action-btn.delete:hover) { background: #ef4444; color: #fff; }
:deep(.action-btn.disabled) { opacity: 0.25; cursor: not-allowed; }

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
}

/* PHONE FRAME PREVIEW PANEL STYLINGS */
.preview-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: auto;

  background:
    radial-gradient(
      circle at top,
      #1e3a8a,
      #0f172a
    );
}

.phone-frame {
  width: 390px;
  height: 844px;
  background: black;
  border-radius: 40px;
  padding: 12px;

  box-shadow:
    0 20px 80px rgba(0,0,0,0.5);
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
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}
</style>