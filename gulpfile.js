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
    scripts: {
        // El orden es vital: primero utilidades, luego la lógica principal
        src: [
            'public/js/utils.js',
            'public/js/app.js',
            'public/js/notifications.js'
        ],
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

// Tarea para concatenar y minificar JS
function scripts() {
    return gulp.src(paths.scripts.src)
        .pipe(concat('app.min.js'))
        .pipe(terser())
        .pipe(gulp.dest(paths.scripts.dest));
}

// Función Watcher: Vigila cambios en tiempo real
function watchFiles() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
}

// Exportar tareas
exports.styles = styles;
exports.scripts = scripts;
exports.watch = watchFiles;

// Tarea por defecto: Ejecuta todo y empieza a vigilar
exports.default = gulp.series(gulp.parallel(styles, scripts), watchFiles);