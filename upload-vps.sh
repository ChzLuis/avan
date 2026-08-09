#!/bin/bash
VPS="root@2.24.200.91"
REMOTE="/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe"
KEY="$HOME/.ssh/vps_key"
SCP="scp -o StrictHostKeyChecking=no -i $KEY"
SSH="ssh -o StrictHostKeyChecking=no -i $KEY"

echo "=== Subiendo archivos al VPS ==="

# Rutas
$SCP routes/web.php $VPS:$REMOTE/routes/web.php && echo "✓ web.php"

# Controladores
$SCP app/Http/Controllers/Comercial/AuthController.php $VPS:$REMOTE/app/Http/Controllers/Comercial/AuthController.php && echo "✓ AuthController.php"
$SCP app/Http/Controllers/Comercial/DashboardController.php $VPS:$REMOTE/app/Http/Controllers/Comercial/DashboardController.php && echo "✓ Comercial/DashboardController.php"
$SCP app/Http/Controllers/HRController.php $VPS:$REMOTE/app/Http/Controllers/HRController.php && echo "✓ HRController.php"
$SCP app/Http/Controllers/WaBotController.php $VPS:$REMOTE/app/Http/Controllers/WaBotController.php && echo "✓ WaBotController.php"
$SCP app/Http/Controllers/ReporteController.php $VPS:$REMOTE/app/Http/Controllers/ReporteController.php && echo "✓ ReporteController.php"
$SCP app/Http/Controllers/RifaController.php $VPS:$REMOTE/app/Http/Controllers/RifaController.php && echo "✓ RifaController.php"
$SCP app/Http/Controllers/InvoiceController.php $VPS:$REMOTE/app/Http/Controllers/InvoiceController.php && echo "✓ InvoiceController.php"
$SCP app/Http/Controllers/BotStatusController.php $VPS:$REMOTE/app/Http/Controllers/BotStatusController.php && echo "✓ BotStatusController.php"

# Jobs
$SSH $VPS "mkdir -p $REMOTE/app/Jobs"
$SCP app/Jobs/SendInvoiceToSunat.php $VPS:$REMOTE/app/Jobs/SendInvoiceToSunat.php && echo "✓ Jobs/SendInvoiceToSunat.php"
$SCP app/Jobs/SendAbandonedCartReminder.php $VPS:$REMOTE/app/Jobs/SendAbandonedCartReminder.php && echo "✓ Jobs/SendAbandonedCartReminder.php"

# Middleware
$SCP app/Http/Middleware/CheckWorkSchedule.php $VPS:$REMOTE/app/Http/Middleware/CheckWorkSchedule.php && echo "✓ Middleware/CheckWorkSchedule.php"
$SCP app/Http/Middleware/CheckPermission.php $VPS:$REMOTE/app/Http/Middleware/CheckPermission.php && echo "✓ Middleware/CheckPermission.php"
$SCP app/Http/Middleware/EnsureProjectScope.php $VPS:$REMOTE/app/Http/Middleware/EnsureProjectScope.php && echo "✓ Middleware/EnsureProjectScope.php"

# Models / Traits
$SSH $VPS "mkdir -p $REMOTE/app/Models/Traits"
$SCP app/Models/Traits/HasProjectScope.php $VPS:$REMOTE/app/Models/Traits/HasProjectScope.php && echo "✓ Models/Traits/HasProjectScope.php"
$SCP app/Models/Order.php $VPS:$REMOTE/app/Models/Order.php && echo "✓ Models/Order.php"
$SCP app/Models/Product.php $VPS:$REMOTE/app/Models/Product.php && echo "✓ Models/Product.php"
$SCP app/Models/Invoice.php $VPS:$REMOTE/app/Models/Invoice.php && echo "✓ Models/Invoice.php"
$SCP app/Models/Quote.php $VPS:$REMOTE/app/Models/Quote.php && echo "✓ Models/Quote.php"
$SCP app/Models/Client.php $VPS:$REMOTE/app/Models/Client.php && echo "✓ Models/Client.php"
$SCP app/Models/Employee.php $VPS:$REMOTE/app/Models/Employee.php && echo "✓ Models/Employee.php"
$SCP app/Models/Attendance.php $VPS:$REMOTE/app/Models/Attendance.php && echo "✓ Models/Attendance.php"
$SCP app/Models/WorkSchedule.php $VPS:$REMOTE/app/Models/WorkSchedule.php && echo "✓ Models/WorkSchedule.php"
$SCP app/Models/Coupon.php $VPS:$REMOTE/app/Models/Coupon.php && echo "✓ Models/Coupon.php"
$SCP app/Models/AbandonedCart.php $VPS:$REMOTE/app/Models/AbandonedCart.php && echo "✓ Models/AbandonedCart.php"

# Bootstrap
$SCP bootstrap/app.php $VPS:$REMOTE/bootstrap/app.php && echo "✓ bootstrap/app.php"

# Vistas
$SCP resources/views/comercial/rifas.blade.php $VPS:$REMOTE/resources/views/comercial/rifas.blade.php && echo "✓ rifas.blade.php"
$SCP resources/views/comercial/layouts/app.blade.php $VPS:$REMOTE/resources/views/comercial/layouts/app.blade.php && echo "✓ comercial/layouts/app.blade.php"
$SCP resources/views/hr/employees.blade.php $VPS:$REMOTE/resources/views/hr/employees.blade.php && echo "✓ hr/employees.blade.php"
$SCP resources/views/bots/flow.blade.php $VPS:$REMOTE/resources/views/bots/flow.blade.php && echo "✓ bots/flow.blade.php"
$SCP resources/views/errors/outside-schedule.blade.php $VPS:$REMOTE/resources/views/errors/outside-schedule.blade.php && echo "✓ errors/outside-schedule.blade.php"

$SSH $VPS "mkdir -p $REMOTE/resources/views/comercial/reportes"
$SCP resources/views/comercial/reportes/ventas.blade.php $VPS:$REMOTE/resources/views/comercial/reportes/ventas.blade.php && echo "✓ reportes/ventas.blade.php"
$SCP resources/views/comercial/reportes/rentabilidad.blade.php $VPS:$REMOTE/resources/views/comercial/reportes/rentabilidad.blade.php && echo "✓ reportes/rentabilidad.blade.php"
$SCP app/Http/Middleware/ComercialAuth.php $VPS:$REMOTE/app/Http/Middleware/ComercialAuth.php && echo "✓ Middleware/ComercialAuth.php"
$SCP resources/views/orders/index.blade.php $VPS:$REMOTE/resources/views/orders/index.blade.php && echo "✓ orders/index.blade.php"
$SCP resources/views/pos/index.blade.php $VPS:$REMOTE/resources/views/pos/index.blade.php && echo "✓ pos/index.blade.php"

# Bot
$SCP whatsbot/engine.js $VPS:$REMOTE/whatsbot/engine.js && echo "✓ engine.js"

# Composer (predis)
echo ""
echo "=== Instalando dependencias (predis) ==="
$SSH $VPS "cd $REMOTE && composer require predis/predis --no-interaction 2>&1 | tail -5"

# Migraciones
echo ""
echo "=== Ejecutando migraciones ==="
$SSH $VPS "cd $REMOTE && php artisan migrate --force"

# Limpieza de caché
echo ""
echo "=== Limpiando caché Laravel ==="
$SSH $VPS "cd $REMOTE && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan config:clear"

# Reiniciar bot
echo ""
echo "=== Reiniciando bot ==="
$SSH $VPS "pm2 restart bot-rifa 2>/dev/null; pm2 status"

echo ""
echo "=== Listo! ==="
