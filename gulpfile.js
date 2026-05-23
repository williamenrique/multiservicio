const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const terser = require('gulp-terser');
const concat = require('gulp-concat');
const rename = require('gulp-rename');

// Rutas de archivos
const paths = {
    styles: {
        src: 'public/css/styles.css',
        dest: 'public/css/'
    },
    vendorStyles: {
        src: 'public/vendor/css/*.css',
        dest: 'public/css/'
    },
    scripts: {
        // El orden es vital: primero utilidades, luego la lógica principal
        src: [
            'public/js/utils.js',
            'public/js/app.js',
            'public/js/notifications.js'
        ],
        dest: 'public/js/'
    },
    vendorScripts: {
        src: 'public/vendor/js/*.js',
        dest: 'public/js/'
    }
};

// Tarea para minificar CSS
function styles() {
    return gulp.src(paths.styles.src)
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.styles.dest));
}

// Tarea para agrupar y minificar CSS de librerías externas
function vendorStyles() {
    return gulp.src(paths.vendorStyles.src)
        .pipe(concat('vendor.min.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest(paths.vendorStyles.dest));
}

// Tarea para concatenar y minificar JS
function scripts() {
    return gulp.src(paths.scripts.src)
        .pipe(concat('app.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.scripts.dest));
}

// Tarea para agrupar y minificar JS de librerías externas
function vendorScripts() {
    // El orden es CRÍTICO: jQuery > SweetAlert > Otros > DataTables
    const vendorFiles = [
        'public/vendor/js/jquery.min.js',
        'public/vendor/js/sweetalert2.all.min.js',
        'public/vendor/js/lucide.min.js',
        'public/vendor/js/toastify.js',
        'public/vendor/js/jquery.dataTables.min.js' // DataTables al final para asegurar que jQuery exista
    ];
    return gulp.src(vendorFiles) // Quitamos allowEmpty para que Gulp avise si falta algún archivo
        .pipe(concat('vendor.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.vendorScripts.dest));
}

// Función Watcher: Vigila cambios en tiempo real
function watchFiles() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
    gulp.watch(paths.vendorStyles.src, vendorStyles);
    gulp.watch(paths.vendorScripts.src, vendorScripts);
}

// Exportar tareas
exports.styles = styles;
exports.vendorStyles = vendorStyles;
exports.scripts = scripts;
exports.vendorScripts = vendorScripts;
exports.watch = watchFiles;

// Tarea por defecto: Ejecuta todo y empieza a vigilar
exports.default = gulp.series(gulp.parallel(styles, vendorStyles, scripts, vendorScripts), watchFiles);