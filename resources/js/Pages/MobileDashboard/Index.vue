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
const filteredScreens = computed(() => props.screens?.filter(s => s.type === 'custom' || s.type === 'dynamic') || []);

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

const saveNav = (nav) => router.put(route('test-dashboard.nav.update', { id: nav.id }), nav, {
    preserveScroll: true,
    preserveState: true
});

const saveScreen = (screen) => router.put(route('test-dashboard.screen.update', { id: screen.id }), screen, {
    preserveScroll: true,
    preserveState: true
});

const deleteData = (type, id) => {
    if(confirm(`Permanent delete ${type} #${id}?`)) router.delete(route('test-dashboard.delete', { type, id }));
};
const deleteNav = (id) => deleteData('nav', id);
</script>

<style>
.bg-gray-850 { background-color: #161b22; }

textarea, pre {
    line-height: 20px !important;
    font-family: 'JetBrains Mono', 'Fira Code', monospace !important;
    tab-size: 4;
}

pre {
    white-space: pre !important; 
    word-wrap: normal !important;
}

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