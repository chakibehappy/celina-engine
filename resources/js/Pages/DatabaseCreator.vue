<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    projectKey: String
});

const canvas = ref(null);
const saveStatus = ref(false);
const isPanning = ref(false);
const activeElement = ref(null);

const projectId = ref(props.projectKey);
const projectName = ref("New Celina Project");


// Expanded for logistics/financial precision and MySQL compatibility
const sqlTypes = [
  'UUID', 'VARCHAR', 'INT', 'BIGINT', 'BOOLEAN', 
  'TIMESTAMP', 'DATETIME', 'TEXT', 'LONGTEXT', 
  'DECIMAL', 'DOUBLE', 'JSON', 'BLOB'
];

const mockTemplates = [
  { 
    name: 'Users', 
    columns: [
      {name: 'id', type: 'UUID', fixed: true, isPk: true}, 
      {name: 'username', type: 'VARCHAR', fixed: true}, 
      {name: 'password', type: 'TEXT', fixed: true},
    ] 
  },
  { 
    name: 'Roles', 
    columns: [
      {name: 'id', type: 'UUID', fixed: true, isPk: true}, 
      {name: 'name', type: 'VARCHAR', fixed: true}
    ] 
  },
  { 
    name: 'Custom Object', 
    columns: [{name: 'id', type: 'UUID', fixed: true, isPk: true}] 
  }
];

const viewport = reactive({ x: 0, y: 0, scale: 1 });
const nodes = ref([]);
const connections = ref([]);
const linkingSource = ref(null);
const mousePos = reactive({ x: 0, y: 0 });

const HEADER_HANDLE_Y_OFFSET = 52; 
const NODE_WIDTH = 256;

// SAVE / LOAD
const saveProject = async () => {
  try {
    const payload = {
        data: {
          manifest: {
            database: nodes.value.map(node => ({
            table: node.name.toLowerCase(),
            columns: node.columns.map(col => ({
              name: col.name,
              type: col.type.toLowerCase() === 'uuid' ? 'increments' : col.type.toLowerCase(),
              unique: col.isPk || false,
              constrained: col.isFk ? "detect" : null 
            })),
            logic: {
              auth: node.name.toLowerCase() === 'users',
              timestamp: true
            }
          })),
        },
        ui_state: {
          nodes: nodes.value,
          connections: connections.value,
          viewport: viewport
        }
      }
    };
    await axios.post(`/projects-database/${projectId.value}`, payload);
    alert('Database Saved!');
  } catch (err) { console.error('Save failed:', err);}
};

// const saveProject = async () => {
//   const manifest = {
//     project_id: projectId.value,
//     // version: "1.0.0",
//     // path: "..generated/",
//     // full_path: "C:\\KITXEL\\generated",
//     // settings: {
//     //   theme: "dark-industrial",
//     //   primary_color: "#ff4400",
//     //   deployment: ["web", "mobile_api"]
//     // },
//     database: nodes.value.map(node => ({
//       table: node.name.toLowerCase(),
//       columns: node.columns.map(col => ({
//         name: col.name,
//         type: col.type.toLowerCase() === 'uuid' ? 'increments' : col.type.toLowerCase(),
//         unique: col.isPk || false,
//         constrained: col.isFk ? "detect" : null 
//       })),
//       logic: {
//         auth: node.name.toLowerCase() === 'users',
//         timestamp: true
//       }
//     })),
//     modules: [] 
//   };

//   const payload = {
//     project_id: "indo-logistics-001",
//     name: "Indo Logistics Project",
//     data: {
//       manifest: manifest,
//       ui_state: {
//         nodes: nodes.value,
//         connections: connections.value,
//         viewport: viewport
//       }
//     }
//   };

//   try {
//     const response = await fetch('/projects', {
//       method: 'POST',
//       headers: {
//         'Content-Type': 'application/json',
//         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
//       },
//       body: JSON.stringify(payload)
//     });
//     if (response.ok) saveStatus.value = true;
//   } catch (error) {
//     console.error("Save failed:", error);
//   }
// };

