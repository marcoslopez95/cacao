const ACTIVATION_LINE_RATIO = 0.3
const BOTTOM_TOLERANCE_PX = 2

function findScrollParent(el: HTMLElement): HTMLElement | null {
    let node = el.parentElement

    while (node) {
        const { overflowY } = getComputedStyle(node)

        if (overflowY === 'auto' || overflowY === 'scroll') {
            return node
        }

        node = node.parentElement
    }

    return null
}

/**
 * Tracks which form section is "current" while the user scrolls.
 *
 * Active section = the last one whose top has crossed the activation line (30% of the
 * viewport). Unlike an IntersectionObserver, this is evaluated over ALL sections on every
 * scroll, so (a) entries that did not change are never lost and (b) when the scroll reaches
 * the bottom, a short last section — which can never reach the activation line — becomes active.
 *
 * @returns cleanup function that removes the listeners.
 */
export function trackActiveSection(
    sectionNums: number[],
    onChange: (sectionNum: number) => void,
): () => void {
    const els = sectionNums
        .map(n => ({ n, el: document.getElementById(`sec-${n}`) }))
        .filter((s): s is { n: number; el: HTMLElement } => s.el !== null)

    if (!els.length) {
        return () => {}
    }

    const scroller = findScrollParent(els[0].el)
    const target: HTMLElement | Window = scroller ?? window
    let frame = 0

    function evaluate(): void {
        frame = 0

        const viewTop = scroller ? scroller.getBoundingClientRect().top : 0
        const viewHeight = scroller ? scroller.clientHeight : window.innerHeight
        const line = viewTop + viewHeight * ACTIVATION_LINE_RATIO

        const scrollTop = scroller ? scroller.scrollTop : window.scrollY
        const scrollHeight = scroller ? scroller.scrollHeight : document.documentElement.scrollHeight
        const scrollable = scrollHeight > viewHeight + BOTTOM_TOLERANCE_PX
        const atBottom = scrollable && scrollTop + viewHeight >= scrollHeight - BOTTOM_TOLERANCE_PX

        let active = els[0].n

        for (const { n, el } of els) {
            if (el.getBoundingClientRect().top <= line) {
                active = n
            }
        }

        if (atBottom) {
            active = els[els.length - 1].n
        }

        onChange(active)
    }

    function schedule(): void {
        if (!frame) {
            frame = requestAnimationFrame(evaluate)
        }
    }

    target.addEventListener('scroll', schedule, { passive: true })
    window.addEventListener('resize', schedule)
    evaluate()

    return () => {
        target.removeEventListener('scroll', schedule)
        window.removeEventListener('resize', schedule)

        if (frame) {
            cancelAnimationFrame(frame)
        }
    }
}
