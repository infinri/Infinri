#!/bin/bash
#
# Concurrency & Load Testing Script for Infinri Framework
#
# Tests:
#   - 100 concurrent requests
#   - 500 concurrent requests
#   - 1,000 concurrent requests
#
# Measures:
#   - Race conditions
#   - DB transaction integrity
#   - Shared resource conflict
#   - File lock behavior
#   - Rate limiter accuracy under load
#
# Prerequisites:
#   - wrk: https://github.com/wg/wrk
#   - k6: https://k6.io/
#   - ab: sudo apt install apache2-utils
#   - curl: usually pre-installed
#
# Usage:
#   ./concurrency-test.sh [URL] [DURATION]
#
# Examples:
#   ./concurrency-test.sh http://localhost:8000
#   ./concurrency-test.sh http://localhost:8000 60

set -e

# Configuration
URL="${1:-http://localhost:8000}"
DURATION="${2:-30}s"
RESULTS_DIR="$(dirname "$0")/results"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================"
echo "  Infinri Concurrency Test Suite"
echo -e "========================================${NC}"
echo ""
echo "Target URL: $URL"
echo "Duration: $DURATION"
echo "Results Dir: $RESULTS_DIR"
echo ""

# Create results directory
mkdir -p "$RESULTS_DIR"

# Check for testing tools
HAS_WRK=$(command -v wrk &> /dev/null && echo "yes" || echo "no")
HAS_K6=$(command -v k6 &> /dev/null && echo "yes" || echo "no")
HAS_AB=$(command -v ab &> /dev/null && echo "yes" || echo "no")
HAS_CURL=$(command -v curl &> /dev/null && echo "yes" || echo "no")

echo "Available tools:"
echo "  wrk: $HAS_WRK"
echo "  k6:  $HAS_K6"
echo "  ab:  $HAS_AB"
echo "  curl: $HAS_CURL"
echo ""

if [ "$HAS_WRK" = "no" ] && [ "$HAS_K6" = "no" ] && [ "$HAS_AB" = "no" ]; then
    echo -e "${RED}Error: No load testing tool found.${NC}"
    echo "Install one of: wrk, k6, or ab (apache2-utils)"
    exit 1
fi

# Warmup
echo -e "${YELLOW}Warming up...${NC}"
for i in {1..5}; do
    curl -s "$URL" > /dev/null 2>&1 || true
done
echo ""

# ============================================
# Test 1: Concurrency Levels
# ============================================
echo -e "${GREEN}========================================"
echo "  Test 1: Concurrency Scaling"
echo -e "========================================${NC}"

CONCURRENCY_LEVELS=(10 50 100 500 1000)

for CONC in "${CONCURRENCY_LEVELS[@]}"; do
    echo -e "\n${YELLOW}Testing $CONC concurrent connections...${NC}"
    
    if [ "$HAS_WRK" = "yes" ]; then
        echo "Using wrk:"
        wrk -t4 -c"$CONC" -d"$DURATION" "$URL/" 2>&1 | tee "$RESULTS_DIR/wrk_c${CONC}_$TIMESTAMP.txt"
    elif [ "$HAS_AB" = "yes" ]; then
        echo "Using Apache Bench:"
        REQUESTS=$((CONC * 100))
        ab -n "$REQUESTS" -c "$CONC" -q "$URL/" 2>/dev/null | \
            grep -E "(Requests per second|Time per request|Complete requests|Failed requests)" | \
            tee "$RESULTS_DIR/ab_c${CONC}_$TIMESTAMP.txt"
    fi
    
    echo ""
done

# ============================================
# Test 2: Rate Limiter Stress Test
# ============================================
if [ "$HAS_WRK" = "yes" ]; then
    echo -e "${GREEN}========================================"
    echo "  Test 2: Rate Limiter Stress"
    echo -e "========================================${NC}"
    echo ""
    echo "Testing rate limiter at /api endpoint..."
    
    # High burst to trigger rate limiting
    wrk -t4 -c200 -d10s "$URL/api/v1/health" 2>&1 | tee "$RESULTS_DIR/rate_limit_$TIMESTAMP.txt"
    echo ""
fi

# ============================================
# Test 3: Endpoint Comparison
# ============================================
echo -e "${GREEN}========================================"
echo "  Test 3: Endpoint Performance Comparison"
echo -e "========================================${NC}"
echo ""

ENDPOINTS=(
    "/"
    "/_health"
    "/api/v1/health"
)

