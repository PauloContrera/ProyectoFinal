import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import { resolve } from 'path'

// import.meta.dirname en lugar de __dirname: con configLoader nativo (el default
// en una proxima major de Vite) __dirname no existe.
const projectRoot = import.meta.dirname

export default defineConfig({
  plugins: [react()],
  server: {
    // Respeta PORT si el entorno lo define; si no, el 5173 de siempre.
    port: Number(process.env.PORT) || 5173,
  },
  resolve: {
    alias: {
      '@': resolve(projectRoot, './src'),  // Alias '@' para apuntar a la carpeta 'src'
      'src': resolve(projectRoot, './src'),  // Alias 'src' también para la carpeta 'src'
    },
  },
})
