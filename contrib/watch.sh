#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LIB_DIR="$ROOT_DIR/lib"
PUBLIC_DIR="$ROOT_DIR/public"
CONTAINER_WORKDIR="/var/www/html"
CONTAINER_SERVICE="tigermate"

STATE_DIR="${TMPDIR:-/tmp}/tigermate-asset-cache-watcher"
PID_FILE="$STATE_DIR/watcher.pid"
LOG_FILE="$STATE_DIR/watcher.log"
SNAPSHOT_FILE="$STATE_DIR/snapshot.tsv"

mkdir -p "$STATE_DIR"

run_in_container() {
    local command="${1:-status}"
    docker compose exec "${CONTAINER_SERVICE}" bash -lc \
        "cd '${CONTAINER_WORKDIR}' && TIGERMATE_WATCH_CONTAINER=1 bash contrib/watch.sh ${command}"
}

ensure_container_context() {
    case "${1:-run}" in
        run|start|stop|status|restart)
            if [[ -z "${TIGERMATE_WATCH_CONTAINER:-}" ]]; then
                run_in_container "${1:-status}"
                exit $?
            fi
            ;;
    esac
}

snapshot() {
    find "$LIB_DIR" -type f \
        \( -iname '*.js' -o -iname '*.css' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \
           -o -iname '*.eot' -o -iname '*.woff' -o -iname '*.woff2' -o -iname '*.ttf' \
           -o -iname '*.svg' -o -iname '*.gif' -o -iname '*.html' -o -iname '*.ico' \) \
        -printf '%P\t%T@\n' | sort
}

invalidate_changed_assets() {
    local previous_snapshot="$1"
    local current_snapshot="$2"
    local changed_paths

    changed_paths="$(
        awk -F '\t' '
        NR == FNR { old[$1] = $2; next }
        {
            seen[$1] = 1;
            if (!( $1 in old ) || old[$1] != $2) {
                print $1;
            }
        }
        END {
            for (path in old) {
                if (!(path in seen)) {
                    print path;
                }
            }
        }
    ' "$previous_snapshot" "$current_snapshot" || true
    )"

    [ -n "$changed_paths" ] || return 0

    while IFS= read -r relative_path; do
        [ -n "$relative_path" ] || continue
        local public_file="$PUBLIC_DIR/$relative_path"
        if [ -e "$public_file" ]; then
            rm -f "$public_file"
            printf '[watch-assets] invalidated %s\n' "$relative_path"
        fi
    done <<< "$changed_paths"
}

run_watcher() {
    local next_snapshot
    next_snapshot="$(mktemp)"
    trap 'rm -f "$next_snapshot"' EXIT

    snapshot > "$next_snapshot"
    if [ -f "$SNAPSHOT_FILE" ]; then
        invalidate_changed_assets "$SNAPSHOT_FILE" "$next_snapshot"
    fi
    mv "$next_snapshot" "$SNAPSHOT_FILE"

    printf '[watch-assets] watching %s -> %s\n' "$LIB_DIR" "$PUBLIC_DIR"

    while true; do
        next_snapshot="$(mktemp)"
        snapshot > "$next_snapshot"
        invalidate_changed_assets "$SNAPSHOT_FILE" "$next_snapshot"
        mv "$next_snapshot" "$SNAPSHOT_FILE"
        sleep 1
    done
}

start_watcher() {
    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
        printf 'watch-assets already running (pid %s)\n' "$(cat "$PID_FILE")"
        return 0
    fi

    : > "$LOG_FILE"
    setsid bash "$0" run </dev/null >> "$LOG_FILE" 2>&1 &
    local pid=$!
    echo "$pid" > "$PID_FILE"
    sleep 1
    if kill -0 "$pid" 2>/dev/null; then
        printf 'watch-assets started (pid %s)\n' "$pid"
        return 0
    fi

    rm -f "$PID_FILE"
    printf 'watch-assets failed to start\n' >&2
    exit 1
}

stop_watcher() {
    if [ ! -f "$PID_FILE" ]; then
        printf 'watch-assets is not running\n'
        return 0
    fi

    local pid
    pid="$(cat "$PID_FILE")"
    if kill -0 "$pid" 2>/dev/null; then
        kill "$pid"
        printf 'watch-assets stopped (pid %s)\n' "$pid"
    else
        printf 'watch-assets pid file was stale (%s)\n' "$pid"
    fi
    rm -f "$PID_FILE"
}

watcher_status() {
    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
        printf 'watch-assets running (pid %s)\n' "$(cat "$PID_FILE")"
    else
        printf 'watch-assets stopped\n'
        return 1
    fi
}

case "${1:-run}" in
    run)
        ensure_container_context "${1:-run}"
        run_watcher
        ;;
    start)
        ensure_container_context "${1:-run}"
        start_watcher
        ;;
    stop)
        ensure_container_context "${1:-run}"
        stop_watcher
        ;;
    status)
        ensure_container_context "${1:-run}"
        watcher_status
        ;;
    restart)
        ensure_container_context "${1:-run}"
        stop_watcher
        start_watcher
        ;;
    *)
        printf 'Usage: %s {run|start|stop|status|restart}\n' "$0" >&2
        exit 1
        ;;
esac
