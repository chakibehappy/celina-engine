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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE ITEMS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-h', label: 'Box Horizontal', icon: 'view_column', desc: 'Row Container' },
  { type: 'box-v', label: 'Box Vertical', icon: 'view_stream', desc: 'Column Container' },
  { type: 'box-stack', label: 'Box Stack', icon: 'layers', desc: 'Stacked Layers' },
  { type: 'text', label: 'Text', icon: 'text_fields', desc: 'Typography label' },
  { type: 'image', label: 'Image', icon: 'image', desc: 'Image block frame' },
  { type: 'button', label: 'Button', icon: 'smart_button', desc: 'Action element' },
  { type: 'icon', label: 'Icon', icon: 'star', desc: 'Material System symbol' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Text input capture' },
  { type: 'spacer', label: 'Spacer', icon: 'space_bar', desc: 'Empty spacing element' }
]

function generateDefaultNode(type) {
  const node = { type, props: {}, styles: {} }
  if (type === 'box-h' || type === 'box-v' || type === 'box-stack') {
    node.children = []
    node.styles.p = 12
    node.styles.gap = 8
  } else if (type === 'text') {
    node.props.value = 'New Text Label'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Click Here'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 10
    node.styles.radius = 8
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 120
  } else if (type === 'input') {
    node.props.placeholder = 'Type something...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 8
    node.styles.border = '#CBD5E1'
    node.styles.radius = 6
  } else if (type === 'spacer') {
    node.styles.h = 24
  }
  return node
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
    "bg": "#FFFFFF"
  },
  "content": []
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

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  const fullTree = { ...parsedData.value, content: updatedContent }
  jsonText.value = JSON.stringify(fullTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| ACTIVE NESTED DRAG AND DROP PIPELINE HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'NEW', type }))
}

function onRootLayerDrop(event, layerName) {
  try {
    const rawData = event.dataTransfer.getData('text/plain')
    if (!rawData) return
    const context = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let elementToInsert = null
    if (context.operation === 'NEW') {
      elementToInsert = generateDefaultNode(context.type)
      if (layerName) elementToInsert.props.layer = layerName
    } else if (context.operation === 'MOVE') {
      elementToInsert = extractElementByPath(mutableContent, context.path)
      mutableContent = clearElementByPath(mutableContent, context.path)
      if (layerName) {
        elementToInsert.props = elementToInsert.props || {}
        elementToInsert.props.layer = layerName
      } else {
        if (elementToInsert.props?.layer) delete elementToInsert.props.layer
      }
    }

    if (elementToInsert) {
      mutableContent.push(elementToInsert)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

function extractElementByPath(array, path) {
  let target = array
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) return target[idx]
    target = target[idx].children
  }
  return null
}

function clearElementByPath(array, path) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 1)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

function insertElementByPath(array, path, element) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 0, element)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

