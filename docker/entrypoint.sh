#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# Entrypoint MAXPORT.
#
# Perintah yang tersedia:
#   web        — nginx + php-fpm (default)
#   worker     — pemroses queue
#   scheduler  — penjadwal task Laravel
#   setup      — tunggu DB, jalankan migrasi, cache konfigurasi, lalu selesai
#   artisan …  — jalankan perintah artisan apa pun
#   <lainnya>  — dijalankan apa adanya
#
# Variabel yang memengaruhi perilaku:
#   PORT                    port HTTP yang didengarkan nginx (default 8080).
#                           Railway mengisinya otomatis.
#   RUN_MIGRATIONS_ON_START jalankan migrasi saat "web" start. Dipakai pada
#                           platform tanpa service setup terpisah (Railway).
#   DB_WAIT_ATTEMPTS        jumlah percobaan menunggu database (default 60).
# ---------------------------------------------------------------------------

APP_DIR=/app
cd "$APP_DIR"

log() { printf '[entrypoint] %s\n' "$*" >&2; }

# Kunci aplikasi wajib ada — tanpa itu session dan enkripsi tidak jalan.
require_app_key() {
    if [ -z "${APP_KEY:-}" ]; then
        log "FATAL: APP_KEY belum diisi. Buat dengan: php artisan key:generate --show"
        exit 1
    fi
}

# Railway (dan PaaS lain) menentukan port lewat $PORT saat runtime.
apply_listen_port() {
    local port="${PORT:-8080}"
    sed -i "s/listen \([0-9]\+\);/listen ${port};/" /etc/nginx/nginx.conf
    log "nginx mendengarkan port ${port}."
}

wait_for_database() {
    # Fallback 127.0.0.1 sengaja disamakan dengan default Laravel sendiri
    # (config/database.php) — supaya pengecekan ini tidak pernah "berhasil"
    # terhadap host yang berbeda dari yang benar-benar dipakai Eloquent.
    # Docker Compose (docker-compose.yml/.prod.yml) selalu mengisi DB_HOST
    # eksplisit ke "mysql"; di Railway, DB_HOST wajib diisi lewat variable
    # reference (mis. ${{MySQL.MYSQLHOST}}).
    local host="${DB_HOST:-127.0.0.1}"
    local port="${DB_PORT:-3306}"
    local attempts="${DB_WAIT_ATTEMPTS:-60}"

    if [ -z "${DB_HOST:-}" ]; then
        log "PERINGATAN: DB_HOST tidak diisi, memakai fallback 127.0.0.1 — kemungkinan besar salah di luar localhost."
    fi

    log "Menunggu database di ${host}:${port} ..."

    for i in $(seq 1 "$attempts"); do
        if php -r '
            $host = getenv("DB_HOST") ?: "127.0.0.1";
            $port = getenv("DB_PORT") ?: "3306";
            $conn = @fsockopen($host, (int) $port, $errno, $errstr, 2);
            exit($conn ? 0 : 1);
        '; then
            log "Database siap setelah ${i} percobaan."
            return 0
        fi
        sleep 2
    done

    log "FATAL: database tidak merespons setelah ${attempts} percobaan."
    exit 1
}

optimize() {
    # Cache dibangun ulang tiap start supaya selalu cocok dengan env saat ini.
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

run_migrations() {
    log "Menjalankan migrasi ..."
    php artisan migrate --force
}

run_setup() {
    require_app_key
    wait_for_database

    if [ "${SETUP_RUN_MIGRATE:-true}" = "true" ]; then
        run_migrations
    fi

    if [ "${SETUP_RUN_SEED:-false}" = "true" ]; then
        log "Menjalankan seeder demo ..."
        php artisan db:seed --force
    fi

    if [ "${SETUP_RUN_STORAGE_LINK:-true}" = "true" ]; then
        php artisan storage:link || true
    fi

    optimize
    log "Setup selesai."
}

case "${1:-web}" in
    web)
        require_app_key
        wait_for_database

        # Pada Railway tidak ada service "setup" terpisah, jadi migrasi
        # dijalankan di sini bila diminta.
        if [ "${RUN_MIGRATIONS_ON_START:-false}" = "true" ]; then
            run_migrations
        fi

        optimize
        apply_listen_port
        exec supervisord -c /etc/supervisor/supervisord.conf
        ;;

    worker)
        require_app_key
        wait_for_database
        exec php artisan queue:work \
            --sleep=3 \
            --tries="${QUEUE_TRIES:-3}" \
            --max-time="${QUEUE_MAX_TIME:-3600}" \
            --timeout="${QUEUE_TIMEOUT:-90}"
        ;;

    scheduler)
        require_app_key
        wait_for_database
        exec php artisan schedule:work
        ;;

    setup)
        run_setup
        ;;

    artisan)
        shift
        exec php artisan "$@"
        ;;

    *)
        exec "$@"
        ;;
esac
