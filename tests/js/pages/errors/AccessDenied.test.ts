import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AccessDenied from '@/pages/errors/AccessDenied.vue'

describe('AccessDenied', () => {
    it('renders 401 copy when status is 401', () => {
        const wrapper = mount(AccessDenied, { props: { status: 401 } })
        expect(wrapper.text()).toContain('bajo llave')
        expect(wrapper.text()).toContain('Iniciar sesión')
        expect(wrapper.text()).toContain('401')
    })

    it('renders 403 copy when status is 403', () => {
        const wrapper = mount(AccessDenied, { props: { status: 403 } })
        expect(wrapper.text()).toContain('no puedes pasar')
        expect(wrapper.text()).toContain('Contactar admin')
        expect(wrapper.text()).toContain('403')
    })

    it('401 primary CTA links to /login', () => {
        const wrapper = mount(AccessDenied, { props: { status: 401 } })
        const primaryBtn = wrapper.findAll('a').find((a) => a.text().includes('Iniciar sesión'))
        expect(primaryBtn?.attributes('href')).toBe('/login')
    })

    it('403 primary CTA links to admin email', () => {
        const wrapper = mount(AccessDenied, { props: { status: 403 } })
        const primaryBtn = wrapper.findAll('a').find((a) => a.text().includes('Contactar admin'))
        expect(primaryBtn?.attributes('href')).toContain('mailto:')
    })

    it('both statuses have a home link', () => {
        for (const status of [401, 403] as const) {
            const wrapper = mount(AccessDenied, { props: { status } })
            const homeLink = wrapper.findAll('a').find((a) => a.text().includes('Ir al inicio'))
            expect(homeLink?.attributes('href')).toBe('/')
        }
    })
})
