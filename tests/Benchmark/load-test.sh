#!/bin/bash
#
# HTTP Load Testing Script for Infinri Framework
#
# Prerequisites:
#   - Apache Bench (ab): sudo apt install apache2-utils
#   - wrk (optional): https://github.com/wg/wrk
#   - hey (optional): go install github.com/rakyll/hey@latest
#
# Usage:
#   ./load-test.sh [URL] [CONCURRENCY] [REQUESTS]
#
# Examples:
#   ./load-test.sh http://localhost:8000
#   ./load-test.sh http://localhost:8000 100 10000

set -e

# Configuration
URL="${1:-http://localhost:8000}"
CONCURRENCY="${2:-50}"
REQUESTS="${3:-5000}"
DURATION="30s"

echo "========================================"
echo "  Infinri Load Testing Suite"
echo "========================================"
echo ""
echo "Target URL: $URL"
echo "Concurrency: $CONCURRENCY"
echo "Requests: $REQUESTS"
echo ""

# Check for testing tools
HAS_AB=$(command -v ab &> /dev/null && echo "yes" || echo "no")
HAS_WRK=$(command -v wrk &> /dev/null && echo "yes" || echo "no")
HAS_HEY=$(command -v hey &> /dev/null && echo "yes" || echo "no")

if [ "$HAS_AB" = "no" ] && [ "$HAS_WRK" = "no" ] && [ "$HAS_HEY" = "no" ]; then
    echo "Error: No load testing tool found."
    echo "Install one of: ab (apache2-utils), wrk, or hey"
    exit 1
fi

# Warmup
echo "Warming up..."
curl -s "$URL" > /dev/null 2>&1 || true
curl -s "$URL" > /dev/null 2>&1 || true
curl -s "$URL" > /dev/null 2>&1 || true
echo ""

# Test homepage
echo "========================================"
echo "  Test 1: Homepage (GET /)"
echo "========================================"

if [ "$HAS_AB" = "yes" ]; then
    echo ""
    echo "Using Apache Bench:"
    ab -n "$REQUESTS" -c "$CONCURRENCY" -q "$URL/" 2>/dev/null | grep -E "(Requests per second|Time per request|Transfer rate|Complete requests|Failed requests)"
fi

if [ "$HAS_WRK" = "yes" ]; then
    echo ""
    echo "Using wrk:"
    wrk -t4 -c"$CONCURRENCY" -d"$DURATION" "$URL/"
fi

# Test health endpoint
echo ""
echo "========================================"
echo "  Test 2: Health Check (GET /_health)"
echo "========================================"

if [ "$HAS_AB" = "yes" ]; then
    echo ""
    echo "Using Apache Bench:"
    ab -n "$REQUESTS" -c "$CONCURRENCY" -q "$URL/_health" 2>/dev/null | grep -E "(Requests per second|Time per request|Transfer rate|Complete requests|Failed requests)"
fi

# Test static asset
echo ""
echo "========================================"
echo "  Test 3: Static Asset (GET /favicon.ico)"
echo "========================================"

if [ "$HAS_AB" = "yes" ]; then
    echo ""
    echo "Using Apache Bench:"
    ab -n "$REQUESTS" -c "$CONCURRENCY" -q "$URL/favicon.ico" 2>/dev/null | grep -E "(Requests per second|Time per request|Transfer rate|Complete requests|Failed requests)"
fi

# Summary
echo ""
echo "========================================"
echo "  Load Test Complete"
echo "========================================"
echo ""
echo "Tips for improving performance:"
echo "  1. Enable OPcache with validate_timestamps=0"
echo "  2. Run 'php bin/console cache:build'"
echo "  3. Use Redis for sessions and cache"
echo "  4. Enable preloading (PHP 8.2+)"
echo "  5. Tune PHP-FPM worker count"
echo ""
