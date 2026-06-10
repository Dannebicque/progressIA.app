<template>
    <div class="prose max-w-none relative" ref="root" v-html="html"></div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { marked } from 'marked'
import hljs from 'highlight.js'
import 'highlight.js/styles/github.css'

const props = defineProps<{ source: string }>()
const html = ref('')
const root = ref<HTMLElement | null>(null)

marked.setOptions({
    gfm: true,
    breaks: false,
    smartypants: true,
    highlight: function (code, lang) {
        try {
            if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(code, { language: lang }).value
            }
            return hljs.highlightAuto(code).value
        } catch (e) {
            return code
        }
    }
})

function enhanceCodeBlocks() {
    if (!root.value) return
    const codes = Array.from(root.value.querySelectorAll('pre > code')) as HTMLElement[]
    codes.forEach((codeEl) => {
        const pre = codeEl.parentElement as HTMLElement
        if (!pre) return
        // avoid adding button twice
        if (pre.querySelector('.pf-copy-btn')) return
        const btn = document.createElement('button')
        btn.className = 'pf-copy-btn absolute right-3 top-3 text-xs bg-white/90 border px-2 py-1 rounded shadow'
        btn.textContent = 'Copier'
        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(codeEl.innerText)
                btn.textContent = 'Copié'
                setTimeout(() => (btn.textContent = 'Copier'), 1500)
            } catch (e) {
                btn.textContent = 'Erreur'
                setTimeout(() => (btn.textContent = 'Copier'), 1500)
            }
        })
        pre.style.position = 'relative'
        pre.appendChild(btn)
    })
}

watch(() => props.source, async (v) => {
    try {
        html.value = marked.parse(v || '')
        await nextTick()
        // highlight.js already applied via marked.highlight option,
        // but run highlightAll for safety
        try { hljs.highlightAll() } catch (e) { }
        enhanceCodeBlocks()
    } catch (e) {
        html.value = '<pre>' + String(v) + '</pre>'
    }
}, { immediate: true })
</script>

<style scoped>
.pf-copy-btn {
    font-weight: 600;
}

pre {
    overflow: auto;
    border-radius: 0.5rem;
    padding: 1rem;
}

pre code {
    white-space: pre;
}

/* ensure long lines wrap visually on small screens */
@media (max-width: 640px) {
    pre code {
        white-space: pre-wrap;
        word-break: break-word;
    }
}
</style>
