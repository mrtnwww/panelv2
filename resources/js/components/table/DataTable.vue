<template>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col">
        <!-- Controles superiores -->
        <div
            class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 border-b border-gray-100"
        >
            <!-- Fila 1 móvil: mostrar + acciones -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-gray-400">Mostrar</span>
                <select
                    :value="perPage"
                    @change="
                        $emit('update:perPage', Number($event.target.value))
                    "
                    class="h-8 px-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] transition-all cursor-pointer"
                >
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
                <span class="text-xs text-gray-400">registros</span>

                <!-- Slot acciones extra -->
                <div class="flex items-center gap-2 flex-wrap">
                    <slot name="actions" />
                </div>
            </div>

            <!-- Búsqueda — ancho completo en móvil, automático en desktop -->
            <div class="relative sm:ml-auto">
                <input
                    :value="search"
                    @input="$emit('update:search', $event.target.value)"
                    type="text"
                    placeholder="Buscar..."
                    class="h-8 w-full sm:w-44 pl-8 pr-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 outline-none focus:border-[#1a5c2a] focus:ring-2 focus:ring-[#1a5c2a]/10 transition-all sm:focus:w-56"
                />
                <span
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"
                >
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                        <circle
                            cx="5.5"
                            cy="5.5"
                            r="4"
                            stroke="currentColor"
                            stroke-width="1.3"
                        />
                        <path
                            d="M10 10L8.5 8.5"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <!-- Checkbox seleccionar todos -->
                        <th v-if="selectable" class="w-10 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="
                                    $emit('toggle-all', $event.target.checked)
                                "
                                class="w-4 h-4 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                            />
                        </th>

                        <th
                            v-for="col in columns"
                            :key="col.key"
                            @click="col.sortable && onSort(col.key)"
                            :class="[
                                'px-3 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide whitespace-nowrap',
                                col.sortable &&
                                    'cursor-pointer hover:text-gray-600 select-none',
                                col.align === 'center' && 'text-center',
                                col.align === 'right' && 'text-right',
                            ]"
                        >
                            <span
                                class="inline-flex items-center gap-1"
                                :class="{
                                    'justify-center': col.align === 'center',
                                    'justify-end': col.align === 'right',
                                }"
                            >
                                {{ col.label }}
                                <span
                                    v-if="col.sortable"
                                    class="inline-flex flex-col gap-px"
                                >
                                    <svg
                                        width="6"
                                        height="4"
                                        viewBox="0 0 6 4"
                                        fill="none"
                                        :class="
                                            sortKey === col.key &&
                                            sortDir === 'asc'
                                                ? 'text-[#1a5c2a]'
                                                : 'text-gray-300'
                                        "
                                    >
                                        <path
                                            d="M3 0L6 4H0L3 0Z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                    <svg
                                        width="6"
                                        height="4"
                                        viewBox="0 0 6 4"
                                        fill="none"
                                        :class="
                                            sortKey === col.key &&
                                            sortDir === 'desc'
                                                ? 'text-[#1a5c2a]'
                                                : 'text-gray-300'
                                        "
                                    >
                                        <path
                                            d="M3 4L0 0H6L3 4Z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Loading -->
                    <tr v-if="loading">
                        <td :colspan="totalCols" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg
                                    class="animate-spin w-6 h-6 text-[#1a5c2a]"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>
                                <span class="text-xs text-gray-400"
                                    >Cargando registros...</span
                                >
                            </div>
                        </td>
                    </tr>

                    <!-- Sin resultados -->
                    <tr v-else-if="!loading && rows.length === 0">
                        <td :colspan="totalCols" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg
                                    width="32"
                                    height="32"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="text-gray-200"
                                >
                                    <circle
                                        cx="11"
                                        cy="11"
                                        r="8"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M21 21l-4.35-4.35"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M8 11h6M11 8v6"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <span class="text-sm text-gray-400">{{
                                    emptyMessage
                                }}</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Filas -->
                    <tr
                        v-else
                        v-for="(row, rowIndex) in rows"
                        :key="row[rowKey] ?? rowIndex"
                        class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors group"
                        :class="{ 'cursor-pointer': clickable }"
                        @click="clickable && $emit('row-click', row)"
                    >
                        <!-- Checkbox fila -->
                        <td v-if="selectable" class="px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="selectedRows.includes(row[rowKey])"
                                @change="$emit('toggle-row', row[rowKey])"
                                @click.stop
                                class="w-4 h-4 rounded border-gray-300 accent-[#1a5c2a] cursor-pointer"
                            />
                        </td>

                        <!-- Celdas -->
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-3 py-3',
                                col.align === 'center' && 'text-center',
                                col.align === 'right' && 'text-right',
                                col.nowrap !== false && 'whitespace-nowrap',
                                col.truncate && 'max-w-[200px] truncate',
                            ]"
                        >
                            <!-- Slot personalizado por columna: #cell-{key} -->
                            <slot
                                :name="`cell-${col.key}`"
                                :row="row"
                                :value="row[col.key]"
                            >
                                <!-- Render por tipo -->
                                <template v-if="col.type === 'boolean'">
                                    <span
                                        v-if="row[col.key]"
                                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-50"
                                    >
                                        <svg
                                            width="10"
                                            height="10"
                                            viewBox="0 0 10 10"
                                            fill="none"
                                        >
                                            <path
                                                d="M1.5 5L3.5 7.5L8.5 2"
                                                stroke="#1a5c2a"
                                                stroke-width="1.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50"
                                    >
                                        <svg
                                            width="10"
                                            height="10"
                                            viewBox="0 0 10 10"
                                            fill="none"
                                        >
                                            <path
                                                d="M2 2L8 8M8 2L2 8"
                                                stroke="#ef4444"
                                                stroke-width="1.5"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                    </span>
                                </template>

                                <template v-else-if="col.type === 'badge'">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="
                                            col.badgeClass?.(row[col.key]) ??
                                            'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            col.format
                                                ? col.format(row[col.key])
                                                : row[col.key]
                                        }}
                                    </span>
                                </template>

                                <template v-else>
                                    <span :class="col.class?.(row) ?? ''">
                                        {{
                                            col.format
                                                ? col.format(row[col.key], row)
                                                : row[col.key]
                                        }}
                                    </span>
                                </template>
                            </slot>
                        </td>
                    </tr>
                    <!-- Slot footer (totales, subtotales, etc.) -->
                    <slot name="footer" />
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div
            class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 border-t border-gray-100"
        >
            <!-- Contador -->
            <p class="text-xs text-gray-400 text-center sm:text-left">
                <template v-if="total > 0">
                    Mostrando {{ from }} a {{ to }} de {{ total }} registros
                </template>
                <template v-else>Sin registros</template>
            </p>

            <!-- Botones paginación -->
            <div
                class="flex items-center justify-center sm:justify-end gap-1 sm:ml-auto flex-wrap"
            >
                <!-- Anterior -->
                <button
                    :disabled="currentPage === 1"
                    @click="$emit('update:currentPage', currentPage - 1)"
                    :class="paginationBtnClass(currentPage === 1)"
                >
                    Anterior
                </button>

                <!-- Páginas — ocultas en móvil, visibles desde sm -->
                <template v-for="page in visiblePages" :key="page">
                    <span
                        v-if="page === '...'"
                        class="hidden sm:inline px-1 text-xs text-gray-300"
                        >...</span
                    >
                    <button
                        v-else
                        @click="$emit('update:currentPage', page)"
                        :class="[
                            'w-8 h-8 rounded-lg text-xs font-medium transition-all',
                            currentPage === page
                                ? 'bg-[#1a5c2a] text-white'
                                : 'hidden sm:flex items-center justify-center text-gray-500 hover:bg-gray-100',
                        ]"
                    >
                        {{ page }}
                    </button>
                </template>

                <!-- Siguiente -->
                <button
                    :disabled="currentPage === totalPages"
                    @click="$emit('update:currentPage', currentPage + 1)"
                    :class="paginationBtnClass(currentPage === totalPages)"
                >
                    Siguiente
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // Datos
    rows: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    rowKey: { type: String, default: 'id' },
    loading: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'No se encontraron registros.' },
    clickable: { type: Boolean, default: false },

    // Selección
    selectable: { type: Boolean, default: false },
    selectedRows: { type: Array, default: () => [] },
    allSelected: { type: Boolean, default: false },

    // Búsqueda y ordenamiento (controlados por el padre)
    search: { type: String, default: '' },
    sortKey: { type: String, default: '' },
    sortDir: { type: String, default: 'asc' },

    // Paginación (controlada por el padre / backend)
    currentPage: { type: Number, default: 1 },
    perPage: { type: Number, default: 10 },
    total: { type: Number, default: 0 },
});

