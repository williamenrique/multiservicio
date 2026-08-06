<?php
// Número de prueba (¡Debe incluir código de país! Ej: 54 para Argentina, 52 para México, 58 para Venezuela)
// No agregues el signo "+" ni espacios

//aqui recibe el numero del cliente del catalogo
$telefono_cliente = "584127726366"; 

$mensaje_prueba = "🤖 *Prueba de Catálogo Local*\n\nSe ha procesado un nuevo pedido de repuestos de manera exitosa.";

// Función cURL para enviar los datos a Node.js
$url = 'http://localhost:3000/enviar-pedido';
$data = array('telefono' => $telefono_cliente, 'mensaje' => $mensaje_prueba);
$payload = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

// Mostrar en pantalla la respuesta del servidor Node.js
echo "Respuesta del sistema de WhatsApp: " . $response;
?>