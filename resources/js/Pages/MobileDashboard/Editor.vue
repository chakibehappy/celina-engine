<template>
  <div class="app">

    <div class="palette-panel">
      <div class="palette-header">
        <h3>Component Elements</h3>
        <p>Drag an element directly into the layout or preview canvas containers below.</p>
      </div>
      <div class="palette-list">
        <div 
          v-for="comp in paletteElements" 
          :key="comp.type" 
          class="palette-item"
          draggable="true"
          @dragstart="handlePaletteDragStart($event, comp.type)"
        >
          <span class="material-symbols-outlined palette-icon">{{ comp.icon }}</span>
          <div class="palette-text-meta">
            <span class="palette-label">{{ comp.label }}</span>
            <span class="palette-desc">{{ comp.desc }}</span>
          </div>
        </div>
      </div>
      
      <div class="clipboard-box" v-if="localClipboard">
        <div class="clipboard-indicator">
          <span class="material-symbols-outlined">content_paste_go</span>
          <div>
            <p>Element Copied</p>
            <span>Type: <b>{{ localClipboard.type }}</b></span>
          </div>
        </div>
        <button class="clear-clip-btn" @click="localClipboard = null">Clear</button>
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
      />
    </div>

    <div class="preview-panel">
      <div class="phone-frame">
        <div
          class="phone-screen"
          :style="{
            background: parsedData?.theme?.bg || '#FFFFFF'
          }"
        >
          <template v-if="parsedData">

            <div 
              class="layer-background layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, 'background')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'bg-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'background'"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

            <div 
              class="screen-scroll layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, null)"
            >
              <template v-for="(item, index) in parsedData.content" :key="'main-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) == null"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

            <div 
              class="layer-root layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, 'root')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'root-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'root'"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

            <div 
              class="layer-header layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, 'header')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'header-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'header'"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

            <div 
              class="layer-floating layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, 'floating')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'floating-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'floating'"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

            <div 
              class="layer-footer layer-dropzone"
              @dragover.prevent
              @drop.stop="handleLayerRootDrop($event, 'footer')"
            >
              <template v-for="(item, index) in parsedData.content" :key="'footer-'+index">
                <Renderer
                  v-if="getLayer(getElement(item)) === 'footer'"
                  :element="getElement(item)"
                  :node-path="[index]"
                />
              </template>
            </div>

          </template>

          <div v-else class="invalid-json">
            Invalid JSON Architecture Structure
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
| PALETTE SCHEMATICS DEFINITIONS
|--------------------------------------------------------------------------
*/
const paletteElements = [
  { type: 'box-v', label: 'Vertical Box', icon: 'view_stream', desc: 'Nested linear layout flex column wrapper' },
  { type: 'box-h', label: 'Horizontal Box', icon: 'view_column', desc: 'Nested linear layout row template element' },
  { type: 'box-stack', label: 'Stack Container', icon: 'layers', desc: 'Relative overlap stack coordinates' },
  { type: 'text', label: 'Typography Text', icon: 'text_fields', desc: 'Custom configured dynamic copy fields' },
  { type: 'image', label: 'Image Content', icon: 'image', desc: 'Absolute media target placeholder reference' },
  { type: 'button', label: 'Action Button', icon: 'smart_button', desc: 'Clickable interactive component handle' },
  { type: 'icon', label: 'Material Symbol', icon: 'star', desc: 'Vector line iconography asset element' },
  { type: 'input', label: 'Input Textbox', icon: 'input', desc: 'Captures and monitors user-entered keys' },
  { type: 'spacer', label: 'Flexible Spacer', icon: 'space_bar', desc: 'Elastic structural spacing gap padding' }
]

function makeBlankInstance(type) {
  const blueprint = { type, props: {}, styles: {} }
  if (['box-v', 'box-h', 'box-stack', 'card', 'data-form', 'grid', 'box-banner'].includes(type)) {
    blueprint.children = []
    blueprint.styles.p = "12"
    blueprint.styles.gap = "8"
  } else if (type === 'text') {
    blueprint.props.value = 'New Editable Text Block Element'
    blueprint.styles.fontSize = "14"
    blueprint.styles.color = "#334155"
  } else if (type === 'button') {
    blueprint.props.value = 'Trigger Action'
    blueprint.styles.bg = "#2563EB"
    blueprint.styles.color = "#FFFFFF"
    blueprint.styles.p = "10"
    blueprint.styles.radius = "6"
  } else if (type === 'icon') {
    blueprint.props.name = 'home'
    blueprint.styles.size = "24"
    blueprint.styles.color = "#2563EB"
  } else if (type === 'image') {
    blueprint.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    blueprint.styles.h = "140"
  } else if (type === 'spacer') {
    blueprint.styles.h = "20"
  }
  return blueprint
}

