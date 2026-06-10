<template>
    <div :style="{ borderTop: '6px solid ' + (course.accentColor || '#7c3aed') }"
        class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-2xl transition transform hover:-translate-y-1">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-lg flex items-center justify-center text-indigo-700 font-bold"
                :style="{ background: `linear-gradient(135deg, ${course.accentColor}22, ${course.accentColor}55)` }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-700" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 20l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 12l9-5-9-5-9 5 9 5z" />
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-lg">{{ course.title }}</h3>
                        <p class="text-sm text-gray-500">{{ course.theme }} · {{ course.context }}</p>
                    </div>
                    <div class="ml-4">
                        <span :style="{ backgroundColor: course.accentColor, color: 'white' }"
                            class="inline-block text-xs px-3 py-1 rounded-full shadow-sm">{{ course.level ||
                                'Intermédiaire' }}</span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-700 line-clamp-3">{{ course.scenario }}</p>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8c-3.314 0-6 2.686-6 6s2.686 6 6 6 6-2.686 6-6-2.686-6-6-6z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2v6"></path>
                    </svg>
                    <span>{{ course.sessions.length }} séances</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M11 0L9 6H3l5 4-2 6 5-4 5 4-2-6 5-4h-6L11 0z" />
                    </svg>
                    <span>{{ course.sessions.length * 20 }} pts</span>
                </div>
            </div>
            <router-link :to="`/course/${course.id}`"
                class="inline-flex items-center gap-2 bg-[var(--accent,#7c3aed)] text-white px-4 py-2 rounded-full font-medium cta-anim"
                :style="{ background: `linear-gradient(90deg, ${course.accentColor} 0%, ${course.accentColor}bb 100%)` }">Commencer
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 5l7 7-7 7" />
                </svg>
            </router-link>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{ course: any }>()
</script>

<style scoped></style>