const loadProject = async () => {
  try {
    const response = await fetch('/projects/' + projectId.value); 
    if (response.ok) {
      const result = await response.json();
      const savedData = result.data; 

      if (savedData && savedData.ui_state) {
        nodes.value = savedData.ui_state.nodes || [];
        connections.value = savedData.ui_state.connections || [];
        if (savedData.ui_state.viewport) {
          viewport.x = savedData.ui_state.viewport.x;
          viewport.y = savedData.ui_state.viewport.y;
          viewport.scale = savedData.ui_state.viewport.scale;
        }
      } else if (savedData && savedData.manifest) {
        const db = savedData.manifest.database || [];
        nodes.value = db.map((table, index) => ({
          id: Date.now() + index,
          name: table.table.toUpperCase(),
          x: 100 + (index * 300),
          y: 150,
          columns: table.columns.map(col => ({
            id: Math.random().toString(36).substr(2, 9),
            name: col.name,
            type: col.type.toUpperCase(),
            fixed: col.unique,
            isPk: col.unique
          }))
        }));
      }
    }
  } catch (e) {
    console.error("Failed to load blueprint from DB", e);
  }
};

const clearCanvas = () => {
  if(confirm("Clear blueprint?")) {
    nodes.value = [];
    connections.value = [];
  }
};

const handleWheel = (e) => {
  const zoomSpeed = 0.0015;
  const newScale = Math.min(Math.max(viewport.scale + (-e.deltaY) * zoomSpeed, 0.2), 2);
  const rect = canvas.value.getBoundingClientRect();
  const mouseX = e.clientX - rect.left;
  const mouseY = e.clientY - rect.top;
  viewport.x -= (mouseX - viewport.x) * (newScale / viewport.scale - 1);
  viewport.y -= (mouseY - viewport.y) * (newScale / viewport.scale - 1);
  viewport.scale = newScale;
};

const handleCanvasMouseDown = (e) => {
  if (e.button === 1 || (e.button === 0 && e.altKey)) {
    isPanning.value = true;
    const startX = e.clientX - viewport.x;
    const startY = e.clientY - viewport.y;
    const onMouseMove = (m) => { viewport.x = m.clientX - startX; viewport.y = m.clientY - startY; };
    const onMouseUp = () => { isPanning.value = false; window.removeEventListener('mousemove', onMouseMove); };
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp, { once: true });
  }
};

const getCanvasCoords = (clientX, clientY) => {
  const rect = canvas.value.getBoundingClientRect();
  return {
    x: (clientX - rect.left - viewport.x) / viewport.scale,
    y: (clientY - rect.top - viewport.y) / viewport.scale
  };
};

const startNodeMove = (e, node) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
  const coords = getCanvasCoords(e.clientX, e.clientY);
  const offsetX = coords.x - node.x;
  const offsetY = coords.y - node.y;
  const onMouseMove = (m) => {
    const mc = getCanvasCoords(m.clientX, m.clientY);
    node.x = mc.x - offsetX;
    node.y = mc.y - offsetY;
  };
  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('mouseup', () => window.removeEventListener('mousemove', onMouseMove), { once: true });
};

const onDrop = (e) => {
  const tableData = JSON.parse(e.dataTransfer.getData('tableData'));
  const coords = getCanvasCoords(e.clientX, e.clientY);
  const newCols = tableData.columns.map(c => ({ 
    ...c, 
    id: Math.random().toString(36).substr(2, 9) 
  }));
  
  nodes.value.push({
    id: Date.now(),
    name: tableData.name,
    x: coords.x - 128,
    y: coords.y - 20,
    columns: newCols
  });
};

const startLink = (node) => {
  linkingSource.value = { id: node.id, name: node.name, x: node.x + NODE_WIDTH, y: node.y + HEADER_HANDLE_Y_OFFSET };
};

const completeLink = (targetNode) => {
  if (linkingSource.value && linkingSource.value.id !== targetNode.id) {
    if (!connections.value.some(c => c.fromId === linkingSource.value.id && c.toId === targetNode.id)) {
      connections.value.push({ fromId: linkingSource.value.id, toId: targetNode.id });
      const fkName = `${linkingSource.value.name.toLowerCase().replace(/s$/, '')}_id`;
      if (!targetNode.columns.some(c => c.name === fkName)) {
        targetNode.columns.push({
          id: Math.random().toString(36).substr(2, 9),
          name: fkName,
          type: 'UUID',
          isFk: true,
          fixed: true
        });
      }
    }
  }
  linkingSource.value = null;
};