/*
|--------------------------------------------------------------------------
| GLOBAL STATES
|--------------------------------------------------------------------------
*/
const formValues = reactive({})
const overrideMap = reactive({})
const globalStates = reactive({})
const localClipboard = ref(null)

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
| JSON MANAGEMENT
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

function writeSchemaUpdate(nextContent) {
  if (!parsedData.value) return
  jsonText.value = JSON.stringify({ ...parsedData.value, content: nextContent }, null, 2)
}

/*
|--------------------------------------------------------------------------
| STRUCTURAL GRAPH PATH UTILITIES (NESTED ACCURACY)
|--------------------------------------------------------------------------
*/
function findNodeByCoordinatePath(rootList, path) {
  let target = rootList
  for (let i = 0; i < path.length; i++) {
    const step = path[i]
    if (i === path.length - 1) {
      return target[step]
    }
    const internal = target[step].portrait || target[step]
    target = internal.children
  }
  return null
}

function exciseNodeByCoordinatePath(rootList, path) {
  const treeClone = JSON.parse(JSON.stringify(rootList))
  let target = treeClone
  for (let i = 0; i < path.length; i++) {
    const step = path[i]
    if (i === path.length - 1) {
      target.splice(step, 1)
    } else {
      const internal = target[step].portrait || target[step]
      if (!internal.children) internal.children = []
      target = internal.children
    }
  }
  return treeClone
}

function implantNodeByCoordinatePath(rootList, path, element) {
  const treeClone = JSON.parse(JSON.stringify(rootList))
  let target = treeClone
  for (let i = 0; i < path.length; i++) {
    const step = path[i]
    if (i === path.length - 1) {
      target.splice(step, 0, element)
    } else {
      const internal = target[step].portrait || target[step]
      if (!internal.children) internal.children = []
      target = internal.children
    }
  }
  return treeClone
}

/*
|--------------------------------------------------------------------------
| TOP-LEVEL BASE LAYERS DRAG AND DROP HANDLERS
|--------------------------------------------------------------------------
*/
function handlePaletteDragStart(e, type) {
  e.dataTransfer.setData('celina-engine-dnd-packet', JSON.stringify({ mode: 'NEW_ELEMENT', type }))
}

function handleLayerRootDrop(e, targetLayer) {
  try {
    const raw = e.dataTransfer.getData('celina-engine-dnd-packet')
    if (!raw) return
    const packet = JSON.parse(raw)
    let workingContentList = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let subject = null
    if (packet.mode === 'NEW_ELEMENT') {
      subject = makeBlankInstance(packet.type)
      if (targetLayer) subject.props.layer = targetLayer
    } else if (packet.mode === 'REPOSITION_ELEMENT') {
      subject = findNodeByCoordinatePath(workingContentList, packet.sourcePath)
      workingContentList = exciseNodeByCoordinatePath(workingContentList, packet.sourcePath)
      if (subject) {
        const item = subject.portrait || subject
        item.props = item.props || {}
        if (targetLayer) item.props.layer = targetLayer; else delete item.props.layer
      }
    }

    if (subject) {
      workingContentList.push(subject)
      writeSchemaUpdate(workingContentList)
    }
  } catch (err) {
    console.error(err)
  }
}

