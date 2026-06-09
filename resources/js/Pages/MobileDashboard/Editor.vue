<template>
  <div class="app">

    <!-- LEFT -->
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

    <!-- RIGHT -->
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

            <!-- BACKGROUND -->
            <div class="layer-background">

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
                />
              </template>

            </div>

            <!-- MAIN -->
            <div class="screen-scroll">

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
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'root'
                  "
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
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'header'
                  "
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
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'floating'
                  "
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
                  v-if="
                    getLayer(
                      getElement(item)
                    ) === 'footer'
                  "
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
    parentActive: Boolean
  },

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
              extra.parentActive
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

        return h(
          'div',
          {
            style:{
              display:'flex',
              flexDirection:'row',
              width:'100%',
              boxSizing:'border-box',
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

    return h(
      'div',
      {
        style:{
          display:'flex',
          flexDirection:'column',

          width:
            s.w === 'fill'
              ? '100%'
              : undefined,

          height:
            s.h === 'fill'
              ? '100%'
              : undefined,

          minHeight:
            s.h === 'fill'
              ? '100%'
              : undefined,

          boxSizing:'border-box',

          ...styleObject(s)
        }
      },

      dynamicItems.value.map(
        item => {

          const cloned =
            JSON.parse(
              JSON.stringify(
                props.element['data-container']
              )
            )

          injectData(
            cloned,
            item
          )

          return h(
            Renderer,
            {
              element:cloned,
              form:localForm,
              overrides:localOverride
            }
          )
        }
      )
    )
  }

  return h(
    'div',
    {
      style:{
        display:'flex',
        flexDirection:'column',

        width:
          s.w === 'fill'
            ? '100%'
            : undefined,

        height:
          s.h === 'fill'
            ? '100%'
            : undefined,

        minHeight:
          s.h === 'fill'
            ? '100%'
            : undefined,

        boxSizing:'border-box',

        position:
          s.absolute === 'true'
            ? 'absolute'
            : 'relative',

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

if (
  props.element.type === 'box-stack'
) {

  const isBackgroundLayer =
    p.layer === 'background'

  return h(
    'div',
    {
      style:{

        /*
        |--------------------------------------------------------------------------
        | COMPOSE-LIKE STACK
        |--------------------------------------------------------------------------
        */

        position:'relative',

        display:'flex',
        flexDirection:'column',

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT FIX
        |--------------------------------------------------------------------------
        */

        width:
          isBackgroundLayer
            ? '100%'
            : (
                s.w === 'fill'
                  ? '100%'
                  : undefined
              ),

        height:
          isBackgroundLayer
            ? '100%'
            : (
                s.h === 'fill'
                  ? '100%'
                  : undefined
              ),

        minHeight:
          isBackgroundLayer
            ? '100%'
            : (
                s.h === 'fill'
                  ? '100%'
                  : undefined
              ),

        /*
        |--------------------------------------------------------------------------
        | BG IMAGE
        |--------------------------------------------------------------------------
        */

        backgroundImage:
          p.bgImage
            ? `url(${p.bgImage})`
            : undefined,

        backgroundSize:'cover',
        backgroundPosition:'center',
        backgroundRepeat:'no-repeat',

        overflow:'hidden',

        ...styleObject({
          ...s,

          /*
          |--------------------------------------------------------------------------
          | PREVENT DUPLICATE BG IMAGE
          |--------------------------------------------------------------------------
          */

          bgImage:null
        })
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
        props.element.type === 'box-banner'
      ) {

        return h(
          'div',
          {
            style:{
              position:'relative',
              overflow:'hidden',
              width:'100%',
              ...styleObject({
                ...s,
                bgImage:p.bgImage
              })
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

      if (
        props.element.type === 'data-form'
      ) {

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

      if (
        props.element.type === 'text'
      ) {

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

      if (
        props.element.type === 'image'
      ) {

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

      if (
        props.element.type ===
        'image-picker'
      ) {

        return h(
          'label',
          {
            style:{
              display:'block',
              cursor:'pointer'
            }
          },

          [

            h(
              'input',
              {
                type:'file',
                accept:'image/*',

                style:{
                  display:'none'
                },

                onChange:e=>{

                  const file =
                    e.target.files[0]

                  if (!file)
                    return

                  localForm[p.name] =
                    URL.createObjectURL(file)
                }
              }
            ),

            localForm[p.name]

              ? h(
                  'img',
                  {
                    src:
                      localForm[p.name],

                    style:{
                      width:'100%',
                      height:'200px',
                      objectFit:'cover',
                      borderRadius:'12px'
                    }
                  }
                )

              : h(
                  'div',
                  {
                    style:{
                      height:'200px',
                      border:'2px dashed #CBD5E1',
                      borderRadius:'12px',
                      display:'flex',
                      alignItems:'center',
                      justifyContent:'center'
                    }
                  },

                  'Tap to upload'
                )
          ]
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

            type:
              p.keyboardType ===
              'password'
                ? 'password'
                : 'text',

            value:
              localForm[p.name]
              || p.value
              || '',

            placeholder:
              p.placeholder || '',

            onInput:e=>{

              localForm[p.name] =
                e.target.value
            },

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

      if (
        props.element.type ===
        'button'
      ) {

        return h(
          'button',
          {

            style:{
              border:'none',
              cursor:'pointer',
              ...styleObject(s)
            },

            onClick:()=>{

              /*
              |--------------------------------------------------------------------------
              | STATE
              |--------------------------------------------------------------------------
              */

              if (
                p.state_key
              ) {

                localForm[
                  p.state_key
                ] = p.set_value
              }

              /*
              |--------------------------------------------------------------------------
              | ACTION
              |--------------------------------------------------------------------------
              */

              if (
                props.element.action
                  ?.target
              ) {

                alert(
                  'Navigate : '
                  +
                  props.element.action
                    .target
                )
              }
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

      if (
        props.element.type ===
        'icon'
      ) {

        return h(
          'span',
          {
            class:
              'material-symbols-outlined',

            style:{
              fontSize:
                (
                  s.size || 24
                ) + 'px',

              display:'flex',
              alignItems:'center',
              justifyContent:'center',

              ...styleObject(s)
            }
          },

          iconMap[
            (
              p.name || ''
            ).toLowerCase()
          ] || 'flash_on'
        )
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

        const columns =
          Number(
            s.columns || 2
          )

        return h(
          'div',
          {
            style:{
              display:'grid',

              gridTemplateColumns:
                `repeat(${columns},minmax(0,1fr))`,

              gap:
                (
                  s.gapV || 8
                ) + 'px',

              width:'100%'
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

      if (
        props.element.type ===
        'spacer'
      ) {

        return h(
          'div',
          {
            style:{
              ...styleObject(s)
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
        props.element.type ===
        'card'
      ) {

        return h(
          'div',
          {
            style:{
              width:'100%',
              boxSizing:'border-box',
              ...styleObject(s)
            }
          },

          renderChildren()
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
            style:{
              display:'flex',
              overflowX:'auto',
              overflowY:'hidden',
              width:'100%',
              boxSizing:'border-box',
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

      if (
        props.element.type ===
        'gesture'
      ) {

        return h(
          'div',
          {

            style:{
              cursor:'pointer',
              position:'relative'
            },

            onClick:()=>{

              if (
                p.state_key
              ) {

                localForm[
                  p.state_key
                ] = p.set_value
              }
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
          p.state_key || 'tab'

        if (
          !globalStates[stateKey]
        ) {

          globalStates[stateKey] =
            p.initial_tab

          applyControlElements(
            p.initial_tab
          )
        }

        return h(
          'div',
          {
            style:{
              display:'flex',
              width:'100%',
              ...styleObject(s)
            }
          },

          props.element.children.map(
            child=>{

              const tabId =
                child.props?.tab_id

              return h(
                'div',
                {

                  style:{
                    flex:1,
                    cursor:'pointer'
                  },

                  onClick:()=>{

                    globalStates[
                      stateKey
                    ] = tabId

                    applyControlElements(
                      tabId
                    )
                  }
                },

                [

                  h(
                    Renderer,
                    {
                      element:child,
                      form:localForm,
                      overrides:localOverride,

                      parentActive:
                        globalStates[
                          stateKey
                        ] === tabId
                    }
                  )
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

        const isOpen =
          localForm[
            p.state_key
          ] === 'true'

        if (!isOpen)
          return null

        return h(
          'div',
          {
            style:{
              position:'absolute',
              left:0,
              right:0,
              bottom:0,
              zIndex:999,
              background:'#FFF',
              borderTopLeftRadius:'24px',
              borderTopRightRadius:'24px',
              boxShadow:
                '0 -10px 40px rgba(0,0,0,0.2)',

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