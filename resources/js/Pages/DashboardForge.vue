<script setup>
import { ref, computed, markRaw, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    projectKey: String
});

import ForgeSidebar from './components/ForgeSidebar.vue';
import ForgeCanvas from './components/ForgeCanvas.vue';
import ForgeInsight from './components/ForgeInsight.vue';

// --- PROJECT METADATA ---
const projectId = ref(props.projectKey);
const projectName = ref('Indo Logistics Dashboard');
const database = ref([]);

// Fetch the active session name on mount
const fetchActiveSession = async () => {
    try {
        const response = await axios.get('/get-active-project');
        if (response.data && response.data.project_id) {
            projectName.value = response.data.project_id.replace(/-/g, ' ');
            projectId.value = response.data.project_id;
            loadProject();
        }
    } catch (e) {
        console.warn("No active project session found.");
    }
};

const activePageId = ref(1);
const selectedTemplate = ref('');
const templates = ref({});

// --- TREE STATE ---
const expandedNodes = ref(new Set([1])); 
const uiConfig = ref({});
const selectedElement = ref(null);

// --- UNDO / REDO / CLIPBOARD SYSTEM ---
const history = ref([]);
const redoStack = ref([]);
const clipboard = ref(null);

const recordHistory = () => {
  const snapshot = JSON.stringify({
    pages: pages.value,
    uiConfig: uiConfig.value
  });
  
  if (history.value.length === 0 || history.value[history.value.length - 1] !== snapshot) {
    history.value.push(snapshot);
    if (history.value.length > 50) history.value.shift(); 
    redoStack.value = []; 
  }
};

const undo = () => {
  if (history.value.length <= 1) return; 
  const current = JSON.stringify({ pages: pages.value, uiConfig: uiConfig.value });
  redoStack.value.push(current);
  history.value.pop(); 
  const previous = JSON.parse(history.value[history.value.length - 1]);  
  pages.value = previous.pages;
  uiConfig.value = previous.uiConfig;
};

const redo = () => {
  if (redoStack.value.length === 0) return;
  const next = JSON.parse(redoStack.value.pop());
  const snapshot = JSON.stringify({ pages: next.pages, uiConfig: next.uiConfig });
  history.value.push(snapshot);
  pages.value = next.pages;
  uiConfig.value = next.uiConfig;
};

// --- CORE LOGIC ---
const selectElement = (id) => {
  selectedElement.value = id;
  const pageId = activePageId.value;

  if (!uiConfig.value[pageId]) {
    uiConfig.value[pageId] = {};
  }

  if (!uiConfig.value[pageId][id]) {
    recordHistory(); 
    uiConfig.value[pageId][id] = {
      style: { backgroundColor: '#ffffff' }
    };
  }
};

const updateStyle = (key, value) => {
  if (!selectedElement.value || !activePage.value) return;
  recordHistory();
  const updateRecursive = (elements) => {
    for (let el of elements) {
      if (el.id === selectedElement.value) {
        if (!el.props.style) el.props.style = {};
        el.props.style[key] = value;
        return true;
      }
      if (el.children && updateRecursive(el.children)) return true;
    }
    return false;
  };

  updateRecursive(activePage.value.elements);

  const pageId = activePageId.value;
  if (!uiConfig.value[pageId]) uiConfig.value[pageId] = {};
  if (!uiConfig.value[pageId][selectedElement.value]) {
    uiConfig.value[pageId][selectedElement.value] = { style: {} };
  }
  uiConfig.value[pageId][selectedElement.value].style[key] = value;
};

const getElementStyle = (id) => {
  const pageId = activePageId.value;
  return uiConfig.value[pageId]?.[id]?.style || {};
};

const toggleExpand = (id) => {
  if (expandedNodes.value.has(id)) expandedNodes.value.delete(id);
  else expandedNodes.value.add(id);
};

// --- PAN & ZOOM LOGIC ---
const scale = ref(0.8);
const panX = ref(0);
const panY = ref(0);
const isPanning = ref(false);

