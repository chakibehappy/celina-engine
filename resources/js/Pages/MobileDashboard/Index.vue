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

        <div v-if="viewMode === 'lab'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <section class="space-y-4">
                <h2 class="text-xl font-bold border-b border-gray-700 pb-2 text-purple-400">Quick Navigation Edit</h2>
                <div v-for="nav in navigations" :key="nav.id" class="bg-gray-800 p-4 rounded-xl border border-gray-700">
                    <div class="flex gap-4">
                        <input v-model="nav.label" @change="saveNav(nav)" class="flex-1 bg-gray-900 border-gray-700 rounded p-2 text-sm">
                        <select v-model="nav.screen_id" @change="saveNav(nav)" class="flex-1 bg-gray-900 border-gray-700 rounded p-2 text-sm">
                            <option v-for="s in screens" :value="s.id">{{ s.title }}</option>
                        </select>
                        <button @click="deleteNav(nav.id)" class="text-gray-500 hover:text-red-500">✕</button>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-xl font-bold border-b border-gray-700 pb-2 text-pink-400">Raw Screen Data</h2>
                
                <div v-for="screen in filteredScreens" :key="screen.id" class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-3">
                            <input v-model="screen.title" @change="saveScreen(screen)" class="bg-transparent border-none font-bold text-lg focus:ring-0 p-0 text-white">
                            <span :class="screen.type === 'custom' ? 'bg-blue-900/30 text-blue-400 border-blue-800/50' : 'bg-yellow-900/30 text-yellow-400 border-yellow-800/50'" 
                                  class="text-[9px] px-2 py-0.5 rounded border uppercase tracking-widest font-bold">
                                {{ screen.type }}
                            </span>
                        </div>
                        <span class="text-[10px] bg-pink-900/30 text-pink-400 px-2 py-1 rounded border border-pink-800/50 uppercase tracking-widest">
                            {{ screen.type === 'custom' ? 'Live Preview' : 'Tree Viewer' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] text-gray-500 font-mono">
                                {{ screen.type === 'dynamic' ? 'JSON CONFIG' : 'HTML CONTENT' }}
                            </label>
                            <textarea 
                                v-model="screen.content_data" 
                                class="w-full h-[580px] font-mono text-[11px] bg-black text-green-500 p-4 rounded-lg border border-gray-700 custom-scrollbar focus:border-purple-500 transition-colors resize-none" 
                                @change="saveScreen(screen)"
                                :placeholder="screen.type === 'dynamic' ? '// JSON format required...' : 'Enter HTML structure...'"
                            ></textarea>
                        </div>

                        <div class="flex flex-col items-center">
                            <label class="text-[10px] text-gray-500 font-mono mb-2 self-start uppercase">Preview Output</label>
                            
                            <div v-if="screen.type === 'custom'" class="relative w-[280px] h-[580px] bg-black rounded-[3rem] border-[8px] border-gray-700 shadow-2xl overflow-hidden ring-4 ring-gray-900">
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-24 h-6 bg-gray-700 rounded-b-2xl z-10"></div>
                                <iframe 
                                    :srcdoc="screen.content_data"
                                    class="w-full h-full bg-white border-none"
                                    loading="lazy"
                                ></iframe>
                                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-20 h-1 bg-gray-400/50 rounded-full"></div>
                            </div>

                            <div v-else class="w-full h-[580px] bg-gray-950 border border-gray-700 rounded-lg p-6 overflow-auto custom-scrollbar font-mono text-xs">
                                <div v-if="getParsedJson(screen.content_data)">
                                    <ul class="space-y-1">
                                        <TreeItem :item="getParsedJson(screen.content_data)" name="root" />
                                    </ul>
                                </div>
                                <div v-else class="text-red-500 italic opacity-50">// Awaiting valid JSON...</div>
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

        <div v-if="showDataModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-gray-800 p-8 rounded-2xl w-full max-w-md border border-gray-600 shadow-2xl">
                <h3 class="text-xl font-bold mb-6 text-purple-400">
                    {{ isEditing ? 'Edit' : 'Create' }} {{ modalType }}
                </h3>
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="field in schemas[modalType]" :key="field.key">
                        <template v-if="field.key !== 'id' || (!isEditing && modalType === 'system_icon')">
                            <label class="text-[10px] text-gray-500 block mb-1 uppercase font-bold">{{ field.label }}</label>
                            <input v-model="formData[field.key]" :type="field.type" class="w-full bg-gray-900 p-3 rounded border border-gray-700 text-white">
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-4 mt-8 pt-4 border-t border-gray-700">
                    <button @click="showDataModal = false" class="text-gray-400 text-sm">Cancel</button>
                    <button @click="saveData" class="bg-purple-600 hover:bg-purple-500 px-8 py-2 rounded-lg font-bold">Commit</button>
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

// Tree Item Component (The "jsonviewer" logic)
const TreeItem = defineComponent({
    name: 'TreeItem',
    props: ['item', 'name'],
    setup(props) {
        const isOpen = ref(true);
        const isObject = computed(() => typeof props.item === 'object' && props.item !== null);
        const isArray = computed(() => Array.isArray(props.item));

        return () => h('li', { class: 'list-none' }, [
            h('div', { class: 'flex items-center gap-1 py-0.5' }, [
                isObject.value ? h('button', { 
                    onClick: (e) => { e.stopPropagation(); isOpen.value = !isOpen.value; },
                    class: 'text-[9px] w-4 text-gray-500 hover:text-white transition'
                }, isOpen.value ? '▼' : '▶') : h('span', { class: 'w-4' }),
                
                h('span', { class: 'text-purple-400' }, props.name + ':'),
                
                !isObject.value ? h('span', { class: 'text-green-500 ml-1' }, 
                    typeof props.item === 'string' ? `"${props.item}"` : String(props.item)
                ) : h('span', { class: 'text-gray-600 text-[10px] ml-1' }, isArray.value ? `Array[${props.item.length}]` : '{ }')
            ]),
            
            (isObject.value && isOpen.value) ? h('ul', { class: 'ml-4 border-l border-gray-800 pl-2' }, 
                Object.entries(props.item).map(([key, value]) => h(TreeItem, { key, name: key, item: value }))
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
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 10px; }
iframe::-webkit-scrollbar { display: none; }
</style>