/*
|--------------------------------------------------------------------------
| VUE INJECT PROVIDER FOR INTER-CANVAS COMMUNICATION (PREVENTS LIFECYCLE RE-RENDER CRASHES)
|--------------------------------------------------------------------------
*/
provide('celinaDnDContext', {
  signalDragStart: (e, path) => {
    e.dataTransfer.setData('celina-engine-dnd-packet', JSON.stringify({ mode: 'REPOSITION_ELEMENT', sourcePath: path }))
  },
  signalDropZone: (e, targetPath, zone) => {
    try {
      const raw = e.dataTransfer.getData('celina-engine-dnd-packet')
      if (!raw) return
      const packet = JSON.parse(raw)
      let workingContentList = JSON.parse(JSON.stringify(parsedData.value.content || []))
      let subject = null

      if (packet.mode === 'NEW_ELEMENT') {
        subject = makeBlankInstance(packet.type)
      } else if (packet.mode === 'REPOSITION_ELEMENT') {
        const sStr = JSON.stringify(packet.sourcePath)
        const tStr = JSON.stringify(targetPath)

        // Strict nested integrity circuit constraint logic check loop
        if (tStr.startsWith(sStr.slice(0, -1))) {
          if (tStr === sStr || tStr.startsWith(sStr.replace(']', '') + ',')) {
            alert("Nesting Boundary Exception: Cannot drag an active container into a loop inside its own downstream layout tree children.")
            return
          }
        }

        subject = findNodeByCoordinatePath(workingContentList, packet.sourcePath)
        workingContentList = exciseNodeByCoordinatePath(workingContentList, packet.sourcePath)

        if (packet.sourcePath.length === targetPath.length) {
          const matchedLineage = packet.sourcePath.slice(0, -1).join(',') === targetPath.slice(0, -1).join(',')
          if (matchedLineage && packet.sourcePath[packet.sourcePath.length - 1] < targetPath[targetPath.length - 1]) {
            targetPath[targetPath.length - 1]--
          }
        }
      }

      if (subject) {
        if (zone === 'APPEND_INNER') {
          let targetNode = findNodeByCoordinatePath(workingContentList, targetPath)
          if (targetNode) {
            const layout = targetNode.portrait || targetNode
            layout.children = layout.children || []
            layout.children.push(subject)
          }
        } else if (zone === 'SLOT_BEFORE') {
          workingContentList = implantNodeByCoordinatePath(workingContentList, targetPath, subject)
        } else if (zone === 'SLOT_AFTER') {
          const shiftPath = [...targetPath]
          shiftPath[shiftPath.length - 1]++
          workingContentList = implantNodeByCoordinatePath(workingContentList, shiftPath, subject)
        }
        writeSchemaUpdate(workingContentList)
      }
    } catch (err) {
      console.error(err)
    }
  },
  executeAction: (action, path) => {
    let workingContentList = JSON.parse(JSON.stringify(parsedData.value.content || []))
    if (action === 'DELETE') {
      workingContentList = exciseNodeByCoordinatePath(workingContentList, path)
      writeSchemaUpdate(workingContentList)
    } else if (action === 'COPY') {
      const match = findNodeByCoordinatePath(workingContentList, path)
      if (match) localClipboard.value = JSON.parse(JSON.stringify(match))
    } else if (action === 'PASTE') {
      if (!localClipboard.value) return
      const copyObject = JSON.parse(JSON.stringify(localClipboard.value))
      let destinationNode = findNodeByCoordinatePath(workingContentList, path)
      const unwrapped = destinationNode?.portrait || destinationNode

      if (unwrapped && ['box-v', 'box-h', 'box-stack', 'card', 'data-form', 'box-banner', 'grid'].includes(unwrapped.type)) {
        unwrapped.children = unwrapped.children || []
        unwrapped.children.push(copyObject)
      } else {
        const standardPath = [...path]
        standardPath[standardPath.length - 1]++
        workingContentList = implantNodeByCoordinatePath(workingContentList, standardPath, copyObject)
      }
      writeSchemaUpdate(workingContentList)
    }
  },
  checkClipboard: () => computed(() => !!localClipboard.value).value
})

