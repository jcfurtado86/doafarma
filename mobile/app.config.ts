import 'dotenv/config';

// Importa a configuração base do app.json
const config = require('./app.json');

export default {
  ...config.expo,
  extra: {
    hostIp: process.env.HOST_IP || '192.168.1.71',
  },
};