const resetZoom = () => {
  scale.value = 0.8;
  panX.value = 0;
  panY.value = 0;
};

const zoomPercentage = computed(() => Math.round(scale.value * 100) + '%');

const canvasStyle = computed(() => ({
  transform: `translate(${panX.value}px, ${panY.value}px) scale(${scale.value})`,
  transformOrigin: 'center center',
  transition: isPanning.value ? 'none' : 'transform 0.1s ease-out'
}));

const handleZoom = (e) => {
  e.preventDefault();
  const delta = e.deltaY > 0 ? -0.05 : 0.05;
  scale.value = Math.min(Math.max(0.2, scale.value + delta), 2);
};
const startPan = (e) => { if (e.button === 1 || (e.button === 0 && e.altKey)) isPanning.value = true; };
const doPan = (e) => { if (isPanning.value) { panX.value += e.movementX; panY.value += e.movementY; } };
const stopPan = () => { isPanning.value = false; };

// --- TREE NAVIGATION DATA ---
const generateStarterKit = (id, name) => [
  {
    id: `root_${id}`,
    type: 'container-v',
    props: { style: { minHeight: '100%', height:'full', width:'full', backgroundColor: '#ffffff' } },
    children: [
      {
        id: `header_text_${id}`,
        type: 'text',
        props: { 
          content: name, 
          style: { fontSize: '32px', fontWeight: '800', marginBottom: '24px', color: '#111827', padding:'10px' } 
        }
      },
    ]
  }
];

const pages = ref([
  { 
    id: 1, name: 'Home',  slug: 'dashboard',  type: 'page', 
    showPage: true,  menuAction: 'None',  children: [], elements: generateStarterKit(1, 'Home')
  },
]);

const findPage = (nodes, id) => {
  for (const node of nodes) {
    if (node.id === id) return node;
    if (node.children?.length) {
      const child = findPage(node.children, id);
      if (child) return child;
    }
  }
  return null;
};

const activePage = computed(() => findPage(pages.value, activePageId.value));

function addPage(parent = null) {
  const name = prompt(parent ? `Sub-page for ${parent.name}:` : "Root Page Name:");
  if(name) {
    recordHistory();
    const id = Date.now();
    const newPage = { 
      id,  name,  slug: name.toLowerCase().replace(/\s+/g, '-'),  type: 'page', 
      showPage: true,  menuAction: 'None', children: [], elements: generateStarterKit(id, name)
    };

    if (parent) {
      parent.children.push(newPage);
      parent.type = 'folder';
      parent.showPage = false;
      expandedNodes.value.add(parent.id); 
    } else {
      pages.value.push(newPage);
    }
    activePageId.value = id;
  }
}

const deleteActivePage = () => {
  if (!activePage.value || activePage.value.id === 1) return alert("Cannot delete home page.");
  
  if (confirm(`Delete page "${activePage.value.name}" and all its children?`)) {
    recordHistory();
    const removeNode = (nodes, id) => {
      const idx = nodes.findIndex(n => n.id === id);
      if (idx !== -1) {
        nodes.splice(idx, 1);
        return true;
      }
      return nodes.some(n => n.children && removeNode(n.children, id));
    };
    removeNode(pages.value, activePageId.value);
    delete uiConfig.value[activePageId.value];
    activePageId.value = pages.value[0]?.id || 1;
  }
};

// --- ELEMENT MANIPULATION HELPERS ---

