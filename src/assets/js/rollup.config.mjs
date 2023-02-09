// See https://lit.dev/docs/tools/production/#building-with-rollup

// Import rollup plugins
import { copy } from '@web/rollup-plugin-copy';
import resolve from '@rollup/plugin-node-resolve';
import { terser } from 'rollup-plugin-terser';
import summary from 'rollup-plugin-summary';

export default {
  plugins: [
    // Resolve bare module specifiers to relative paths
    resolve(),
    // Minify JS
    terser({
      ecma: 2020,
      module: true,
      warnings: true,
    }),
    // Print bundle summary
    summary(),
    // Optional: copy any static assets to build directory
    copy({
      patterns: [],
    }),
  ],
  input: './index.js',
  output: {
    dir: 'build',
  },
  preserveEntrySignatures: 'strict',
};
