<template>
    <div class="p-8 bg-gray-900 min-h-screen text-gray-100 font-sans">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-600">
                    Celina Engine Lab
                </h1>
                <p class="text-xs text-gray-500 font-mono mt-1">v2.0 - Auto-Schema Engine</p>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-gray-800 p-1 rounded-lg flex border border-gray-700">
                    <button @click="viewMode = 'lab'" :class="viewMode === 'lab' ? 'bg-purple-600' : ''" class="px-4 py-1 rounded-md text-xs transition">Visual Lab</button>
                    <button @click="viewMode = 'tables'" :class="viewMode === 'tables' ? 'bg-purple-600' : ''" class="px-4 py-1 rounded-md text-xs transition">Data Tables</button>
                    <button @click="viewMode = 'synth'" :class="viewMode === 'synth' ? 'bg-purple-600' : ''" class="px-4 py-1 rounded-md text-xs transition">Database Synth</button>
                </div>
            </div>
        </div>

        <div v-if="viewMode === 'lab'" class="space-y-12">
            <section class="space-y-4">
                <h2 class="text-xl font-bold border-b border-gray-700 pb-2 text-purple-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">navigation</span> Quick Navigation Edit
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="nav in navigations" :key="nav.id" class="bg-gray-800 p-4 rounded-xl border border-gray-700 hover:border-purple-500/50 transition">
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-500 font-mono uppercase">Nav #{{ nav.id }}</span>
                                <button @click="deleteNav(nav.id)" class="text-gray-600 hover:text-red-500 transition">✕</button>
                            </div>
                            <input v-model="nav.label" @change="saveNav(nav)" class="w-full bg-gray-900 border-gray-700 rounded p-2 text-sm focus:ring-1 focus:ring-purple-500 outline-none" placeholder="Label">
                            <select v-model="nav.screen_id" @change="saveNav(nav)" class="w-full bg-gray-900 border-gray-700 rounded p-2 text-sm focus:ring-1 focus:ring-purple-500">
                                <option v-for="s in screens" :value="s.id">{{ s.title }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <h2 class="text-xl font-bold border-b border-gray-700 pb-2 text-pink-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">terminal</span> IDE Environment
                </h2>
                
                <div v-for="screen in filteredScreens" :key="screen.id" class="bg-gray-800 rounded-2xl border border-gray-700 shadow-2xl overflow-hidden">
                    <div class="bg-gray-850 px-6 py-3 border-b border-gray-700 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-1.5 mr-4">
                                <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                            </div>
                            <div class="bg-[#0d1117] px-4 py-1 rounded-t-lg border-x border-t border-gray-700 -mb-[13px] z-10 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-orange-400">{{ screen.type === 'custom' ? 'html' : 'settings' }}</span>
                                <input v-model="screen.title" class="bg-transparent border-none font-mono text-xs focus:ring-0 p-0 text-gray-300 w-auto min-w-[120px]" spellcheck="false">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="saveScreen(screen)" class="flex items-center gap-1.5 px-3 py-1 bg-purple-600 hover:bg-purple-500 rounded text-[10px] font-bold transition uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[14px]">save</span>
                                Save
                            </button>
                            <div class="h-4 w-[1px] bg-gray-700"></div>
                            <span class="text-[10px] font-mono text-pink-500 uppercase font-bold">{{ screen.type }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12">
                        <div class="lg:col-span-7 bg-[#0d1117] flex border-r border-gray-700 relative h-[720px] overflow-hidden">
                            <div :id="'lines-' + screen.id" class="w-12 bg-[#0d1117] border-r border-gray-800/50 py-6 text-right pr-3 select-none overflow-hidden">
                                <div v-for="n in (screen.content_data?.split('\n').length || 1)" :key="n" 
                                     class="text-[11px] font-mono text-gray-600 leading-[20px] h-[20px]">
                                    {{ n }}
                                </div>
                            </div>
                            
                            <div class="flex-1 relative overflow-hidden">
                                <textarea 
                                    v-model="screen.content_data" 
                                    class="absolute inset-0 w-full h-full font-mono text-[13px] bg-transparent text-transparent caret-white p-6 focus:ring-0 outline-none z-20 resize-none leading-[20px] overflow-auto custom-scrollbar whitespace-pre" 
                                    @scroll="syncScroll($event, screen.id)"
                                    spellcheck="false"
                                ></textarea>

                                <pre :id="'pre-' + screen.id" 
                                     class="absolute inset-0 w-full h-full font-mono text-[13px] p-6 pointer-events-none z-10 leading-[20px] overflow-hidden whitespace-pre select-none"
                                     v-html="highlightCode(screen.content_data, screen.type)"></pre>
                                
                                <div class="absolute bottom-4 right-6 text-[9px] text-gray-600 font-mono z-30 bg-[#0d1117]/80 px-2 py-1 rounded border border-gray-800">
                                    {{ screen.type === 'custom' ? 'HTML5' : 'JSON' }}
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-5 bg-gray-900 flex flex-col h-[720px]">
                            <div class="bg-gray-800/50 px-4 py-2 border-b border-gray-700 text-[10px] font-mono text-gray-400 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[12px]">play_arrow</span> LIVE_OUTPUT
                                </div>
                            </div>
                            
                            <div class="flex-1 p-6 flex justify-center items-start overflow-hidden">
                                <div v-if="screen.type === 'custom'" class="relative w-[300px] h-[600px] scale-95 origin-top bg-black rounded-[3rem] border-[8px] border-gray-800 shadow-2xl overflow-hidden ring-1 ring-white/10">
                                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-24 h-6 bg-gray-800 rounded-b-2xl z-10"></div>
                                    <iframe :srcdoc="screen.content_data" class="w-full h-full bg-white border-none"></iframe>
                                </div>

                                <div v-else class="w-full h-full bg-black/40 border border-gray-800 rounded-xl p-6 font-mono text-xs overflow-auto custom-scrollbar shadow-inner">
                                    <ul v-if="getParsedJson(screen.content_data)" class="space-y-1">
                                        <TreeItem :item="getParsedJson(screen.content_data)" name="root" :depth="0" />
                                    </ul>
                                    <div v-else class="text-red-500 flex flex-col items-center justify-center h-full gap-2 opacity-50">
                                        <span class="material-symbols-outlined text-4xl">heart_broken</span>
                                        <span class="text-xs uppercase tracking-widest font-bold">invalid_json_format</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div v-if="viewMode === 'tables'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div v-for="(fields, type) in schemas" :key="type" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden h-fit">
                <div class="p-4 bg-gray-700/50 font-bold flex justify-between items-center border-b border-gray-700">
                    <span class="uppercase tracking-widest text-xs text-purple-300">{{ type.replace(/_/g, ' ') }}s</span>
                    <button @click="openModal(type)" class="text-[10px] bg-green-600 px-3 py-1 rounded hover:bg-green-500">+ NEW</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-gray-500 bg-gray-900/50">
                            <tr>
                                <th v-for="f in fields" :key="f.key" class="p-3">{{ f.label }}</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in props[type === 'submodule' ? 'subModules' : type + 's']" :key="item.id" class="border-b border-gray-700/50 hover:bg-gray-750 text-gray-300">
                                <td v-for="f in fields" :key="f.key" class="p-3">
                                    <span v-if="f.key === 'id'" class="font-mono text-gray-600">#{{ item[f.key] }}</span>
                                    <span v-else class="truncate max-w-[150px] inline-block">{{ item[f.key] }}</span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button @click="openModal(type, item)" class="text-blue-400 mr-3">Edit</button>
                                    <button @click="deleteData(type, item.id)" class="text-gray-600 hover:text-red-500">✕</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div v-if="showDataModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-gray-800 p-8 rounded-2xl w-full max-w-md border border-gray-600 shadow-2xl">
                <h3 class="text-xl font-bold mb-6 capitalize text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-500">
                    {{ isEditing ? 'Edit' : 'Create' }} {{ modalType }}
                </h3>
                
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="field in schemas[modalType]" :key="field.key">
                        <template v-if="(field.key !== 'id' && !field.hidden) || (!isEditing && modalType === 'system_icon')">
                            <label class="text-[10px] text-gray-500 block mb-1 uppercase font-bold tracking-tighter">{{ field.label }}</label>
                            
                            <select v-if="field.type === 'select_screen_type'" v-model="formData[field.key]" class="w-full bg-gray-900 p-3 rounded border border-gray-700 text-white">
                                <option value="standard">Standard</option>
                                <option value="custom">Custom</option>
                                <option value="dynamic">Dynamic</option>
                            </select>

                            <select v-else-if="field.type === 'select'" v-model="formData[field.key]" class="w-full bg-gray-900 p-3 rounded border border-gray-700 text-white">
                                <option v-for="opt in props[field.options]" :key="opt.id" :value="opt.id">
                                    {{ opt.name || opt.label || opt.title || opt.id }}
                                </option>
                            </select>

                            <textarea v-else-if="field.type === 'textarea'" v-model="formData[field.key]" rows="3" class="w-full bg-gray-900 p-3 rounded border border-gray-700 text-white font-mono text-xs"></textarea>

                            <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 p-3 bg-gray-900 rounded border border-gray-700">
                                <input type="checkbox" v-model="formData[field.key]" class="rounded border-gray-700 text-purple-600 focus:ring-purple-500">
                                <span class="text-xs text-gray-400">Enabled / Active</span>
                            </div>

                            <input v-else :type="field.type" v-model="formData[field.key]" class="w-full bg-gray-900 p-3 rounded border border-gray-700 text-white">
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-4 border-t border-gray-700">
                    <button @click="showDataModal = false" class="text-gray-400 text-sm">Cancel</button>
                    <button @click="saveData" class="bg-purple-600 hover:bg-purple-500 px-8 py-2 rounded-lg font-bold shadow-lg transition">
                        Commit
                    </button>
                </div>
            </div>
        </div>

        <!-- The Synth View -->
<div v-if="viewMode === 'synth'" class="space-y-6">
    <!-- Header/Action Bar -->
    <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 flex items-center justify-between shadow-2xl">
        <div class="flex items-center gap-6">
            <!-- App Selector -->
            <div v-if="!activeTable">
                <label class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Target Application</label>
                <select v-model="selectedAppId" @change="fetchTables" class="bg-gray-900 border-gray-700 rounded-lg text-sm p-2 w-64 focus:ring-purple-500 outline-none">
                    <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
                </select>
            </div>
            
            <!-- Breadcrumb Navigation if a table is open -->
            <div v-else class="flex items-center gap-3">
                <button @click="activeTable = null" class="p-2 hover:bg-gray-700 rounded-full transition text-gray-400">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <div>
                    <h3 class="text-purple-400 font-mono font-bold leading-none uppercase">{{ activeTable }}</h3>
                    <p class="text-[9px] text-gray-500 mt-1 uppercase tracking-widest">Live Data Explorer</p>
                </div>
            </div>

            <div v-if="selectedAppId" class="h-10 w-[1px] bg-gray-700"></div>
            
            <!-- Action: Synthesize Table (Only on App View) -->
            <div v-if="selectedAppId && !activeTable">
                <label class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Structure</label>
                 <button @click="showSynthModal = true" class="flex items-center gap-2 bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-2 rounded-lg text-xs font-bold hover:scale-105 transition">
                    <span class="material-symbols-outlined text-sm">add_box</span>
                    SYNTHESIZE TABLE
                </button>
            </div>

            <!-- Action: Create New Data (Only when table is active and NOT empty) -->
            <div v-if="activeTable && tableData.length > 0">
                <label class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Record Entry</label>
                 <button @click="openCreateModal" class="flex items-center gap-2 bg-gray-900 border border-purple-500/50 text-purple-400 px-4 py-2 rounded-lg text-xs font-bold hover:bg-purple-500/10 transition">
                    <span class="material-symbols-outlined text-sm">post_add</span>
                    INSERT DATA
                </button>
            </div>
        </div>
    </div>

    <!-- State A: Selection Grid -->
    <div v-if="!activeTable" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="table in appTables" :key="table" class="bg-gray-800 border border-gray-700 rounded-xl p-4 hover:border-purple-500/50 transition group">
            <div class="flex justify-between items-start mb-3">
                <div class="p-2 bg-purple-500/10 rounded text-purple-400"><span class="material-symbols-outlined text-sm">table_rows</span></div>
                <button @click="viewTableData(table)" class="text-blue-400 opacity-0 group-hover:opacity-100 transition">
                    <span class="material-symbols-outlined text-sm">database_search</span>
                </button>
            </div>
            <h4 class="font-mono text-sm text-gray-200">{{ table }}</h4>
        </div>
    </div>

    <!-- State B: Dynamic Explorer -->
    <div v-else class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-800/50 border-b border-gray-800">
                    <tr>
                        <th v-for="col in tableColumns" :key="col" class="p-4 text-[10px] font-bold text-gray-500 uppercase font-mono">{{ col }}</th>
                        <th class="p-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr v-for="row in tableData" :key="row.id" class="hover:bg-purple-500/5 transition-colors">
                        <td v-for="col in tableColumns" :key="col" class="p-4 text-xs font-mono text-gray-300">{{ row[col] }}</td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-3">
                                <!-- Edit Button -->
                                <button @click="openEditModal(row)" class="text-gray-600 hover:text-blue-400 transition">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <!-- Delete Button -->
                                <button @click="deleteRow(row.id)" class="text-gray-600 hover:text-red-500 transition">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State UI (Kept as is for UX) -->
        <div v-if="tableData.length === 0" class="p-12 text-center border-t border-gray-800">
            <p class="text-gray-600 font-mono text-[10px] uppercase tracking-widest">There's no data</p>
            <button @click="openCreateModal" class="mt-4 text-purple-500 text-[10px] hover:underline uppercase font-bold">+ Create New Data</button>
        </div>
    </div>
</div>

<!-- Dynamic Data Entry Modal -->
<div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="bg-gray-800 border border-gray-700 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-gray-700 flex justify-between items-center">
            <!-- Title switches based on mode -->
            <h3 class="text-purple-400 font-mono font-bold uppercase tracking-wider">
                {{ newData.id ? 'Update_Entry' : 'New_Entry' }}: {{ activeTable }}
            </h3>
            <button @click="showCreateModal = false" class="text-gray-500 hover:text-white transition">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
            <div v-for="(value, key) in newData" :key="key">
                <div class="flex justify-between items-center mb-1">
                    <label class="text-[10px] text-gray-500 uppercase font-bold font-mono">{{ key }}</label>
                    <!-- Visual indicator for protected fields -->
                    <span v-if="key === 'id'" class="text-[9px] text-amber-500/70 font-mono flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">lock</span> 
                        READ_ONLY
                    </span>
                </div>
                
                <input 
                    v-model="newData[key]" 
                    type="text"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 text-sm text-gray-200 outline-none transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="key === 'id' ? 'border-amber-500/20 text-amber-500/50' : 'focus:ring-1 focus:ring-purple-500'"
                    :placeholder="'Enter ' + key + '...'"
                    :disabled="key === 'id'"
                >
            </div>
        </div>

        <div class="p-6 bg-gray-900/50 flex gap-3">
            <button 
                @click="commitNewData" 
                :disabled="isProcessing"
                class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 py-3 rounded-xl text-xs font-bold hover:scale-[1.02] active:scale-95 transition disabled:opacity-50"
            >
                {{ isProcessing ? 'EXECUTING...' : 'COMMIT_DATA_SEQUENCE' }}
            </button>
            <button @click="showCreateModal = false" class="px-6 py-3 border border-gray-700 rounded-xl text-xs font-bold text-gray-400 hover:bg-gray-700 transition">
                CANCEL
            </button>
        </div>
    </div>
</div>

        <div v-if="showSynthModal" class="fixed inset-0 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 z-[60]">
            <div class="bg-gray-800 p-8 rounded-2xl w-full max-w-3xl border border-purple-500/30 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-purple-400 font-mono flex items-center gap-2">
                        <span class="material-symbols-outlined">database</span> 
                        SCHEMA_SYNTHESIZER
                    </h3>
                    <button @click="showSynthModal = false" class="text-gray-500 hover:text-white">✕</button>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <!-- Table Name -->
                    <div>
                        <label class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Table Name (Snake Case)</label>
                        <input v-model="synthData.table_name" placeholder="e.g. inventory_logs" 
                            class="w-full bg-gray-900 border-gray-700 rounded-lg p-3 text-sm font-mono text-purple-300 focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>

                    <!-- Dynamic Columns List -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center border-b border-gray-700 pb-2">
                            <label class="text-[10px] text-gray-500 uppercase font-bold">Architecture / Column Definitions</label>
                            <button @click="addColumn" class="bg-purple-600/20 text-purple-400 px-3 py-1 rounded text-[10px] font-bold hover:bg-purple-600 hover:text-white transition">
                                + ADD FIELD
                            </button>
                        </div>
                        
                        <div class="max-h-[350px] overflow-y-auto pr-2 custom-scrollbar space-y-2">
                            <div v-for="(col, index) in synthData.columns" :key="index" 
                                class="flex gap-2 items-center bg-gray-900 p-2 rounded-lg border border-gray-700 hover:border-gray-600 transition">
                                
                                <!-- Column Name -->
                                <div class="flex-1">
                                    <input v-model="col.name" placeholder="field_name" 
                                        class="w-full bg-transparent border-none p-1 text-xs font-mono text-gray-200 focus:ring-0">
                                </div>

                                <
                                <!-- Data Type Selection -->
                                <select v-model="col.type" class="bg-gray-850 border-gray-700 rounded p-1 text-[11px] text-orange-400 font-mono outline-none">
                                    <option v-for="type in availableTypes" :key="type" :value="type">
                                        {{ type }}
                                    </option>
                                </select>

                                <!-- Nullable Toggle -->
                                <button @click="col.nullable = !col.nullable" 
                                        :class="col.nullable ? 'text-green-500 bg-green-500/10' : 'text-gray-600 bg-gray-800'"
                                        class="px-2 py-1 rounded text-[9px] font-bold transition">
                                    NULL
                                </button>

                                <!-- Remove Field -->
                                <button @click="removeColumn(index)" class="text-gray-700 hover:text-red-500 px-2 transition">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex justify-end gap-4 mt-8 pt-4 border-t border-gray-700">
                    <button @click="showSynthModal = false" class="text-gray-400 text-sm hover:text-white">Cancel</button>
                    <button @click="commitTableSynth" 
                            :disabled="!synthData.table_name || synthData.columns.some(c => !c.name)"
                            class="bg-gradient-to-r from-purple-600 to-blue-600 disabled:opacity-20 px-8 py-2 rounded-lg font-bold shadow-lg transition-all hover:scale-105 active:scale-95">
                        EXECUTE GENERATION
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
const props = defineProps({ 
    navigations: Array, screens: Array, apps: Array, users: Array, 
    roles: Array, menus: Array, subModules: Array, system_icons: Array, schemas: Object
});

const viewMode = ref('lab'); 
const filteredScreens = computed(() => props.screens?.filter(s => s.type === 'custom' || s.type === 'dynamic') || []);

// --- Data Management State ---
const showDataModal = ref(false);
const modalType = ref('');
const isEditing = ref(false);
const formData = ref({});

// --- IDE Logic ---
const syncScroll = (e, id) => {
    const pre = document.getElementById('pre-' + id);
    const lines = document.getElementById('lines-' + id);
    if (pre) {
        pre.scrollTop = e.target.scrollTop;
        pre.scrollLeft = e.target.scrollLeft;
    }
    if (lines) {
        lines.scrollTop = e.target.scrollTop;
    }
};

const highlightCode = (code, type) => {
    if (!code) return '';
    let res = code.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    if (type === 'dynamic') { 
        return res
            .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*")(\s*:)/g, '<span class="text-purple-400">$1</span>$3') 
            .replace(/:\s*("(?:\\.|[^\\"])*")/g, ': <span class="text-green-300">$1</span>') 
            .replace(/:\s*(\d+)/g, ': <span class="text-orange-300">$1</span>') 
            .replace(/:\s*(true|false|null)/g, ': <span class="text-blue-400">$1</span>'); 
    } else { 
        const regex = /("[^"]*")|(&lt;\/?)([a-z0-9-]+)|(\b[a-z-]+(?==))|(\b(function|var|let|const|return|if|else|for|while|true|false|null|undefined)\b)/gi;
        return res.replace(regex, (match, string, tagStart, tagName, attrName, keyword) => {
            if (string) return `<span class="text-green-300">${string}</span>`;
            if (tagName) return `${tagStart}<span class="text-pink-500">${tagName}</span>`;
            if (attrName) return `<span class="text-orange-300">${attrName}</span>`;
            if (keyword) {
                const color = /true|false|null|undefined/.test(keyword) ? 'text-blue-400' : 'text-purple-400';
                return `<span class="${color}">${keyword}</span>`;
            }
            return match;
        });
    }
};

const getParsedJson = (data) => {
    try { return typeof data === 'string' ? JSON.parse(data) : data; } catch (e) { return null; }
};

// --- CRUD Actions ---
const openModal = (type, item = null) => {
    modalType.value = type;
    isEditing.value = !!item;
    formData.value = item ? { ...item } : { app_id: props.apps[0]?.id };
    showDataModal.value = true;
};

const saveData = () => {
    const routeName = isEditing.value ? 'test-dashboard.update' : 'test-dashboard.store';
    const routeParams = isEditing.value 
        ? { type: modalType.value, id: formData.value.id } 
        : { type: modalType.value };

    router[isEditing.value ? 'put' : 'post'](route(routeName, routeParams), formData.value, {
        onSuccess: () => showDataModal.value = false
    });
};

const deleteData = (type, id) => {
    if(confirm(`Permanent delete ${type} #${id}?`)) {
        router.delete(route('test-dashboard.delete', { type, id }), { preserveScroll: true });
    }
};

// Quick Autosaves for Lab Mode
const saveNav = (nav) => router.put(route('test-dashboard.nav.update', { id: nav.id }), nav, { preserveScroll: true, preserveState: true });
const saveScreen = (screen) => router.put(route('test-dashboard.screen.update', { id: screen.id }), screen, { preserveScroll: true, preserveState: true });
const deleteNav = (id) => deleteData('nav', id);

// --- JSON Tree Component ---
const TreeItem = defineComponent({
    name: 'TreeItem',
    props: ['item', 'name', 'depth'],
    setup(props) {
        const isOpen = ref(props.depth < 4);
        const isObject = computed(() => typeof props.item === 'object' && props.item !== null);
        const isArray = computed(() => Array.isArray(props.item));
        return () => h('li', { class: 'list-none' }, [
            h('div', { class: 'flex items-center gap-1 py-0.5 group cursor-default' }, [
                isObject.value ? h('button', { 
                    onClick: (e) => { e.stopPropagation(); isOpen.value = !isOpen.value; },
                    class: 'text-[9px] w-4 text-gray-600 hover:text-purple-400 transition transform' + (isOpen.value ? '' : ' -rotate-90')
                }, '▼') : h('span', { class: 'w-4' }),
                h('span', { class: 'text-blue-400 font-medium' }, props.name),
                h('span', { class: 'text-gray-500 mx-0.5' }, ':'),
                !isObject.value ? h('span', { class: 'text-amber-200/90 ml-1' }, 
                    typeof props.item === 'string' ? `"${props.item}"` : String(props.item)
                ) : h('span', { class: 'text-gray-500 text-[9px] ml-1 opacity-50 uppercase tracking-tighter' }, 
                    isArray.value ? `Array[${props.item.length}]` : 'Object'
                )
            ]),
            (isObject.value && isOpen.value) ? h('ul', { class: 'ml-4 border-l border-white/5 pl-3 mt-0.5' }, 
                Object.entries(props.item).map(([key, value]) => h(TreeItem, { 
                    key, name: key, item: value, depth: props.depth + 1 
                }))
            ) : null
        ]);
    }
});

// --- CELINA-SYNTH STATE ---
const selectedAppId = ref(null);
const appTables = ref([]);
const showSynthModal = ref(false);

const synthData = ref({
    app_id: null,
    table_name: '',
    columns: [
        { name: '', type: 'string', nullable: false }
    ]
});

// Map of supported Laravel data types for the UI dropdown
const availableTypes = [
    'string', 'text', 'mediumText', 'longText', 
    'integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger',
    'boolean', 'decimal', 'float', 'double',
    'date', 'dateTime', 'timestamp', 'time', 'year',
    'json', 'jsonb', 'binary', 'uuid', 'ipAddress'
];

// --- ACTIONS ---
const addColumn = () => {
    synthData.value.columns.push({ name: '', type: 'string', nullable: false });
};

const removeColumn = (index) => {
    if (synthData.value.columns.length > 1) {
        synthData.value.columns.splice(index, 1);
    }
};

const commitTableSynth = () => {
    synthData.value.app_id = selectedAppId.value;
    
    // Inertia request to the createTable method
    router.post(route('architect.create-table'), synthData.value, {
        onSuccess: () => {
            showSynthModal.value = false;
            fetchTables();
        },
        preserveScroll: true
    });
};

// --- DYNAMIC CRUD STATE ---
const activeTable = ref(null); 
const tableData = ref([]);
const tableColumns = ref([]);
const isProcessing = ref(false);

// --- ACTIONS ---

const fetchTables = async () => {
    if (!selectedAppId.value) return;
    try {
        const url = route('architect.get-tables', { appId: selectedAppId.value });
        const response = await fetch(url);
        appTables.value = await response.json();
    } catch (e) {
        console.error("Link to CELINA-SYNTH broken.", e);
    }
};

const viewTableData = async (tableName) => {
    activeTable.value = tableName;
    try {
        const url = route('dynamic.index', { appId: selectedAppId.value, tableName });
        const response = await fetch(url);
        const result = await response.json();
        tableColumns.value = result.columns;
        tableData.value = result.data;
    } catch (e) {
        console.error("Data link failed.", e);
    }
};

const saveRow = async (rowData) => {
    isProcessing.value = true;
    try {
        const isUpdate = !!rowData.id;
        
        // Ensure params are correctly mapped for Ziggy
        const url = isUpdate 
            ? route('dynamic.update', { appId: selectedAppId.value, tableName: activeTable.value, id: rowData.id })
            : route('dynamic.store', { appId: selectedAppId.value, tableName: activeTable.value });

        // Use a standard axios call to ensure data is sent in the body
        const response = await axios({
            method: isUpdate ? 'put' : 'post',
            url: url,
            data: rowData
        });

        if (response.data.success) {
            // Re-fetch the entire dataset to sync the frontend with the DB
            await viewTableData(activeTable.value);
            return true;
        }
    } catch (e) {
        // Log the actual server error to the console
        console.error("DATA_WRITE_ERROR:", e.response?.data || e.message);
        alert("Transmission Failed: Check console for logs.");
        return false;
    } finally {
        isProcessing.value = false;
    }
};

const deleteRow = async (id) => {
    if(!confirm('Execute sequence: PERMANENT_DELETE?')) return;
    try {
        const url = route('dynamic.delete', { appId: selectedAppId.value, tableName: activeTable.value, id });
        await axios.delete(url);
        tableData.value = tableData.value.filter(item => item.id !== id);
    } catch (e) {
        console.error("Protocol Breach: Deletion failed.", e);
    }
};

// --- DYNAMIC CREATE STATE ---
const showCreateModal = ref(false);
const newData = ref({});

// --- ACTIONS ---

const openCreateModal = () => {
    // Reset and build the dynamic object based on detected columns
    newData.value = {};
    
    // Filter out columns typically handled by the DB to keep entry clean
    const skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    tableColumns.value.forEach(col => {
        if (!skipColumns.includes(col)) {
            newData.value[col] = '';
        }
    });
    
    showCreateModal.value = true;
};

const openEditModal = (row) => {
    const skipColumns = ['created_at', 'updated_at', 'deleted_at'];
    const buffer = {};
    Object.keys(row).forEach(key => {
        if (!skipColumns.includes(key)) {
            buffer[key] = row[key];
        }
    });
    newData.value = buffer;
    showCreateModal.value = true;
};

const commitNewData = async () => {
    // Basic validation to prevent sending empty objects
    if (Object.values(newData.value).every(v => v === '')) {
        console.warn("ABORT: Empty dataset.");
        return;
    }
    const success = await saveRow(newData.value);
    if (success) {
        showCreateModal.value = false;
        newData.value = {}; // Clear the buffer
    }
};
</script>

<style>
.bg-gray-850 { background-color: #161b22; }
textarea, pre {
    line-height: 20px !important;
    font-family: 'JetBrains Mono', 'Fira Code', monospace !important;
    tab-size: 4;
}
pre { white-space: pre !important; word-wrap: normal !important; }
.custom-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #0d1117; }
.custom-scrollbar::-webkit-scrollbar-thumb { 
    background: #30363d; 
    border-radius: 10px;
    border: 2px solid #0d1117;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #484f58; }
iframe::-webkit-scrollbar { display: none; }
</style>