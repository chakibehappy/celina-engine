<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import DatabaseCreator from './DatabaseCreator.vue';
import DashboardForge from './DashboardForge.vue';

const currentView = ref('db');
const toolsOpen = ref(false);
const fileOpen = ref(false);
const showModal = ref(false);
const fileMenu = ref(null);
const toolsMenu = ref(null);
const filePicker = ref(null);
const activeProjectName = ref(''); 

// Initialize with your requested default string
const projectKey = ref('new-celina-project');
const pageKey = ref('');

const newProject = ref({ 
    name: '', 
    full_path: 'C:\\Celina Engine Projects' 
});


const fetchActiveSession = async () => {
    try {
        const response = await axios.get('/get-active-project');
        if (response.data && response.data.project_id) {
            activeProjectName.value = response.data.project_id.replace(/-/g, ' ');
            projectKey.value = response.data.project_id;
        }
    } catch (e) {
        console.warn("No active project session found.");
    }
};

const openNewProjectModal = () => {
    showModal.value = false;
    fileOpen.value = false;
    showModal.value = true;
};

const triggerFilePicker = () => {
    fileOpen.value = false;
    filePicker.value.click();
};

const handleOpenFile = async (event) => {
    const file = event.target.files[0];
    if (file) {
        const projectId = file.name.replace('.celina', '');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            await axios.post('/set-active-project', { project_id: projectId }, {
                headers: { 'X-CSRF-TOKEN': token }
            });

            activeProjectName.value = projectId.replace(/-/g, ' ');
            projectKey.value = `${projectId}}`; 
            pageKey.value = `${projectId}-${Date.now()}`;
            console.log("Session switched to:", projectId);
        } catch (error) {
            console.error("Failed to switch project session:", error);
            alert("Could not open project session.");
        }
    }
};

const saveProject = async () => {
    const generated_id = newProject.value.name.toLowerCase().replace(/\s+/g, '-');
    
    const manifest = {
        project_id: generated_id,
        version: "1.0.0",
        path: "..generated/",
        full_path: newProject.value.full_path,
        settings: { 
            theme: "dark-industrial", 
            primary_color: "#ff4400", 
            deployment: ["web", "mobile_api"] 
        },
        database: [], 
        modules: [] 
    };

    const payload = {
        project_id: generated_id,
        name: newProject.value.name,
        full_path: newProject.value.full_path,
        data: {
            manifest: manifest,
            ui_state: {  nodes: [],  connections: [],  viewport: { x: 0, y: 0, scale: 1 }}
        }
    };

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const response = await axios.post('/create-project', payload, {
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.status === 200) {
            activeProjectName.value = newProject.value.name; 
            projectKey.value = generated_id;
            pageKey.value = `${generated_id}-${Date.now()}`;            
            showModal.value = false;
        }
    } catch (error) {
        if (error.response && error.response.status === 419) {
            alert("Session expired. Please refresh the page.");
        } else {
            console.error("Save failed:", error);
            alert("Error: " + (error.response?.data?.message || "Internal Server Error"));
        }
    }
};

const handleClickOutside = (event) => {
    if (toolsMenu.value && !toolsMenu.value.contains(event.target)) toolsOpen.value = false;
    if (fileMenu.value && !fileMenu.value.contains(event.target)) fileOpen.value = false;
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    fetchActiveSession(); 
});

onUnmounted(() => window.removeEventListener('click', handleClickOutside));

function setView(view) {
    currentView.value = view;
    toolsOpen.value = false;
}
</script>

<template>
    <div class="flex flex-col h-screen bg-[#0d0f14] text-gray-300 font-sans overflow-hidden">
        <header class="flex items-center justify-between px-4 py-5 h-12 bg-[#1a1d23] border-b border-gray-800 shadow-md">
            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-2">
                    <div class="bg-blue-600 p-1 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="font-bold text-white tracking-tight text-sm uppercase">Celina <span class="text-blue-500 text-[10px]">Engine</span></span>
                </div>

                <nav class="flex items-center space-x-5 text-xs">
                    <div class="relative" ref="fileMenu">
                        <div @click="fileOpen = !fileOpen" class="cursor-pointer hover:text-white transition-colors" :class="{'text-blue-400': fileOpen}">File</div>
                        <div v-if="fileOpen" class="absolute left-0 mt-2 w-48 bg-[#1a1d23] border border-gray-700 shadow-2xl z-50 py-1 rounded-sm">
                            <div @click="openNewProjectModal" class="px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer">New Project</div>
                            <div @click="triggerFilePicker" class="px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer border-t border-gray-800">Open Project</div>
                            <input type="file" ref="filePicker" class="hidden" accept=".celina" @change="handleOpenFile" />
                        </div>
                    </div>

                    <div class="cursor-pointer hover:text-white transition-colors">Settings</div>

                    <div class="relative" ref="toolsMenu">
                        <div @click="toolsOpen = !toolsOpen" class="flex items-center space-x-1 cursor-pointer hover:text-white transition-colors tracking-wider font-semibold" :class="{'text-blue-400': toolsOpen}">
                            Tools
                        </div>
                        <div v-if="toolsOpen" class="absolute left-0 mt-2 w-48 bg-[#1a1d23] border border-gray-700 shadow-2xl z-50 py-1 rounded-sm">
                            <div @click="setView('db')" class="px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer flex items-center justify-between" :class="{'text-blue-400': currentView === 'db'}">
                                <span>Database Creator</span>
                            </div>
                            <div @click="setView('dashboard')" class="px-4 py-2 hover:bg-blue-600 hover:text-white cursor-pointer flex items-center justify-between" :class="{'text-blue-400': currentView === 'dashboard'}">
                                <span>Dashboard Forge</span>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>

            <div class="flex items-center space-x-3">
                <div class="text-[10px] text-gray-500 mr-4 border-r border-gray-700 pr-4 uppercase">
                    Project: <span class="text-blue-400 font-mono font-bold">{{ activeProjectName || 'None' }}</span>
                </div>
                <button class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded text-xs font-bold transition-all shadow-lg active:scale-95 uppercase">
                    <span>Build Project</span>
                </button>
            </div>
        </header>

        <div v-if="showModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div class="bg-[#1a1d23] border border-gray-700 w-full max-w-md p-6 rounded shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4 uppercase tracking-widest">Create New Project</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] text-gray-500 uppercase block mb-1">Project Name</label>
                        <input v-model="newProject.name" type="text" class="w-full bg-[#0d0f14] border border-gray-700 rounded px-3 py-2 text-sm focus:border-blue-500 outline-none" placeholder="My Awesome Project" />
                    </div>

                    <div>
                        <label class="text-[10px] text-gray-500 uppercase block mb-1">Project Full Path</label>
                        <input v-model="newProject.full_path" type="text" class="w-full bg-[#0d0f14] border border-gray-700 rounded px-3 py-2 text-sm focus:border-blue-500 outline-none font-mono text-xs" placeholder="C:\KITXEL\generated" />
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="showModal = false" class="text-xs uppercase hover:text-white">Cancel</button>
                    <button @click="saveProject" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded text-xs font-bold uppercase">Save Project</button>
                </div>
            </div>
        </div>

        <main class="flex-grow overflow-hidden relative">
            <div v-if="currentView === 'db'" class="h-full">
                <DatabaseCreator :projectKey="projectKey" :key="pageKey" />
            </div>
            <div v-if="currentView === 'dashboard'" class="h-full">
                <DashboardForge :projectKey="projectKey" :key="pageKey" />
            </div>
        </main>
    </div>
</template>