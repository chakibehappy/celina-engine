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
| GLOBAL
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
| STYLE ENGINE
|--------------------------------------------------------------------------
*/

function styleObject(styles = {}) {

  const obj = {}

  obj.boxSizing = 'border-box'

  /*
  |--------------------------------------------------------------------------
  | WIDTH
  |--------------------------------------------------------------------------
  */

  if (styles.w === 'fill') {

    obj.width = '100%'
  }
  else if (styles.w) {

    if (
      String(styles.w).includes('%')
    ) {

      obj.width = styles.w
    }
    else {

      obj.width =
        styles.w + 'px'
    }
  }

  /*
  |--------------------------------------------------------------------------
  | HEIGHT
  |--------------------------------------------------------------------------
  */

  if (styles.h === 'fill') {

    obj.height = '100%'
    obj.minHeight = '100%'
  }
  else if (styles.h) {

    if (
      String(styles.h).includes('%')
    ) {

      obj.height = styles.h
    }
    else {

      obj.height =
        styles.h + 'px'
    }
  }

  /*
  |--------------------------------------------------------------------------
  | FLEX
  |--------------------------------------------------------------------------
  */

  if (styles.weight) {

    obj.flex =
      Number(styles.weight)
  }

  /*
  |--------------------------------------------------------------------------
  | BG
  |--------------------------------------------------------------------------
  */

  if (styles.bg) {

    obj.background =
      styles.bg
  }

  /*
  |--------------------------------------------------------------------------
  | BG IMAGE
  |--------------------------------------------------------------------------
  */

  if (styles.bgImage) {

    obj.backgroundImage =
      `url(${styles.bgImage})`

    obj.backgroundSize =
      'cover'

    obj.backgroundPosition =
      'center'

    obj.backgroundRepeat =
      'no-repeat'
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

  if (styles.align === 'left') {

    obj.alignItems = 'flex-start'
    obj.textAlign = 'left'
  }

  if (styles.align === 'right') {

    obj.alignItems = 'flex-end'
    obj.textAlign = 'right'
  }

  /*
  |--------------------------------------------------------------------------
  | ARRANGEMENT
  |--------------------------------------------------------------------------
  */

  if (
    styles.arrangement === 'center'
  ) {

    obj.justifyContent = 'center'
  }

  if (
    styles.arrangement === 'between'
  ) {

    obj.justifyContent = 'space-between'
  }

  if (
    styles.arrangement === 'around'
  ) {

    obj.justifyContent = 'space-around'
  }

  if (
    styles.arrangement === 'evenly'
  ) {

    obj.justifyContent = 'space-evenly'
  }

  /*
  |--------------------------------------------------------------------------
  | GAP
  |--------------------------------------------------------------------------
  */

  if (styles.gap) {

    obj.gap =
      styles.gap + 'px'
  }

  /*
  |--------------------------------------------------------------------------
  | PADDING
  |--------------------------------------------------------------------------
  */

  if (styles.p)
    obj.padding =
      styles.p + 'px'

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

  if (styles.m)
    obj.margin =
      styles.m + 'px'

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
  | RADIUS
  |--------------------------------------------------------------------------
  */

  if (styles.radius) {

    obj.borderRadius =
      styles.radius + 'px'
  }

  if (styles.radiusT) {

    obj.borderTopLeftRadius =
      styles.radiusT + 'px'

    obj.borderTopRightRadius =
      styles.radiusT + 'px'
  }

  if (styles.radiusB) {

    obj.borderBottomLeftRadius =
      styles.radiusB + 'px'

    obj.borderBottomRightRadius =
      styles.radiusB + 'px'
  }

  /*
  |--------------------------------------------------------------------------
  | BORDER
  |--------------------------------------------------------------------------
  */

  if (styles.border) {

    obj.border =
      `1px solid ${styles.border}`
  }

  /*
  |--------------------------------------------------------------------------
  | ELEVATION
  |--------------------------------------------------------------------------
  */

  if (styles.elevation) {

    const e =
      Number(styles.elevation)

    obj.boxShadow =
      `0 ${e}px ${e*4}px rgba(0,0,0,0.15)`
  }

  /*
  |--------------------------------------------------------------------------
  | ALPHA
  |--------------------------------------------------------------------------
  */

  if (styles.alpha) {

    obj.opacity =
      Number(styles.alpha)
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

    obj.transform =
      `translate(${styles.offsetX || 0}px, ${styles.offsetY || 0}px)`
  }

  /*
  |--------------------------------------------------------------------------
  | POSITION
  |--------------------------------------------------------------------------
  */

  if (
    styles.absolute === 'true'
  ) {

    obj.position = 'absolute'

    if (styles.top)
      obj.top =
        styles.top + 'px'

    if (styles.left)
      obj.left =
        styles.left + 'px'

    if (styles.right)
      obj.right =
        styles.right + 'px'

    if (styles.bottom)
      obj.bottom =
        styles.bottom + 'px'
  }

  return obj
}

/*
|--------------------------------------------------------------------------
| RENDERER
|--------------------------------------------------------------------------
*/

const Renderer = defineComponent({

  name:'Renderer',

  props:{
    element:Object,
    parentActive:Boolean
  },

  setup(props){

    return ()=>{

      const p =
        props.element.props || {}

      const s = {

        ...(props.element.styles || {}),

        ...(props.parentActive
          ? props.element.activeStyles || {}
          : {}
        )
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
              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
            style:{
              display:'flex',
              flexDirection:'column',
              width:'100%',
              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
            style:{

              position:'relative',

              width:'100%',

              minHeight:
                s.h === 'fill'
                  ? '100%'
                  : undefined,

              height:
                s.h === 'fill'
                  ? '100%'
                  : (
                      s.h
                        ? (
                            String(s.h).includes('%')
                              ? s.h
                              : s.h + 'px'
                          )
                        : undefined
                    ),

              overflow:'hidden',

              backgroundImage:
                p.bgImage
                  ? `url(${p.bgImage})`
                  : undefined,

              backgroundSize:'cover',

              backgroundPosition:'center',

              backgroundRepeat:'no-repeat',

              ...styleObject({
                ...s,
                bgImage:null
              })
            }
          },

          [

            h(
              'div',
              {
                style:{
                  position:'relative',
                  width:'100%',
                  height:'100%'
                }
              },

              props.element.children?.map(
                child =>
                  h(Renderer,{
                    element:child
                  })
              )
            )
          ]
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
            style:{
              position:'relative',
              width:'100%',
              overflow:'hidden',

              backgroundImage:
                p.bgImage
                  ? `url(${p.bgImage})`
                  : undefined,

              backgroundSize:'cover',

              backgroundPosition:'center',

              backgroundRepeat:'no-repeat',

              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
                `repeat(${columns}, minmax(0,1fr))`,

              gap:
                (s.gapV || 8) + 'px',

              width:'100%',

              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
              ...styleObject(s)
            }
          },

          p.value || ''
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
              formValues[
                p.name
              ] || '',

            placeholder:
              p.placeholder || '',

            onInput:e=>{

              formValues[
                p.name
              ] = e.target.value
            },

            style:{

              border:'none',
              outline:'none',

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
            }
          },

          p.value || 'Button'
        )
      }

      /*
      |--------------------------------------------------------------------------
      | IMAGE
      |--------------------------------------------------------------------------
      */

      if (
        props.element.type ===
        'image'
      ) {

        return h(
          'img',
          {

            src:p.url,

            style:{

              width:'100%',
              objectFit:'cover',
              display:'block',

              ...styleObject(s)
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
              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
              ...styleObject(s)
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
              width:'100%'
            },

            onClick:()=>{

              if (
                p.state_key
              ) {

                formValues[
                  p.state_key
                ] = p.set_value
              }
            }
          },

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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

          props.element.children?.map(
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
                  }
                },

                [

                  h(Renderer,{
                    element:child,

                    parentActive:
                      globalStates[
                        stateKey
                      ] === tabId
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

        const isOpen =
          formValues[
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

          props.element.children?.map(
            child =>
              h(Renderer,{
                element:child
              })
          )
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
          style:{
            color:'red',
            padding:'6px',
            fontSize:'12px'
          }
        },

        `UNKNOWN TYPE : ${props.element.type}`
      )
    }
  }
})
</script>

<style scoped>
*{
  box-sizing:border-box;
}

body{
  margin:0;
}

.app{
  width:100%;
  height:100vh;
  display:flex;
  overflow:hidden;
  background:#0F172A;
}

.editor-panel{
  width:50%;
  height:100%;
  background:#020617;
  border-right:1px solid #1E293B;
  display:flex;
  flex-direction:column;
}

.toolbar{
  height:60px;
  min-height:60px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 16px;
  border-bottom:1px solid #1E293B;
  color:white;
}

.toolbar button{
  height:38px;
  border:none;
  background:#2563EB;
  color:white;
  border-radius:10px;
  padding:0 16px;
  cursor:pointer;
}

.json-editor{
  flex:1;
  width:100%;
  resize:none;
  border:none;
  outline:none;
  background:#020617;
  color:#E2E8F0;
  padding:20px;
  font-size:13px;
  line-height:1.6;
  font-family:monospace;
}

.preview-panel{
  flex:1;
  display:flex;
  justify-content:center;
  align-items:center;

  background:
    radial-gradient(
      circle at top,
      #1E3A8A,
      #0F172A
    );
}

.phone-frame{
  width:390px;
  height:844px;
  background:black;
  border-radius:40px;
  padding:12px;

  box-shadow:
    0 20px 80px rgba(0,0,0,0.5);
}

.phone-screen{
  width:100%;
  height:100%;
  border-radius:32px;
  overflow:hidden;
  position:relative;
}

.layer-background{
  position:absolute;
  inset:0;
  z-index:0;
  overflow:hidden;
  width:100%;
  height:100%;
}

.layer-background > *{
  width:100%;
  height:100%;
}

.screen-scroll{
  position:relative;
  z-index:1;

  width:100%;
  height:100%;

  overflow-y:auto;
  overflow-x:hidden;
}

.layer-root{
  position:absolute;
  inset:0;
  z-index:5;
  pointer-events:none;
}

.layer-root > *{
  pointer-events:auto;
}

.layer-header{
  position:absolute;
  top:0;
  left:0;
  width:100%;
  z-index:10;
}

.layer-floating{
  position:absolute;
  inset:0;
  z-index:20;
  pointer-events:none;
}

.layer-floating > *{
  pointer-events:auto;
}

.layer-footer{
  position:absolute;
  bottom:0;
  left:0;
  width:100%;
  z-index:15;
}

.material-symbols-outlined{
  user-select:none;
}

.screen-scroll::-webkit-scrollbar{
  display:none;
}
</style>