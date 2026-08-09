<?php

add_action('admin_menu', function() {
    add_menu_page('Tickets PRO', '🎟 Tickets', 'manage_options', 'tickets-pro', 'ctdw_admin_page');
});

function ctdw_admin_page() {

    global $wpdb;
    $table = $wpdb->prefix . 'ctdw_tickets';

    // ==========================
    // EXPORTAR
    // ==========================
    if (isset($_GET['export'])) {
        if (ob_get_length()) ob_clean();
        $tickets = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tickets.csv');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID','Ticket','DNI','Nombres','Apellidos','Correo','Telefono','Ciudad','Punto de venta','Origen','Estado','Fecha']);
        foreach ($tickets as $t) {
            fputcsv($output, [$t->id, $t->codigo, $t->dni, $t->nombres, $t->apellidos, $t->correo, $t->telefono, $t->ciudad, $t->punto_venta,  $t->origen, $t->estado, $t->created_at]);
        }
        fclose($output);
        exit;
    }

    // ==========================
    // CREAR / ACTUALIZAR
    // ==========================
    $con_woo = isset($_POST['guardar_ticket_woo']) || isset($_POST['confirmar_edicion_woo']);

    if (isset($_POST['guardar_ticket']) || isset($_POST['guardar_ticket_woo']) || isset($_POST['confirmar_edicion']) || isset($_POST['confirmar_edicion_woo'])) {

        if (!isset($_POST['ctdw_nonce']) || !wp_verify_nonce($_POST['ctdw_nonce'], 'guardar_ticket_nonce')) {
            echo "<div class='notice notice-error'><p>&#10060; Error de seguridad</p></div>"; return;
        }

        $codigo    = sanitize_text_field($_POST['codigo']);
        $dni       = sanitize_text_field($_POST['dni']);
        $nombres   = sanitize_text_field($_POST['nombres']);
        $apellidos = sanitize_text_field($_POST['apellidos']);
        $correo    = sanitize_email($_POST['correo']);
        $telefono  = sanitize_text_field($_POST['telefono']);
        $ciudad    = sanitize_text_field($_POST['ciudad']);
        $pVenta    = sanitize_text_field($_POST['pventa']);

        if (!preg_match('/^TS-\d{5}$/', $codigo)) {
            echo "<div class='notice notice-error'><p>&#10060; C&oacute;digo inv&aacute;lido (ej: TS-00001)</p></div>"; return;
        }
        if (empty($dni) || empty($correo)) {
            echo "<div class='notice notice-error'><p>&#10060; DNI y correo son obligatorios</p></div>"; return;
        }

        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE codigo = %s", $codigo));

        if ($ticket && !isset($_POST['confirmar_edicion']) && !isset($_POST['confirmar_edicion_woo'])) {
            echo "<div class='notice notice-warning'>
                <p>&#9888; El ticket <b>" . esc_html($codigo) . "</b> ya existe (DNI: <b>" . esc_html($ticket->dni) . "</b>)</p>
                <form method='post'>
                    " . wp_nonce_field('guardar_ticket_nonce', 'ctdw_nonce', true, false) . "
                    <input type='hidden' name='codigo' value='" . esc_attr($codigo) . "'>
                    <input type='hidden' name='dni' value='" . esc_attr($dni) . "'>
                    <input type='hidden' name='nombres' value='" . esc_attr($nombres) . "'>
                    <input type='hidden' name='apellidos' value='" . esc_attr($apellidos) . "'>
                    <input type='hidden' name='correo' value='" . esc_attr($correo) . "'>
                    <input type='hidden' name='telefono' value='" . esc_attr($telefono) . "'>
                    <input type='hidden' name='ciudad' value='" . esc_attr($ciudad) . "'>
                    <input type='hidden' name='pventa' value='" . esc_attr($pVenta) . "'>
                    <button name='confirmar_edicion' class='button button-primary'>&#10003; Editar (solo manual)</button>
                    <button name='confirmar_edicion_woo' class='button' style='background:#7b5ea7;color:#fff;border-color:#7b5ea7;margin-left:8px;'>&#10003; Editar + Web</button>
                    <a href='?page=tickets-pro' class='button' style='margin-left:8px;'>&#10060; Cancelar</a>
                </form>
            </div>";

        } elseif ($ticket && (isset($_POST['confirmar_edicion']) || isset($_POST['confirmar_edicion_woo']))) {
            $wpdb->update($table, ['dni' => $dni, 'nombres' => $nombres, 'apellidos' => $apellidos, 'correo' => $correo, 'telefono' => $telefono, 'ciudad' => $ciudad, 'origen' => 'manual'], ['codigo' => $codigo]);
            echo "<div class='notice notice-success'><p>&#10003; Ticket actualizado</p></div>";
            if ($con_woo) {
                ctdw_crear_pedido_woocommerce($codigo, $dni, $nombres, $apellidos, $correo, $telefono, $ciudad);
                echo "<div class='notice notice-success'><p>&#10003; Pedido Web creado</p></div>";
            }

        } else {
            $wpdb->insert($table, ['codigo' => $codigo, 'dni' => $dni, 'nombres' => $nombres, 'apellidos' => $apellidos, 'correo' => $correo, 'telefono' => $telefono, 'ciudad' => $ciudad , 'punto_venta' => $pVenta, 'origen' => 'manual', 'estado' => 'activo']);
            echo "<div class='notice notice-success'><p>&#10003; Ticket registrado manualmente</p></div>";
            if ($con_woo) {
                ctdw_crear_pedido_woocommerce($codigo, $dni, $nombres, $apellidos, $correo, $telefono, $ciudad);
                echo "<div class='notice notice-success'><p>&#10003; Pedido Web creado</p></div>";
            }
        }
    }

    // ==========================
    // BUSCAR DNI
    // ==========================
    $results_manual = [];
    $results_web    = [];
    $dni_buscar     = '';

    if (isset($_POST['buscar'])) {
        $dni_buscar = sanitize_text_field($_POST['dni']);

        $results_manual = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE dni = %s ORDER BY id ASC", $dni_buscar)
        );

        $rows_woo = $wpdb->get_results($wpdb->prepare("
            SELECT om_t.meta_value, om_doc.order_id
            FROM {$wpdb->prefix}wc_orders_meta om_doc
            JOIN {$wpdb->prefix}wc_orders_meta om_t ON om_t.order_id = om_doc.order_id
                AND om_t.meta_key IN ('_ticket_codigos', 'ticket_codigo')
            WHERE om_doc.meta_key = '_billing_num_documento' AND om_doc.meta_value = %s
        ", $dni_buscar));

        foreach ($rows_woo as $row) {
            $raw = $row->meta_value;
            if (str_starts_with($raw, 'a:')) {
                $arr = @unserialize($raw);
                if (is_array($arr)) {
                    foreach ($arr as $cod) {
                        $results_web[] = ['codigo' => $cod, 'order_id' => $row->order_id];
                    }
                }
            } else {
                $results_web[] = ['codigo' => trim($raw), 'order_id' => $row->order_id];
            }
        }
    }

?>

<div class="wrap">
<h1>&#127903; Tickets PRO</h1>

<a href="?page=tickets-pro&export=1" class="button button-primary" style="margin-bottom:16px;">&#128202; Exportar Excel</a>

<hr>

<!-- ===================== REGISTRAR ===================== -->
<h2>&#10133; Registrar Ticket Manual</h2>

<form method="post" role="form" style="max-width:520px;background:#fff;padding:24px;border-radius:10px;border:1px solid #ddd;">
    <?php wp_nonce_field('guardar_ticket_nonce', 'ctdw_nonce'); ?>
    <table style="width:100%;border-collapse:collapse;">
        <tr><td style="padding:5px 0;color:#555;">DNI</td><td><input name="dni" id="dni" placeholder="DNI" required style="width:70%;"><input type="button" id="consultareniec" name="consultareniec" value="Consulta Reniec"> </td></tr>
        <tr><td style="padding:5px 0;width:110px;color:#555;">C&oacute;digo</td><td><input name="codigo" placeholder="TS-00001" required style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Nombres</td><td><input name="nombres" id="nombres" placeholder="Nombres" required style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Apellidos</td><td><input name="apellidos" id="apellidos" placeholder="Apellidos" required style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Correo</td><td><input name="correo" placeholder="Correo" value="TELEMAX.AYACUCHO@GMAIL.COM" required style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Tel&eacute;fono</td><td><input name="telefono" placeholder="Telefono" style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Ciudad</td><td><input name="ciudad" placeholder="Ciudad" style="width:100%;"></td></tr>
        <tr><td style="padding:5px 0;color:#555;">Punto de venta</td><td><input name="pventa" placeholder="Punto de venta" style="width:100%;"></td></tr>
    </table>
    <div style="display:flex;gap:10px;margin-top:14px;">
        <button name="guardar_ticket" class="button button-primary">&#9997; Guardar Manual</button>
        <button name="guardar_ticket_woo" class="button" style="display:none;">&#127760; Guardar + Web</button>
    </div>
    <p style="color:#aaa;font-size:11px;margin-top:8px;">
        <b>Guardar Manual</b>: solo en tabla interna &nbsp;|&nbsp;
        <b>Guardar + Web</b>: tambi&eacute;n crea pedido en WooCommerce
    </p>
<script>
jQuery(document).ready(function($){
    $("#consultareniec").click(function(){
        var dni = $("#dni").val();
        $.ajax({
            type: "POST",
            url: "<?php echo plugin_dir_url(__FILE__); ?>ajax/consulta-dni-reniec.ajax.php",
            data: {dni:dni},
            dataType: 'json',
            success: function(data){
                console.log(data);
                if(data == 1){
                    alert('Ingrese los 8 dígitos del DNI');
                    $("#dni").focus();
                }else{
                    $("#nombres").val(data.nombres);
                    $("#apellidos").val(data.apellidoPaterno + " " + data.apellidoMaterno);
                }
            },
            error:function(xhr){
                console.log(xhr.responseText);
                alert("Error en la consulta");
            }
        });
    });
});
</script>
</form>

<hr>

<!-- ===================== BUSCAR ===================== -->
<h2>&#128269; Buscar por DNI</h2>

<form method="post" style="display:flex;gap:10px;align-items:center;margin-bottom:20px;">
    <input name="dni" placeholder="Ingresa el DNI" required value="<?= esc_attr($dni_buscar) ?>" style="width:220px;">
    <button name="buscar" class="button button-primary">Buscar</button>
</form>

<?php if ($dni_buscar && (isset($_POST['buscar']))): ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- MANUAL -->
    <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:10px;padding:16px;">
        <h3 style="margin:0 0 12px;">&#9997; Manual
            <span style="background:#2e7d32;color:#fff;border-radius:20px;padding:2px 10px;font-size:12px;font-weight:normal;margin-left:6px;"><?= count($results_manual) ?> tickets</span>
        </h3>
        <?php if (!empty($results_manual)): ?>
        <table class="widefat striped">
            <tr><th>Ticket</th><th>Nombre</th><th>Tel&eacute;fono</th><th>Fecha</th></tr>
            <?php foreach($results_manual as $r): ?>
            <tr>
                <td><strong><?= esc_html($r->codigo) ?></strong></td>
                <td><?= esc_html($r->nombres . ' ' . $r->apellidos) ?></td>
                <td><?= esc_html($r->telefono) ?></td>
                <td><?= esc_html($r->created_at) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <p style="color:#888;margin:0;">Sin tickets manuales para este DNI.</p>
        <?php endif; ?>
    </div>

    <!-- WEB -->
    <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:10px;padding:16px;">
        <h3 style="margin:0 0 12px;">&#127760; Web
            <span style="background:#7b5ea7;color:#fff;border-radius:20px;padding:2px 10px;font-size:12px;font-weight:normal;margin-left:6px;"><?= count($results_web) ?> tickets</span>
        </h3>
        <?php if (!empty($results_web)): ?>
        <table class="widefat striped">
            <tr><th>Ticket</th><th>Pedido</th></tr>
            <?php foreach($results_web as $r): ?>
            <tr>
                <td><strong><?= esc_html($r['codigo']) ?></strong></td>
                <td><a href="<?= admin_url('admin.php?page=wc-orders&id=' . intval($r['order_id'])) ?>" target="_blank">#<?= intval($r['order_id']) ?></a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <p style="color:#888;margin:0;">Sin tickets web para este DNI.</p>
        <?php endif; ?>
    </div>

</div>

<?php endif; ?>

</div>

<?php
}