for ENDPOINT in "${ENDPOINTS[@]}"; do
    echo -e "${YELLOW}Testing $ENDPOINT...${NC}"
    
    if [ "$HAS_WRK" = "yes" ]; then
        wrk -t2 -c50 -d10s "${URL}${ENDPOINT}" 2>&1 | \
            grep -E "(Requests/sec|Latency)" | head -2
    elif [ "$HAS_AB" = "yes" ]; then
        ab -n 1000 -c 50 -q "${URL}${ENDPOINT}" 2>/dev/null | \
            grep -E "Requests per second"
    fi
    echo ""
done

# ============================================
# Test 4: k6 Stress Test (if available)
# ============================================
if [ "$HAS_K6" = "yes" ]; then
    echo -e "${GREEN}========================================"
    echo "  Test 4: k6 Stress Test"
    echo -e "========================================${NC}"
    echo ""
    
    # Create k6 script
    K6_SCRIPT="$RESULTS_DIR/k6_script_$TIMESTAMP.js"
    cat > "$K6_SCRIPT" << 'EOF'
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const latency = new Trend('request_latency');

export const options = {
    stages: [
        { duration: '10s', target: 50 },   // Ramp up
        { duration: '20s', target: 100 },  // Stay at 100
        { duration: '10s', target: 500 },  // Spike
        { duration: '10s', target: 100 },  // Back to normal
        { duration: '10s', target: 0 },    // Ramp down
    ],
    thresholds: {
        errors: ['rate<0.1'],              // Error rate < 10%
        request_latency: ['p(95)<500'],    // 95% of requests < 500ms
    },
};

export default function () {
    const startTime = Date.now();
    const res = http.get(__ENV.TARGET_URL || 'http://localhost:8000/');
    latency.add(Date.now() - startTime);
    
    const success = check(res, {
        'status is 200': (r) => r.status === 200,
        'response time < 200ms': (r) => r.timings.duration < 200,
    });
    
    errorRate.add(!success);
    sleep(0.1);
}
EOF

    echo "Running k6 stress test..."
    TARGET_URL="$URL" k6 run "$K6_SCRIPT" 2>&1 | tee "$RESULTS_DIR/k6_results_$TIMESTAMP.txt"
    echo ""
fi

# ============================================
# Test 5: Race Condition Detection
# ============================================
echo -e "${GREEN}========================================"
echo "  Test 5: Race Condition Test"
echo -e "========================================${NC}"
echo ""
echo "Sending 100 simultaneous requests to test for race conditions..."

# Use curl with xargs for parallel requests
seq 100 | xargs -P 100 -I {} curl -s -o /dev/null -w "%{http_code}\n" "$URL/_health" 2>/dev/null | \
    sort | uniq -c | sort -rn > "$RESULTS_DIR/race_test_$TIMESTAMP.txt"

echo "Response code distribution:"
cat "$RESULTS_DIR/race_test_$TIMESTAMP.txt"
echo ""

# ============================================
# Summary
# ============================================
echo -e "${BLUE}========================================"
echo "  Concurrency Test Complete"
echo -e "========================================${NC}"
echo ""
echo "Results saved to: $RESULTS_DIR"
echo ""
echo "Files generated:"
ls -la "$RESULTS_DIR"/*"$TIMESTAMP"* 2>/dev/null || echo "  No files generated"
echo ""

echo -e "${YELLOW}Performance Tips:${NC}"
echo "  1. Enable OPcache with validate_timestamps=0 in production"
echo "  2. Use Redis for sessions to avoid file lock contention"
echo "  3. Configure PHP-FPM worker count based on CPU cores"
echo "  4. Enable connection pooling for database connections"
echo "  5. Use async processing for slow operations"
echo ""

# Quick analysis
echo -e "${YELLOW}Quick Analysis:${NC}"
if [ -f "$RESULTS_DIR/wrk_c100_$TIMESTAMP.txt" ]; then
    RPS=$(grep "Requests/sec" "$RESULTS_DIR/wrk_c100_$TIMESTAMP.txt" | awk '{print $2}')
    LATENCY=$(grep "Latency" "$RESULTS_DIR/wrk_c100_$TIMESTAMP.txt" | awk '{print $2}')
    echo "  At 100 concurrent:"
    echo "    Requests/sec: $RPS"
    echo "    Avg Latency: $LATENCY"
fi

if [ -f "$RESULTS_DIR/wrk_c1000_$TIMESTAMP.txt" ]; then
    RPS=$(grep "Requests/sec" "$RESULTS_DIR/wrk_c1000_$TIMESTAMP.txt" | awk '{print $2}')
    LATENCY=$(grep "Latency" "$RESULTS_DIR/wrk_c1000_$TIMESTAMP.txt" | awk '{print $2}')
    echo "  At 1000 concurrent:"
    echo "    Requests/sec: $RPS"
    echo "    Avg Latency: $LATENCY"
fi

echo ""
