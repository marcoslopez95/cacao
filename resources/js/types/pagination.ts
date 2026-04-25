export interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    path: string
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface PaginationLinks {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
}
