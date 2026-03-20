<template>
    <div>
        <button
            @click="
                item.children ? (open = !open) : $emit('navigate', item.path)
            "
            :class="[
                'w-full flex items-center px-3 py-2.5 rounded-lg transition-all text-md',
                collapsed ? 'justify-center gap-0' : 'gap-3',
                isActive
                    ? 'bg-white/15 text-white font-medium'
                    : 'text-white/60 hover:text-white hover:bg-white/10',
            ]"
            :title="collapsed ? item.name : ''"
        >
            <!-- Ícono -->
            <i :class="item.icon"></i>

            <!-- Label -->
            <span
                :class="[
                    'flex-1 text-left whitespace-nowrap transition-all duration-300 overflow-hidden',
                    collapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-xs',
                ]"
            >
                {{ item.name }}
            </span>

            <!-- Chevron si tiene hijos -->
            <svg
                v-if="item.children && !collapsed"
                width="14"
                height="14"
                viewBox="0 0 16 16"
                fill="none"
                :class="[
                    'transition-transform duration-200 shrink-0',
                    open ? 'rotate-180' : '',
                ]"
            >
                <path
                    d="M4 6L8 10L12 6"
                    stroke="currentColor"
                    stroke-width="1.3"
                    stroke-linecap="round"
                />
            </svg>
        </button>

        <!-- Subitems -->
        <div
            v-if="item.children && !collapsed && open"
            class="ml-4 mt-0.5 flex flex-col gap-0.5 border-l border-white/10 pl-3"
        >
            <button
                v-for="child in item.children"
                :key="child.path"
                @click="$emit('navigate', child.path)"
                :class="[
                    'w-full text-left px-2 py-1.5 rounded-md text-sm transition-all',
                    activePath === child.path
                        ? 'text-white font-medium'
                        : 'text-white/45 hover:text-white/80',
                ]"
            >
                {{ child.name }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    activePath: {
        type: String,
        default: '',
    },
})

defineEmits(['navigate'])

const open = ref(false)

const isActive = computed(() => props.activePath.startsWith(props.item.path))
</script>