/*
|--------------------------------------------------------------------------
| RUNTIME COMPONENT CONTEXT HELPER METHODS
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
| DATA PLACEHOLDER REPLACEMENT ENGINE
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
| RECURSIVE RENDERER COMPONENT WITH NATIVE DESIGNER OVERLAYS
|--------------------------------------------------------------------------
*/
const Renderer = defineComponent({
  name: 'Renderer',
  props: {
    element: Object,
    form: Object,
    overrides: Object,
    parentActive: Boolean,
    nodePath: { type: Array, default: () => [] }
  },
  inject: ['celinaDnDContext'],
  setup(props) {
    const localForm = props.form || formValues
    const localOverride = props.overrides || overrideMap

    function mergedProps() {
      const name = props.element.props?.name
      const override = name ? localOverride[name] : null
      return {
        ...(props.element.props || {}),
        ...(override?.props || {})
      }
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
          nodePath: [...props.nodePath, index]
        })
      )
    }

    function applyControlElements(tabId) {
      const controls = props.element['control-elements']
      if (!controls) return
      controls.forEach(control => {
        const target = control['target-name']
        const config = control['on-values']?.[tabId]
        if (config) {
          localOverride[target] = config
        }
      })
    }

    const dynamicItems = ref([])
    onMounted(async () => {
      if (props.element['data-source']) {
        try {
          const response = await fetch(props.element['data-source'])
          dynamicItems.value = await response.json()
        } catch (e) {
          console.log(e)
        }
      }
    })

    /*
    |--------------------------------------------------------------------------
    | TRANSPARENT WORKSPACE CANVAS CONTAINER DECORATOR INTERFACE
    |--------------------------------------------------------------------------
    */
    function attachDesignerOverlays(type, coreNodeAttributes, nativeVNodeChildren) {
      const isStructuralContainer = ['box-v', 'box-h', 'box-stack', 'card', 'data-form', 'box-banner', 'grid'].includes(type)
      const clipboardPopulated = props.celinaDnDContext.checkClipboard()

      // Interactive blueprint toolbars overlay controls HUD panel
      const overlayControlsHUD = h('div', { class: 'canvas-element-actions' }, [
        h('span', { class: 'action-badge' }, type),
        h('button', {
          class: 'action-btn copy',
          title: 'Copy component data',
          onClick: (e) => { e.stopPropagation(); props.celinaDnDContext.executeAction('COPY', props.nodePath) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_copy')]),
        isStructuralContainer ? h('button', {
          class: `action-btn paste ${!clipboardPopulated ? 'disabled' : ''}`,
          title: 'Paste element inside structural container',
          disabled: !clipboardPopulated,
          onClick: (e) => { e.stopPropagation(); props.celinaDnDContext.executeAction('PASTE', props.nodePath) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'content_paste')]) : null,
        h('button', {
          class: 'action-btn delete',
          title: 'Delete block',
          onClick: (e) => { e.stopPropagation(); props.celinaDnDContext.executeAction('DELETE', props.nodePath) }
        }, [h('span', { class: 'material-symbols-outlined' }, 'delete')])
      ])

      // Wrap the element rendering within the exact visual style specs expected on screen
      return h('div', {
        class: `canvas-wrapper ${isStructuralContainer ? 'structure-container' : 'structure-leaf'}`,
        draggable: 'true',
        onDragstart: (e) => {
          e.stopPropagation()
          props.celinaDnDContext.signalDragStart(e, props.nodePath)
        },
        onDragover: (e) => {
          e.preventDefault()
          e.stopPropagation()
        },
        onDrop: (e) => {
          e.stopPropagation()
          e.preventDefault()

          const dimensions = e.currentTarget.getBoundingClientRect()
          const exactYOffset = e.clientY - dimensions.top

          if (isStructuralContainer) {
            if (exactYOffset < dimensions.height * 0.2) {
              props.celinaDnDContext.signalDropZone(e, props.nodePath, 'SLOT_BEFORE')
            } else if (exactYOffset > dimensions.height * 0.8) {
              props.celinaDnDContext.signalDropZone(e, props.nodePath, 'SLOT_AFTER')
            } else {
              props.celinaDnDContext.signalDropZone(e, props.nodePath, 'APPEND_INNER')
            }
          } else {
            if (exactYOffset > dimensions.height * 0.5) {
              props.celinaDnDContext.signalDropZone(e, props.nodePath, 'SLOT_AFTER')
            } else {
              props.celinaDnDContext.signalDropZone(e, props.nodePath, 'SLOT_BEFORE')
            }
          }
        }
      }, [
        overlayControlsHUD,
        h(coreNodeAttributes.tag, coreNodeAttributes.attrs, nativeVNodeChildren)
      ])
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /* BOX H */
      if (props.element.type === 'box-h') {
        return attachDesignerOverlays('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '34px',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /* BOX V */
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return attachDesignerOverlays('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '34px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map((item, idx) => {
            const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
            injectData(cloned, item)
            return h(Renderer, {
              element: cloned,
              form: localForm,
              overrides: localOverride,
              nodePath: [...props.nodePath, idx]
            })
          }))
        }

        return attachDesignerOverlays('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '34px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /* BOX STACK */
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return attachDesignerOverlays('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '38px'),
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

      /* BOX BANNER */
      if (props.element.type === 'box-banner') {
        return attachDesignerOverlays('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '45px',
              ...styleObject({ ...s, bgImage: p.bgImage })
            }
          }
        }, renderChildren())
      }

      /* DATA FORM */
      if (props.element.type === 'data-form') {
        return attachDesignerOverlays('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '34px',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /* TEXT */
      if (props.element.type === 'text') {
        return attachDesignerOverlays('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
              minHeight: '16px',
              ...styleObject(s)
            }
          }
        }, p.value || '')
      }

      /* IMAGE */
      if (props.element.type === 'image') {
        return attachDesignerOverlays('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '28px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /* IMAGE PICKER */
      if (props.element.type === 'image-picker') {
        return attachDesignerOverlays('image-picker', {
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

      /* INPUT */
      if (props.element.type === 'input') {
        return attachDesignerOverlays('input', {
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

      /* BUTTON */
      if (props.element.type === 'button') {
        return attachDesignerOverlays('button', {
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

      /* ICON */
      if (props.element.type === 'icon') {
        return attachDesignerOverlays('icon', {
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

      /* GRID */
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return attachDesignerOverlays('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%',
              minHeight: '34px'
            }
          }
        }, renderChildren())
      }

      /* SPACER */
      if (props.element.type === 'spacer') {
        return attachDesignerOverlays('spacer', {
          tag: 'div',
          attrs: {
            style: { minHeight: '12px', ...styleObject(s) }
          }
        }, null)
      }

      /* CARD */
      if (props.element.type === 'card') {
        return attachDesignerOverlays('card', {
          tag: 'div',
          attrs: {
            style: { width: '100%', boxSizing: 'border-box', minHeight: '34px', ...styleObject(s) }
          }
        }, renderChildren())
      }

      /* ITEMS SCROLLER H */
      if (props.element.type === 'items-scroller-h') {
        return attachDesignerOverlays('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: { display: 'flex', overflowX: 'auto', overflowY: 'hidden', width: '100%', boxSizing: 'border-box', minHeight: '44px', ...styleObject(s) }
          }
        }, renderChildren())
      }

      /* GESTURE */
      if (props.element.type === 'gesture') {
        return attachDesignerOverlays('gesture', {
          tag: 'div',
          attrs: {
            style: { cursor: 'pointer', position: 'relative' },
            onClick: () => { if (p.state_key) localForm[p.state_key] = p.set_value }
          }
        }, renderChildren())
      }

      /* TAB MENU */
      if (props.element.type === 'tab-menu') {
        const stateKey = p.state_key || 'tab'
        if (!globalStates[stateKey]) {
          globalStates[stateKey] = p.initial_tab
          applyControlElements(p.initial_tab)
        }

        return attachDesignerOverlays('tab-menu', {
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
              nodePath: [...props.nodePath, index]
            })
          ])
        }))
      }

      /* BOTTOM DRAWER */
      if (props.element.type === 'bottom-drawer') {
        const isOpen = localForm[p.state_key] === 'true'
        if (!isOpen) return null

        return attachDesignerOverlays('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999, background: '#FFF',
              borderTopLeftRadius: '24px', borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
              minHeight: '64px',
              ...styleObject(s)
            }
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

/* PALETTE SIDEBAR UI SPECS */
.palette-panel {
  width: 280px;
  min-width: 280px;
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

.palette-header p {
  font-size: 11px;
  color: #64748b;
  margin: 6px 0 0 0;
  line-height: 1.4;
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
  padding: 10px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
}

.palette-item:hover {
  background: #24354a;
  border-color: #3b82f6;
}

.palette-icon {
  color: #3b82f6;
  font-size: 20px;
}

.palette-text-meta {
  display: flex;
  flex-direction: column;
}

.palette-label {
  color: #e2e8f0;
  font-size: 12px;
  font-weight: 500;
}

.palette-desc {
  color: #64748b;
  font-size: 10px;
  margin-top: 2px;
}

.clipboard-box {
  padding: 12px;
  background: #020617;
  border-top: 1px solid #1e293b;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.clipboard-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #10b981;
}

.clipboard-indicator span { font-size: 20px; }
.clipboard-indicator p { margin: 0; font-size: 11px; font-weight: bold; }
.clipboard-indicator span b { color: #fff; }

.clear-clip-btn {
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  font-size: 10px;
  padding: 3px 8px;
  border-radius: 4px;
  cursor: pointer;
}

/* COMPACT EDITOR PANEL UI SPECS */
.editor-panel {
  width: 30%;
  min-width: 320px;
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
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 36px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
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

/* OVERLAYS & LAYOUT CONTAINER INDICATORS (DND CAPABILITIES) */
:deep(.canvas-wrapper) {
  position: relative;
  transition: all 0.15s ease;
}

:deep(.canvas-element-actions) {
  display: none;
  position: absolute;
  top: -14px;
  right: 6px;
  background: #1e293b;
  border: 1px solid #3b82f6;
  border-radius: 4px;
  padding: 2px 4px;
  align-items: center;
  gap: 4px;
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
  margin-right: 4px;
  text-transform: uppercase;
  font-weight: bold;
}

:deep(.action-btn) {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

:deep(.action-btn span) { font-size: 14px; }
:deep(.action-btn:hover) { background: #334155; color: #fff; }
:deep(.action-btn.delete:hover) { background: #ef4444; color: #fff; }
:deep(.action-btn.disabled) { opacity: 0.2; cursor: not-allowed; }

:deep(.structure-container) {
  min-height: 38px;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.05) !important;
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
}

/* PREVIEW WORKSPACE PANEL SPECS */
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

.layer-dropzone {
  min-height: 100%;
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