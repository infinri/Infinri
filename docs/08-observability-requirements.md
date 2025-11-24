# Observability & Operations Requirements

**Purpose:** Define monitoring, logging, and debugging requirements  
**Audience:** DevOps, developers, architects  
**Status:** MANDATORY for Phase 1-2

---

## 📐 Overview

This document defines the **minimum observability** required before production. These are hard requirements, not nice-to-haves.

---

## 🎯 Core Requirements (Phase 1-2)

### 1. Request Metrics (RoadRunner + Prometheus)

**What to track:**
- Request count (total, per endpoint)
- Request duration (P50, P95, P99)
- Memory usage per request
- Worker restarts (count, reason)
- Database connection errors
- HTTP status codes (2xx, 4xx, 5xx)

**Implementation:**
```yaml
# .rr.yaml
metrics:
  address: "0.0.0.0:2112"
  
http:
  middleware: ["metrics"]
```

**Endpoints:**
```bash
# Prometheus metrics
curl http://localhost:2112/metrics

# Example output:
# http_requests_total{method="GET",status="200"} 1523
# http_request_duration_seconds{quantile="0.95"} 0.145
# roadrunner_worker_memory_bytes 134217728
# roadrunner_worker_restarts_total 3
```

---

### 2. Structured Logging (JSON + Correlation ID)

**Format:** JSON per request

**Required fields:**
- `correlation_id` - Unique request ID
- `timestamp` - ISO 8601
- `level` - debug, info, warning, error
- `message` - Log message
- `context` - Array of contextual data
- `request` - HTTP method, URI, IP
- `user_id` - If authenticated

**Implementation:**
```php
// app/Core/Log/Logger.php
class Logger implements LoggerInterface {
    public function log($level, $message, array $context = []): void {
        $entry = [
            'correlation_id' => request()->correlationId(),
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'request' => [
                'method' => request()->method(),
                'uri' => request()->uri(),
                'ip' => request()->ip(),
            ],
            'user_id' => auth()->id() ?? null,
            'memory' => memory_get_usage(true),
        ];
        
        file_put_contents(
            storage_path('logs/app.log'),
            json_encode($entry) . PHP_EOL,
            FILE_APPEND
        );
    }
}
```

**Example log entry:**
```json
{
  "correlation_id": "req_8f4e2d1c",
  "timestamp": "2025-11-24T17:30:45+00:00",
  "level": "error",
  "message": "Database connection failed",
  "context": {
    "exception": "PDOException",
    "code": 2002,
    "host": "127.0.0.1:5432"
  },
  "request": {
    "method": "POST",
    "uri": "/api/contact",
    "ip": "192.168.1.100"
  },
  "user_id": null,
  "memory": 45678912
}
```

---

### 3. Health Dashboard (Protected Route)

**Route:** `GET /health` (protected by IP whitelist or auth)

**Response format:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-24T17:30:45+00:00",
  "app": {
    "name": "Infinri",
    "version": "0.1.0",
    "environment": "production"
  },
  "system": {
    "uptime_seconds": 86400,
    "memory_usage_mb": 512,
    "memory_limit_mb": 1024,
    "cpu_load": [1.2, 1.5, 1.8]
  },
  "roadrunner": {
    "workers": {
      "active": 4,
      "total": 4,
      "max_jobs": 1000
    },
    "requests_served": 15234,
    "avg_memory_mb": 128
  },
  "database": {
    "status": "connected",
    "connections": 3,
    "last_migration": "2025-11-20T10:00:00+00:00"
  },
  "cache": {
    "driver": "file",
    "hits": 8912,
    "misses": 1234,
    "hit_rate": 0.878
  },
  "queue": {
    "pending": 0,
    "failed": 0,
    "processed_today": 452
  }
}
```

**Implementation:**
```php
// app/Core/Http/Controllers/HealthController.php
class HealthController {
    public function index(): JsonResponse {
        return response()->json([
            'status' => $this->getOverallStatus(),
            'timestamp' => now()->toIso8601String(),
            'app' => $this->getAppInfo(),
            'system' => $this->getSystemInfo(),
            'roadrunner' => $this->getRoadRunnerInfo(),
            'database' => $this->getDatabaseInfo(),
            'cache' => $this->getCacheInfo(),
            'queue' => $this->getQueueInfo(),
        ]);
    }
    
