<template>
    <div :class="wrapperClass">
        <!-- Label -->
        <label
            v-if="label"
            class="block text-xs font-medium text-gray-500 uppercase mb-1"
        >
            {{ label }}
            <span v-if="required" class="text-red-400 ml-0.5">*</span>
        </label>

        <div class="relative">
            <!-- Trigger -->
            <div
                :id="`select-trigger-${uid}`"
                :class="[
                    fieldClass,
                    'flex items-center justify-between cursor-pointer pr-8 select-none',
                    disabled && 'opacity-50 pointer-events-none',
                ]"
                @click="toggleDropdown"
            >
                <span
                    :class="[
                        selectedLabel ? 'text-gray-700' : 'text-gray-300',
                        'truncate text-sm',
                    ]"
                >
                    {{ selectedLabel || placeholder || 'Seleccione...' }}
                </span>

                <!-- Limpiar selección -->
                <button
                    v-if="modelValue && clearable"
                    class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors"
                    @click.stop="clearSelection"
                >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path
                            d="M2 2L10 10M10 2L2 10"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>
            </div>

            <!-- Chevron -->
            <ChevronIcon :dropdownOpen="dropdownOpen" />

            <!-- Dropdown -->
            <Teleport to="body">
                <div
                    v-if="dropdownOpen"
                    :data-dropdown="uid"
                    :style="dropdownStyle"
                    class="fixed z-9999 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                >
                    <!-- Búsqueda -->
                    <div class="p-2 border-b border-gray-100">
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar..."
                            class="w-full h-8 px-3 text-sm rounded-md border border-gray-200 bg-gray-50 outline-none focus:border-[#1a5c2a] focus:ring-1 focus:ring-[#1a5c2a]/10"
                            @click.stop
                        />
                    </div>

                    <!-- Opciones -->
                    <ul class="max-h-52 overflow-y-auto">
                        <!-- Loading -->
                        <li
                            v-if="loading"
                            class="px-3 py-5 flex justify-center"
                        >
                            <svg
                                class="animate-spin w-4 h-4 text-[#1a5c2a]"
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
                        </li>

                        <template v-else>
                            <!-- Opción vacía -->
                            <li
                                class="px-3 py-2 text-sm text-gray-300 cursor-pointer hover:bg-gray-50"
                                @click="selectOption(null, '')"
                            >
                                {{ placeholder || 'Seleccione...' }}
                            </li>

                            <!-- Resultados -->
                            <li
                                v-for="opt in options"
                                :key="opt.value"
                                class="px-3 py-2 text-sm text-gray-600 uppercase cursor-pointer hover:bg-gray-50 transition-colors whitespace-pre-line"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 font-medium':
                                        opt.value == modelValue,
                                }"
                                @click="selectOption(opt.value, opt.label)"
                            >
                                {{ opt.label }}
                            </li>

                            <!-- Sin resultados -->
                            <li
                                v-if="options.length === 0 && searchQuery"
                                class="px-3 py-3 text-sm text-gray-300 text-center"
                            >
                                Sin resultados para "{{ searchQuery }}"
                            </li>
                        </template>
                    </ul>
                </div>
            </Teleport>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'

// -- Componentes -----------------------------------------------------
import ChevronIcon from '@/components/form/ChevronIcon.vue'

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    fetchOptions: { type: Function, required: true }, // (query: string) => Promise<{value, label}[]>
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Seleccione...' },
    wrapperClass: { type: String, default: '' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
    debounce: { type: Number, default: 350 },
    size: { type: String, default: 'md' }, // sm | md
    initialOption: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

// -- Estado -----------------------------------------------------------
const dropdownOpen = ref(false)
const searchQuery = ref('')
const options = ref([])
const loading = ref(false)
const selectedLabel = ref('')
const searchInputRef = ref(null)
const dropdownStyle = ref({})
let fetchTimeout = null
let skipNextFetch = false

// -- Estilos del field -------------------------------------------------
const fieldClass = computed(() => {
    const base =
        'w-full rounded-lg border bg-gray-50 text-gray-700 outline-none transition-all'
    const sizes = { sm: 'h-8 px-2.5 text-xs', md: 'h-10 px-3 text-sm' }
    const border = dropdownOpen.value
        ? 'border-[#1a5c2a] ring-1 ring-[#1a5c2a]/10'
        : props.error
          ? 'border-red-300'
          : 'border-gray-200 hover:border-gray-300'
    return [base, sizes[props.size] ?? sizes.md, border]
})

// -- Dropdown posición ----------------------------------------------------
function calcDropdownStyle() {
    const el =
        document.activeElement?.closest('[data-select-trigger]') ??
        document.querySelector('[data-select-open="true"]')
    // Usamos el elemento raíz del componente vía ref en el trigger
    const trigger = document.getElementById(`select-trigger-${uid}`)
    if (!trigger) return

    const rect = trigger.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const openUp = spaceBelow < 220 && rect.top > 220

    dropdownStyle.value = {
        width: `${rect.width}px`,
        left: `${rect.left + window.scrollX}px`,
        ...(openUp
            ? { bottom: `${window.innerHeight - rect.top - window.scrollY}px` }
            : { top: `${rect.bottom + window.scrollY + 4}px` }),
    }
}

// -- UID para identificar el trigger ----------------------------------------
const uid = Math.random().toString(36).slice(2, 8)

// -- Fetch ------------------------------------------------------------------
async function runFetch(query = '') {
    loading.value = true
    try {
        options.value = await props.fetchOptions(query)
    } finally {
        loading.value = false
    }
}

// -- Watchers ----------------------------------------------------------------
watch(searchQuery, val => {
    if (skipNextFetch) {
        skipNextFetch = false
        return
    }

    clearTimeout(fetchTimeout)
    fetchTimeout = setTimeout(() => runFetch(val), props.debounce)
})

watch(dropdownOpen, async open => {
    if (open) {
        // 1. Esperar a que el Teleport renderice el dropdown en el DOM
        await nextTick()

        // 2. Ahora calcular posición (el elemento ya existe)
        calcDropdownStyle()

        // 3. Hacer el fetch inicial
        await runFetch('')

        // 4. Focus al input de búsqueda
        searchInputRef.value?.focus()

        window.addEventListener('click', onClickOutside)
    } else {
        skipNextFetch = true
        searchQuery.value = ''
        window.removeEventListener('click', onClickOutside)
    }
})

watch(
    () => props.modelValue,
    val => {
        if (!val) {
            selectedLabel.value = ''
        }
    }
)

watch(
    () => props.initialOption,
    opt => {
        if (opt) selectedLabel.value = opt
    },
    { immediate: true }
)

// -- Acciones -----------------------------------------------------------------
function toggleDropdown() {
    if (props.disabled) return
    dropdownOpen.value = !dropdownOpen.value
}

function selectOption(value, label) {
    skipNextFetch = true // Evita el fetch al limpiar searchQuery
    emit('update:modelValue', value)
    emit('change', value)
    selectedLabel.value = label
    dropdownOpen.value = false
}

function clearSelection() {
    selectOption(null, '')
}

function onClickOutside(e) {
    const trigger = document.getElementById(`select-trigger-${uid}`)
    const dropdown = document.querySelector(`[data-dropdown="${uid}"]`)
    if (!trigger?.contains(e.target) && !dropdown?.contains(e.target)) {
        dropdownOpen.value = false
    }
}

onBeforeUnmount(() => {
    window.removeEventListener('click', onClickOutside)
    clearTimeout(fetchTimeout)
})
</script>
