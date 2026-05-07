<template>
  <div class="app">
    <div class="editor-panel">
      <div class="toolbar">
        <h2>Celina Engine Vue Runtime</h2>

        <button @click="loadExample">
          Load Example
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

            <!-- MAIN CONTENT -->
            <div class="screen-scroll">
              <template
                v-for="(item,index) in parsedData.content"
                :key="'main-'+index"
              >
                <Renderer
                  v-if="getElement(item).props?.layer == null"
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
                  v-if="getElement(item).props?.layer === 'header'"
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
                  v-if="getElement(item).props?.layer === 'floating'"
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
                  v-if="getElement(item).props?.layer === 'footer'"
                  :element="getElement(item)"
                />
              </template>
            </div>

          </template>

          <div v-else class="invalid-json">
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
  computed,
  defineComponent,
  h,
  reactive
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
| JSON INPUT
|--------------------------------------------------------------------------
*/

const jsonText = ref(`{
  "theme": {
    "bg": "#F8FAFC"
  },
  "content": [
    {
      "portrait": {
        "type": "box-v",
        "props": {
          "layer": "header"
        },
        "children": [
          {
            "type": "box-banner",
            "props": {
              "name": "hero_banner",
              "bgImage": "https://images.unsplash.com/photo-1516321497487-e288fb19713f?q=80&w=1200"
            },
            "styles": {
              "h": "220",
              "startRadius": "32",
              "endRadius": "32"
            },
            "children": [
              {
                "type": "box-v",
                "styles": {
                  "p": "16",
                  "pt": "50"
                },
                "children": [
                  {
                    "type": "text",
                    "props": {
                      "value": "CELINA ENGINE"
                    },
                    "styles": {
                      "fontSize": "24",
                      "bold": "true",
                      "color": "#FFFFFF"
                    }
                  },
                  {
                    "type": "spacer",
                    "styles": {
                      "h": "12"
                    }
                  },
                  {
                    "type": "text",
                    "props": {
                      "value": "Vue 3 Runtime Renderer"
                    },
                    "styles": {
                      "fontSize": "14",
                      "color": "#FFFFFF"
                    }
                  }
                ]
              }
            ]
          }
        ]
      }
    },

    {
      "portrait": {
        "type": "box-v",
        "styles": {
          "bg": "#F8FAFC"
        },
        "children": [
          {
            "type": "spacer",
            "styles": {
              "h": "220"
            }
          },

          {
            "type": "box-v",
            "styles": {
              "bg": "#FFFFFF",
              "radiusT": "24",
              "p": "16"
            },
            "children": [

              {
                "type": "text",
                "props": {
                  "value": "Category"
                },
                "styles": {
                  "fontSize": "16",
                  "bold": "true"
                }
              },

              {
                "type": "spacer",
                "styles": {
                  "h": "12"
                }
              },

              {
                "type": "tab-menu",
                "props": {
                  "state_key": "main_tab",
                  "initial_tab": "TAB_1"
                },
                "styles": {
                  "bg": "#EEF2FF",
                  "radius": "12",
                  "p": "4"
                },
                "control-elements": [
                  {
                    "target-name": "tab_content_1",
                    "on-values": {
                      "TAB_1": {
                        "props": {
                          "visibility": "on"
                        }
                      },
                      "TAB_2": {
                        "props": {
                          "visibility": "off"
                        }
                      }
                    }
                  },

                  {
                    "target-name": "tab_content_2",
                    "on-values": {
                      "TAB_1": {
                        "props": {
                          "visibility": "off"
                        }
                      },
                      "TAB_2": {
                        "props": {
                          "visibility": "on"
                        }
                      }
                    }
                  }
                ],
                "children": [

                  {
                    "type": "box-v",
                    "props": {
                      "tab_id": "TAB_1"
                    },
                    "styles": {
                      "weight": "1",
                      "align": "center",
                      "bg": "#2563EB",
                      "radius": "8",
                      "p": "8"
                    },
                    "children": [
                      {
                        "type": "text",
                        "props": {
                          "value": "PRODUCT"
                        },
                        "styles": {
                          "color": "#FFFFFF",
                          "bold": "true",
                          "fontSize": "12"
                        }
                      }
                    ]
                  },

                  {
                    "type": "box-v",
                    "props": {
                      "tab_id": "TAB_2"
                    },
                    "styles": {
                      "weight": "1",
                      "align": "center",
                      "radius": "8",
                      "p": "8"
                    },
                    "children": [
                      {
                        "type": "text",
                        "props": {
                          "value": "TOPUP"
                        },
                        "styles": {
                          "fontSize": "12"
                        }
                      }
                    ]
                  }

                ]
              },

              {
                "type": "spacer",
                "styles": {
                  "h": "20"
                }
              },

              {
                "type": "box-v",
                "props": {
                  "name": "tab_content_1"
                },
                "children": [
                  {
                    "type": "grid",
                    "styles": {
                      "columns": "2",
                      "gapV": "12"
                    },
                    "children": [

                      {
                        "type": "card",
                        "styles": {
                          "bg": "#FFFFFF",
                          "radius": "16",
                          "p": "8",
                          "border": "#E2E8F0"
                        },
                        "children": [
                          {
                            "type": "image",
                            "props": {
                              "url": "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=800"
                            },
                            "styles": {
                              "h": "120",
                              "radius": "12"
                            }
                          },

                          {
                            "type": "spacer",
                            "styles": {
                              "h": "8"
                            }
                          },

                          {
                            "type": "text",
                            "props": {
                              "value": "iPhone Product"
                            },
                            "styles": {
                              "fontSize": "13",
                              "bold": "true"
                            }
                          },

                          {
                            "type": "text",
                            "props": {
                              "value": "Rp 15.000.000"
                            },
                            "styles": {
                              "fontSize": "12",
                              "color": "#2563EB",
                              "bold": "true"
                            }
                          }
                        ]
                      },

                      {
                        "type": "card",
                        "styles": {
                          "bg": "#FFFFFF",
                          "radius": "16",
                          "p": "8",
                          "border": "#E2E8F0"
                        },
                        "children": [
                          {
                            "type": "image",
                            "props": {
                              "url": "https://images.unsplash.com/photo-1546868871-7041f2a55e12?q=80&w=800"
                            },
                            "styles": {
                              "h": "120",
                              "radius": "12"
                            }
                          },

                          {
                            "type": "spacer",
                            "styles": {
                              "h": "8"
                            }
                          },

                          {
                            "type": "text",
                            "props": {
                              "value": "Smart Watch"
                            },
                            "styles": {
                              "fontSize": "13",
                              "bold": "true"
                            }
                          },

                          {
                            "type": "text",
                            "props": {
                              "value": "Rp 5.000.000"
                            },
                            "styles": {
                              "fontSize": "12",
                              "color": "#2563EB",
                              "bold": "true"
                            }
                          }
                        ]
                      }

                    ]
                  }
                ]
              },

              {
                "type": "box-v",
                "props": {
                  "name": "tab_content_2",
                  "visibility": "off"
                },
                "children": [
                  {
                    "type": "text",
                    "props": {
                      "value": "Topup Content Here"
                    },
                    "styles": {
                      "fontSize": "14",
                      "bold": "true"
                    }
                  }
                ]
              }

            ]
          }
        ]
      }
    }
  ]
}`)

