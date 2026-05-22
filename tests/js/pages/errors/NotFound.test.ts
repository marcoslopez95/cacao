import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import NotFound from '@/pages/errors/NotFound.vue'

describe('NotFound', () => {
    it('renders 404 copy', () => {
        const wrapper = mount(NotFound)
        expect(wrapper.text()).toContain('no aparece')
        expect(wrapper.text()).toContain('Ir al inicio')
    })

    it('has link to home', () => {
        const wrapper = mount(NotFound)
        const homeLink = wrapper.findAll('a').find((a) => a.text().includes('Ir al inicio'))
        expect(homeLink?.attributes('href')).toBe('/')
    })

    it('renders status pill with 404', () => {
        const wrapper = mount(NotFound)
        expect(wrapper.text()).toContain('404')
        expect(wrapper.text()).toContain('Página no encontrada')
    })

    it('has report broken link button', () => {
        const wrapper = mount(NotFound)
        expect(wrapper.text()).toContain('Reportar enlace roto')
    })
})
