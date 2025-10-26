#!/bin/sh
# Simple overload monitor: creates /tmp/OVERLOAD when free memory is below threshold
# or disk usage is above threshold. Intended to be run under supervisord in the container.

FLAG=/tmp/OVERLOAD
LOG=/var/log/overload-monitor.log

# Thresholds (can be overridden via env vars)
MEM_AVAILABLE_PCT=${MEM_AVAILABLE_PCT:-10}    # percent of memory available required
DISK_USAGE_PCT=${DISK_USAGE_PCT:-90}         # percent disk used to trigger overload
CHECK_INTERVAL=${CHECK_INTERVAL:-5}          # seconds between checks

log_snapshot() {
  echo "==== snapshot: $(date -u +'%Y-%m-%dT%H:%M:%SZ') ====" >> "$LOG" 2>&1
  echo "Hostname: $(hostname)" >> "$LOG" 2>&1
  echo "Uptime: $(awk '{printf "%d seconds", $1}' /proc/uptime)" >> "$LOG" 2>&1
  echo "Load average: $(cat /proc/loadavg)" >> "$LOG" 2>&1
  echo "CPU count: $(nproc 2>/dev/null || echo unknown)" >> "$LOG" 2>&1
  awk '/model name/ {print "CPU model: " substr($0, index($0,$3)) ; exit}' /proc/cpuinfo 2>/dev/null >> "$LOG" 2>&1 || true
  awk '/MemTotal/ {printf "MemTotal: %d kB\n", $2} /MemAvailable/ {printf "MemAvailable: %d kB\n", $2}' /proc/meminfo 2>/dev/null >> "$LOG" 2>&1 || true

  if [ -f /sys/fs/cgroup/memory/memory.limit_in_bytes ]; then
    mem_limit_bytes=$(cat /sys/fs/cgroup/memory/memory.limit_in_bytes 2>/dev/null || echo "0")
    echo "cgroup.memory.limit_in_bytes: $mem_limit_bytes" >> "$LOG" 2>&1
  fi
  if [ -f /sys/fs/cgroup/memory.max ]; then
    mem_max=$(cat /sys/fs/cgroup/memory.max 2>/dev/null || echo "max")
    echo "cgroup.memory.max: $mem_max" >> "$LOG" 2>&1
  fi

  df -h /var 2>/dev/null >> "$LOG" 2>&1 || true

  echo "Kernel: $(uname -r)" >> "$LOG" 2>&1
  echo "Cmdline: $(cat /proc/cmdline 2>/dev/null)" >> "$LOG" 2>&1
  echo "==== end snapshot ====" >> "$LOG" 2>&1
}

echo "overload-monitor: starting (mem_pct_threshold=$MEM_AVAILABLE_PCT, disk_pct_threshold=$DISK_USAGE_PCT)" >> "$LOG" 2>&1
log_snapshot

while true; do
  # Read memory info (kB) and compute available percent
  if [ -r /proc/meminfo ]; then
    mem_avail_kb=$(awk '/MemAvailable/ {print $2}' /proc/meminfo 2>/dev/null || echo 0)
    mem_total_kb=$(awk '/MemTotal/ {print $2}' /proc/meminfo 2>/dev/null || echo 1)
    if [ "$mem_total_kb" -gt 0 ]; then
      mem_available_pct=$(( (mem_avail_kb * 100) / mem_total_kb ))
    else
      mem_available_pct=0
    fi
  else
    mem_available_pct=100
  fi

  # Disk usage for /var (percent used without % sign)
  disk_pct=$(df --output=pcent /var 2>/dev/null | tail -1 | tr -dc '0-9')
  disk_pct=${disk_pct:-0}

  overloaded=0
  if [ "$mem_available_pct" -lt "$MEM_AVAILABLE_PCT" ]; then
    echo "overload-monitor: low memory: ${mem_available_pct}% available (< ${MEM_AVAILABLE_PCT}%)" >> "$LOG" 2>&1
    overloaded=1
  fi
  if [ "$disk_pct" -ge "$DISK_USAGE_PCT" ]; then
    echo "overload-monitor: high disk usage: ${disk_pct}% used (>= ${DISK_USAGE_PCT}%)" >> "$LOG" 2>&1
    overloaded=1
  fi

  if [ "$overloaded" -eq 1 ]; then
    if [ ! -f "$FLAG" ]; then
      echo "overload-monitor: entering OVERLOADED state at $(date -u +'%Y-%m-%dT%H:%M:%SZ')" >> "$LOG" 2>&1
      touch "$FLAG"
      log_snapshot
    fi
  else
    if [ -f "$FLAG" ]; then
      echo "overload-monitor: leaving OVERLOADED state at $(date -u +'%Y-%m-%dT%H:%M:%SZ')" >> "$LOG" 2>&1
      rm -f "$FLAG"
      log_snapshot
    fi
  fi

  sleep "$CHECK_INTERVAL"
done
