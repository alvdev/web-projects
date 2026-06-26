import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: 'public/assets',
        assetsDir: '',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'assets/src/js/app.js'),
                'steam-chart': path.resolve(__dirname, 'assets/src/js/steam-chart.js')
            }
        }
    },
    server: {
        port: 5173,
        strictPort: true
    }
})