const cloneWithNewIds = (element) => {
  const newId = `${element.type}_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
  const cloned = JSON.parse(JSON.stringify(element));
  cloned.id = newId;
  if (cloned.children) {
    cloned.children = cloned.children.map(child => cloneWithNewIds(child));
  }
  return cloned;
};

const handleDelete = () => {
  if (selectedElement.value) {
    // 🛡️ ROOT PROTECTION: Check if the selected element is the root container
    // Typically the first element in activePage.value.elements
    const rootElement = activePage.value?.elements[0];
    if (rootElement && selectedElement.value === rootElement.id) {
      console.warn("Root container cannot be deleted.");
      return; 
    }

    recordHistory();
    const removeFromTree = (list) => {
      const idx = list.findIndex(el => el.id === selectedElement.value);
      if (idx !== -1) {
        list.splice(idx, 1);
        return true;
      }
      return list.some(el => el.children && removeFromTree(el.children));
    };
    removeFromTree(activePage.value.elements);
    selectedElement.value = null;
  } else {
    deleteActivePage();
  }
};

const copyElement = () => {
  if (!selectedElement.value || !activePage.value) return;
  const findInTree = (list) => {
    for (let el of list) {
      if (el.id === selectedElement.value) return el;
      if (el.children) {
        const found = findInTree(el.children);
        if (found) return found;
      }
    }
    return null;
  };
  const target = findInTree(activePage.value.elements);
  if (target) {
    clipboard.value = JSON.stringify({ type: 'ELEMENT_CLIP', data: target });
  }
};

const pasteElement = () => {
  if (!clipboard.value || !activePage.value) return;
  const clip = JSON.parse(clipboard.value);
  if (clip.type !== 'ELEMENT_CLIP') return;

  recordHistory();
  const newElement = cloneWithNewIds(clip.data);
  const targetId = selectedElement.value || activePage.value.elements[0].id;

  const insertIntoTree = (list) => {
    for (let el of list) {
      if (el.id === targetId) {
        if (el.type.includes('container')) {
          if (!el.children) el.children = [];
          el.children.push(newElement);
        } else {
          list.push(newElement);
        }
        return true;
      }
      if (el.children && insertIntoTree(el.children)) return true;
    }
    return false;
  };

  insertIntoTree(activePage.value.elements);
  selectedElement.value = newElement.id;
};

// --- GLOBAL KEYBOARD SHORTCUTS ---
const handleShortcuts = (e) => {
  const isCtrl = e.ctrlKey || e.metaKey;
  const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;

  if (isTyping) return; 

  if (isCtrl && e.key.toLowerCase() === 'z') { e.preventDefault(); undo(); }
  if (isCtrl && e.key.toLowerCase() === 'y') { e.preventDefault(); redo(); }
  
  if (isCtrl && e.key.toLowerCase() === 'c') {
    e.preventDefault();
    if (selectedElement.value) {
      copyElement();
    } else if (activePage.value) {
      clipboard.value = JSON.stringify({
        type: 'PAGE_CLIP',
        data: activePage.value,
        config: uiConfig.value[activePageId.value] || {}
      });
    }
  }

  if (isCtrl && e.key.toLowerCase() === 'v' && clipboard.value) {
    e.preventDefault();
    const clip = JSON.parse(clipboard.value);
    if (clip.type === 'ELEMENT_CLIP') {
      pasteElement();
    } else if (clip.type === 'PAGE_CLIP') {
      recordHistory();
      const newId = Date.now();
      const newPage = { ...JSON.parse(JSON.stringify(clip.data)), id: newId, name: clip.data.name + ' (Copy)' };
      pages.value.push(newPage);
      uiConfig.value[newId] = JSON.parse(JSON.stringify(clip.config));
      activePageId.value = newId;
    }
  }

  if (e.key === 'Delete' || (e.key === 'Backspace' && !isTyping)) {
    e.preventDefault();
    handleDelete();
  }
};

const moveElement = ({ elementId, targetParentId, blueprint = null }) => {
  if (!activePage.value) return;
  
  recordHistory();
  let elementToMove = null;

  if (elementId) {
    const findAndRemove = (list) => {
      for (let i = 0; i < list.length; i++) {
        if (list[i].id === elementId) {
          elementToMove = list.splice(i, 1)[0];
          return true;
        }
        if (list[i].children && findAndRemove(list[i].children)) return true;
      }
      return false;
    };
    findAndRemove(activePage.value.elements);
  } else if (blueprint) {
    elementToMove = JSON.parse(blueprint);
  }

  if (!elementToMove) return;

  const findAndPush = (list) => {
    for (let node of list) {
      if (node.id === targetParentId) {
        if (!node.children) node.children = [];
        node.children.push(elementToMove);
        return true;
      }
      if (node.children && findAndPush(node.children)) return true;
    }
    return false;
  };
  findAndPush(activePage.value.elements);
};

// --- TEMPLATE AUTO-LOADER ---
const templateModules = import.meta.glob('./DashboardTemplates/*.vue', { eager: true });
Object.entries(templateModules).forEach(([path, module]) => {
  const name = path.split('/').pop().replace('.vue', '');
  templates.value[name] = markRaw(module.default);
});

// --- SAVE / LOAD ---
const saveProject = async () => {
  try {
    const payload = {
      data: {
        manifest: {
          database: database.value,
          dashboard: {
            pages: pages.value,
            activePageId: activePageId.value,
            selectedTemplate: selectedTemplate.value,
            expandedNodes: Array.from(expandedNodes.value),
            ui_config: uiConfig.value
          }
        },
        ui_state: {
          dashboard_viewport: { x: panX.value, y: panY.value, scale: scale.value }
        }
      }
    };
    await axios.post(`/projects-dashboard/${projectId.value}`, payload);
    alert('Dashboard Saved!');
  } catch (err) { console.error('Save failed:', err);}
};

const loadProject = async () => {
  try {
    const { data } = await axios.get(`/projects/${projectId.value}`);
    if (data && data.data) {
      const manifest = data.data.manifest?.dashboard || {};
      const ui = data.data.ui_state?.dashboard_viewport || {};
      pages.value = manifest.pages || pages.value;
      activePageId.value = manifest.activePageId || activePageId.value;
      selectedTemplate.value = manifest.selectedTemplate || selectedTemplate.value;
      expandedNodes.value = new Set(manifest.expandedNodes || []);
      uiConfig.value = manifest.ui_config || {};

      panX.value = ui.x ?? panX.value;
      panY.value = ui.y ?? panY.value;
      scale.value = ui.scale ?? scale.value;

      database.value = manifest.database || data.data.manifest?.database || [];
    }
  } catch (err) { console.warn('Using local defaults.');}
};


onMounted(async () => {
  window.addEventListener('keydown', handleShortcuts);
  // await loadProject();
  if (!selectedTemplate.value && Object.keys(templates.value).length > 0) 
    selectedTemplate.value = Object.keys(templates.value)[0];
  recordHistory(); 
  fetchActiveSession(); 
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleShortcuts);
});

</script>

<template>
  <div class="flex w-full h-full bg-[#0d0f14] text-gray-300 overflow-hidden select-none font-sans">
    <ForgeSidebar 
        :pages="pages" :activePageId="activePageId" :expandedNodes="expandedNodes" :templates="templates"
        v-model:selectedTemplate="selectedTemplate" @update:activePageId="id => activePageId = id"
        @toggleExpand="toggleExpand" @addPage="addPage"
    />
    <ForgeCanvas 
        :activePage="activePage" :pages="pages" :activePageId="activePageId" :expandedNodes="expandedNodes"
        :selectedTemplate="selectedTemplate" :templates="templates" :canvasStyle="canvasStyle"  :zoomPercentage="zoomPercentage"
        :getElementStyle="getElementStyle" :selectElement="selectElement" :selectedElement="selectedElement" 
        @handleZoom="handleZoom" @startPan="startPan" @doPan="doPan" @stopPan="stopPan" @save="saveProject" @updateData="saveProject"
        @resetZoom="resetZoom" @update:activePageId="id => activePageId = id" @toggleExpand="toggleExpand" @recordHistory="recordHistory" 
        @moveElement="moveElement" @handleDelete="handleDelete" :database="database"
    />
    <ForgeInsight 
        :activePage="activePage" :activePageId="activePageId" :selectedElement="selectedElement"
        :uiConfig="uiConfig" @updateStyle="updateStyle" :database="database"
    />
  </div>
</template>