    private function getOverallStatus(): string {
        // Check critical services
        if (!$this->isDatabaseConnected()) {
            return 'unhealthy';
        }
        
        if ($this->getMemoryUsage() > 0.9) {
            return 'degraded';
        }
        
        return 'healthy';
    }
}
```

---

### 4. Error Tracking (Exceptions + Stack Traces)

**What to track:**
- All exceptions (caught and uncaught)
- Stack traces
- Request context
- User context
- Environment variables (sanitized)

**Implementation:**
```php
// app/Core/Exceptions/Handler.php
class ExceptionHandler {
    public function report(Throwable $e): void {
        logger()->error($e->getMessage(), [
            'exception' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'previous' => $e->getPrevious()?->getMessage(),
        ]);
        
        // Send to external service (Phase 4+)
        // sentry()->captureException($e);
    }
}
```

---

## 📊 Phase-Specific Requirements

### Phase 1: Container & Config

**Minimum:**
- [ ] Logger service registered in container
- [ ] Correlation ID middleware
- [ ] JSON logging to file
- [ ] Basic health check route (`/health`)

**Metrics:**
- Container resolution time
- Config load time
- Memory usage after bootstrap

---

### Phase 2: HTTP & Router

**Minimum:**
- [ ] Request timing middleware
- [ ] HTTP status code logging
- [ ] Route matching errors logged
- [ ] Middleware execution timing

**Metrics:**
- Route resolution time
- Middleware execution time per middleware
- Controller dispatch time
- Response generation time

**Health check additions:**
- Router status
- Registered routes count
- Middleware stack

---

### Phase 3: Database & Schema

**Minimum:**
- [ ] Database connection errors logged
- [ ] Query execution time logged (>100ms)
- [ ] Schema migration history
- [ ] Connection pool status

**Metrics:**
- Database query count
- Query duration (P50, P95, P99)
- Connection pool usage
- Failed queries

**Health check additions:**
- Database connection status
- Last migration applied
- Connection count vs limit

---

## 🚨 Alert Thresholds

**Critical (immediate action):**
- Database connection failures
- Memory usage > 90%
- Error rate > 5%
- Worker crashes

**Warning (investigate soon):**
- P95 response time > 500ms
- Memory usage > 75%
- Error rate > 1%
- Worker restarts > 10/hour

**Info (monitor):**
- P95 response time > 300ms
- Memory usage > 50%
- Worker restarts > 5/hour

---

## 📝 Log Retention

**Local files:**
- Last 7 days kept locally
- Rotated daily
- Compressed after 1 day

**Remote (Phase 4+):**
- All logs sent to external service
- 90 days retention
- Searchable and filterable

---

## 🔍 Debugging Workflow

### When Things Go Wrong

**Step 1: Check health dashboard**
```bash
curl -H "X-Admin-Token: secret" https://infinri.com/health
```

**Step 2: Check Prometheus metrics**
```bash
curl http://localhost:2112/metrics | grep error
```

**Step 3: Search logs by correlation ID**
```bash
# Find all logs for a specific request
cat var/log/app.log | jq 'select(.correlation_id=="req_8f4e2d1c")'
```

**Step 4: Check recent errors**
```bash
# Last 10 errors
cat var/log/app.log | jq 'select(.level=="error")' | tail -10
```

**Step 5: Check worker status**
```bash
# RoadRunner status
curl http://localhost:8080/health
```

---

## 🎯 Success Criteria

**Phase 1-2 cannot merge without:**
- ✅ All logs output JSON format
- ✅ Correlation ID on every request
- ✅ `/health` endpoint returns valid JSON
- ✅ Prometheus metrics endpoint works
- ✅ Exception handler logs stack traces

**Production readiness checklist:**
- [ ] Log rotation configured
- [ ] Prometheus endpoint secured (localhost only)
- [ ] Health check secured (IP whitelist)
- [ ] Alert thresholds configured
- [ ] On-call runbook created

---

## 🛠️ Tools & Commands

### Development

**View logs in realtime:**
```bash
tail -f var/log/app.log | jq '.'
```

**Filter by level:**
```bash
cat var/log/app.log | jq 'select(.level=="error")'
```

**Find slow requests:**
```bash
cat var/log/app.log | jq 'select(.context.duration_ms > 500)'
```

### Production

**Check health:**
```bash
curl https://infinri.com/health
```

**View metrics:**
```bash
curl http://localhost:2112/metrics
```

**Restart workers gracefully:**
```bash
systemctl reload roadrunner
```

---

## 📚 Phase Implementation Order

### Phase 1 (Week 1-2)
1. Create Logger service
2. Add correlation ID middleware
3. Implement JSON logging
4. Create basic `/health` endpoint
5. Add exception handler

### Phase 2 (Week 3-4)
1. Add request timing middleware
2. Add Prometheus metrics endpoint
3. Enhance `/health` with RoadRunner stats
4. Add HTTP status code tracking
5. Add route resolution metrics

### Phase 3 (Week 5-6)
1. Add database query logging
2. Add connection pool monitoring
3. Enhance `/health` with DB status
4. Add slow query detection (>100ms)
5. Add migration history tracking

---

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Status:** MANDATORY - No production deployment without these  
**Next Review:** After Phase 2 completion
