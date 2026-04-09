<template>
    <div
        class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm"
    >
        <div v-if="title" class="px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-[#1a5c2a]">{{ title }}</p>
        </div>

        <div class="overflow-x-auto">
            <div class="min-w-150">
                <div
                    class="grid gap-4 px-4 py-2 bg-gray-50 border-b border-gray-100"
                    :style="{ gridTemplateColumns: gridTemplate }"
                >
                    <span
                        v-for="col in columns"
                        :key="col.key"
                        class="text-[11px] font-bold text-gray-500 uppercase tracking-tight"
                        :class="col.headerClass"
                    >
                        {{ col.label }}
                    </span>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="grid gap-4 px-4 py-3 items-center hover:bg-gray-50/50 transition-colors"
                        :style="{ gridTemplateColumns: gridTemplate }"
                        @click="$emit('row-click', item)"
                    >
                        <div
                            v-for="col in columns"
                            :key="col.key"
                            class="text-xs text-gray-600 overflow-hidden"
                            :class="col.cellClass"
                        >
                            <slot :name="`cell(${col.key})`" :item="item">
                                <span class="block">{{ item[col.key] }}</span>
                            </slot>
                        </div>
                    </div>
                    <slot name="insertion-row" />
                </div>

                <div
                    v-if="!items?.length"
                    class="px-4 py-8 text-center text-gray-400 text-xs italic"
                >
                    {{ emptyText }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: String,
    items: { type: Array, default: () => [] },
    columns: { type: Array, required: true }, // [{ key, label, width }]
    emptyText: { type: String, default: 'No hay registros disponibles' },
})

// Genera el estilo de grid-template-columns automáticamente
const gridTemplate = computed(() => {
    return props.columns.map(col => col.width || '1fr').join(' ')
})
</script>
