<template>
  <div class="app">
    <div class="sidebar-panel">
      <div class="sidebar-header">
        <h3>Components</h3>
      </div>
      <div class="palette-list">
        <div 
          v-for="comp in paletteComponents" 
          :key="comp.type"
          class="palette-item"
          draggable="true"
          @dragstart="onPaletteDragStart($event, comp.type)"
        >
          <span class="material-symbols-outlined">{{ comp.icon }}</span>
          <span>{{ comp.label }}</span>
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
            background:
              parsedData?.theme?.bg
              || '#FFFFFF'
          }"
        >
          <template v-if="parsedData">
            <div 
              class="layer-background"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'background')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'bg-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) === 'background'"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
              </template>
            </div>

            <div 
              class="screen-scroll"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'main')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'main-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) == null"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
              </template>
            </div>

            <div 
              class="layer-root"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'root')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'root-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) === 'root'"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
              </template>
            </div>

            <div 
              class="layer-header"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'header')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'header-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) === 'header'"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
              </template>
            </div>

            <div 
              class="layer-floating"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'floating')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'floating-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) === 'floating'"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
              </template>
            </div>

            <div 
              class="layer-footer"
              @dragover.prevent
              @drop.stop="onCanvasDrop($event, 'footer')"
            >
              <template
                v-for="(item,index) in parsedData.content"
                :key="'footer-'+index"
              >
                <div 
                  v-if="getLayer(getElement(item)) === 'footer'"
                  class="draggable-wrapper"
                  draggable="true"
                  @dragstart.stop="onCanvasDragStart($event, ['content', index])"
                  @dragover.prevent
                  @drop.stop="onCanvasDrop($event, ['content', index])"
                >
                  <Renderer
                    :element="getElement(item)"
                    :path="['content', index]"
                    @update-tree="syncJsonTree"
                  />
                </div>
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
  onMounted
} from 'vue'

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
| DRAG & DROP GLOBAL DOCK
|--------------------------------------------------------------------------
*/
const currentDragSource = ref(null) // 'palette' or 'canvas'
const draggedComponentType = ref(null) // component type string from palette
const draggedNodePath = ref(null) // array path to current layout node instance

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE SPECIFICATION
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-v', label: 'Vertical Box', icon: 'splitscreen' },
  { type: 'box-h', label: 'Horizontal Box', icon: 'view_week' },
  { type: 'box-stack', label: 'Stack Box', icon: 'layers' },
  { type: 'text', label: 'Text Field', icon: 'match_case' },
  { type: 'image', label: 'Image Element', icon: 'image' },
  { type: 'button', label: 'Action Button', icon: 'smart_button' },
  { type: 'input', label: 'Text Input', icon: 'text_fields' },
  { type: 'icon', label: 'Material Icon', icon: 'mood' },
  { type: 'spacer', label: 'Layout Spacer', icon: 'space_bar' },
  { type: 'grid', label: 'Grid System', icon: 'grid_view' }
]

function generateDefaultNode(type) {
  const baseNode = {
    type: type,
    props: {},
    styles: {}
  }

  // Inject defaults to keep layout previews usable upon drops
  if (type === 'text') baseNode.props.value = 'New Text Element'
  if (type === 'button') baseNode.props.value = 'Action Button'
  if (type === 'image') baseNode.props.url = 'https://picsum.photos/200/300'
  if (type === 'icon') baseNode.props.name = 'home'
  if (type === 'input') baseNode.props.placeholder = 'Enter input value...'
  if (['box-v', 'box-h', 'box-stack'].includes(type)) {
    baseNode.children = []
    baseNode.styles.p = '12'
    baseNode.styles.gap = '8'
    if (type === 'box-v' || type === 'box-stack') baseNode.styles.w = 'fill'
    if (type === 'box-h') baseNode.styles.w = 'fill'
  }
  return baseNode
}

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
| JSON
|--------------------------------------------------------------------------
*/

const jsonText = ref(`{
  "theme": {
    "bg": "#1e1e2e"
  },
  "content": [
    {
      "type": "box-v",
      "props": { },
      "styles": { "bg": "#11111b", "p": "16", "w": "fill" },
      "children": []
    }
  ]
}`)

/*
|--------------------------------------------------------------------------
| PARSED
|--------------------------------------------------------------------------
*/

