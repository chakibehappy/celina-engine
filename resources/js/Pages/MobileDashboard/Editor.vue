<template>
  <div class="app">

    <div class="editor-panel">

      <div class="toolbar">
        <h2>Celina Engine Vue Runtime</h2>

        <button @click="loadExample">
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
            background: parsedData?.theme?.bg || '#F8FAFC'
          }"
        >

          <template v-if="parsedData">

            <!-- BACKGROUND -->
            <div class="layer-background">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'bg-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'background'"
                  :element="getElement(item)"
                />
              </template>

            </div>

            <!-- MAIN CONTENT -->
            <div class="screen-scroll">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'main-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) == null"
                  :element="getElement(item)"
                />
              </template>

            </div>

            <!-- ROOT -->
            <div class="layer-root">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'root-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'root'"
                  :element="getElement(item)"
                />
              </template>

            </div>

            <!-- HEADER -->
            <div class="layer-header">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'header-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'header'"
                  :element="getElement(item)"
                />
              </template>

            </div>

            <!-- FLOATING -->
            <div class="layer-floating">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'floating-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'floating'"
                  :element="getElement(item)"
                />
              </template>

            </div>

            <!-- FOOTER -->
            <div class="layer-footer">

              <template
                v-for="(item,index) in parsedData.content"
                :key="'footer-'+index"
              >
                <Renderer
                  v-if="getLayer(getElement(item)) === 'footer'"
                  :element="getElement(item)"
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
  h
} from 'vue'

/*
|--------------------------------------------------------------------------
| STATES
|--------------------------------------------------------------------------
*/

const formValues = reactive({})
const overrideMap = reactive({})
const globalStates = reactive({})

/*
|--------------------------------------------------------------------------
| ICONS
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
  more_horiz: 'more_horiz'
}

/*
|--------------------------------------------------------------------------
| JSON
|--------------------------------------------------------------------------
*/

const jsonText = ref(`PASTE YOUR JSON HERE`)

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

function loadExample() {
  jsonText.value = jsonText.value
}

function getElement(item) {
  return item.portrait || item
}

function getLayer(element) {
  return element.props?.layer || null
}

