/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/index.html",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        /* 语义令牌：CSS 变量驱动，亮/暗双主题（见 resources/css/app.css） */
        base: 'rgb(var(--bg-base) / <alpha-value>)',
        card: 'rgb(var(--bg-card) / <alpha-value>)',
        well: 'rgb(var(--bg-well) / <alpha-value>)',
        codebg: 'rgb(var(--bg-code) / <alpha-value>)',
        line: {
          DEFAULT: 'rgb(var(--border-subtle) / <alpha-value>)',
          strong: 'rgb(var(--border-strong) / <alpha-value>)',
        },
        ink: {
          DEFAULT: 'rgb(var(--text-primary) / <alpha-value>)',
          secondary: 'rgb(var(--text-secondary) / <alpha-value>)',
          muted: 'rgb(var(--text-muted) / <alpha-value>)',
          faint: 'rgb(var(--text-faint) / <alpha-value>)',
        },
        /* rgba 变量，完整色值（填充/hover 用） */
        surface: 'var(--surface)',
        'surface-strong': 'var(--surface-strong)',
        horizon: {
          primary: '#6366f1'
        }
      },
      fontFamily: {
        sans: [
          'Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto',
          'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'sans-serif'
        ],
        mono: [
          'ui-monospace', 'SFMono-Regular', 'JetBrains Mono', 'Menlo',
          'Consolas', 'Liberation Mono', 'monospace'
        ]
      },
      boxShadow: {
        'glow-primary': '0 0 24px -6px rgba(99, 102, 241, 0.45)',
        'drawer': 'var(--shadow-drawer)'
      },
      keyframes: {
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(6px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        }
      },
      animation: {
        'fade-in-up': 'fade-in-up 0.25s ease-out both'
      }
    },
  },
  plugins: [],
}
