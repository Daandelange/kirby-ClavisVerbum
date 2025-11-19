import { defineConfig } from 'kirbyup/config'

import { resolve } from 'node:path'

export default defineConfig({
  alias: {
    '@/': `${resolve(__dirname, '../../../kirby/panel/src')}/`,
  }
})