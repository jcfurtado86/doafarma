// https://docs.expo.dev/guides/using-eslint/
const path = require('path');

module.exports = {
  extends: ['expo', 'prettier'],
  plugins: ['prettier'],
  rules: {
    'prettier/prettier': 'error',
    // Regras novas do eslint-config-expo 57 (React Compiler). Adoção é tarefa separada
    // do backlog de migração do eslint; desligadas aqui pra não misturar com o upgrade do SDK.
    'react-hooks/set-state-in-effect': 'off',
    'react-hooks/immutability': 'off',
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
