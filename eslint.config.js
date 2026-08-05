import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';

export default [
    {
        name: 'app/files-to-lint',
        files: ['**/*.{js,mjs,cjs,vue}'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: [
            'node_modules/**',
            'public/build/**',
            'vendor/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    skipFormatting,
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-undef': 'off',
        },
    },
];
