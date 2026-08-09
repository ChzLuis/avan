module.exports = {
  apps: [
    {
      name: 'bot-rifa',
      script: 'engine.js',
      args: '--bot=rifa --port=3002',
      cwd: __dirname,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      restart_delay: 10000,
      max_restarts: 10,
      min_uptime: '30s',
      env: {
        NODE_ENV: 'production',
        LARAVEL_URL: 'https://bot.pruebatusuerte.com.pe',
      },
    },
  ],
};
