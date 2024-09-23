#!/bin/bash
LOCAL_IP=$(node get-local-ip.js)

# Output the local IP to verify
echo "Local IP is: $LOCAL_IP"

# Replace APP_URL in .env with the local IP
sed -i '' "s|^APP_URL=.*|APP_URL=http://$LOCAL_IP:8000|g" .env

# Start Laravel server
php artisan serve --host=$LOCAL_IP --port=8000 &

# Start Vite dev server
npm run dev -- --host=$LOCAL_IP