// ==========================
// CREAR PEDIDO WOOCOMMERCE
// ==========================
function ctdw_crear_pedido_woocommerce($codigo, $dni, $nombres, $apellidos, $correo, $telefono, $ciudad) {
    if (!class_exists('WooCommerce')) return;
    $product_id = 123;
    $order = wc_create_order();
    $product = wc_get_product($product_id);
    $order->add_product($product, 1);
    $order->set_address(['first_name' => $nombres, 'last_name' => $apellidos, 'email' => $correo, 'phone' => $telefono, 'city' => $ciudad], 'billing');
    $order->update_meta_data('billing_num_documento', $dni);
    $order->update_meta_data('_billing_num_documento', $dni);
    $order->update_meta_data('ticket_codigo', $codigo);
    $order->calculate_totals();
    $order->payment_complete();
    $order->update_status('completed', 'Pedido automatico desde Tickets PRO');
    $order->save();
}

// ==========================
// BIXO API REST
// ==========================
add_action('rest_api_init', function () {
    register_rest_route('bixo/v1', '/tickets', [
        'methods'             => 'GET',
        'callback'            => 'bixo_api_tickets',
        'permission_callback' => '__return_true',
    ]);
});

function bixo_api_tickets(WP_REST_Request $request) {
    if ($request->get_param('key') !== 'bixo-tickets-2024') {
        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }
    global $wpdb;
    $table  = $wpdb->prefix . 'ctdw_tickets';
    $action = $request->get_param('action') ?: 'buscar';

    if ($action === 'buscar') {
        $dni = sanitize_text_field($request->get_param('dni'));
        if (!$dni) return new WP_REST_Response(['ok' => false, 'error' => 'dni requerido'], 400);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE dni = %s ORDER BY id ASC", $dni), ARRAY_A);
        return new WP_REST_Response(['ok' => true, 'total' => count($rows), 'tickets' => $rows]);
    }

    if ($action === 'listar') {
        $limit  = min((int)($request->get_param('limit') ?: 100), 500);
        $offset = (int)($request->get_param('offset') ?: 0);
        $buscar = sanitize_text_field($request->get_param('buscar') ?: '');
        if ($buscar) {
            $b    = '%' . $wpdb->esc_like($buscar) . '%';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE dni LIKE %s OR nombres LIKE %s OR apellidos LIKE %s OR telefono LIKE %s OR codigo LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
                $b, $b, $b, $b, $b, $limit, $offset
            ), ARRAY_A);
        } else {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset
            ), ARRAY_A);
        }
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table");
        return new WP_REST_Response(['ok' => true, 'total' => $total, 'offset' => $offset, 'tickets' => $rows]);
    }

    if ($action === 'stats') {
        $total    = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table");
        $clientes = (int)$wpdb->get_var("SELECT COUNT(DISTINCT dni) FROM $table");
        $ultimo   = $wpdb->get_var("SELECT created_at FROM $table ORDER BY id DESC LIMIT 1");
        return new WP_REST_Response(['ok' => true, 'total_tickets' => $total, 'total_clientes' => $clientes, 'ultimo' => $ultimo]);
    }

    return new WP_REST_Response(['ok' => false, 'error' => 'action no válida'], 400);
}

