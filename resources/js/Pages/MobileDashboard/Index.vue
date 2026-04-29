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
                    <span class="material-symbols-outlined text-sm">terminal</span> Raw Screen Data
                </h2>
                
                <div v-for="screen in filteredScreens" :key="screen.id" class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-2xl">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-4">
                            <input v-model="screen.title" @change="saveScreen(screen)" class="bg-transparent border-none font-bold text-2xl focus:ring-0 p-0 text-white w-auto min-w-[200px]" placeholder="Screen Title">
                            <span :class="screen.type === 'custom' ? 'bg-blue-900/30 text-blue-400 border-blue-800/50' : 'bg-yellow-900/30 text-yellow-400 border-yellow-800/50'" 
                                  class="text-[10px] px-3 py-1 rounded-full border uppercase tracking-widest font-bold">
                                {{ screen.type }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] bg-pink-900/30 text-pink-400 px-3 py-1 rounded-full border border-pink-800/50 uppercase tracking-widest font-bold">
                                {{ screen.type === 'custom' ? 'HTML Editor' : 'Logic Engine' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <div class="lg:col-span-7 space-y-2">
                            <label class="text-[10px] text-gray-500 font-mono flex justify-between">
                                <span>{{ screen.type === 'dynamic' ? 'JSON SCHEMA CONFIG' : 'SOURCE CODE' }}</span>
                                <span class="text-purple-500/50 italic font-normal">Auto-saving enabled...</span>
                            </label>
                            <div class="relative group">
                                <textarea 
                                    v-model="screen.content_data" 
                                    class="w-full h-[650px] font-mono text-[13px] bg-slate-950 text-slate-300 p-6 rounded-xl border border-gray-700 custom-scrollbar focus:border-purple-500 outline-none transition-all resize-none shadow-inner leading-relaxed" 
                                    @change="saveScreen(screen)"
                                    spellcheck="false"
                                ></textarea>
                                <div class="absolute top-4 right-4 opacity-20 group-hover:opacity-100 transition pointer-events-none">
                                    <span class="material-symbols-outlined text-white">code</span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-5 flex flex-col">
                            <label class="text-[10px] text-gray-500 font-mono mb-2 uppercase tracking-widest">Live Execution Output</label>
                            
                            <div v-if="screen.type === 'custom'" class="flex justify-center items-center h-[650px] bg-slate-950/50 rounded-xl border border-dashed border-gray-700">
                                <div class="relative w-[280px] h-[580px] bg-black rounded-[3rem] border-[8px] border-gray-800 shadow-[0_0_50px_rgba(0,0,0,0.5)] overflow-hidden ring-1 ring-gray-700">
                                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-24 h-6 bg-gray-800 rounded-b-2xl z-10"></div>
                                    <iframe :srcdoc="screen.content_data" class="w-full h-full bg-white border-none" loading="lazy"></iframe>
                                    <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-20 h-1 bg-gray-600 rounded-full"></div>
                                </div>
                            </div>

                            <div v-else class="w-full h-[650px] bg-slate-950 border border-gray-700 rounded-xl p-8 overflow-auto custom-scrollbar font-mono text-xs shadow-inner">
                                <div v-if="getParsedJson(screen.content_data)">
                                    <ul class="space-y-1">
                                        <TreeItem :item="getParsedJson(screen.content_data)" name="root" :depth="0" />
                                    </ul>
                                </div>
                                <div v-else class="h-full flex flex-col items-center justify-center opacity-30 italic">
                                    <span class="material-symbols-outlined text-4xl mb-2 text-red-500">heart_broken</span>
                                    <span class="text-red-400 text-sm">// Syntax Error: Invalid JSON Format</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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

        </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({ 
    navigations: Array, screens: Array, apps: Array, users: Array, 
    roles: Array, menus: Array, subModules: Array, system_icons: Array, schemas: Object
});

const viewMode = ref('lab'); 
const showDataModal = ref(false);
const modalType = ref('');
const isEditing = ref(false);
const formData = ref({});

const filteredScreens = computed(() => props.screens.filter(s => s.type === 'custom' || s.type === 'dynamic'));

const getParsedJson = (data) => {
    try { return typeof data === 'string' ? JSON.parse(data) : data; } catch (e) { return null; }
};

// Tree Item Component with Depth-Based Auto-Collapse
const TreeItem = defineComponent({
    name: 'TreeItem',
    props: ['item', 'name', 'depth'],
    setup(props) {
        // Automatically close if depth > 2 (root is 0, children is 1, index is 2)
        const isOpen = ref(props.depth < 3);
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
                    key, 
                    name: key, 
                    item: value, 
                    depth: props.depth + 1 
                }))
            ) : null
        ]);
    }
});

const openModal = (type, item = null) => {
    modalType.value = type;
    isEditing.value = !!item;
    formData.value = item ? { ...item } : { app_id: props.apps[0]?.id };
    showDataModal.value = true;
};

const saveData = () => {
    const routeName = isEditing.value ? 'test-dashboard.update' : 'test-dashboard.store';
    router[isEditing.value ? 'put' : 'post'](route(routeName, { type: modalType.value, id: formData.value.id }), formData.value, {
        onSuccess: () => showDataModal.value = false
    });
};

const deleteData = (type, id) => {
    if(confirm(`Permanent delete ${type} #${id}?`)) router.delete(route('test-dashboard.delete', { type, id }));
};

const saveNav = (nav) => router.put(route('test-dashboard.nav.update', { id: nav.id }), nav);
const saveScreen = (screen) => router.put(route('test-dashboard.screen.update', { id: screen.id }), screen);
const deleteNav = (id) => deleteData('nav', id);
</script>

<style>
/* Professional Scrollbar */
.custom-scrollbar::-webkit-scrollbar { 
    width: 6px; 
}
.custom-scrollbar::-webkit-scrollbar-track { 
    background: #020617; /* slate-950 */
}
.custom-scrollbar::-webkit-scrollbar-thumb { 
    background: #334155; /* slate-700 */
    border-radius: 10px; 
    cursor: pointer !important; /* Visual cue for the user */
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover { 
    background: #475569; /* slate-600 */
}

/* Ensure the pointer shows on the thumb */
.custom-scrollbar {
    scrollbar-color: #334155 #020617;
    scrollbar-width: thin;
}

iframe::-webkit-scrollbar { display: none; }
</style>