const emit = defineEmits([
    'update:search',
    'update:currentPage',
    'update:perPage',
    'update:sortKey',
    'update:sortDir',
    'sort',
    'toggle-all',
    'toggle-row',
    'row-click',
]);

// ── Columnas totales (para colspan) ───────────────────────────────────────
const totalCols = computed(
    () => props.columns.length + (props.selectable ? 1 : 0)
);

// ── Paginación ─────────────────────────────────────────────────────────────
const totalPages = computed(() =>
    Math.max(1, Math.ceil(props.total / props.perPage))
);

const from = computed(() =>
    props.total === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1
);

const to = computed(() =>
    Math.min(props.currentPage * props.perPage, props.total)
);

const visiblePages = computed(() => {
    const total = totalPages.value;
    const cur = props.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (cur <= 4) return [1, 2, 3, 4, 5, '...', total];
    if (cur >= total - 3)
        return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
    return [1, '...', cur - 1, cur, cur + 1, '...', total];
});

// ── Ordenamiento ───────────────────────────────────────────────────────────
function onSort(key) {
    const newDir =
        props.sortKey === key && props.sortDir === 'asc' ? 'desc' : 'asc';
    emit('update:sortKey', key);
    emit('update:sortDir', newDir);
    emit('sort', { key, dir: newDir });
}

// ── Helper clases paginación ───────────────────────────────────────────────
function paginationBtnClass(disabled) {
    return [
        'h-8 px-3 rounded-lg text-xs font-medium transition-all border',
        disabled
            ? 'border-gray-100 text-gray-300 cursor-not-allowed'
            : 'border-gray-200 text-gray-500 hover:bg-gray-50 hover:border-gray-300',
    ];
}
</script>