provide('treeDragDropContext', {
  triggerNodeDragStart: (event, path) => {
    event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'MOVE', path }))
  },
  triggerNodeDrop: (event, destinationPath, layoutContext) => {
    try {
      const rawData = event.dataTransfer.getData('text/plain')
      if (!rawData) return
      const context = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

      let elementToInsert = null
      if (context.operation === 'NEW') {
        elementToInsert = generateDefaultNode(context.type)
      } else if (context.operation === 'MOVE') {
        const sourceStr = JSON.stringify(context.path)
        const destStr = JSON.stringify(destinationPath)
        if (destStr.startsWith(sourceStr)) {
          alert("Invalid Move Placement: Cannot nest a parent container into its own layout branch.")
          return
        }
        elementToInsert = extractElementByPath(mutableContent, context.path)
        mutableContent = clearElementByPath(mutableContent, context.path)

        if (context.path.length === destinationPath.length) {
          const matchingHierarchy = context.path.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (matchingHierarchy && context.path[context.path.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (elementToInsert) {
        if (layoutContext === 'APPEND_INSIDE') {
          let node = extractElementByPath(mutableContent, destinationPath)
          if (node) {
            node.children = node.children || []
            node.children.push(elementToInsert)
          }
        } else if (layoutContext === 'INSERT_BEFORE') {
          mutableContent = insertElementByPath(mutableContent, destinationPath, elementToInsert)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
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

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/

function injectData(node,data) {
  if (!node) return
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

  if (styles.w) {
    if (styles.w === 'fill') { obj.width = '100%' }
    else if (String(styles.w).includes('%')) { obj.width = styles.w }
    else { obj.width = styles.w + 'px' }
  }

  if (styles.h) {
    if (styles.h === 'fill') { obj.height = '100%' }
    else if (String(styles.h).includes('%')) { obj.height = styles.h }
    else { obj.height = styles.h + 'px' }
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
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0
    const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') { obj.overflowY = 'auto' }

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
    path: Array
  },
  inject: ['treeDragDropContext'],

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

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) =>
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

    function injectDragDropZone(type, nodeConfig, nodes) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack', 'box-banner', 'card', 'data-form'].includes(type)
      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          style: { position: 'relative' },
          onDragstart: (e) => {
            e.stopPropagation()
            props.treeDragDropContext.triggerNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            const boundingBox = e.currentTarget.getBoundingClientRect()
            const pointerY = e.clientY - boundingBox.top
            
            if (isStructureContainer) {
              // If dropped directly in the upper or lower 20% margin edge, handle it as an insertion layout shortcut
              if (pointerY < boundingBox.height * 0.20) {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
              } else {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'APPEND_INSIDE')
              }
            } else {
              props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
            }
          }
        },
        [
          h('span', { class: 'canvas-node-indicator' }, type),
          h(nodeConfig.tag, nodeConfig.attrs, nodes)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return injectDragDropZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '44px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return injectDragDropZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '44px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element: cloned,
                form: localForm,
                overrides: localOverride,
                path: props.path
              })
          }))
        }

        return injectDragDropZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '44px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return injectDragDropZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '44px'),
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

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-banner') {
        return injectDragDropZone('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'data-form') {
        return injectDragDropZone('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'text') {
        return injectDragDropZone('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
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
      if (props.element.type === 'image') {
        return injectDragDropZone('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '20px',
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
      if (props.element.type === 'image-picker') {
        return injectDragDropZone('image-picker', {
          tag: 'label',
          attrs: {
            style: {
              display: 'block',
              cursor: 'pointer'
            }
          }
        }, [
            h('input', {
              type: 'file',
              accept: 'image/*',
              style: { display: 'none' },
              onChange: e => {
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', {
                  src: localForm[p.name],
                  style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' }
                })
              : h('div', {
                  style: {
                    height: '200px',
                    border: '2px dashed #CBD5E1',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }
                }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'input') {
        return injectDragDropZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'button') {
        return injectDragDropZone('button', {
          tag: 'button',
          attrs: {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(s)
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
              if (props.element.action?.target) {
                alert('Navigate : ' + props.element.action.target)
              }
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'icon') {
        return injectDragDropZone('icon', {
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
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return injectDragDropZone('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'spacer') {
        return injectDragDropZone('spacer', {
          tag: 'div',
          attrs: {
            style: {
              minHeight: '8px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'card') {
        return injectDragDropZone('card', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'items-scroller-h') {
        return injectDragDropZone('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'gesture') {
        return injectDragDropZone('gesture', {
          tag: 'div',
          attrs: {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
            }
          }
        }, renderChildren())
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

        return injectDragDropZone('tab-menu', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              width: '100%',
              ...styleObject(s)
            }
          }
        }, props.element.children.map(child => {
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
                  path: props.path,
                  parentActive: globalStates[stateKey] === tabId
                })
              ])
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

        return injectDragDropZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999,
              background: '#FFF',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
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
      return h('div', {
          style: { color: 'red', fontSize: '12px', padding: '4px' }
        }, `UNKNOWN TYPE : ${props.element.type}`)
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

.palette-panel {
  width: 240px;
  min-width: 240px;
  background: #090d16;
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
  color: #f1f5f9;
  font-size: 14px;
  font-weight: 600;
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
  gap: 12px;
  padding: 8px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.palette-item:hover {
  background: #273549;
  border-color: #3b82f6;
}

.palette-item:active {
  cursor: grabbing;
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

.editor-panel {
  width: 30%;
  min-width: 300px;
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
  font-size: 13px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
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

:deep(.canvas-drag-wrapper:hover > .canvas-node-indicator) {
  display: inline-block;
}

:deep(.canvas-node-indicator) {
  display: none;
  position: absolute;
  top: -2px;
  left: 2px;
  background: #2563eb;
  color: #ffffff;
  font-family: monospace;
  font-size: 8px;
  padding: 1px 3px;
  border-radius: 3px;
  z-index: 9999;
  pointer-events: none;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
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
</style><template>
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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE ITEMS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-h', label: 'Box Horizontal', icon: 'view_column', desc: 'Row Container' },
  { type: 'box-v', label: 'Box Vertical', icon: 'view_stream', desc: 'Column Container' },
  { type: 'box-stack', label: 'Box Stack', icon: 'layers', desc: 'Stacked Layers' },
  { type: 'text', label: 'Text', icon: 'text_fields', desc: 'Typography label' },
  { type: 'image', label: 'Image', icon: 'image', desc: 'Image block frame' },
  { type: 'button', label: 'Button', icon: 'smart_button', desc: 'Action element' },
  { type: 'icon', label: 'Icon', icon: 'star', desc: 'Material System symbol' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Text input capture' },
  { type: 'spacer', label: 'Spacer', icon: 'space_bar', desc: 'Empty spacing element' }
]

function generateDefaultNode(type) {
  const node = { type, props: {}, styles: {} }
  if (type === 'box-h' || type === 'box-v' || type === 'box-stack') {
    node.children = []
    node.styles.p = 12
    node.styles.gap = 8
  } else if (type === 'text') {
    node.props.value = 'New Text Label'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Click Here'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 10
    node.styles.radius = 8
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 120
  } else if (type === 'input') {
    node.props.placeholder = 'Type something...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 8
    node.styles.border = '#CBD5E1'
    node.styles.radius = 6
  } else if (type === 'spacer') {
    node.styles.h = 24
  }
  return node
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
    "bg": "#FFFFFF"
  },
  "content": []
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

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  const fullTree = { ...parsedData.value, content: updatedContent }
  jsonText.value = JSON.stringify(fullTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| ACTIVE NESTED DRAG AND DROP PIPELINE HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'NEW', type }))
}

function onRootLayerDrop(event, layerName) {
  try {
    const rawData = event.dataTransfer.getData('text/plain')
    if (!rawData) return
    const context = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let elementToInsert = null
    if (context.operation === 'NEW') {
      elementToInsert = generateDefaultNode(context.type)
      if (layerName) elementToInsert.props.layer = layerName
    } else if (context.operation === 'MOVE') {
      elementToInsert = extractElementByPath(mutableContent, context.path)
      mutableContent = clearElementByPath(mutableContent, context.path)
      if (layerName) {
        elementToInsert.props = elementToInsert.props || {}
        elementToInsert.props.layer = layerName
      } else {
        if (elementToInsert.props?.layer) delete elementToInsert.props.layer
      }
    }

    if (elementToInsert) {
      mutableContent.push(elementToInsert)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

function extractElementByPath(array, path) {
  let target = array
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) return target[idx]
    target = target[idx].children
  }
  return null
}

function clearElementByPath(array, path) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 1)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

function insertElementByPath(array, path, element) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 0, element)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

provide('treeDragDropContext', {
  triggerNodeDragStart: (event, path) => {
    event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'MOVE', path }))
  },
  triggerNodeDrop: (event, destinationPath, layoutContext) => {
    try {
      const rawData = event.dataTransfer.getData('text/plain')
      if (!rawData) return
      const context = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

      let elementToInsert = null
      if (context.operation === 'NEW') {
        elementToInsert = generateDefaultNode(context.type)
      } else if (context.operation === 'MOVE') {
        const sourceStr = JSON.stringify(context.path)
        const destStr = JSON.stringify(destinationPath)
        if (destStr.startsWith(sourceStr)) {
          alert("Invalid Move Placement: Cannot nest a parent container into its own layout branch.")
          return
        }
        elementToInsert = extractElementByPath(mutableContent, context.path)
        mutableContent = clearElementByPath(mutableContent, context.path)

        if (context.path.length === destinationPath.length) {
          const matchingHierarchy = context.path.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (matchingHierarchy && context.path[context.path.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (elementToInsert) {
        if (layoutContext === 'APPEND_INSIDE') {
          let node = extractElementByPath(mutableContent, destinationPath)
          if (node) {
            node.children = node.children || []
            node.children.push(elementToInsert)
          }
        } else if (layoutContext === 'INSERT_BEFORE') {
          mutableContent = insertElementByPath(mutableContent, destinationPath, elementToInsert)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
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

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/

function injectData(node,data) {
  if (!node) return
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

  if (styles.w) {
    if (styles.w === 'fill') { obj.width = '100%' }
    else if (String(styles.w).includes('%')) { obj.width = styles.w }
    else { obj.width = styles.w + 'px' }
  }

  if (styles.h) {
    if (styles.h === 'fill') { obj.height = '100%' }
    else if (String(styles.h).includes('%')) { obj.height = styles.h }
    else { obj.height = styles.h + 'px' }
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
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0
    const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') { obj.overflowY = 'auto' }

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
    path: Array
  },
  inject: ['treeDragDropContext'],

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

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) =>
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

    function injectDragDropZone(type, nodeConfig, nodes) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack', 'box-banner', 'card', 'data-form'].includes(type)
      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          style: { position: 'relative' },
          onDragstart: (e) => {
            e.stopPropagation()
            props.treeDragDropContext.triggerNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            const boundingBox = e.currentTarget.getBoundingClientRect()
            const pointerY = e.clientY - boundingBox.top
            
            if (isStructureContainer) {
              // If dropped directly in the upper or lower 20% margin edge, handle it as an insertion layout shortcut
              if (pointerY < boundingBox.height * 0.20) {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
              } else {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'APPEND_INSIDE')
              }
            } else {
              props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
            }
          }
        },
        [
          h('span', { class: 'canvas-node-indicator' }, type),
          h(nodeConfig.tag, nodeConfig.attrs, nodes)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return injectDragDropZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '44px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return injectDragDropZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '44px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element: cloned,
                form: localForm,
                overrides: localOverride,
                path: props.path
              })
          }))
        }

        return injectDragDropZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '44px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return injectDragDropZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '44px'),
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

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-banner') {
        return injectDragDropZone('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'data-form') {
        return injectDragDropZone('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'text') {
        return injectDragDropZone('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
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
      if (props.element.type === 'image') {
        return injectDragDropZone('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '20px',
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
      if (props.element.type === 'image-picker') {
        return injectDragDropZone('image-picker', {
          tag: 'label',
          attrs: {
            style: {
              display: 'block',
              cursor: 'pointer'
            }
          }
        }, [
            h('input', {
              type: 'file',
              accept: 'image/*',
              style: { display: 'none' },
              onChange: e => {
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', {
                  src: localForm[p.name],
                  style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' }
                })
              : h('div', {
                  style: {
                    height: '200px',
                    border: '2px dashed #CBD5E1',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }
                }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'input') {
        return injectDragDropZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'button') {
        return injectDragDropZone('button', {
          tag: 'button',
          attrs: {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(s)
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
              if (props.element.action?.target) {
                alert('Navigate : ' + props.element.action.target)
              }
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'icon') {
        return injectDragDropZone('icon', {
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
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return injectDragDropZone('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'spacer') {
        return injectDragDropZone('spacer', {
          tag: 'div',
          attrs: {
            style: {
              minHeight: '8px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'card') {
        return injectDragDropZone('card', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'items-scroller-h') {
        return injectDragDropZone('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'gesture') {
        return injectDragDropZone('gesture', {
          tag: 'div',
          attrs: {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
            }
          }
        }, renderChildren())
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

        return injectDragDropZone('tab-menu', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              width: '100%',
              ...styleObject(s)
            }
          }
        }, props.element.children.map(child => {
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
                  path: props.path,
                  parentActive: globalStates[stateKey] === tabId
                })
              ])
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

        return injectDragDropZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999,
              background: '#FFF',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
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
      return h('div', {
          style: { color: 'red', fontSize: '12px', padding: '4px' }
        }, `UNKNOWN TYPE : ${props.element.type}`)
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

.palette-panel {
  width: 240px;
  min-width: 240px;
  background: #090d16;
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
  color: #f1f5f9;
  font-size: 14px;
  font-weight: 600;
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
  gap: 12px;
  padding: 8px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.palette-item:hover {
  background: #273549;
  border-color: #3b82f6;
}

.palette-item:active {
  cursor: grabbing;
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

.editor-panel {
  width: 30%;
  min-width: 300px;
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
  font-size: 13px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
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

:deep(.canvas-drag-wrapper:hover > .canvas-node-indicator) {
  display: inline-block;
}

:deep(.canvas-node-indicator) {
  display: none;
  position: absolute;
  top: -2px;
  left: 2px;
  background: #2563eb;
  color: #ffffff;
  font-family: monospace;
  font-size: 8px;
  padding: 1px 3px;
  border-radius: 3px;
  z-index: 9999;
  pointer-events: none;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
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
</style><template>
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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE ITEMS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-h', label: 'Box Horizontal', icon: 'view_column', desc: 'Row Container' },
  { type: 'box-v', label: 'Box Vertical', icon: 'view_stream', desc: 'Column Container' },
  { type: 'box-stack', label: 'Box Stack', icon: 'layers', desc: 'Stacked Layers' },
  { type: 'text', label: 'Text', icon: 'text_fields', desc: 'Typography label' },
  { type: 'image', label: 'Image', icon: 'image', desc: 'Image block frame' },
  { type: 'button', label: 'Button', icon: 'smart_button', desc: 'Action element' },
  { type: 'icon', label: 'Icon', icon: 'star', desc: 'Material System symbol' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Text input capture' },
  { type: 'spacer', label: 'Spacer', icon: 'space_bar', desc: 'Empty spacing element' }
]

function generateDefaultNode(type) {
  const node = { type, props: {}, styles: {} }
  if (type === 'box-h' || type === 'box-v' || type === 'box-stack') {
    node.children = []
    node.styles.p = 12
    node.styles.gap = 8
  } else if (type === 'text') {
    node.props.value = 'New Text Label'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Click Here'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 10
    node.styles.radius = 8
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 120
  } else if (type === 'input') {
    node.props.placeholder = 'Type something...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 8
    node.styles.border = '#CBD5E1'
    node.styles.radius = 6
  } else if (type === 'spacer') {
    node.styles.h = 24
  }
  return node
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
    "bg": "#FFFFFF"
  },
  "content": []
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

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  const fullTree = { ...parsedData.value, content: updatedContent }
  jsonText.value = JSON.stringify(fullTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| ACTIVE NESTED DRAG AND DROP PIPELINE HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'NEW', type }))
}

function onRootLayerDrop(event, layerName) {
  try {
    const rawData = event.dataTransfer.getData('text/plain')
    if (!rawData) return
    const context = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let elementToInsert = null
    if (context.operation === 'NEW') {
      elementToInsert = generateDefaultNode(context.type)
      if (layerName) elementToInsert.props.layer = layerName
    } else if (context.operation === 'MOVE') {
      elementToInsert = extractElementByPath(mutableContent, context.path)
      mutableContent = clearElementByPath(mutableContent, context.path)
      if (layerName) {
        elementToInsert.props = elementToInsert.props || {}
        elementToInsert.props.layer = layerName
      } else {
        if (elementToInsert.props?.layer) delete elementToInsert.props.layer
      }
    }

    if (elementToInsert) {
      mutableContent.push(elementToInsert)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

function extractElementByPath(array, path) {
  let target = array
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) return target[idx]
    target = target[idx].children
  }
  return null
}

function clearElementByPath(array, path) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 1)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

function insertElementByPath(array, path, element) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 0, element)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

provide('treeDragDropContext', {
  triggerNodeDragStart: (event, path) => {
    event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'MOVE', path }))
  },
  triggerNodeDrop: (event, destinationPath, layoutContext) => {
    try {
      const rawData = event.dataTransfer.getData('text/plain')
      if (!rawData) return
      const context = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

      let elementToInsert = null
      if (context.operation === 'NEW') {
        elementToInsert = generateDefaultNode(context.type)
      } else if (context.operation === 'MOVE') {
        const sourceStr = JSON.stringify(context.path)
        const destStr = JSON.stringify(destinationPath)
        if (destStr.startsWith(sourceStr)) {
          alert("Invalid Move Placement: Cannot nest a parent container into its own layout branch.")
          return
        }
        elementToInsert = extractElementByPath(mutableContent, context.path)
        mutableContent = clearElementByPath(mutableContent, context.path)

        if (context.path.length === destinationPath.length) {
          const matchingHierarchy = context.path.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (matchingHierarchy && context.path[context.path.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (elementToInsert) {
        if (layoutContext === 'APPEND_INSIDE') {
          let node = extractElementByPath(mutableContent, destinationPath)
          if (node) {
            node.children = node.children || []
            node.children.push(elementToInsert)
          }
        } else if (layoutContext === 'INSERT_BEFORE') {
          mutableContent = insertElementByPath(mutableContent, destinationPath, elementToInsert)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
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

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/

function injectData(node,data) {
  if (!node) return
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

  if (styles.w) {
    if (styles.w === 'fill') { obj.width = '100%' }
    else if (String(styles.w).includes('%')) { obj.width = styles.w }
    else { obj.width = styles.w + 'px' }
  }

  if (styles.h) {
    if (styles.h === 'fill') { obj.height = '100%' }
    else if (String(styles.h).includes('%')) { obj.height = styles.h }
    else { obj.height = styles.h + 'px' }
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
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0
    const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') { obj.overflowY = 'auto' }

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
    path: Array
  },
  inject: ['treeDragDropContext'],

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

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) =>
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

    function injectDragDropZone(type, nodeConfig, nodes) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack', 'box-banner', 'card', 'data-form'].includes(type)
      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          style: { position: 'relative' },
          onDragstart: (e) => {
            e.stopPropagation()
            props.treeDragDropContext.triggerNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            const boundingBox = e.currentTarget.getBoundingClientRect()
            const pointerY = e.clientY - boundingBox.top
            
            if (isStructureContainer) {
              // If dropped directly in the upper or lower 20% margin edge, handle it as an insertion layout shortcut
              if (pointerY < boundingBox.height * 0.20) {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
              } else {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'APPEND_INSIDE')
              }
            } else {
              props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
            }
          }
        },
        [
          h('span', { class: 'canvas-node-indicator' }, type),
          h(nodeConfig.tag, nodeConfig.attrs, nodes)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return injectDragDropZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '44px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return injectDragDropZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '44px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element: cloned,
                form: localForm,
                overrides: localOverride,
                path: props.path
              })
          }))
        }

        return injectDragDropZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '44px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return injectDragDropZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '44px'),
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

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-banner') {
        return injectDragDropZone('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'data-form') {
        return injectDragDropZone('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'text') {
        return injectDragDropZone('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
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
      if (props.element.type === 'image') {
        return injectDragDropZone('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '20px',
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
      if (props.element.type === 'image-picker') {
        return injectDragDropZone('image-picker', {
          tag: 'label',
          attrs: {
            style: {
              display: 'block',
              cursor: 'pointer'
            }
          }
        }, [
            h('input', {
              type: 'file',
              accept: 'image/*',
              style: { display: 'none' },
              onChange: e => {
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', {
                  src: localForm[p.name],
                  style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' }
                })
              : h('div', {
                  style: {
                    height: '200px',
                    border: '2px dashed #CBD5E1',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }
                }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'input') {
        return injectDragDropZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'button') {
        return injectDragDropZone('button', {
          tag: 'button',
          attrs: {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(s)
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
              if (props.element.action?.target) {
                alert('Navigate : ' + props.element.action.target)
              }
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'icon') {
        return injectDragDropZone('icon', {
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
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return injectDragDropZone('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'spacer') {
        return injectDragDropZone('spacer', {
          tag: 'div',
          attrs: {
            style: {
              minHeight: '8px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'card') {
        return injectDragDropZone('card', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'items-scroller-h') {
        return injectDragDropZone('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'gesture') {
        return injectDragDropZone('gesture', {
          tag: 'div',
          attrs: {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
            }
          }
        }, renderChildren())
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

        return injectDragDropZone('tab-menu', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              width: '100%',
              ...styleObject(s)
            }
          }
        }, props.element.children.map(child => {
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
                  path: props.path,
                  parentActive: globalStates[stateKey] === tabId
                })
              ])
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

        return injectDragDropZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999,
              background: '#FFF',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
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
      return h('div', {
          style: { color: 'red', fontSize: '12px', padding: '4px' }
        }, `UNKNOWN TYPE : ${props.element.type}`)
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

.palette-panel {
  width: 240px;
  min-width: 240px;
  background: #090d16;
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
  color: #f1f5f9;
  font-size: 14px;
  font-weight: 600;
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
  gap: 12px;
  padding: 8px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.palette-item:hover {
  background: #273549;
  border-color: #3b82f6;
}

.palette-item:active {
  cursor: grabbing;
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

.editor-panel {
  width: 30%;
  min-width: 300px;
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
  font-size: 13px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
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

:deep(.canvas-drag-wrapper:hover > .canvas-node-indicator) {
  display: inline-block;
}

:deep(.canvas-node-indicator) {
  display: none;
  position: absolute;
  top: -2px;
  left: 2px;
  background: #2563eb;
  color: #ffffff;
  font-family: monospace;
  font-size: 8px;
  padding: 1px 3px;
  border-radius: 3px;
  z-index: 9999;
  pointer-events: none;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
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
</style><template>
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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE ITEMS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-h', label: 'Box Horizontal', icon: 'view_column', desc: 'Row Container' },
  { type: 'box-v', label: 'Box Vertical', icon: 'view_stream', desc: 'Column Container' },
  { type: 'box-stack', label: 'Box Stack', icon: 'layers', desc: 'Stacked Layers' },
  { type: 'text', label: 'Text', icon: 'text_fields', desc: 'Typography label' },
  { type: 'image', label: 'Image', icon: 'image', desc: 'Image block frame' },
  { type: 'button', label: 'Button', icon: 'smart_button', desc: 'Action element' },
  { type: 'icon', label: 'Icon', icon: 'star', desc: 'Material System symbol' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Text input capture' },
  { type: 'spacer', label: 'Spacer', icon: 'space_bar', desc: 'Empty spacing element' }
]

function generateDefaultNode(type) {
  const node = { type, props: {}, styles: {} }
  if (type === 'box-h' || type === 'box-v' || type === 'box-stack') {
    node.children = []
    node.styles.p = 12
    node.styles.gap = 8
  } else if (type === 'text') {
    node.props.value = 'New Text Label'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Click Here'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 10
    node.styles.radius = 8
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 120
  } else if (type === 'input') {
    node.props.placeholder = 'Type something...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 8
    node.styles.border = '#CBD5E1'
    node.styles.radius = 6
  } else if (type === 'spacer') {
    node.styles.h = 24
  }
  return node
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
    "bg": "#FFFFFF"
  },
  "content": []
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

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  const fullTree = { ...parsedData.value, content: updatedContent }
  jsonText.value = JSON.stringify(fullTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| ACTIVE NESTED DRAG AND DROP PIPELINE HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'NEW', type }))
}

function onRootLayerDrop(event, layerName) {
  try {
    const rawData = event.dataTransfer.getData('text/plain')
    if (!rawData) return
    const context = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let elementToInsert = null
    if (context.operation === 'NEW') {
      elementToInsert = generateDefaultNode(context.type)
      if (layerName) elementToInsert.props.layer = layerName
    } else if (context.operation === 'MOVE') {
      elementToInsert = extractElementByPath(mutableContent, context.path)
      mutableContent = clearElementByPath(mutableContent, context.path)
      if (layerName) {
        elementToInsert.props = elementToInsert.props || {}
        elementToInsert.props.layer = layerName
      } else {
        if (elementToInsert.props?.layer) delete elementToInsert.props.layer
      }
    }

    if (elementToInsert) {
      mutableContent.push(elementToInsert)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

function extractElementByPath(array, path) {
  let target = array
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) return target[idx]
    target = target[idx].children
  }
  return null
}

function clearElementByPath(array, path) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 1)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

function insertElementByPath(array, path, element) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 0, element)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

provide('treeDragDropContext', {
  triggerNodeDragStart: (event, path) => {
    event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'MOVE', path }))
  },
  triggerNodeDrop: (event, destinationPath, layoutContext) => {
    try {
      const rawData = event.dataTransfer.getData('text/plain')
      if (!rawData) return
      const context = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

      let elementToInsert = null
      if (context.operation === 'NEW') {
        elementToInsert = generateDefaultNode(context.type)
      } else if (context.operation === 'MOVE') {
        const sourceStr = JSON.stringify(context.path)
        const destStr = JSON.stringify(destinationPath)
        if (destStr.startsWith(sourceStr)) {
          alert("Invalid Move Placement: Cannot nest a parent container into its own layout branch.")
          return
        }
        elementToInsert = extractElementByPath(mutableContent, context.path)
        mutableContent = clearElementByPath(mutableContent, context.path)

        if (context.path.length === destinationPath.length) {
          const matchingHierarchy = context.path.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (matchingHierarchy && context.path[context.path.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (elementToInsert) {
        if (layoutContext === 'APPEND_INSIDE') {
          let node = extractElementByPath(mutableContent, destinationPath)
          if (node) {
            node.children = node.children || []
            node.children.push(elementToInsert)
          }
        } else if (layoutContext === 'INSERT_BEFORE') {
          mutableContent = insertElementByPath(mutableContent, destinationPath, elementToInsert)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
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

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/

function injectData(node,data) {
  if (!node) return
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

  if (styles.w) {
    if (styles.w === 'fill') { obj.width = '100%' }
    else if (String(styles.w).includes('%')) { obj.width = styles.w }
    else { obj.width = styles.w + 'px' }
  }

  if (styles.h) {
    if (styles.h === 'fill') { obj.height = '100%' }
    else if (String(styles.h).includes('%')) { obj.height = styles.h }
    else { obj.height = styles.h + 'px' }
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
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0
    const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') { obj.overflowY = 'auto' }

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
    path: Array
  },
  inject: ['treeDragDropContext'],

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

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) =>
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

    function injectDragDropZone(type, nodeConfig, nodes) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack', 'box-banner', 'card', 'data-form'].includes(type)
      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          style: { position: 'relative' },
          onDragstart: (e) => {
            e.stopPropagation()
            props.treeDragDropContext.triggerNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            const boundingBox = e.currentTarget.getBoundingClientRect()
            const pointerY = e.clientY - boundingBox.top
            
            if (isStructureContainer) {
              // If dropped directly in the upper or lower 20% margin edge, handle it as an insertion layout shortcut
              if (pointerY < boundingBox.height * 0.20) {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
              } else {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'APPEND_INSIDE')
              }
            } else {
              props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
            }
          }
        },
        [
          h('span', { class: 'canvas-node-indicator' }, type),
          h(nodeConfig.tag, nodeConfig.attrs, nodes)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return injectDragDropZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '44px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return injectDragDropZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '44px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element: cloned,
                form: localForm,
                overrides: localOverride,
                path: props.path
              })
          }))
        }

        return injectDragDropZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '44px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return injectDragDropZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '44px'),
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

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-banner') {
        return injectDragDropZone('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'data-form') {
        return injectDragDropZone('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'text') {
        return injectDragDropZone('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
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
      if (props.element.type === 'image') {
        return injectDragDropZone('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '20px',
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
      if (props.element.type === 'image-picker') {
        return injectDragDropZone('image-picker', {
          tag: 'label',
          attrs: {
            style: {
              display: 'block',
              cursor: 'pointer'
            }
          }
        }, [
            h('input', {
              type: 'file',
              accept: 'image/*',
              style: { display: 'none' },
              onChange: e => {
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', {
                  src: localForm[p.name],
                  style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' }
                })
              : h('div', {
                  style: {
                    height: '200px',
                    border: '2px dashed #CBD5E1',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }
                }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'input') {
        return injectDragDropZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'button') {
        return injectDragDropZone('button', {
          tag: 'button',
          attrs: {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(s)
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
              if (props.element.action?.target) {
                alert('Navigate : ' + props.element.action.target)
              }
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'icon') {
        return injectDragDropZone('icon', {
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
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return injectDragDropZone('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'spacer') {
        return injectDragDropZone('spacer', {
          tag: 'div',
          attrs: {
            style: {
              minHeight: '8px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'card') {
        return injectDragDropZone('card', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'items-scroller-h') {
        return injectDragDropZone('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'gesture') {
        return injectDragDropZone('gesture', {
          tag: 'div',
          attrs: {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
            }
          }
        }, renderChildren())
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

        return injectDragDropZone('tab-menu', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              width: '100%',
              ...styleObject(s)
            }
          }
        }, props.element.children.map(child => {
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
                  path: props.path,
                  parentActive: globalStates[stateKey] === tabId
                })
              ])
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

        return injectDragDropZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999,
              background: '#FFF',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
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
      return h('div', {
          style: { color: 'red', fontSize: '12px', padding: '4px' }
        }, `UNKNOWN TYPE : ${props.element.type}`)
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

.palette-panel {
  width: 240px;
  min-width: 240px;
  background: #090d16;
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
  color: #f1f5f9;
  font-size: 14px;
  font-weight: 600;
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
  gap: 12px;
  padding: 8px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.palette-item:hover {
  background: #273549;
  border-color: #3b82f6;
}

.palette-item:active {
  cursor: grabbing;
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

.editor-panel {
  width: 30%;
  min-width: 300px;
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
  font-size: 13px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
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

:deep(.canvas-drag-wrapper:hover > .canvas-node-indicator) {
  display: inline-block;
}

:deep(.canvas-node-indicator) {
  display: none;
  position: absolute;
  top: -2px;
  left: 2px;
  background: #2563eb;
  color: #ffffff;
  font-family: monospace;
  font-size: 8px;
  padding: 1px 3px;
  border-radius: 3px;
  z-index: 9999;
  pointer-events: none;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
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
</style><template>
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
  onMounted,
  provide
} from 'vue'

/*
|--------------------------------------------------------------------------
| COMPONENT PALETTE ITEMS
|--------------------------------------------------------------------------
*/
const paletteComponents = [
  { type: 'box-h', label: 'Box Horizontal', icon: 'view_column', desc: 'Row Container' },
  { type: 'box-v', label: 'Box Vertical', icon: 'view_stream', desc: 'Column Container' },
  { type: 'box-stack', label: 'Box Stack', icon: 'layers', desc: 'Stacked Layers' },
  { type: 'text', label: 'Text', icon: 'text_fields', desc: 'Typography label' },
  { type: 'image', label: 'Image', icon: 'image', desc: 'Image block frame' },
  { type: 'button', label: 'Button', icon: 'smart_button', desc: 'Action element' },
  { type: 'icon', label: 'Icon', icon: 'star', desc: 'Material System symbol' },
  { type: 'input', label: 'Input Field', icon: 'input', desc: 'Text input capture' },
  { type: 'spacer', label: 'Spacer', icon: 'space_bar', desc: 'Empty spacing element' }
]

function generateDefaultNode(type) {
  const node = { type, props: {}, styles: {} }
  if (type === 'box-h' || type === 'box-v' || type === 'box-stack') {
    node.children = []
    node.styles.p = 12
    node.styles.gap = 8
  } else if (type === 'text') {
    node.props.value = 'New Text Label'
    node.styles.fontSize = 14
  } else if (type === 'button') {
    node.props.value = 'Click Here'
    node.styles.bg = '#2563eb'
    node.styles.color = '#ffffff'
    node.styles.p = 10
    node.styles.radius = 8
  } else if (type === 'icon') {
    node.props.name = 'home'
    node.styles.size = 24
  } else if (type === 'image') {
    node.props.url = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400'
    node.styles.h = 120
  } else if (type === 'input') {
    node.props.placeholder = 'Type something...'
    node.props.name = 'field_' + Math.random().toString(36).substring(7)
    node.styles.p = 8
    node.styles.border = '#CBD5E1'
    node.styles.radius = 6
  } else if (type === 'spacer') {
    node.styles.h = 24
  }
  return node
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
    "bg": "#FFFFFF"
  },
  "content": []
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

function syncJsonTree(updatedContent) {
  if (!parsedData.value) return
  const fullTree = { ...parsedData.value, content: updatedContent }
  jsonText.value = JSON.stringify(fullTree, null, 2)
}

/*
|--------------------------------------------------------------------------
| ACTIVE NESTED DRAG AND DROP PIPELINE HANDLERS
|--------------------------------------------------------------------------
*/
function onPaletteDragStart(event, type) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'NEW', type }))
}

function onRootLayerDrop(event, layerName) {
  try {
    const rawData = event.dataTransfer.getData('text/plain')
    if (!rawData) return
    const context = JSON.parse(rawData)
    let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

    let elementToInsert = null
    if (context.operation === 'NEW') {
      elementToInsert = generateDefaultNode(context.type)
      if (layerName) elementToInsert.props.layer = layerName
    } else if (context.operation === 'MOVE') {
      elementToInsert = extractElementByPath(mutableContent, context.path)
      mutableContent = clearElementByPath(mutableContent, context.path)
      if (layerName) {
        elementToInsert.props = elementToInsert.props || {}
        elementToInsert.props.layer = layerName
      } else {
        if (elementToInsert.props?.layer) delete elementToInsert.props.layer
      }
    }

    if (elementToInsert) {
      mutableContent.push(elementToInsert)
      syncJsonTree(mutableContent)
    }
  } catch (err) {
    console.error(err)
  }
}

function extractElementByPath(array, path) {
  let target = array
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) return target[idx]
    target = target[idx].children
  }
  return null
}

function clearElementByPath(array, path) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 1)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

function insertElementByPath(array, path, element) {
  const root = [...array]
  let target = root
  for (let i = 0; i < path.length; i++) {
    const idx = path[i]
    if (i === path.length - 1) {
      target.splice(idx, 0, element)
    } else {
      target[idx].children = [...target[idx].children]
      target = target[idx].children
    }
  }
  return root
}

provide('treeDragDropContext', {
  triggerNodeDragStart: (event, path) => {
    event.dataTransfer.setData('text/plain', JSON.stringify({ operation: 'MOVE', path }))
  },
  triggerNodeDrop: (event, destinationPath, layoutContext) => {
    try {
      const rawData = event.dataTransfer.getData('text/plain')
      if (!rawData) return
      const context = JSON.parse(rawData)
      let mutableContent = JSON.parse(JSON.stringify(parsedData.value.content || []))

      let elementToInsert = null
      if (context.operation === 'NEW') {
        elementToInsert = generateDefaultNode(context.type)
      } else if (context.operation === 'MOVE') {
        const sourceStr = JSON.stringify(context.path)
        const destStr = JSON.stringify(destinationPath)
        if (destStr.startsWith(sourceStr)) {
          alert("Invalid Move Placement: Cannot nest a parent container into its own layout branch.")
          return
        }
        elementToInsert = extractElementByPath(mutableContent, context.path)
        mutableContent = clearElementByPath(mutableContent, context.path)

        if (context.path.length === destinationPath.length) {
          const matchingHierarchy = context.path.slice(0, -1).join(',') === destinationPath.slice(0, -1).join(',')
          if (matchingHierarchy && context.path[context.path.length - 1] < destinationPath[destinationPath.length - 1]) {
            destinationPath[destinationPath.length - 1]--
          }
        }
      }

      if (elementToInsert) {
        if (layoutContext === 'APPEND_INSIDE') {
          let node = extractElementByPath(mutableContent, destinationPath)
          if (node) {
            node.children = node.children || []
            node.children.push(elementToInsert)
          }
        } else if (layoutContext === 'INSERT_BEFORE') {
          mutableContent = insertElementByPath(mutableContent, destinationPath, elementToInsert)
        }
        syncJsonTree(mutableContent)
      }
    } catch (err) {
      console.error(err)
    }
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

/*
|--------------------------------------------------------------------------
| DATA PLACEHOLDER
|--------------------------------------------------------------------------
*/

function injectData(node,data) {
  if (!node) return
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

  if (styles.w) {
    if (styles.w === 'fill') { obj.width = '100%' }
    else if (String(styles.w).includes('%')) { obj.width = styles.w }
    else { obj.width = styles.w + 'px' }
  }

  if (styles.h) {
    if (styles.h === 'fill') { obj.height = '100%' }
    else if (String(styles.h).includes('%')) { obj.height = styles.h }
    else { obj.height = styles.h + 'px' }
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
    const elevation = Number(styles.elevation)
    obj.boxShadow = `0 ${elevation * 2}px ${elevation * 8}px rgba(0,0,0,0.12)`
  }

  if (styles.offsetX || styles.offsetY) {
    const x = styles.offsetX || 0
    const y = styles.offsetY || 0
    obj.transform = `translate(${x}px,${y}px)`
  }

  if (styles.alpha) obj.opacity = styles.alpha
  if (styles.z) obj.zIndex = styles.z
  if (styles.scrollable === 'true') { obj.overflowY = 'auto' }

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
    path: Array
  },
  inject: ['treeDragDropContext'],

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

    function renderChildren(extra={}) {
      if (!props.element.children) return null
      return props.element.children.map(
        (child, index) =>
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

    function injectDragDropZone(type, nodeConfig, nodes) {
      const isStructureContainer = ['box-v', 'box-h', 'box-stack', 'box-banner', 'card', 'data-form'].includes(type)
      return h(
        'div',
        {
          class: `canvas-drag-wrapper ${isStructureContainer ? 'structure-container' : 'structure-leaf'}`,
          draggable: 'true',
          style: { position: 'relative' },
          onDragstart: (e) => {
            e.stopPropagation()
            props.treeDragDropContext.triggerNodeDragStart(e, props.path)
          },
          onDragover: (e) => {
            e.preventDefault()
            e.stopPropagation()
          },
          onDrop: (e) => {
            e.stopPropagation()
            e.preventDefault()
            const boundingBox = e.currentTarget.getBoundingClientRect()
            const pointerY = e.clientY - boundingBox.top
            
            if (isStructureContainer) {
              // If dropped directly in the upper or lower 20% margin edge, handle it as an insertion layout shortcut
              if (pointerY < boundingBox.height * 0.20) {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
              } else {
                props.treeDragDropContext.triggerNodeDrop(e, props.path, 'APPEND_INSIDE')
              }
            } else {
              props.treeDragDropContext.triggerNodeDrop(e, props.path, 'INSERT_BEFORE')
            }
          }
        },
        [
          h('span', { class: 'canvas-node-indicator' }, type),
          h(nodeConfig.tag, nodeConfig.attrs, nodes)
        ]
      )
    }

    return () => {
      const p = mergedProps()
      const s = mergedStyles()

      if (p.visibility === 'off' || p.visibility === 'false') {
        return null
      }

      /*
      |--------------------------------------------------------------------------
      | BOX H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-h') {
        return injectDragDropZone('box-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              minHeight: '44px',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-v') {
        if (props.element['data-source'] && props.element['data-container']) {
          return injectDragDropZone('box-v', {
            tag: 'div',
            attrs: {
              style: {
                display: 'flex',
                flexDirection: 'column',
                width: s.w === 'fill' ? '100%' : undefined,
                height: s.h === 'fill' ? '100%' : undefined,
                minHeight: s.h === 'fill' ? '100%' : '44px',
                boxSizing: 'border-box',
                ...styleObject(s)
              }
            }
          }, dynamicItems.value.map(item => {
              const cloned = JSON.parse(JSON.stringify(props.element['data-container']))
              injectData(cloned, item)
              return h(Renderer, {
                element: cloned,
                form: localForm,
                overrides: localOverride,
                path: props.path
              })
          }))
        }

        return injectDragDropZone('box-v', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: s.w === 'fill' ? '100%' : undefined,
              height: s.h === 'fill' ? '100%' : undefined,
              minHeight: s.h === 'fill' ? '100%' : '44px',
              boxSizing: 'border-box',
              position: s.absolute === 'true' ? 'absolute' : 'relative',
              outline: '1px dashed rgba(37, 99, 235, 0.25)',
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
      if (props.element.type === 'box-stack') {
        const isBackgroundLayer = p.layer === 'background'
        return injectDragDropZone('box-stack', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              display: 'flex',
              flexDirection: 'column',
              width: isBackgroundLayer ? '100%' : (s.w === 'fill' ? '100%' : undefined),
              height: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : undefined),
              minHeight: isBackgroundLayer ? '100%' : (s.h === 'fill' ? '100%' : '44px'),
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

      /*
      |--------------------------------------------------------------------------
      | BOX BANNER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'box-banner') {
        return injectDragDropZone('box-banner', {
          tag: 'div',
          attrs: {
            style: {
              position: 'relative',
              overflow: 'hidden',
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'data-form') {
        return injectDragDropZone('data-form', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
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
      if (props.element.type === 'text') {
        return injectDragDropZone('text', {
          tag: 'div',
          attrs: {
            style: {
              boxSizing: 'border-box',
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
      if (props.element.type === 'image') {
        return injectDragDropZone('image', {
          tag: 'img',
          attrs: {
            src: p.url,
            style: {
              width: '100%',
              display: 'block',
              objectFit: 'cover',
              minHeight: '20px',
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
      if (props.element.type === 'image-picker') {
        return injectDragDropZone('image-picker', {
          tag: 'label',
          attrs: {
            style: {
              display: 'block',
              cursor: 'pointer'
            }
          }
        }, [
            h('input', {
              type: 'file',
              accept: 'image/*',
              style: { display: 'none' },
              onChange: e => {
                const file = e.target.files[0]
                if (!file) return
                localForm[p.name] = URL.createObjectURL(file)
              }
            }),
            localForm[p.name]
              ? h('img', {
                  src: localForm[p.name],
                  style: { width: '100%', height: '200px', objectFit: 'cover', borderRadius: '12px' }
                })
              : h('div', {
                  style: {
                    height: '200px',
                    border: '2px dashed #CBD5E1',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }
                }, 'Tap to upload')
          ]
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'input') {
        return injectDragDropZone('input', {
          tag: 'input',
          attrs: {
            type: p.keyboardType === 'password' ? 'password' : 'text',
            value: localForm[p.name] || p.value || '',
            placeholder: p.placeholder || '',
            onInput: e => { localForm[p.name] = e.target.value },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'button') {
        return injectDragDropZone('button', {
          tag: 'button',
          attrs: {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(s)
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
              if (props.element.action?.target) {
                alert('Navigate : ' + props.element.action.target)
              }
            }
          }
        }, p.value || 'Button')
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'icon') {
        return injectDragDropZone('icon', {
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
      if (props.element.type === 'grid') {
        const columns = Number(s.columns || 2)
        return injectDragDropZone('grid', {
          tag: 'div',
          attrs: {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},minmax(0,1fr))`,
              gap: (s.gapV || 8) + 'px',
              width: '100%'
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'spacer') {
        return injectDragDropZone('spacer', {
          tag: 'div',
          attrs: {
            style: {
              minHeight: '8px',
              ...styleObject(s)
            }
          }
        }, null)
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'card') {
        return injectDragDropZone('card', {
          tag: 'div',
          attrs: {
            style: {
              width: '100%',
              minHeight: '44px',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | ITEMS SCROLLER H
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'items-scroller-h') {
        return injectDragDropZone('items-scroller-h', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(s)
            }
          }
        }, renderChildren())
      }

      /*
      |--------------------------------------------------------------------------
      | GESTURE
      |--------------------------------------------------------------------------
      */
      if (props.element.type === 'gesture') {
        return injectDragDropZone('gesture', {
          tag: 'div',
          attrs: {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },
            onClick: () => {
              if (p.state_key) {
                localForm[p.state_key] = p.set_value
              }
            }
          }
        }, renderChildren())
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

        return injectDragDropZone('tab-menu', {
          tag: 'div',
          attrs: {
            style: {
              display: 'flex',
              width: '100%',
              ...styleObject(s)
            }
          }
        }, props.element.children.map(child => {
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
                  path: props.path,
                  parentActive: globalStates[stateKey] === tabId
                })
              ])
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

        return injectDragDropZone('bottom-drawer', {
          tag: 'div',
          attrs: {
            style: {
              position: 'absolute',
              left: 0, right: 0, bottom: 0,
              zIndex: 999,
              background: '#FFF',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.2)',
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
      return h('div', {
          style: { color: 'red', fontSize: '12px', padding: '4px' }
        }, `UNKNOWN TYPE : ${props.element.type}`)
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

.palette-panel {
  width: 240px;
  min-width: 240px;
  background: #090d16;
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
  color: #f1f5f9;
  font-size: 14px;
  font-weight: 600;
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
  gap: 12px;
  padding: 8px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  cursor: grab;
  user-select: none;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.palette-item:hover {
  background: #273549;
  border-color: #3b82f6;
}

.palette-item:active {
  cursor: grabbing;
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

.editor-panel {
  width: 30%;
  min-width: 300px;
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
  font-size: 13px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar button {
  background: #2563eb;
  color: white;
  border: none;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
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

:deep(.canvas-drag-wrapper:hover > .canvas-node-indicator) {
  display: inline-block;
}

:deep(.canvas-node-indicator) {
  display: none;
  position: absolute;
  top: -2px;
  left: 2px;
  background: #2563eb;
  color: #ffffff;
  font-family: monospace;
  font-size: 8px;
  padding: 1px 3px;
  border-radius: 3px;
  z-index: 9999;
  pointer-events: none;
}

:deep(.structure-container:hover) {
  outline: 1px dashed #3b82f6 !important;
  background: rgba(59, 130, 246, 0.04);
}

:deep(.structure-leaf:hover) {
  outline: 1px dashed #10b981 !important;
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