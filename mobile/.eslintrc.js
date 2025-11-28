// https://docs.expo.dev/guides/using-eslint/
const path = require('path');

module.exports = {
  extends: ['expo', 'prettier'],
  plugins: ['prettier'],
  rules: {
    'prettier/prettier': 'error',
  },
  settings: {
    node: {
      extensions: ['.js', '.jsx', '.ts', '.tsx'],
    },
    'import/resolver': {
      alias: {
        map: [['@', path.resolve(__dirname, './')]],
        extensions: ['.js', '.jsx', '.ts', '.tsx'],
      },
    },
  },
  ignorePatterns: ['/dist/*'],
  root: true,
  env: {
    node: true,
  },
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
  },
};
