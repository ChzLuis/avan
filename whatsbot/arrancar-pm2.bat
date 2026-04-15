@echo off
cd /d C:\xampp\htdocs\avan\whatsbot
pm2 resurrect
pm2 start index.js --name whatsbot
pm2 save
