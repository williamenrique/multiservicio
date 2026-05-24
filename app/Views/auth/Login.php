<!DOCTYPE html>
<!-- 
  Vista Principal de Login 
  Utiliza Tailwind CSS y Lucide Icons.
-->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="<?php echo URL_IMG; ?>logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($data['titulo']); ?> | Taller Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?php echo URL_CSS; ?>styles.css">
    <style>
        .glass-login {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        body {
            background: radial-gradient(circle at top right, #0f172a, #020617);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-navy-blue rounded-2xl border border-gray-800 mb-4 shadow-lg">
                <i data-lucide="wrench" class="text-neon-green w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-wider">TALLER<span class="text-neon-green">PRO</span></h1>
            <p class="text-gray-400 text-sm mt-2">Sistema de Gestión Automotriz</p>
        </div>

        <!-- Login Card -->
        <div class="glass-login p-8 rounded-3xl shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 text-center uppercase tracking-widest"><?php echo s($data['titulo']); ?></h2>
            
            <!-- Contenedor dinámico de alertas (errores y sesión activa) -->
            <div id="alert-error" class="hidden mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-xl text-red-500 text-sm font-medium flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                <span id="error-text"></span>
            </div>

            <form id="formLogin" class="space-y-5">
                <input type="hidden" id="csrf_token" value="<?php echo csrf_token(); ?>">

                <div>
                    <label for="usuario" class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Usuario o Correo Electrónico</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
                        <input type="text" id="usuario" 
                               class="w-full bg-slate-900/50 border border-gray-700 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all" 
                               placeholder="Ej: admin o nombre@taller.com" required>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Contraseña</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
                        <input type="password" id="password" 
                               class="w-full bg-slate-900/50 border border-gray-700 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all" 
                               placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" id="btnSubmit" 
                        class="w-full bg-neon-green text-black font-black py-4 rounded-xl shadow-lg shadow-neon-green/20 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-4">
                    Ingresar al Sistema
                </button>
            </form>
            <!-- Agregar esto en app/Views/auth/login.php -->
            <div class="mt-4 text-center">
                <a href="#" id="btnForgotPassword" class="text-xs font-bold text-slate-400 hover:text-neon-green transition-colors uppercase tracking-widest">
                    ¿Olvidaste tu usuario o contraseña?
                </a>
            </div>
        </div>
        <p class="text-center text-gray-600 text-xs mt-8">© <?php echo date('Y'); ?> Taller Pro v1.0 - Gestión Profesional</p>
    </div>

    <script>
        const URLROOT = "<?php echo URLROOT; ?>";
        lucide.createIcons();
    </script>
    <script src="<?php echo URL_JS; ?>utils.js"></script>
    <script src="<?php echo URL_JS; ?>login.js"></script>
</body>
</html>