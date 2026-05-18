import path from 'path';
import { fileURLToPath } from 'url';
import { FlatCompat } from '@eslint/eslintrc';
import globals from 'globals';
import reactRefresh from 'eslint-plugin-react-refresh';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const compat = new FlatCompat({ baseDirectory: __dirname });

export default [
  { ignores: ['dist/**', 'coverage/**'] },
  ...compat.extends('airbnb', 'airbnb/hooks'),
  {
    languageOptions: {
      globals: { ...globals.browser },
      ecmaVersion: 'latest',
      sourceType: 'module',
    },
    settings: {
      react: { version: 'detect' },
      'import/resolver': {
        node: { extensions: ['.js', '.jsx'] },
      },
    },
    rules: {
      'react/react-in-js-scope': 'off',
      'react/jsx-no-useless-fragment': ['error', { allowExpressions: true }],
      'react/prop-types': 'off',
      'react/function-component-definition': 'off',
      'no-underscore-dangle': ['error', { allow: ['__filename', '__dirname'] }],
      'import/no-extraneous-dependencies': [
        'error',
        {
          devDependencies: [
            'vite.config.js',
            'eslint.config.js',
            'src/test/**',
            '**/*.test.*',
          ],
        },
      ],
    },
  },
  {
    plugins: { 'react-refresh': reactRefresh },
    rules: {
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
    },
  },
  {
    files: ['vite.config.js'],
    rules: {
      'import/no-unresolved': 'off',
    },
  },
];
