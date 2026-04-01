// src/composables/useDataTable.js
import { ref, reactive } from 'vue'

export function useDataTable(fetchFn, options = {}) {
    const {
        debounce = 600,
        initialPerPage = 10,
        initialSortKey = '',
        initialSortDir = 'desc',
    } = options

    const search = ref('')
    const pagination = reactive({
        currentPage: 1,
        perPage: initialPerPage,
        total: 0,
    })
    const sort = reactive({
        key: initialSortKey,
        dir: initialSortDir,
    })

    let searchTimeout = null

    function onPageChange(page) {
        pagination.currentPage = page
        fetchFn()
    }

    function onPerPageChange(val) {
        pagination.perPage = val
        pagination.currentPage = 1
        fetchFn()
    }

    function onSearch(val) {
        search.value = val
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            pagination.currentPage = 1
            fetchFn()
        }, debounce)
    }

    function onSort({ key, dir }) {
        sort.key = key
        sort.dir = dir
        pagination.currentPage = 1
        fetchFn()
    }

    return {
        search,
        pagination,
        sort,
        onPageChange,
        onPerPageChange,
        onSearch,
        onSort,
    }
}