// ── BIXO API — Registrar, Editar, Eliminar ──

add_action('rest_api_init', function () {
    register_rest_route('bixo/v1', '/registrar', [
        'methods'             => 'POST',
        'callback'            => 'bixo_api_registrar',
        'permission_callback' => '__return_true',
    ]);
});

function bixo_api_registrar(WP_REST_Request $request) {
    if ($request->get_param('key') !== 'bixo-tickets-2024') {
        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ctdw_tickets';

    $codigo    = sanitize_text_field($request->get_param('codigo'));
    $dni       = sanitize_text_field($request->get_param('dni'));
    $nombres   = sanitize_text_field($request->get_param('nombres'));
    $apellidos = sanitize_text_field($request->get_param('apellidos'));
    $correo    = sanitize_email($request->get_param('correo'));
    $telefono  = sanitize_text_field($request->get_param('telefono'));
    $ciudad    = sanitize_text_field($request->get_param('ciudad'));
    $origen    = sanitize_text_field($request->get_param('origen') ?: 'bixo');
    $estado    = sanitize_text_field($request->get_param('estado') ?: 'activo');

    if (!$codigo) return new WP_REST_Response(['ok' => false, 'error' => 'codigo requerido'], 400);

    $existe = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE codigo = %s", $codigo));
    if ($existe) {
        $wpdb->update($table, compact('dni','nombres','apellidos','correo','telefono','ciudad','estado'), ['codigo' => $codigo]);
    } else {
        $wpdb->insert($table, compact('codigo','dni','nombres','apellidos','correo','telefono','ciudad','origen','estado'));
    }

    return new WP_REST_Response(['ok' => true, 'codigo' => $codigo, 'accion' => $existe ? 'actualizado' : 'creado']);
}

add_action('rest_api_init', function () {
    register_rest_route('bixo/v1', '/editar', [
        'methods'             => 'POST',
        'callback'            => 'bixo_api_editar',
        'permission_callback' => '__return_true',
    ]);
});

function bixo_api_editar(WP_REST_Request $request) {
    if ($request->get_param('key') !== 'bixo-tickets-2024') {
        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ctdw_tickets';

    $codigo    = sanitize_text_field($request->get_param('codigo'));
    $dni       = sanitize_text_field($request->get_param('dni'));
    $nombres   = sanitize_text_field($request->get_param('nombres'));
    $apellidos = sanitize_text_field($request->get_param('apellidos'));
    $correo    = sanitize_email($request->get_param('correo'));
    $telefono  = sanitize_text_field($request->get_param('telefono'));
    $ciudad    = sanitize_text_field($request->get_param('ciudad'));
    $pventa    = sanitize_text_field($request->get_param('punto_venta'));
    $estado    = sanitize_text_field($request->get_param('estado') ?: 'activo');

    if (!$codigo) return new WP_REST_Response(['ok' => false, 'error' => 'codigo requerido'], 400);

    $existe = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE codigo = %s", $codigo));
    if (!$existe) return new WP_REST_Response(['ok' => false, 'error' => 'ticket no encontrado'], 404);

    $wpdb->update($table,
        ['dni'=>$dni,'nombres'=>$nombres,'apellidos'=>$apellidos,'correo'=>$correo,
         'telefono'=>$telefono,'ciudad'=>$ciudad,'punto_venta'=>$pventa,'estado'=>$estado],
        ['codigo' => $codigo]
    );

    return new WP_REST_Response(['ok' => true, 'codigo' => $codigo, 'accion' => 'actualizado']);
}

add_action('rest_api_init', function () {
    register_rest_route('bixo/v1', '/eliminar', [
        'methods'             => 'POST',
        'callback'            => 'bixo_api_eliminar',
        'permission_callback' => '__return_true',
    ]);
});

function bixo_api_eliminar(WP_REST_Request $request) {
    if ($request->get_param('key') !== 'bixo-tickets-2024') {
        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ctdw_tickets';

    $codigo = sanitize_text_field($request->get_param('codigo'));
    if (!$codigo) return new WP_REST_Response(['ok' => false, 'error' => 'codigo requerido'], 400);

    $existe = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE codigo = %s", $codigo));
    if (!$existe) return new WP_REST_Response(['ok' => false, 'error' => 'ticket no encontrado'], 404);

    $wpdb->delete($table, ['codigo' => $codigo]);

    return new WP_REST_Response(['ok' => true, 'codigo' => $codigo, 'accion' => 'eliminado']);
}
