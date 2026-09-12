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

    // Devoluciones
    'devoluciones'           => 'Devoluciones@index',
    'devoluciones/historial' => 'Devoluciones@historial',
    'devoluciones/detalle'   => 'Devoluciones@detalle',
    'devoluciones/pdf'       => 'Devoluciones@pdf',
    'devoluciones/imprimir'  => 'Devoluciones@imprimir',

    // Emails
    'email'                    => 'Email@index',
    'email/listar'             => 'Email@listar',
    'email/compose'            => 'Email@compose',
    'email/enviar'             => 'Email@enviar',
    'email/enviar-plantilla'   => 'Email@enviarConPlantilla',
    'email/getClientes'        => 'Email@getClientes',
    'email/plantillas'         => 'Email@plantillas',
    'email/plantillas/listar'  => 'Email@plantillas',
    'email/plantillas/guardar' => 'Email@guardarPlantilla',
    'email/plantillas/eliminar' => 'Email@eliminarPlantilla',
    'email/plantillas/obtener' => 'Email@obtenerPlantilla',

    // Presupuestos
    'presupuesto'                    => 'Presupuesto@index',
    'presupuesto/listar'             => 'Presupuesto@listar',
    'presupuesto/crear'              => 'Presupuesto@crear',
    'presupuesto/editar'             => 'Presupuesto@editar',
    'presupuesto/ver'                => 'Presupuesto@ver',
    'presupuesto/guardar'            => 'Presupuesto@guardar',
    'presupuesto/actualizar'         => 'Presupuesto@actualizar',
    'presupuesto/cambiarEstado'      => 'Presupuesto@cambiarEstado',
    'presupuesto/eliminar'           => 'Presupuesto@eliminar',
    'presupuesto/pdf'                => 'Presupuesto@pdf',
    'presupuesto/imprimir'           => 'Presupuesto@imprimir',
    'presupuesto/enviarEmail'        => 'Presupuesto@enviarEmail',
    'presupuesto/buscarProductos'    => 'Presupuesto@buscarProductos',
    'presupuesto/buscarClientes'     => 'Presupuesto@buscarClientes',
    'presupuesto/getStats'           => 'Presupuesto@getStats',

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
    'catalogo/listar-pedidos-pendientes-api' => 'Catalogo@listarPedidosPendientesApi',
];