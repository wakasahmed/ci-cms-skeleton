/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './application/views/frontend/**/*.php',
    './assets/frontend/js/**/*.js',
  ],
  theme: {
    container: {
      center: true,
      padding: { DEFAULT: '1.25rem', sm: '1.5rem', lg: '2rem' },
      screens: { '2xl': '1240px' },
    },
    extend: {
      colors: {
        alam: {
          50:  '#F7F5FB',
          100: '#EEEAF7',
          200: '#DDD7EC',
          300: '#C3B9DF',
          400: '#9385C4',
          500: '#63569B',
          600: '#564A8A',
          700: '#4C427A',
          800: '#3E3565',
          900: '#2E2749',
          DEFAULT: '#63569B',
        },
        ink: {
          DEFAULT: '#252331',
          muted: '#6F6B78',
          soft: '#767281',
        },
        surface: {
          DEFAULT: '#FFFFFF',
          soft: '#FAFAFC',
          tint: '#F7F5FB',
        },
        line: {
          DEFAULT: '#D6CFE6',
          strong: '#C3BAD9',
        },
        sand: {
          50: '#FBF8F3',
          100: '#F4EDE1',
          200: '#E7DAC5',
          500: '#B99A63',
        },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
        /* Arabic pages only. IBM Plex Sans Arabic carries the Arabic script and
           its own Latin, so a mixed run — an email address inside an Arabic
           sentence — stays in one family instead of falling back mid-line.
           Applied through the [dir="rtl"] rule in assets/frontend/css/src/tailwind.css
           rather than a class, so no template has to know which locale it is in. */
        arabic: ['"IBM Plex Sans Arabic"', '"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
      },
      /* =====================================================================
       * Typography scale — the single source of truth for text sizing.
       *
       * Semantic names, not pixel names: pick by what the text IS, not how
       * big you want it. Never put a raw text-[13px] in a template again.
       *
       * Floor is 14px (`caption`), and that is only for badges, captions and
       * genuinely peripheral helper text. Anything a visitor actually reads
       * starts at 16px (`body-sm`). Nothing shrinks on mobile — reduce
       * padding instead.
       * ================================================================== */
      fontSize: {
        // --- Headings ---
        'display': ['clamp(2.25rem, 1.4rem + 3.4vw, 4rem)', { lineHeight: '1.25', letterSpacing: '-0.03em' }],
        'h1': ['clamp(2rem, 1.4rem + 2.4vw, 3.25rem)', { lineHeight: '1.1', letterSpacing: '-0.025em' }],
        'h2': ['clamp(1.65rem, 1.25rem + 1.6vw, 2.5rem)', { lineHeight: '1.15', letterSpacing: '-0.02em' }],
        'h3': ['clamp(1.25rem, 1.1rem + 0.7vw, 1.6rem)', { lineHeight: '1.25', letterSpacing: '-0.015em' }],

        // --- Body copy ---
        'body-lg':    ['1.125rem',  { lineHeight: '1.75' }],  // 18px — intros, lead paragraphs
        'body':       ['1.0625rem', { lineHeight: '1.7' }],   // 17px — standard prose
        'body-sm':    ['1rem',      { lineHeight: '1.6' }],   // 16px — minimum for readable copy

        // --- Cards ---
        'card-title': ['1.1875rem', { lineHeight: '1.35', letterSpacing: '-0.01em' }], // 19px
        'card-body':  ['1rem',      { lineHeight: '1.6' }],   // 16px

        // --- Supporting text ---
        'meta':    ['0.9375rem', { lineHeight: '1.5' }],   // 15px — duration, dates, counts
        'caption': ['0.875rem',  { lineHeight: '1.45' }],  // 14px — badges, captions ONLY

        // --- Interface ---
        'nav':        ['1rem',      { lineHeight: '1.4' }],   // 16px
        'button':     ['1rem',      { lineHeight: '1.2' }],   // 16px
        'button-sm':  ['0.9375rem', { lineHeight: '1.2' }],   // 15px — floor for buttons
        'form-label': ['1rem',      { lineHeight: '1.4' }],   // 16px
        'form-input': ['1rem',      { lineHeight: '1.5' }],   // 16px — avoids iOS zoom-on-focus
        'form-help':  ['0.9375rem', { lineHeight: '1.5' }],   // 15px
        'eyebrow':    ['0.875rem',  { lineHeight: '1.3', letterSpacing: '0.14em' }], // 14px
      },
      borderRadius: {
        control: '11px',
        card: '16px',
        feature: '24px',
      },
      boxShadow: {
        card: '0 1px 2px rgba(37,35,49,0.04), 0 8px 24px -16px rgba(62,53,101,0.28)',
        lift: '0 2px 4px rgba(37,35,49,0.05), 0 18px 40px -22px rgba(62,53,101,0.45)',
        panel: '0 24px 60px -32px rgba(62,53,101,0.45)',
      },
      maxWidth: {
        // Target ~65-70 characters per line for long-form copy.
        // NOT expressed in `ch`: Plus Jakarta Sans has an unusually wide zero
        // glyph, so 68ch measured out at ~95 characters per line in practice.
        prose: '38rem',
      },
      keyframes: {
        'fade-up': { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'none' } },
        'fade-in': { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
      },
      animation: {
        'fade-up': 'fade-up .45s cubic-bezier(.22,.61,.36,1) both',
        'fade-in': 'fade-in .3s ease-out both',
      },
    },
  },
  plugins: [],
};
