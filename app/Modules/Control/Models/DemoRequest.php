<?php

namespace App\Modules\Control\Models;

use App\Models\Project;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = [
        'business_name', 'rubro', 'features_selected',
        'contact_name', 'email', 'phone', 'whatsapp',
        'project_id', 'user_id', 'demo_password',
        'status', 'expires_at',
    ];

    protected $casts = [
        'features_selected' => 'array',
        'expires_at'        => 'datetime',
    ];

    public function project()  { return $this->belongsTo(Project::class); }
    public function user()     { return $this->belongsTo(User::class); }

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isExpired(): bool  { return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast()); }

    /** Configuración de rubros: módulos a activar + features visibles */
    public static function rubroConfig(string $rubro): array
    {
        $configs = [
            'restaurante' => [
                'label'   => 'Restaurante / Cafetería',
                'emoji'   => '🍽️',
                'tagline' => 'Gestiona pedidos, menú digital y reservas de mesas',
                'problems' => [
                    'Pedidos por WhatsApp sin control',
                    'Reservas de mesa desordenadas',
                    'Catálogo desactualizado (PDF o imagen)',
                    'Sin seguimiento del estado del pedido',
                ],
                'detail_modules' => [
                    'Comercial'   => ['Catálogo de platos', 'Promociones', 'Combos', 'Pedidos'],
                    'Agenda'      => ['Reservas de mesa', 'Control de mesas'],
                    'Operaciones' => ['Estado del pedido', 'Delivery'],
                    'Marketing'   => ['Cupones', 'Campañas WA'],
                    'Web'         => ['Menú online', 'Reservas online'],
                ],
                'modules' => ['store','orders','catalog','clients','agenda','hr'],
                'features_available' => [
                    ['key' => 'pos',        'label' => 'Punto de venta (POS)',           'default' => true,  'available' => true],
                    ['key' => 'whatsapp',   'label' => 'Pedidos por WhatsApp',           'default' => true,  'available' => true],
                    ['key' => 'catalog',    'label' => 'Catálogo con fotos y precios',   'default' => true,  'available' => true],
                    ['key' => 'agenda',     'label' => 'Reservas de mesa',               'default' => true,  'available' => true],
                    ['key' => 'sedes',      'label' => 'Múltiples sucursales',           'default' => false, 'available' => true],
                    ['key' => 'hr',         'label' => 'Gestión de empleados',           'default' => false, 'available' => true],
                    ['key' => 'delivery',   'label' => 'Delivery con costo',             'default' => true,  'available' => true],
                    ['key' => 'qr_menu',    'label' => 'Menú digital QR por mesa',      'default' => false, 'available' => false],
                    ['key' => 'comandas',   'label' => 'Comandas a cocina',              'default' => false, 'available' => false],
                    ['key' => 'rappi',      'label' => 'Integración Rappi/PedidosYa',   'default' => false, 'available' => false],
                ],
            ],
            'peluqueria' => [
                'label'   => 'Peluquería / Spa',
                'emoji'   => '✂️',
                'tagline' => 'Agenda de citas, servicios y fidelización de clientes',
                'problems' => [
                    'Citas por WhatsApp sin organización',
                    'Sin recordatorio automático al cliente',
                    'No hay historial de preferencias',
                    'Difícil gestionar horarios de estilistas',
                ],
                'detail_modules' => [
                    'Agenda'    => ['Citas online', 'Calendario de estilistas', 'Recordatorios'],
                    'Comercial' => ['Catálogo de servicios', 'Paquetes', 'POS'],
                    'Clientes'  => ['Historial', 'Segmentos', 'Fidelización'],
                    'Marketing' => ['Campañas WA', 'Cupones de descuento'],
                ],
                'modules' => ['store','orders','catalog','agenda','clients','hr'],
                'features_available' => [
                    ['key' => 'agenda',        'label' => 'Agenda de citas',                    'default' => true,  'available' => true],
                    ['key' => 'catalog',       'label' => 'Catálogo de servicios con precios',  'default' => true,  'available' => true],
                    ['key' => 'pos',           'label' => 'POS para cobrar servicios',          'default' => true,  'available' => true],
                    ['key' => 'hr',            'label' => 'Gestión de profesionales',           'default' => true,  'available' => true],
                    ['key' => 'whatsapp',      'label' => 'Recordatorios por WhatsApp',         'default' => true,  'available' => true],
                    ['key' => 'productos',     'label' => 'Venta de productos',                 'default' => false, 'available' => true],
                    ['key' => 'ficha_cliente', 'label' => 'Ficha del cliente (preferencias)',   'default' => false, 'available' => false],
                    ['key' => 'reprogramar',   'label' => 'Reprogramación por el cliente',      'default' => false, 'available' => false],
                    ['key' => 'membresias',    'label' => 'Paquetes y membresías',              'default' => false, 'available' => false],
                ],
            ],
            'clinica' => [
                'label'   => 'Clínica / Consultorio',
                'emoji'   => '🏥',
                'tagline' => 'Citas médicas, historial y comunicación con pacientes',
                'problems' => [
                    'Citas por llamada o WhatsApp sin orden',
                    'Sin recordatorio automático de citas',
                    'Historiales en papel difíciles de buscar',
                    'Comunicación desorganizada con pacientes',
                ],
                'detail_modules' => [
                    'Agenda'    => ['Citas online', 'Calendario médicos', 'Recordatorios'],
                    'Pacientes' => ['Historial', 'Seguimiento', 'Segmentos'],
                    'Comercial' => ['Servicios', 'Cotizaciones', 'Facturas'],
                    'Marketing' => ['Campañas WA', 'Comunicaciones'],
                ],
                'modules' => ['store','orders','catalog','agenda','clients','invoices','hr'],
                'features_available' => [
                    ['key' => 'agenda',      'label' => 'Agenda de citas',              'default' => true,  'available' => true],
                    ['key' => 'catalog',     'label' => 'Catálogo de servicios',        'default' => true,  'available' => true],
                    ['key' => 'recordatorio','label' => 'Recordatorios automáticos',    'default' => true,  'available' => true],
                    ['key' => 'hr',          'label' => 'Gestión de profesionales',     'default' => true,  'available' => true],
                    ['key' => 'pos',         'label' => 'Cobro por consultas',          'default' => true,  'available' => true],
                    ['key' => 'invoices',    'label' => 'Facturación electrónica',      'default' => false, 'available' => true],
                    ['key' => 'historia',    'label' => 'Historia clínica básica',      'default' => false, 'available' => false],
                    ['key' => 'recetas',     'label' => 'Recetas digitales',            'default' => false, 'available' => false],
                    ['key' => 'portal',      'label' => 'Portal del paciente',          'default' => false, 'available' => false],
                ],
            ],
            'retail' => [
                'label'   => 'Tienda Retail',
                'emoji'   => '🛍️',
                'tagline' => 'Catálogo digital, ventas y control de inventario',
                'problems' => [
                    'Catálogo desactualizado o en PDF/imágenes',
                    'Sin sistema de ventas formal',
                    'Precios y stock difíciles de gestionar',
                    'Sin historial de clientes frecuentes',
                ],
                'detail_modules' => [
                    'Comercial'   => ['Catálogo', 'Cotizaciones', 'Facturas', 'POS'],
                    'Operaciones' => ['Inventario', 'Alertas de stock'],
                    'Web'         => ['Tienda online', 'Catálogo público'],
                    'Reportes'    => ['Ventas', 'Productos más vendidos'],
                ],
                'modules' => ['store','orders','catalog','clients','invoices'],
                'features_available' => [
                    ['key' => 'pos',       'label' => 'POS con búsqueda rápida',         'default' => true,  'available' => true],
                    ['key' => 'tienda',    'label' => 'Tienda online propia',             'default' => true,  'available' => true],
                    ['key' => 'catalog',   'label' => 'Catálogo con fotos',              'default' => true,  'available' => true],
                    ['key' => 'whatsapp',  'label' => 'Pedidos por WhatsApp',            'default' => true,  'available' => true],
                    ['key' => 'stock',     'label' => 'Control de stock',                'default' => true,  'available' => true],
                    ['key' => 'pagos',     'label' => 'Pagos online (Culqi/MP)',         'default' => true,  'available' => true],
                    ['key' => 'cupones',   'label' => 'Cupones de descuento',            'default' => false, 'available' => true],
                    ['key' => 'variantes', 'label' => 'Variantes (talla, color)',        'default' => false, 'available' => false],
                    ['key' => 'barcode',   'label' => 'Código de barras',                'default' => false, 'available' => false],
                    ['key' => 'reportes',  'label' => 'Reportes de productos más vendidos','default' => false, 'available' => false],
                ],
            ],
            'whatsapp' => [
                'label'   => 'Negocio por WhatsApp',
                'emoji'   => '🤖',
                'tagline' => 'Bot automático, catálogo y pedidos por WhatsApp',
                'problems' => [
                    'Responder manualmente cada mensaje a toda hora',
                    'Sin catálogo organizado para compartir',
                    'Pedidos perdidos o mal anotados',
                    'Sin seguimiento de clientes y conversaciones',
                ],
                'detail_modules' => [
                    'Bot WA'     => ['Respuestas automáticas 24/7', 'Toma de pedidos', 'Catálogo interactivo'],
                    'Comercial'  => ['Catálogo con fotos', 'Pedidos', 'POS'],
                    'Clientes'   => ['CRM básico', 'Historial de chats', 'Segmentos'],
                    'Marketing'  => ['Campañas masivas WA', 'Respuestas rápidas'],
                ],
                'modules' => ['store','orders','catalog','clients'],
                'features_available' => [
                    ['key' => 'bot',         'label' => 'Bot automático 24/7',               'default' => true,  'available' => true],
                    ['key' => 'catalog_wa',  'label' => 'Catálogo por WhatsApp con fotos',  'default' => true,  'available' => true],
                    ['key' => 'orders_wa',   'label' => 'Toma de pedidos por WhatsApp',     'default' => true,  'available' => true],
                    ['key' => 'bandeja',     'label' => 'Bandeja para responder chats',     'default' => true,  'available' => true],
                    ['key' => 'snippets',    'label' => 'Respuestas rápidas',               'default' => true,  'available' => true],
                    ['key' => 'crm',         'label' => 'CRM de clientes',                  'default' => true,  'available' => true],
                    ['key' => 'vouchers',    'label' => 'Detección automática de vouchers', 'default' => false, 'available' => false],
                    ['key' => 'escalamiento','label' => 'Bot → agente humano',             'default' => false, 'available' => false],
                    ['key' => 'analytics',   'label' => 'Analítica de conversaciones',      'default' => false, 'available' => false],
                ],
            ],
            'farmacia' => [
                'label'   => 'Farmacia / Botica',
                'emoji'   => '💊',
                'tagline' => 'Catálogo, stock y ventas rápidas para farmacias',
                'problems' => [
                    'Clientes preguntan disponibilidad por WhatsApp',
                    'Control de stock manual y desactualizado',
                    'Sin control de fechas de vencimiento',
                    'Facturación lenta o sin sistema',
                ],
                'detail_modules' => [
                    'Comercial'   => ['POS rápido', 'Catálogo de productos', 'Pedidos WA'],
                    'Operaciones' => ['Control de stock', 'Alertas de mínimo', 'Vencimientos'],
                    'Facturación' => ['Boletas', 'Facturas electrónicas'],
                    'Reportes'    => ['Productos más vendidos', 'Ventas diarias'],
                ],
                'modules' => ['store','orders','catalog','clients','invoices','proveedores'],
                'features_available' => [
                    ['key' => 'pos',         'label' => 'POS rápido',                      'default' => true,  'available' => true],
                    ['key' => 'catalog',     'label' => 'Catálogo de productos',           'default' => true,  'available' => true],
                    ['key' => 'stock',       'label' => 'Control de stock',                'default' => true,  'available' => true],
                    ['key' => 'whatsapp',    'label' => 'Pedidos por WhatsApp',            'default' => true,  'available' => true],
                    ['key' => 'proveedores', 'label' => 'Gestión de proveedores',          'default' => false, 'available' => true],
                    ['key' => 'invoices',    'label' => 'Facturación electrónica',         'default' => false, 'available' => true],
                    ['key' => 'barcode',     'label' => 'Código de barras',                'default' => false, 'available' => false],
                    ['key' => 'stock_alert', 'label' => 'Alertas de stock mínimo',         'default' => false, 'available' => false],
                    ['key' => 'vencimiento', 'label' => 'Control de vencimientos',         'default' => false, 'available' => false],
                    ['key' => 'oc',          'label' => 'Órdenes de compra',               'default' => false, 'available' => false],
                ],
            ],
            'veterinaria' => [
                'label'   => 'Veterinaria / Pet Shop',
                'emoji'   => '🐾',
                'tagline' => 'Citas, historial de mascotas y venta de productos',
                'problems' => [
                    'Citas de consulta sin organización',
                    'Sin recordatorio de vacunas o controles',
                    'Historial de mascotas en papel',
                    'Venta de productos sin control de stock',
                ],
                'detail_modules' => [
                    'Agenda'    => ['Citas de consulta', 'Control de vacunas', 'Recordatorios WA'],
                    'Pacientes' => ['Ficha de mascota', 'Historial clínico', 'Dueños'],
                    'Comercial' => ['Catálogo productos', 'POS', 'Servicios'],
                    'Marketing' => ['Recordatorios automáticos', 'Campañas WA'],
                ],
                'modules' => ['store','orders','catalog','agenda','clients','hr'],
                'features_available' => [
                    ['key' => 'agenda',      'label' => 'Agenda de consultas',              'default' => true,  'available' => true],
                    ['key' => 'catalog',     'label' => 'Catálogo de servicios',            'default' => true,  'available' => true],
                    ['key' => 'pos',         'label' => 'POS para cobrar',                  'default' => true,  'available' => true],
                    ['key' => 'productos',   'label' => 'Venta de productos (alimentos)',   'default' => true,  'available' => true],
                    ['key' => 'whatsapp',    'label' => 'Recordatorios de vacunas (WA)',    'default' => true,  'available' => true],
                    ['key' => 'hr',          'label' => 'Gestión de veterinarios',          'default' => false, 'available' => true],
                    ['key' => 'ficha_pet',   'label' => 'Ficha de mascota (historial)',     'default' => false, 'available' => false],
                    ['key' => 'vacunas',     'label' => 'Control de vacunas',               'default' => false, 'available' => false],
                    ['key' => 'portal_pet',  'label' => 'Portal del dueño',                 'default' => false, 'available' => false],
                ],
            ],
            'taller' => [
                'label'   => 'Taller Mecánico / Técnico',
                'emoji'   => '🔧',
                'tagline' => 'Órdenes de trabajo, cotizaciones y seguimiento de vehículos',
                'problems' => [
                    'Sin control de vehículos en taller',
                    'Cotizaciones a mano o por WhatsApp',
                    'Clientes sin actualización del estado',
                    'Historial del vehículo inexistente',
                ],
                'detail_modules' => [
                    'Operaciones' => ['Orden de trabajo', 'Estado en tiempo real', 'Control de técnicos'],
                    'Comercial'   => ['Cotizaciones digitales', 'Facturación', 'Repuestos'],
                    'Clientes'    => ['Historial del vehículo', 'Segmentos'],
                    'Marketing'   => ['Notificaciones WA al cliente', 'Campañas'],
                ],
                'modules' => ['store','orders','catalog','agenda','quotes','clients','invoices','hr'],
                'features_available' => [
                    ['key' => 'agenda',      'label' => 'Agenda de ingresos',               'default' => true,  'available' => true],
                    ['key' => 'catalog',     'label' => 'Catálogo de servicios',            'default' => true,  'available' => true],
                    ['key' => 'quotes',      'label' => 'Cotizaciones digitales',           'default' => true,  'available' => true],
                    ['key' => 'invoices',    'label' => 'Facturación electrónica',          'default' => true,  'available' => true],
                    ['key' => 'hr',          'label' => 'Gestión de técnicos',              'default' => false, 'available' => true],
                    ['key' => 'whatsapp',    'label' => 'Notificaciones al cliente (WA)',   'default' => true,  'available' => true],
                    ['key' => 'orden_trabajo','label' => 'Orden de trabajo digital',        'default' => false, 'available' => false],
                    ['key' => 'historial_v', 'label' => 'Historial del vehículo',           'default' => false, 'available' => false],
                    ['key' => 'repuestos',   'label' => 'Control de repuestos',             'default' => false, 'available' => false],
                ],
            ],
        ];

        return $configs[$rubro] ?? [];
    }
}
