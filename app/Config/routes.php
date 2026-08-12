<?php
/**
 * MAPEO DE RUTAS EXPLÍCITAS
 * Aquí puedes definir nombres de URL personalizados que no coincidan 
 * necesariamente con el nombre del controlador.
 * 
 * Formato: 'url-amigable' => 'Controlador@metodo'
 */
return [
    // Garantías
    'garantia'           => 'Garantia@index',
    'garantia/historial' => 'Garantia@historial',
    'garantia/detalle'   => 'Garantia@detalle',
    'garantia/pdf'       => 'Garantia@pdf',
    'garantia/imprimir'  => 'Garantia@imprimir',

    // Auth
    'login'      => 'Auth@index',
    'logout'     => 'Auth@logout',
    'mi-perfil'  => 'Perfil@index',
    'solicitudes-acceso' => 'Auth@solicitudes',
    
    // Taller - Corrección de rutas con guiones bajos
    'taller/nueva_orden' => 'Taller@nuevaOrden',

    // Catálogo público - rutas con guiones
    'catalogo/procesar-pedido'         => 'Catalogo@procesarPedido',
    'catalogo/agregar-carrito'         => 'Catalogo@agregarCarrito',
    'catalogo/actualizar-carrito'      => 'Catalogo@actualizarCarrito',
    'catalogo/eliminar-carrito'        => 'Catalogo@eliminarCarrito',
    'catalogo/contar-carrito'          => 'Catalogo@contarCarrito',
    'catalogo/limpiar-carrito'         => 'Catalogo@limpiarCarrito',

    // Catálogo staff - gestión de pedidos
    'catalogo/pedidos-pendientes'      => 'Catalogo@pedidosPendientes',
    'catalogo/pedidos-procesados'      => 'Catalogo@pedidosProcesados',
    'catalogo/ver-pedido'              => 'Catalogo@verPedido',
    'catalogo/procesar-pedido-staff'   => 'Catalogo@procesarPedidoStaff',
    'catalogo/cancelar-pedido-staff'   => 'Catalogo@cancelarPedidoStaff',
    'catalogo/notificaciones-pedidos'  => 'Catalogo@notificacionesPedidos',
];