const generatePath = (link) => {
  const from = nodes.value.find(n => n.id === link.fromId);
  const to = nodes.value.find(n => n.id === link.toId);
  if (!from || !to) return '';
  const x1 = from.x + NODE_WIDTH;
  const y1 = from.y + HEADER_HANDLE_Y_OFFSET;
  const x2 = to.x;
  const y2 = to.y + HEADER_HANDLE_Y_OFFSET;
  const curvature = Math.max(Math.abs(x2 - x1) * 0.5, 40);
  return `M ${x1} ${y1} C ${x1 + curvature} ${y1}, ${x2 - curvature} ${y2}, ${x2} ${y2}`;
};

const updateMouse = (e) => {
  if (!canvas.value) return;
  const coords = getCanvasCoords(e.clientX, e.clientY);
  mousePos.x = coords.x; mousePos.y = coords.y;
};

const removeConnection = (index) => {
  const link = connections.value[index];
  const sourceNode = nodes.value.find(n => n.id === link.fromId);
  const targetNode = nodes.value.find(n => n.id === link.toId);
  if (sourceNode && targetNode) {
    const fkName = `${sourceNode.name.toLowerCase().replace(/s$/, '')}_id`; 
    targetNode.columns = targetNode.columns.filter(c => c.name !== fkName);
  }
  connections.value.splice(index, 1);
};

const removeNode = (id) => {
  nodes.value = nodes.value.filter(n => n.id !== id);
  connections.value = connections.value.filter(c => c.fromId !== id && c.toId !== id);
};
const addColumn = (node) => node.columns.push({ id: Math.random().toString(36).substr(2, 9), name: 'new_field', type: 'VARCHAR', fixed: false });
const removeColumn = (node, colId) => node.columns = node.columns.filter(c => c.id !== colId);
const onDragStart = (e, table) => e.dataTransfer.setData('tableData', JSON.stringify(table));

const sqlInput = ref(null);

const handleSqlImport = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => parseSqlToNodes(e.target.result);
    reader.readAsText(file);
    event.target.value = '';
};


