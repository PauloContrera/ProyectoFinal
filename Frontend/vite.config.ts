import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import { resolve } from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),  // Alias '@' para apuntar a la carpeta 'src'
      'src': resolve(__dirname, './src'),  // Alias 'src' también para la carpeta 'src'
    },
  },
})
