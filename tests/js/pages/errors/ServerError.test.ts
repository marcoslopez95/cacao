import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ServerError from '@/pages/errors/ServerError.vue'

beforeEach(() => {
    sessionStorage.clear()
})

describe('ServerError', () => {
    it('generates incident id on mount', async () => {
        const wrapper = mount(ServerError)
        await flushPromises()
        const text = wrapper.text()
        expect(text).toMatch(/CAC-[0-9A-F]{6}/)
    })

    it('reuses incident id from sessionStorage', async () => {
        sessionStorage.setItem('ep_incident', 'CAC-AABBCC')
        const wrapper = mount(ServerError)
        await flushPromises()
        expect(wrapper.text()).toContain('CAC-AABBCC')
    })

    it('stores generated id in sessionStorage', async () => {
        mount(ServerError)
        await flushPromises()
        expect(sessionStorage.getItem('ep_incident')).toMatch(/CAC-[0-9A-F]{6}/)
    })

    it('copy button copies incident id to clipboard', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined)
        vi.stubGlobal('navigator', { clipboard: { writeText } })

        sessionStorage.setItem('ep_incident', 'CAC-123456')
        const wrapper = mount(ServerError)
        await flushPromises()

        const copyBtn = wrapper.find('.ep-copy-btn')
        await copyBtn.trigger('click')
        await flushPromises()

        expect(writeText).toHaveBeenCalledWith('CAC-123456')
    })

    it('copy button label changes to Copiado after click', async () => {
        vi.stubGlobal('navigator', { clipboard: { writeText: vi.fn().mockResolvedValue(undefined) } })

        const wrapper = mount(ServerError)
        await flushPromises()

        const copyBtn = wrapper.find('.ep-copy-btn')
        expect(copyBtn.text()).toBe('Copiar')

        await copyBtn.trigger('click')
        await flushPromises()

        expect(copyBtn.text()).toBe('Copiado')
    })

    it('renders 500 copy', async () => {
        const wrapper = mount(ServerError)
        await flushPromises()
        expect(wrapper.text()).toContain('rompió')
        expect(wrapper.text()).toContain('Reintentar')
        expect(wrapper.text()).toContain('500')
    })
})