const autoLinkNodes = (sql, allNodes) => {
    const newConnections = [];
    
    // 1. PRIMARY: Explicit Foreign Key Constraints
    // This looks for: CONSTRAINT `fk_name` FOREIGN KEY (`local_col`) REFERENCES `target_table` (`target_col`)
    const fkRegex = /FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)/g;
    let match;
    while ((match = fkRegex.exec(sql)) !== null) {
        const sourceColName = match[1];
        const targetTableName = match[2].toUpperCase();
        
        // Find which node contains this FK definition
        const fromNode = allNodes.find(n => n.columns.some(c => c.name === sourceColName));
        const toNode = allNodes.find(n => n.name === targetTableName);

        if (fromNode && toNode) {
            newConnections.push({ fromId: toNode.id, toId: fromNode.id });
            const col = fromNode.columns.find(c => c.name === sourceColName);
            if (col) { 
                col.isFk = true; 
                col.fixed = true; 
            }
        }
    }

    // 2. SECONDARY: Conservative Heuristic (Only if no formal FKs were found for a node)
    allNodes.forEach(targetNode => {
        // Only run heuristic if this node hasn't been connected via formal FKs yet
        const alreadyLinked = newConnections.some(c => c.toId === targetNode.id);
        
        if (!alreadyLinked) {
            targetNode.columns.forEach(col => {
                // Ignore columns like 'sales_id' if they aren't meant to be connections
                // We check for table_id pattern but ensure it's not the PK
                if (col.name.endsWith('_id') && !col.isPk) {
                    const potentialTableName = col.name.replace('_id', '').toUpperCase();
                    const sourceNode = allNodes.find(n => n.name === potentialTableName);
                    
                    if (sourceNode && !newConnections.some(c => c.toId === targetNode.id && c.fromId === sourceNode.id)) {
                        // Double check: In logistics, label columns are often VARCHAR, 
                        // while true IDs are usually INT/BIGINT/UUID.
                        const isLikelyActualId = col.type !== 'VARCHAR'; 
                        
                        if (isLikelyActualId) {
                            newConnections.push({ fromId: sourceNode.id, toId: targetNode.id });
                            col.isFk = true;
                        }
                    }
                }
            });
        }
    });
    
    return newConnections;
};
const parseSqlToNodes = (sql) => {
    const newNodes = [];
    const tableRegex = /CREATE TABLE `(\w+)` \(([\s\S]+?)\) ENGINE/g;
    let match;
    let index = 0;

    // 1. TABLE PARSER 
    while ((match = tableRegex.exec(sql)) !== null) {
        const tableName = match[1];
        const columnBlock = match[2];
        const columnLines = columnBlock.split(',\n');
        const columns = [];

        columnLines.forEach(line => {
            const colMatch = line.trim().match(/^`(\w+)` (\w+)(\([\d,]+\))?/);
            if (colMatch) {
                const colName = colMatch[1];
                let rawType = colMatch[2].toUpperCase();
                let type = 'VARCHAR';
                
                if (rawType.includes('INT')) type = rawType.includes('BIG') ? 'BIGINT' : 'INT';
                if (rawType.includes('TEXT')) type = rawType.includes('LONG') ? 'LONGTEXT' : 'TEXT';
                if (rawType.includes('DECIMAL') || rawType === 'FLOAT') type = 'DECIMAL';
                if (rawType === 'DOUBLE') type = 'DOUBLE';
                if (rawType === 'DATETIME') type = 'DATETIME';
                if (rawType.includes('TIMESTAMP')) type = 'TIMESTAMP';
                if (rawType === 'TINYINT' && line.includes('(1)')) type = 'BOOLEAN';
                if (rawType.includes('BLOB')) type = 'BLOB';
                if (rawType.includes('JSON')) type = 'JSON';

                const isPk = line.toLowerCase().includes('primary key') || colName === 'id';
                columns.push({
                    id: Math.random().toString(36).substr(2, 9),
                    name: colName,
                    type: type,
                    fixed: isPk,
                    isPk: isPk
                });
            }
        });

        newNodes.push({
            id: Date.now() + index,
            name: tableName.toUpperCase(),
            x: (index % 4) * 350 + 50,
            y: Math.floor(index / 4) * 450 + 50,
            columns: columns,
            records: [] // Initializing for data storage
        });
        index++;
    }

    // 2. DATA RECORD PARSER
    // Matches: INSERT INTO `table` (`col1`, `col2`) VALUES ('val1', 'val2');
    const insertRegex = /INSERT INTO `(\w+)` \((.+?)\) VALUES\s*\((.+?)\);/g;
    let insertMatch;
    while ((insertMatch = insertRegex.exec(sql)) !== null) {
        const tableName = insertMatch[1].toUpperCase();
        const columnsStr = insertMatch[2].replace(/`/g, '').split(',').map(s => s.trim());
        const valuesStr = insertMatch[3].split(',').map(s => s.trim().replace(/^'|'$/g, ''));

        const targetNode = newNodes.find(n => n.name === tableName);
        if (targetNode) {
            const record = {};
            columnsStr.forEach((colName, i) => {
                record[colName] = valuesStr[i];
            });
            targetNode.records.push(record);
        }
    }

    // 3. CHECK PK PASS
    const alterPkRegex = /ALTER TABLE `(\w+)` ADD PRIMARY KEY \(`(\w+)`\)/g;
    while ((match = alterPkRegex.exec(sql)) !== null) {
        const node = newNodes.find(n => n.name === match[1].toUpperCase());
        if (node) {
            const col = node.columns.find(c => c.name === match[2]);
            if (col) { col.isPk = true; col.fixed = true; }
        }
    }

    // 4. FINALIZATION
    if (newNodes.length > 0) {
        if (confirm(`Detected ${newNodes.length} tables. Append to canvas?`)) {
            const autoConnections = autoLinkNodes(sql, newNodes);
            nodes.value = [...nodes.value, ...newNodes];
            connections.value = [...connections.value, ...autoConnections];
        }
    }
};

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

onMounted(() => {
  window.addEventListener('mousemove', updateMouse);
  fetchActiveSession(); 
});
onUnmounted(() => window.removeEventListener('mousemove', updateMouse));
</script>

<template>
  <div class="flex h-screen bg-[#0f1115] text-slate-300 font-sans overflow-hidden select-none">
    
    <aside class="w-64 border-r border-slate-800 bg-[#161920] flex flex-col z-20">
      
      <div class="p-4 flex-1 overflow-y-auto space-y-6">
        <div class="px-4 pt-4 mb-4">
          <button @click="$refs.sqlInput.click()" class="w-full bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-indigo-500/30 text-[10px] font-bold py-2 px-4 rounded transition flex items-center justify-center gap-2">
              <span>📂</span> IMPORT DATABASE (.SQL)
          </button>
          <input type="file" ref="sqlInput" class="hidden" accept=".sql" @change="handleSqlImport" />
      </div>
        <div>
          <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Blueprint Templates</h3>
          <div class="space-y-1">
            <div 
              v-for="node in nodes" 
              :key="node.id" 
              class="flex items-center gap-2 p-2 bg-slate-800/40 rounded-lg border border-slate-700/50 group transition-all"
            >
              <span class="text-indigo-400 font-mono text-[10px]">[T]</span>
              <span class="text-sm font-medium text-slate-200 truncate">{{ node.name || 'UNNAMED' }}</span>
            </div>

            <div class="pt-4 pb-2 border-t border-slate-800/50 mt-4">
               <h3 class="text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-2">Available Objects</h3>
               <div 
                v-for="table in mockTemplates" 
                :key="table.name" 
                draggable="true"
                @dragstart="onDragStart($event, table)"
                class="flex items-center gap-2 p-2 hover:bg-slate-800 rounded-lg cursor-grab group transition-colors border border-transparent hover:border-slate-700 mb-1"
              >
                <span class="text-slate-500 group-hover:text-indigo-400 font-mono text-xs">+</span>
                <span class="text-xs font-medium">{{ table.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <main class="flex-1 relative flex flex-col min-w-0">
      <header class="h-12 border-b border-slate-800 bg-[#161920] flex items-center justify-between px-4 z-10">
        <div class="flex gap-4 items-center">
          <button class="text-xs font-medium text-white border-b-2 border-indigo-500 px-2 h-12">Architect View</button>
          <div class="flex gap-2 items-center text-[10px] text-slate-500 font-mono italic">
            <span>Nodes: {{ nodes.length }}</span>
            <span>|</span>
            <span>Right-click handles to link</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="clearCanvas" class="text-[10px] text-slate-400 hover:text-white px-3">CLEAR</button>
          <button @click="saveProject" class="bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-bold py-1.5 px-4 rounded transition shadow-lg shadow-indigo-500/20">
            Save Database
          </button>
        </div>
      </header>

      <div 
        ref="canvas"
        class="flex-1 relative bg-[#0f1115] overflow-hidden outline-none" 
        :class="{ 'cursor-grabbing': isPanning }"
        :style="{
          backgroundImage: `radial-gradient(#334155 1px, transparent 1px)`,
          backgroundSize: `${24 * viewport.scale}px ${24 * viewport.scale}px`,
          backgroundPosition: `${viewport.x}px ${viewport.y}px`
        }"
        @dragover.prevent
        @drop="onDrop"
        @wheel.prevent="handleWheel"
        @mousedown="handleCanvasMouseDown"
        @contextmenu.prevent
      >
        <div 
          class="absolute inset-0 origin-top-left pointer-events-none"
          :style="{ transform: `translate(${viewport.x}px, ${viewport.y}px) scale(${viewport.scale})` }"
        >
          <svg class="absolute inset-0 pointer-events-none overflow-visible z-0">
            <g v-for="(link, index) in connections" :key="index" class="pointer-events-auto cursor-pointer">
              <path 
                :d="generatePath(link)" 
                stroke="#4f46e5" 
                stroke-width="2.5" 
                fill="none" 
                class="hover:stroke-red-500 transition-colors"
                @contextmenu.prevent="removeConnection(index)"
              />
            </g>
            <line v-if="linkingSource" 
              :x1="linkingSource.x" :y1="linkingSource.y" 
              :x2="mousePos.x" :y2="mousePos.y" 
              stroke="#6366f1" stroke-width="2" stroke-dasharray="4" />
          </svg>

          <div 
            v-for="node in nodes" 
            :key="node.id"
            class="absolute w-64 bg-[#1c212c] border border-slate-700 rounded-lg shadow-2xl z-10 cursor-move min-h-[100px] pointer-events-auto group/node"
            :style="{ left: node.x + 'px', top: node.y + 'px' }"
            @mousedown.stop="startNodeMove($event, node)"
          >
         

            <div 
            @contextmenu.prevent.stop="completeLink(node)" 
            class="absolute -left-0 top-[52px] -translate-y-1/2 w-4 h-4  border-2 rounded-full cursor-alias transition-all z-20"
            :class="[
                connections.some(c => c.toId === node.id) 
                ? 'border-indigo-400 bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.8)]' 
                : 'border-slate-600  bg-slate-900 hover:bg-green-500 hover:border-green-400'
            ]"
            ></div>

            <div 
            @contextmenu.prevent.stop="startLink(node)" 
            class="absolute -right-0 top-[52px] -translate-y-1/2 w-4 h-4  border-2 rounded-full cursor-alias transition-all z-20"
            :class="[
                connections.some(c => c.fromId === node.id) 
                ? 'border-indigo-400 bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.8)]' 
                : 'border-slate-600 bg-slate-900 hover:bg-indigo-500 hover:border-indigo-400'
            ]"
            ></div>
            
            <div 
              class="p-3 border-b border-slate-700 flex justify-between items-center transition-colors rounded-t-lg"
              :class="activeElement === `node-${node.id}` ? 'bg-indigo-500/15' : 'bg-white/5'"
            >
              <input 
                v-model="node.name" 
                @focus="activeElement = `node-${node.id}`"
                @blur="activeElement = null"
                class="bg-transparent border-none text-xs font-bold text-white uppercase tracking-tighter focus:ring-0 focus:outline-none w-full"
                placeholder="TABLE_NAME"
              />
              <button @click.stop="removeNode(node.id)" class="text-slate-500 hover:text-red-400 text-lg ml-2">&times;</button>
            </div>
            
            <div class="p-2 space-y-1">
              <div 
                v-for="col in node.columns" 
                :key="col.id" 
                class="flex gap-1.5 items-center p-1 rounded transition-all bg-slate-800/30 focus-within:bg-indigo-500/25 group/row"
              >
                <span v-if="col.isPk" class="text-[7px] font-bold bg-amber-500/20 text-amber-500 px-1 rounded flex-shrink-0">PK</span>
                <span v-if="col.isFk" class="text-[7px] font-bold bg-indigo-500/20 text-indigo-400 px-1 rounded flex-shrink-0">FK</span>

                <input 
                    v-model="col.name"
                    :disabled="col.fixed"
                    @focus="activeElement = `col-${node.id}-${col.id}`"
                    @blur="activeElement = null"
                    class="bg-transparent border-none text-[11px] font-mono text-slate-300 focus:ring-0 focus:outline-none w-full p-0 px-1 disabled:text-slate-500 disabled:cursor-not-allowed"
                />
                <select 
                    v-model="col.type"
                    :disabled="col.fixed"
                    @focus="activeElement = `col-${node.id}-${col.id}`"
                    @blur="activeElement = null"
                    class="bg-transparent border-none text-[10px] font-mono text-indigo-400 focus:ring-0 focus:outline-none cursor-pointer pr-4 disabled:text-indigo-900 disabled:cursor-not-allowed"
                >
                    <option v-for="type in sqlTypes" :key="type" :value="type">{{ type }}</option>
                </select>
                <button 
                  v-if="!col.fixed"
                  @click="removeColumn(node, col.id)" 
                  class="opacity-0 group-hover/row:opacity-100 text-slate-500 hover:text-red-400 text-xs px-1"
                >×</button>
              </div>

              <button @mousedown.stop @click="addColumn(node)" class="w-full mt-2 py-1.5 border border-dashed border-slate-700 rounded text-[10px] text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/5 transition-all">
                + ADD COLUMN
              </button>
            </div>
          </div>
        </div>
      </div>

      <footer class="h-10 border-t border-slate-800 bg-[#0f1115] p-3 font-mono text-[10px] flex items-center justify-between">
        <div class="flex gap-4">
          <span class="text-green-500 font-bold" v-if="saveStatus">✓ BLUEPRINT PERSISTED</span>
          <span class="text-slate-500" v-else>READY | Fixed columns (PK) cannot be modified</span>
        </div>
      </footer>
    </main>
  </div>
</template>