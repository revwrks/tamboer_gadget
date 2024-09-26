#!/bin/bash
LOCAL_IP=$(node get-local-ip.js)

# Replace APP_URL in .env with the local IP
sed -i "s|^APP_URL=.*|APP_URL=http://192.168.1.82:8000|g" .env

# Start Laravel server
php artisan serve --host=192.168.1.82 --port=8000 &

# Start Vite dev server
npm run dev -- --host=0.0.0.0