/*
|--------------------------------------------------------------------------
| PARSED DATA
|--------------------------------------------------------------------------
*/

const parsedData = computed(() => {
  try {
    return JSON.parse(jsonText.value)
  } catch (e) {
    return null
  }
})

/*
|--------------------------------------------------------------------------
| GET ACTIVE ELEMENT
|--------------------------------------------------------------------------
*/

function getElement(item) {
  return item.portrait || item
}

/*
|--------------------------------------------------------------------------
| PARSE COLOR
|--------------------------------------------------------------------------
*/

function parseColor(color) {
  if (!color) return undefined
  return color
}

/*
|--------------------------------------------------------------------------
| APPLY STYLES
|--------------------------------------------------------------------------
*/

function styleObject(styles = {}) {

  const obj = {}

  if (styles.bg)
    obj.background = styles.bg

  if (styles.color)
    obj.color = styles.color

  if (styles.fontSize)
    obj.fontSize = styles.fontSize + 'px'

  if (styles.bold === 'true')
    obj.fontWeight = '700'

  if (styles.w === 'fill')
    obj.width = '100%'

  if (styles.h)
    obj.height = styles.h + 'px'

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

  if (styles.mt)
    obj.marginTop = styles.mt + 'px'

  if (styles.mb)
    obj.marginBottom = styles.mb + 'px'

  if (styles.ml)
    obj.marginLeft = styles.ml + 'px'

  if (styles.mr)
    obj.marginRight = styles.mr + 'px'

  if (styles.radius)
    obj.borderRadius = styles.radius + 'px'

  if (styles.radiusT) {
    obj.borderTopLeftRadius = styles.radiusT + 'px'
    obj.borderTopRightRadius = styles.radiusT + 'px'
  }

  if (styles.border)
    obj.border = '1px solid ' + styles.border

  if (styles.align === 'center')
    obj.alignItems = 'center'

  if (styles.distribute === 'between')
    obj.justifyContent = 'space-between'

  if (styles.gap)
    obj.gap = styles.gap + 'px'

  if (styles.weight)
    obj.flex = styles.weight

  if (styles.lineHeight)
    obj.lineHeight = styles.lineHeight + 'px'

  if (styles.align === 'center')
    obj.textAlign = 'center'

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

    function renderChildren() {

      if (!props.element.children)
        return null

      return props.element.children.map((child, index) =>
        h(Renderer, {
          element: child,
          key: index
        })
      )
    }

    function getMergedProps() {

      const name = props.element.props?.name

      const override = name
        ? overrideMap[name]
        : null

      return {
        ...(props.element.props || {}),
        ...(override?.props || {})
      }
    }

    function getMergedStyles() {

      const name = props.element.props?.name

      const override = name
        ? overrideMap[name]
        : null

      return {
        ...(props.element.styles || {}),
        ...(override?.styles || {})
      }
    }

    function applyControlElements(tabId) {

      const controls = props.element['control-elements']

      if (!controls) return

      controls.forEach(control => {

        const target = control['target-name']

        const config = control['on-values'][tabId]

        if (config) {
          overrideMap[target] = config
        }
      })
    }

    return () => {

      const mergedProps = getMergedProps()
      const mergedStyles = getMergedStyles()

      /*
      |--------------------------------------------------------------------------
      | VISIBILITY
      |--------------------------------------------------------------------------
      */

      if (mergedProps.visibility === 'off') {
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
            style: {
              display: 'flex',
              flexDirection: 'row',
              width: '100%',
              ...styleObject(mergedStyles)
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

        return h(
          'div',
          {
            style: {
              display: 'flex',
              flexDirection: 'column',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(mergedStyles)
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

        return h(
          'div',
          {
            style: {
              position: 'relative',
              width: '100%',
              ...styleObject(mergedStyles)
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
            style: {
              boxSizing: 'border-box',
              ...styleObject(mergedStyles)
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

      if (props.element.type === 'image') {

        return h(
          'img',
          {
            src: mergedProps.url,
            style: {
              width: '100%',
              objectFit: 'cover',
              display: 'block',
              ...styleObject(mergedStyles)
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | SPACER
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'spacer') {

        return h(
          'div',
          {
            style: {
              ...styleObject(mergedStyles)
            }
          }
        )
      }

      /*
      |--------------------------------------------------------------------------
      | CARD
      |--------------------------------------------------------------------------
      */

      if (props.element.type === 'card') {

        return h(
          'div',
          {
            style: {
              boxSizing: 'border-box',
              boxShadow: '0 2px 10px rgba(0,0,0,0.05)',
              ...styleObject(mergedStyles)
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

      if (props.element.type === 'grid') {

        const columns = mergedStyles.columns || 2

        return h(
          'div',
          {
            style: {
              display: 'grid',
              gridTemplateColumns: `repeat(${columns},1fr)`,
              gap: (mergedStyles.gapV || 8) + 'px',
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

      if (props.element.type === 'button') {

        return h(
          'button',
          {
            style: {
              border: 'none',
              cursor: 'pointer',
              ...styleObject(mergedStyles)
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

      if (props.element.type === 'input') {

        return h(
          'input',
          {
            value: formValues[mergedProps.name] || '',
            placeholder: mergedProps.placeholder || '',
            onInput: e => {
              formValues[mergedProps.name] = e.target.value
            },
            style: {
              border: 'none',
              outline: 'none',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(mergedStyles)
            }
          }
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
            style: {
              width: '100%',
              position: 'relative',
              overflow: 'hidden',
              backgroundImage: `url(${mergedProps.bgImage})`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
              borderBottomLeftRadius:
                (mergedStyles.startRadius || 0) + 'px',
              borderBottomRightRadius:
                (mergedStyles.endRadius || 0) + 'px',
              ...styleObject(mergedStyles)
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

      if (props.element.type === 'tab-menu') {

        const stateKey =
          mergedProps.state_key || 'default_tab'

        if (!globalStates[stateKey]) {
          globalStates[stateKey] =
            mergedProps.initial_tab
        }

        return h(
          'div',
          {
            style: {
              display: 'flex',
              width: '100%',
              boxSizing: 'border-box',
              ...styleObject(mergedStyles)
            }
          },

          props.element.children.map(child => {

            const tabId = child.props?.tab_id

            return h(
              'div',
              {
                style: {
                  flex: 1,
                  cursor: 'pointer'
                },

                onClick: () => {

                  globalStates[stateKey] = tabId

                  applyControlElements(tabId)
                }
              },

              [
                h(Renderer, {
                  element: child
                })
              ]
            )
          })
        )
      }

      /*
      |--------------------------------------------------------------------------
      | DEFAULT
      |--------------------------------------------------------------------------
      */

      return h(
        'div',
        {},
        `UNKNOWN TYPE : ${props.element.type}`
      )
    }
  }
})

/*
|--------------------------------------------------------------------------
| LOAD EXAMPLE
|--------------------------------------------------------------------------
*/

function loadExample() {
  jsonText.value = jsonText.value
}
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
    radial-gradient(circle at top,
      #1e3a8a,
      #0f172a);
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
}

.layer-header {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 20;
}

.layer-floating {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 30;
}

.layer-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 20;
}

.invalid-json {
  color: red;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}
</style>