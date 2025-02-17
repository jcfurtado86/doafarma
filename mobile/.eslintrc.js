// https://docs.expo.dev/guides/using-eslint/
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
  },
  ignorePatterns: ['/dist/*'],
};