const parsedData = computed(() => {
  try {
    return JSON.parse(jsonText.value)
  }
  catch (e) {
    return null
  }
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

function syncJsonTree(newTree) {
  jsonText.value = JSON.stringify(newTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| TREE NODE UTILITIES FOR NESTED DRAG AND DROP MUTATIONS
|--------------------------------------------------------------------------
*/
function getNodeByPath(root, path) {
  let current = root
  for (let i = 0; i < path.length; i++) {
    current = current[path[i]]
  }
  return current
}

function removeNodeByPath(root, path) {
  const parentPath = path.slice(0, -1)
  const indexToRemove = path[path.length - 1]
  const parent = getNodeByPath(root, parentPath)
  if (Array.isArray(parent)) {
    return parent.splice(indexToRemove, 1)[0]
  }
  return null
}

/*
|--------------------------------------------------------------------------
| DRAG & DROP CORE LOGIC HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  currentDragSource.value = 'palette'
  draggedComponentType.value = type
  event.dataTransfer.effectAllowed = 'copy'
}

function onCanvasDragStart(event, path) {
  currentDragSource.value = 'canvas'
  draggedNodePath.value = path
  event.dataTransfer.effectAllowed = 'move'
}

function onCanvasDrop(event, targetContext) {
  if (!parsedData.value) return
  const freshTree = JSON.parse(JSON.stringify(parsedData.value))
  let activeElement = null

  // 1. Harvest or generate layout node blueprint
  if (currentDragSource.value === 'palette') {
    activeElement = generateDefaultNode(draggedComponentType.value)
  } else if (currentDragSource.value === 'canvas' && draggedNodePath.value) {
    activeElement = removeNodeByPath(freshTree, draggedNodePath.value)
  }

  if (!activeElement) return

  // 2. Resolve drop onto localized dynamic targets
  if (typeof targetContext === 'string') {
    // Structural drops directly into explicit layer root zones
    if (!freshTree.content) freshTree.content = []
    
    if (targetContext !== 'main') {
      if (!activeElement.props) activeElement.props = {}
      activeElement.props.layer = targetContext
    } else {
      if (activeElement.props?.layer) delete activeElement.props.layer
    }
    freshTree.content.push(activeElement)
  } 
  else if (Array.isArray(targetContext)) {
    // Intercepted drops pointing directly onto or inside existing elements
    const targetNode = getNodeByPath(freshTree, targetContext)
    
    if (['box-v', 'box-h', 'box-stack'].includes(targetNode?.type)) {
      if (!targetNode.children) targetNode.children = []
      targetNode.children.push(activeElement)
    } else {
      // Handle siblings placement injection 
      const parentPath = targetContext.slice(0, -1)
      const targetIndex = targetContext[targetContext.length - 1]
      const parentArray = getNodeByPath(freshTree, parentPath)
      if (Array.isArray(parentArray)) {
        parentArray.splice(targetIndex + 1, 0, activeElement)
      }
    }
  }

  syncJsonTree(freshTree)
  resetDragDock()
}

function resetDragDock() {
  currentDragSource.value = null
  draggedComponentType.value = null
  draggedNodePath.value = null
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
    path: Array // Path coordinates tracking depth inside parsed tree layout
  },

  emits: ['update-tree'],

  setup(props, { emit }) {
    const localForm = props.form || formValues
    const localOverride = props.overrides || overrideMap

    /*
    |--------------------------------------------------------------------------
    | MERGED
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | CHILDREN
    |--------------------------------------------------------------------------
    */

    function renderChildren(extra={}) {
      if (!props.element.children) return null

      return props.element.children.map((child, index) => {
        const nextPath = [...(props.path || []), 'children', index]
        
        return h(
          'div',
          {
            class: 'draggable-wrapper nested-element',
            draggable: true,
            onDragstart: (e) => {
              e.stopPropagation()
              currentDragSource.value = 'canvas'
              draggedNodePath.value = nextPath
              e.dataTransfer.effectAllowed = 'move'
            },
            onDragover: (e) => e.preventDefault(),
            onDrop: (e) => {
              e.stopPropagation()
              // Bubbling events payload tracking update tree upwards
              emit('update-tree', handleTreeDrop(nextPath))
            }
          },
          [
            h(Renderer, {
              key: index,
              element: child,
              form: localForm,
              overrides: localOverride,
              parentActive: extra.parentActive,
              path: nextPath,
              onUpdateTree: (newTree) => emit('update-tree', newTree)
            })
          ]
        )
      })
    }

    function handleTreeDrop(targetPath) {
      const freshTree = JSON.parse(JSON.stringify(parsedData.value))
      let sourceNode = null

      if (currentDragSource.value === 'palette') {
        sourceNode = generateDefaultNode(draggedComponentType.value)
      } else if (currentDragSource.value === 'canvas' && draggedNodePath.value) {
        sourceNode = removeNodeByPath(freshTree, draggedNodePath.value)
      }

      if (!sourceNode) return parsedData.value

      const targetNode = getNodeByPath(freshTree, targetPath)
      if (['box-v', 'box-h', 'box-stack'].includes(targetNode?.type)) {
        if (!targetNode.children) targetNode.children = []
        targetNode.children.push(sourceNode)
      } else {
        const parentPath = targetPath.slice(0, -1)
        const targetIndex = targetPath[targetPath.length - 1]
        const parentArray = getNodeByPath(freshTree, parentPath)
        if (Array.isArray(parentArray)) {
          parentArray.splice(targetIndex + 1, 0, sourceNode)
        }
      }

      resetDragDock()
      return freshTree
    }

    /*
    |--------------------------------------------------------------------------
    | CONTROL ELEMENT
    |--------------------------------------------------------------------------
    */

    function applyControlElements(tabId) {
      const controls = props.element['control-elements']
      if (!controls) return

      controls.forEach(control=>{
        const target = control['target-name']
        const config = control['on-values']?.[tabId]
        if (config) {
          localOverride[target] = config
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
      if (props.element['data-source']) {
        try {
          const response = await fetch(props.element['data-source'])
          dynamicItems.value = await response.json()
        }
        catch(e){
          console.log(e)
        }
      }
    })

    return ()=> {
      const p = mergedProps()
      const s = mergedStyles()

      /*
      |--------------------------------------------------------------------------
      | VISIBILITY
      |--------------------------------------------------------------------------
      */

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'box-h') {
        return h(
          'div',
          {
            style:{
              display:'flex',
              flexDirection:'row',
              width:'100%',
              boxSizing:'border-box',
              minHeight: '40px',
              ...styleObject(s)
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BOX V
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return h(
            'div',
            {
              style:{
                display:'flex',
                flexDirection:'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: '40px',
                boxSizing:'border-box',
                ...styleObject(s)
              }
            },
            dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element:cloned,
                form:localForm,
                overrides:localOverride,
                path: props.path,
                onUpdateTree: (newTree) => emit('update-tree', newTree)
              })
            })
          )
        }

        return h(
          'div',
          {
            style:{
              display:'flex',
              flexDirection:'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: '40px',
              boxSizing:'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              ...styleObject(s)
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BOX STACK
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'

        return h(
          'div',
          {
            style:{
              position:'relative',
              display:'flex',
              flexDirection:'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: '40px',
              backgroundImage: p.bgImage ? `url(${p.bgImage})` : undefined,
              backgroundSize:'cover',
              backgroundPosition:'center',
              backgroundRepeat:'no-repeat',
              overflow:'hidden',
              ...styleObject({ ...s, bgImage:null })
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'box-banner') {
        return h(
          'div',
          {
            style:{
              position:'relative',
              overflow:'hidden',
              width:'100%',
              minHeight: '50px',
              ...styleObject({ ...s, bgImage:p.bgImage })
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | DATA FORM
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'data-form') {
        return h(
          'div',
          {
            style:{
              width:'100%',
              ...styleObject(s)
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | TEXT
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'text') {
        return h(
          'div',
          {
            style:{
              boxSizing:'border-box',
              ...styleObject(s)
            }
          },
          p.value || ''
        )
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'image') {
        return h(
          'img',
          {
            src:p.url,
            style:{
              width:'100%',
              display:'block',
              objectFit:'cover',
              ...styleObject(s)
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE PICKER
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'image-picker') {
        return h(
          'label',
          { style:{ display:'block', cursor:'pointer' } },
          [
            h('input', {
              type:'file',
              accept:'image/*',
              style:{ display:'none' },
              onChange:e=>{
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', { src: localForm[p.name], style:{ width:'100%', height:'200px', objectFit:'cover', borderRadius:'12px' } })
              : h('div', { style:{ height:'200px', border:'2px dashed #CBD5E1', borderRadius:'12px', display:'flex', alignItems:'center', justifyContent:'center' } }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'input') {
        return h(
          'input',
          {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput:e=>{ localForm[p.name] = e.target.value },
            style:{
              border:'none',
              outline:'none',
              width:'100%',
              boxSizing:'border-box',
              ...styleObject(s)
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'button') {
        return h(
          'button',
          {
            style:{ border:'none', cursor:'pointer', ...styleObject(s) },
            onClick:()=>{
              if (p.state_key) { localForm[p.state_key] = p.set_value }
              if (props.element.action?.target) { alert('Navigate : ' + props.element.action.target) }
            }
          },
          p.value || 'Button'
        )
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'icon') {
        return h(
          'span',
          {
            class: 'material-symbols-outlined',
            style:{
              fontSize: (s.size || 24) + 'px',
              display:'flex',
              alignItems:'center',
              justifyContent:'center',
              ...styleObject(s)
            }
          },
          iconMap[(p.name || '').toLowerCase()] || 'flash_on'
        )
      }

      /*
      |--------------------------------------------------------------------------
      | GRID
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return h(
          'div',
          {
            style:{
              display:'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width:'100%',
              minHeight: '40px'
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'spacer') {
        return h('div', { style:{ minHeight: '12px', ...styleObject(s) } })
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'card') {
        return h(
          'div',
          { style:{ width:'100%', boxSizing:'border-box', ...styleObject(s) } },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'items-scroller-h') {
        return h(
          'div',
          {
            style:{
              display:'flex',
              overflowX:'auto',
              overflowY:'hidden',
              width:'100%',
              boxSizing:'border-box',
              minHeight: '40px',
              ...styleObject(s)
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'gesture') {
        return h(
          'div',
          {
            style:{ cursor:'pointer', position:'relative' },
            onClick:()=>{ if (p.state_key) { localForm[p.state_key] = p.set_value } }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | TAB MENU
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'tab-menu') {
        const stateKey = p.state_key || 'tab'
        if (!globalStates[stateKey]) {
          globalStates[stateKey] = p.initial_tab
          applyControlElements(p.initial_tab)
        }

        return h(
          'div',
          { style:{ display:'flex', width:'100%', ...styleObject(s) } },
          props.element.children.map(child=>{
            const tabId = child.props?.tab_id
            return h(
              'div',
              {
                style:{ flex:1, cursor:'pointer' },
                onClick:()=>{
                  globalStates[stateKey] = tabId
                  applyControlElements(tabId)
                }
              },
              [
                h(Renderer, {
                  element:child,
                  form:localForm,
                  overrides:localOverride,
                  parentActive: globalStates[stateKey] === tabId,
                  path: props.path,
                  onUpdateTree: (newTree) => emit('update-tree', newTree)
                })
              ]
            )
          })
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BOTTOM DRAWER
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'bottom-drawer') {
        const isOpen = localForm[p.state_key] === 'true'
        if (!isOpen) return null

        return h(
          'div',
          {
            style:{
              position:'absolute',
              left:0, right:0, bottom:0,
              zIndex:999,
              background:'#FFF',
              borderTopLeftRadius:'24px', borderTopRightRadius:'24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
              ...styleObject(s)
            }
          },
          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | UNKNOWN
      |--------------------------------------------------------------------------
      */

      return h(
        'div',
        { style:{ color:'red', fontSize:'12px', padding:'4px'} },
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

/* SIDEBAR STYLING */
.sidebar-panel {
  width: 18%;
  min-width: 200px;
  height: 100%;
  background: #11111b;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 16px;
  color: #cdd6f4;
  border-bottom: 1px solid #1e293b;
}

.palette-list {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow-y: auto;
}

.palette-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: #1e1e2e;
  color: #a6adc8;
  border-radius: 8px;
  cursor: grab;
  border: 1px solid #313244;
  transition: all 0.2s ease;
  user-select: none;
}

.palette-item:hover {
  background: #313244;
  color: #f5e0dc;
  border-color: #2563eb;
}

.palette-item:active {
  cursor: grabbing;
}

/* EDITOR SPLIT LAYOUT ADAPTATIONS */
.editor-panel {
  width: 37%;
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
  font-size: 15px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

/* PREVIEW WORKSPACE AND NESTED CANVA DRAG HOVERS */
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

/* DRAGGABLE WRAPPER & HOVER EFFECTS */
.draggable-wrapper {
  position: relative;
  transition: outline 0.15s ease-in-out;
}

.draggable-wrapper:hover {
  outline: 2px dashed #2563eb;
  outline-offset: -1px;
}

.nested-element {
  width: 100%;
  box-sizing: border-box;
}

.screen-scroll {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  overflow-x: auto;
  z-index: 2;
  padding-bottom: 80px; /* Space helper to drop into container base area */
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