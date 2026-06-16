<?php
/**
 * MAPEO DE RUTAS EXPLÍCITAS
 * Aquí puedes definir nombres de URL personalizados que no coincidan 
 * necesariamente con el nombre del controlador.
 * 
 * Formato: 'url-amigable' => 'Controlador@metodo'
 */
return [
    // Auth
    'login'      => 'Auth@index',
    'logout'     => 'Auth@logout',
    'mi-perfil'  => 'Perfil@index',
    'solicitudes-acceso' => 'Auth@solicitudes',
    
    // Taller - Corrección de rutas con guiones bajos
    'taller/nueva_orden' => 'Taller@nuevaOrden'
];