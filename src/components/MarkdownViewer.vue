<template>
    <div ref="root" class="prose max-w-none relative" v-html="html"></div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import hljs from 'highlight.js'
import 'highlight.js/styles/github.css'

const props = defineProps<{
    source: string
}>()

const html = ref('')
const root = ref<HTMLElement | null>(null)

marked.setOptions({
    gfm: true,
    breaks: false,
})

async function renderMarkdown(value: string) {
    const raw = await marked.parse(value || '')
    html.value = DOMPurify.sanitize(raw)

    await nextTick()

    if (!root.value) return

    root.value.querySelectorAll('pre code').forEach((block) => {
        hljs.highlightElement(block as HTMLElement)
    })

    enhanceCodeBlocks()
}

function enhanceCodeBlocks() {
    if (!root.value) return

    const codes = Array.from(root.value.querySelectorAll('pre > code')) as HTMLElement[]

    codes.forEach((codeEl) => {
        const pre = codeEl.parentElement as HTMLElement
        if (!pre) return
        if (pre.querySelector('.pf-copy-btn')) return

        const btn = document.createElement('button')
        btn.className = 'pf-copy-btn absolute right-3 top-3 text-xs bg-white/90 border px-2 py-1 rounded shadow'
        btn.textContent = 'Copier'

        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(codeEl.innerText)
                btn.textContent = 'Copié'
                setTimeout(() => (btn.textContent = 'Copier'), 1500)
            } catch {
                btn.textContent = 'Erreur'
                setTimeout(() => (btn.textContent = 'Copier'), 1500)
            }
        })

        pre.style.position = 'relative'
        pre.appendChild(btn)
    })
}

watch(
    () => props.source,
    renderMarkdown,
    { immediate: true }
)
</script>