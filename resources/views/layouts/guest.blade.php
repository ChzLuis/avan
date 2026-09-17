{{-- Login principal del panel BIXO. Usa el sistema comun <x-bixo-auth> con la
     identidad por defecto (BIXO by Eskala). Cada portal pasa su propia identidad.

     OJO: este archivo NO debe volver a escribirse con markup propio. El
     formulario (`auth/login.blade.php`) usa las clases `bixo-*` que define
     este componente; sustituirlo por otro layout deja los iconos y los campos
     SIN estilos (incidente 2026-08-31). --}}
<x-bixo-auth
    name="BIXO" by="Configuración"
    tagline="Tu negocio,<br>bajo <em>control</em>."
    sub="Administra tus tiendas, catálogo, ventas y asistente desde un solo panel."
    :caps="['Tiendas y catálogo', 'Ventas y pedidos', 'Asistente WhatsApp']"
    title="Iniciar sesión">
    {{ $slot }}
</x-bixo-auth>
