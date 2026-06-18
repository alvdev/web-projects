import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [tailwindcss()],
    root: 'assets/src',
    base: '/assets/',
    build: {
        outDir: '../../public/assets',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: 'assets/src/js/app.js'
            }
        }
    },
    server: {
        port: 5173,
        strictPort: false
    }
})