/*
|--------------------------------------------------------------------------
| STYLES
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

    if (
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
  | COLORS
  |--------------------------------------------------------------------------
  */

  if (styles.bg)
    obj.background = styles.bg

  if (styles.color)
    obj.color = styles.color

  /*
  |--------------------------------------------------------------------------
  | TEXT
  |--------------------------------------------------------------------------
  */

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
  | TEXT ALIGN
  |--------------------------------------------------------------------------
  */

  if (styles.align === 'center')
    obj.textAlign = 'center'

  if (styles.align === 'right')
    obj.textAlign = 'right'

  /*
  |--------------------------------------------------------------------------
  | LINE CLAMP
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
  | PADDING
  |--------------------------------------------------------------------------
  */

  if (styles.p)
    obj.padding = styles.p + 'px'

  if (styles.pt)
    obj.paddingTop =
      styles.pt + 'px'

  if (styles.pb)
    obj.paddingBottom =
      styles.pb + 'px'

  if (styles.pl)
    obj.paddingLeft =
      styles.pl + 'px'

  if (styles.pr)
    obj.paddingRight =
      styles.pr + 'px'

  /*
  |--------------------------------------------------------------------------
  | MARGIN
  |--------------------------------------------------------------------------
  */

  if (styles.mt)
    obj.marginTop =
      styles.mt + 'px'

  if (styles.mb)
    obj.marginBottom =
      styles.mb + 'px'

  if (styles.ml)
    obj.marginLeft =
      styles.ml + 'px'

  if (styles.mr)
    obj.marginRight =
      styles.mr + 'px'

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
  | RADIUS
  |--------------------------------------------------------------------------
  */

  if (styles.radius)
    obj.borderRadius =
      styles.radius + 'px'

  if (styles.radiusT) {

    obj.borderTopLeftRadius =
      styles.radiusT + 'px'

    obj.borderTopRightRadius =
      styles.radiusT + 'px'
  }

  /*
  |--------------------------------------------------------------------------
  | FLEX
  |--------------------------------------------------------------------------
  */

  if (styles.weight)
    obj.flex = styles.weight

  /*
  |--------------------------------------------------------------------------
  | ALIGN
  |--------------------------------------------------------------------------
  */

  if (styles.align === 'center')
    obj.alignItems = 'center'

  if (styles.align === 'top')
    obj.alignItems = 'flex-start'

  /*
  |--------------------------------------------------------------------------
  | DISTRIBUTE
  |--------------------------------------------------------------------------
  */

  if (styles.distribute === 'between')
    obj.justifyContent =
      'space-between'

  if (styles.distribute === 'center')
    obj.justifyContent =
      'center'

  /*
  |--------------------------------------------------------------------------
  | GAP
  |--------------------------------------------------------------------------
  */

  if (styles.gap)
    obj.gap = styles.gap + 'px'

  /*
  |--------------------------------------------------------------------------
  | OFFSET
  |--------------------------------------------------------------------------
  */

  if (
    styles.offsetX ||
    styles.offsetY
  ) {

    obj.position = 'relative'

    const x =
      styles.offsetX || 0

    const y =
      styles.offsetY || 0

    obj.transform =
      `translate(${x}px, ${y}px)`
  }

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
    element: Object
  },

  setup(props) {

    function getMergedProps() {

      const name =
        props.element.props?.name

      const override =
        name
          ? overrideMap[name]
          : null

      return {
        ...(props.element.props || {}),
        ...(override?.props || {})
      }
    }

    function getMergedStyles() {

      const name =
        props.element.props?.name

      const override =
        name
          ? overrideMap[name]
          : null

      return {
        ...(props.element.styles || {}),
        ...(override?.styles || {})
      }
    }

    function renderChildren() {

      if (!props.element.children)
        return null

      return props.element.children.map(
        (child, index) =>
          h(Renderer, {
            element: child,
            key: index
          })
      )
    }

    function applyControlElements(tabId) {

      const controls =
        props.element['control-elements']

      if (!controls)
        return

      controls.forEach(control => {

        const target =
          control['target-name']

        const config =
          control['on-values'][tabId]

        if (config) {
          overrideMap[target] = config
        }
      })
    }

    return () => {

      const mergedProps =
        getMergedProps()

      const mergedStyles =
        getMergedStyles()

      /*
      |--------------------------------------------------------------------------
      | VISIBILITY
      |--------------------------------------------------------------------------
      */

      if (
        mergedProps.visibility === 'off'
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

        return h(
          'div',
          {
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              boxSizing: 'border-box',
              alignItems: 'center',
              ...styleObject(
                mergedStyles
              )
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

      if (
        props.element.type === 'box-v'
      ) {

        return h(
          'div',
          {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(
                mergedStyles
              )
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

      if (
        props.element.type === 'box-stack'
      ) {

        return h(
          'div',
          {
            style: {
              position: 'relative',
              width: '100%',
              ...styleObject(
                mergedStyles
              )
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

      if (
        props.element.type === 'text'
      ) {

        return h(
          'div',
          {
            style: {
              boxSizing: 'border-box',
              ...styleObject(
                mergedStyles
              )
            }
          },

          mergedProps.value || ''
        )
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'image'
      ) {

        return h(
          'img',
          {
            src: mergedProps.url,

            style: {
              width: '100%',
              objectFit: 'cover',
              display: 'block',

              ...styleObject(
                mergedStyles
              )
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'spacer'
      ) {

        return h(
          'div',
          {
            style: {
              ...styleObject(
                mergedStyles
              )
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'card'
      ) {

        return h(
          'div',
          {
            style: {
              boxSizing: 'border-box',

              ...styleObject(
                mergedStyles
              )
            }
          },

          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | GRID
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'grid'
      ) {

        const columns =
          Number(
            mergedStyles.columns || 2
          )

        return h(
          'div',
          {
            style: {
              display: 'grid',

              gridTemplateColumns:
                `repeat(${columns}, minmax(0,1fr))`,

              gap:
                (mergedStyles.gapV || 8)
                + 'px',

              width: '100%'
            }
          },

          renderChildren()
        )
      }

      /*
      |--------------------------------------------------------------------------
      | BUTTON
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'button'
      ) {

        return h(
          'button',
          {
            style: {
              border: 'none',
              cursor: 'pointer',

              ...styleObject(
                mergedStyles
              )
            }
          },

          mergedProps.value
        )
      }

      /*
      |--------------------------------------------------------------------------
      | INPUT
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'input'
      ) {

        return h(
          'input',
          {
            value:
              formValues[
                mergedProps.name
              ] || '',

            placeholder:
              mergedProps.placeholder
              || '',

            onInput: e => {

              formValues[
                mergedProps.name
              ] = e.target.value
            },

            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',

              ...styleObject(
                mergedStyles
              )
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | ICON
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type === 'icon'
      ) {

        return h(
          'span',
          {
            class:
              'material-symbols-outlined',

            style: {
              fontSize:
                (mergedStyles.size || 24)
                + 'px',

              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',

              ...styleObject(
                mergedStyles
              )
            }
          },

          iconMap[
            (
              mergedProps.name || ''
            ).toLowerCase()
          ] || 'flash_on'
        )
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

        return h(
          'div',
          {
            style: {
              display: 'flex',
              overflowX: 'auto',
              overflowY: 'hidden',
              width: '100%',
              boxSizing: 'border-box',

              scrollbarWidth: 'none',

              ...styleObject(
                mergedStyles
              )
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

      if (
        props.element.type ===
        'gesture'
      ) {

        return h(
          'div',
          {
            style: {
              cursor: 'pointer',
              position: 'relative'
            },

            onClick: () => {

              const stateKey =
                mergedProps.state_key

              const setValue =
                mergedProps.set_value

              globalStates[
                stateKey
              ] = setValue
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

      if (
        props.element.type ===
        'box-banner'
      ) {

        return h(
          'div',
          {
            style: {
              width: '100%',
              position: 'relative',
              overflow: 'hidden',

              backgroundImage:
                `url(${mergedProps.bgImage})`,

              backgroundSize: 'cover',

              backgroundPosition:
                'center',

              borderBottomLeftRadius:
                (
                  mergedStyles
                    .startRadius || 0
                ) + 'px',

              borderBottomRightRadius:
                (
                  mergedStyles
                    .endRadius || 0
                ) + 'px',

              ...styleObject(
                mergedStyles
              )
            }
          },

          renderChildren()
        )
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

        const stateKey =
          mergedProps.state_key
          || 'default_tab'

        if (
          !globalStates[stateKey]
        ) {

          globalStates[stateKey] =
            mergedProps.initial_tab

          applyControlElements(
            mergedProps.initial_tab
          )
        }

        return h(
          'div',
          {
            style: {
              display: 'flex',
              width: '100%',
              boxSizing: 'border-box',

              ...styleObject(
                mergedStyles
              )
            }
          },

          props.element.children.map(
            child => {

              const tabId =
                child.props?.tab_id

              return h(
                'div',
                {
                  style: {
                    flex: 1,
                    cursor: 'pointer'
                  },

                  onClick: () => {

                    globalStates[
                      stateKey
                    ] = tabId

                    applyControlElements(
                      tabId
                    )
                  }
                },

                [
                  h(Renderer, {
                    element: child
                  })
                ]
              )
            }
          )
        )
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

        const stateKey =
          mergedProps.state_key

        const isOpen =
          globalStates[stateKey]
          === 'true'

        if (!isOpen)
          return null

        return h(
          'div',
          {
            style: {

              position: 'absolute',

              left: 0,
              right: 0,
              bottom: 0,

              background:
                mergedStyles.bg || '#FFFFFF',

              borderTopLeftRadius:
                '24px',

              borderTopRightRadius:
                '24px',

              boxShadow:
                '0 -10px 40px rgba(0,0,0,0.15)',

              zIndex: 999,

              maxHeight: '75%',

              overflowY: 'auto',

              animation:
                'drawerUp .25s ease'
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
        {
          style: {
            color: 'red',
            fontSize: '12px',
            padding: '4px'
          }
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

.editor-panel {
  width: 50%;
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
}

.screen-scroll {
  width: 100%;
  height: 100%;
  overflow-y: auto;
  position: relative;
  z-index: 1;
}

.layer-background {
  position: absolute;
  inset: 0;
  z-index: 0;
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