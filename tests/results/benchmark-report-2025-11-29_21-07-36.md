# Infinri Framework Benchmark Report

**Generated:** 2025-11-29_21-07-36  
**PHP Version:** 8.4.15  
**Quick Mode:** No  

---

## autoloader

**Status:** completed  
**Duration:** 0.72 seconds  
**Memory:** 6.00 MB  

### Detailed Results

```
========================================
  Autoloader Performance Benchmark
========================================

Discovered 159 classes for testing

Benchmarking Classmap Load... Done
Benchmarking PSR-4 Resolution... Done
Benchmarking file_exists Penalties... Done
Benchmarking OPcache Impact... Skipped (OPcache disabled)
Benchmarking Autoload Hit Ratio... Done
Benchmarking Cold vs Warm Load... Done

========================================
  Results
========================================

Classmap Load:
  Avg us: 6715.39 µs
  Min us: 5894.40 µs
  Max us: 11597.59 µs
  Entries: 4810
  Iterations: 100

PSR-4 Resolution (Cached):
  Avg ns: 725 ns
  Ops per sec: 1,378,569
  Classes tested: 159

PSR-4 Resolution (Miss):
  Avg us: 11.14 µs
  Ops per sec: 89,783
  Classes tested: 100

file_exists (Hit):
  Avg ns: 2139 ns
  Ops per sec: 467,611
  Files tested: 100

file_exists (Miss):
  Avg ns: 1699 ns
  Ops per sec: 588,467
  Files tested: 100

OPcache Impact:
  Skipped (OPcache disabled)

Autoload Hit Ratio:
  Classes tested: 159
  Successfully loaded: 139
  Failed to load: 20
  Newly autoloaded: 141
  Hit ratio: 87.42%

Class Load (Warm):
  Avg ns: 2385 ns
  Ops per sec: 419,301

get_class():
  Avg ns: 884 ns
  Ops per sec: 1,131,465

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 12.00 MB
  OPcache: Disabled

```

---

## module

**Status:** completed  
**Duration:** 0.21 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Module Scalability Benchmark
========================================

Benchmarking 1 modules... Done
Benchmarking 10 modules... Done
Benchmarking 50 modules... Done
Benchmarking 100 modules... Done
Benchmarking 250 modules... Done

Analyzing scalability patterns... Done

========================================
  Results
========================================

Modules (1):
  Boot Time: 0.316 ms (min: 0.263, max: 0.470)
  Registration Time: 0.303 ms
  Resolution Time: 0.165 ms
  Memory Growth: 0.00 MB
  Memory Per Module: 0.00 KB

Modules (10):
  Boot Time: 0.294 ms (min: 0.270, max: 0.346)
  Registration Time: 0.283 ms
  Resolution Time: 1.193 ms
  Memory Growth: 0.00 MB
  Memory Per Module: 0.00 KB

Modules (50):
  Boot Time: 0.391 ms (min: 0.316, max: 0.516)
  Registration Time: 0.377 ms
  Resolution Time: 5.784 ms
  Memory Growth: 0.00 MB
  Memory Per Module: 0.00 KB

Modules (100):
  Boot Time: 0.445 ms (min: 0.352, max: 0.553)
  Registration Time: 0.426 ms
  Resolution Time: 8.109 ms
  Memory Growth: 0.00 MB
  Memory Per Module: 0.00 KB

Modules (250):
  Boot Time: 0.351 ms (min: 0.299, max: 0.388)
  Registration Time: 0.334 ms
  Resolution Time: 5.919 ms
  Memory Growth: 0.00 MB
  Memory Per Module: 0.00 KB

========================================
  Scalability Analysis
========================================
  Pattern: Excellent (Sub-linear / Logarithmic)
  Average Efficiency: 4.86

  Growth Ratios:
    1 → 10 modules: 10.00x modules, 0.93x time (efficiency: 10.76)
    10 → 50 modules: 5.00x modules, 1.33x time (efficiency: 3.75)
    50 → 100 modules: 2.00x modules, 1.14x time (efficiency: 1.76)
    100 → 250 modules: 2.50x modules, 0.79x time (efficiency: 3.17)

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 12.00 MB

```

---

## middleware

**Status:** completed  
**Duration:** 0.45 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Middleware Pipeline Benchmark
========================================

Benchmarking 0 middleware... Done
Benchmarking 1 middleware... Done
Benchmarking 5 middleware... Done
Benchmarking 10 middleware... Done
Benchmarking 25 middleware... Done
Benchmarking Real Middleware... Done
Benchmarking Middleware Types... Done
Analyzing Per-Layer Cost... Done

========================================
  Results
========================================

Pipeline Scaling:
--------------------------------------------------------------------------------
Configuration                     Avg (µs)    Per Layer      Ops/sec    Memory Churn
--------------------------------------------------------------------------------
Pipeline (0 middleware)                8.61         0.00         116,129           20 bytes
Pipeline (1 middleware)               12.74        12.74          78,481           20 bytes
Pipeline (5 middleware)               24.72         4.94          40,446           20 bytes
Pipeline (10 middleware)              44.34         4.43          22,550           20 bytes
Pipeline (25 middleware)              87.06         3.48          11,486           20 bytes

Real Middleware Performance:
------------------------------------------------------------
  Real: SecurityHeaders: 33.48 µs (29,872 ops/sec)
  Real: RequestTiming: 53.76 µs (18,601 ops/sec)
  Real: Metrics: 129.20 µs (7,740 ops/sec)

Middleware Type Performance:
------------------------------------------------------------
  Type: Pass-through: 4.11 µs (243,156 ops/sec)
  Type: Header Modification: 7.56 µs (132,242 ops/sec)
  Type: Request Inspection: 6.76 µs (148,027 ops/sec)
  Type: Array Manipulation: 6.86 µs (145,861 ops/sec)

========================================
  Per-Layer Analysis
========================================
  Pipeline Baseline: 8.61 µs
  Avg Cost Per Layer: 3.47 µs
  Meets Goal (<30µs): YES
  Rating: Excellent

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 12.00 MB
  Goal: < 30µs per middleware layer

```

---

## routing

**Status:** completed  
**Duration:** 0.49 seconds  
**Memory:** 2.00 MB  

### Detailed Results

```
========================================
  Routing Precision Benchmark
========================================

Benchmarking Route Types... Done
Benchmarking Route Scale... Done
Benchmarking Route Position... Done
Benchmarking HTTP Methods... Done
Analyzing Scaling... Done

========================================
  Results
========================================

Route Type Performance:
----------------------------------------------------------------------
Type                         Avg (µs)    Max (µs)         Ops/sec
----------------------------------------------------------------------
Static                           20.85        75.06          47,955
Dynamic (1 param)                21.88        71.41          45,696
Dynamic (2 params)               22.62        95.05          44,209
Regex (digits)                   72.20       246.56          13,850
Regex (path)                     74.95       131.96          13,343
Grouped                          23.88       105.90          41,876
Nested Group                     19.37        56.60          51,626

Route Scale Performance:
--------------------------------------------------------------------------------
Routes           First (µs) Middle (µs)   Last (µs)   Miss (µs)    Avg (µs)
--------------------------------------------------------------------------------
100                    21.64        20.38        25.72        77.02        22.58
500                    21.08        19.20        19.75        75.64        20.01
1,000                  19.91        20.12        20.20        79.16        20.08

HTTP Method Performance:
--------------------------------------------------
  GET: 18.44 µs (54,231 ops/sec)
  POST: 19.67 µs (50,841 ops/sec)
  PUT: 18.67 µs (53,548 ops/sec)
  PATCH: 18.58 µs (53,820 ops/sec)
  DELETE: 20.47 µs (48,842 ops/sec)

Position Impact (500 routes):
--------------------------------------------------
  Route #0: 18.21 µs
  Route #50: 19.64 µs
  Route #100: 19.06 µs
  Route #250: 18.60 µs
  Route #499: 19.60 µs
  Position Sensitivity: 1.08x

========================================
  Scaling Analysis
========================================
  Route Multiplier: 10x
  Time Multiplier: 0.89x
  Pattern: O(1) - Constant (Excellent)
  Efficiency Score: 11.25

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 14.00 MB

```

---

## database

**Status:** completed  
**Duration:** 4.37 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Database & ORM Benchmark
========================================

Connecting to infinri_test@127.0.0.1... Creating test tables... Done
Seeding database... Done
Benchmarking Simple SELECT... Done
Benchmarking Prepared Statements... Done
Benchmarking JOINs... Done
Benchmarking Bulk Inserts... Done
Benchmarking Hydration... Done
Benchmarking Model Hydration... Done
Benchmarking Large Dataset... Done

========================================
  Results
========================================

SELECT (Single Row):
  Avg us: 704.05 µs
  Ops per sec: 1,420

SELECT (Multiple Rows):
  Avg us: 1009.89 µs
  Ops per sec: 990

SELECT (All 100 Rows):
  Avg us: 875.92 µs
  Ops per sec: 1,142

Prepared Statement:
  Avg us: 731.79 µs
  Ops per sec: 1,367

JOIN (Simple):
  Avg us: 784.93 µs
  Ops per sec: 1,274

JOIN (Triple):
  Avg us: 1037.85 µs
  Ops per sec: 964

INSERT (Single):
  Avg us: 1606.79 µs
  Ops per sec: 622

INSERT (100 in Transaction):
  Avg us: 74658.55 µs
  Per row us: 746.59 µs
  Rows per sec: 1,339

Hydration (Array, 50 rows):
  Avg us: 651.27 µs
  Rows: 50
  Per row us: 13.03 µs

Model Hydration (Single):
  Avg ns: 18171 ns
  Ops per sec: 55,032
  Memory per instance bytes: 21 bytes

Model Hydration (100 instances):
  Avg us: 1799.72 µs
  Per model ns: 17997 ns
  Models per sec: 55,564

Large Query (500 posts + aggregation):
  Avg ms: 3.37 ms

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 14.00 MB

```

---

## reflection

**Status:** completed  
**Duration:** 0.05 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Reflection Costs Audit
========================================

Benchmarking ReflectionClass Creation... Done
Benchmarking Constructor Analysis... Done
Benchmarking Parameter Analysis... Done
Benchmarking Method Analysis... Done
Benchmarking Property Analysis... Done
Benchmarking Container Resolution... Done
Benchmarking Cached vs Uncached... Done
Benchmarking Autowiring... Done

========================================
  Results
========================================

ReflectionClass: stdClass:
  Avg ns: 909 ns
  Min ns: 673
  Max ns: 14260
  Ops per sec: 1,099,797

ReflectionClass: Container:
  Avg ns: 843 ns
  Min ns: 608
  Max ns: 2308
  Ops per sec: 1,186,323

ReflectionClass: Application:
  Avg ns: 843 ns
  Min ns: 620
  Max ns: 1890
  Ops per sec: 1,186,382

Constructor (None):
  Avg ns: 579 ns
  Ops per sec: 1,727,704

Constructor (3 params):
  Avg ns: 1749 ns
  Ops per sec: 571,827

Parameter Type Analysis (5 params):
  Avg ns: 5655 ns
  Per param ns: 1131 ns
  Ops per sec: 176,830

Default Value Analysis (3 params):
  Avg ns: 2193 ns
  Ops per sec: 456,048

getMethods (20 methods):
  Avg ns: 2289 ns
  Ops per sec: 436,958

getMethod (single):
  Avg ns: 936 ns
  Ops per sec: 1,067,899

getProperties (4 props):
  Avg ns: 1329 ns
  Ops per sec: 752,614

Property Attribute Analysis:
  Avg ns: 3985 ns
  Per prop ns: 996 ns

Container: Singleton:
  Avg ns: 2345 ns
  Ops per sec: 426,461

Container: Factory:
  Avg ns: 4339 ns
  Ops per sec: 230,455

Uncached Full Analysis:
  Avg ns: 1764 ns
  Ops per sec: 566,797

Cached (Reused Reflection):
  Avg ns: 433 ns
  Ops per sec: 2,311,920

Cache Speedup:
  Speedup factor: 4.08x

Manual Resolution:
  Avg ns: 6030 ns
  Ops per sec: 165,841

Full Autowiring (Reflection):
  Avg ns: 9945 ns
  Ops per sec: 100,555

========================================
  Recommendations
========================================

  PHP Version: 8.4.15
  Peak Memory: 14.00 MB

```

---

## serialization

**Status:** completed  
**Duration:** 2.69 seconds  
**Memory:** 4.00 MB  

### Detailed Results

```
========================================
  Serialization Benchmark
========================================

Benchmarking JSON Encode... Done
Benchmarking JSON Decode... Done
Benchmarking PHP Serialize... Done
Benchmarking PHP Unserialize... Done
Benchmarking var_export... Done
Benchmarking Array Conversion... Done
Benchmarking Cache Payloads... Done
Benchmarking Config Parsing... Done
Benchmarking Large Payloads... Done

========================================
  Results
========================================

JSON Encode:
------------------------------------------------------------
  Small Array:
    Avg us: 1.02 µs
    Ops per sec: 977,639
    Output bytes: 36
  Medium Array:
    Avg us: 55.97 µs
    Ops per sec: 17,868
    Output bytes: 3,801
  Nested Array:
    Avg us: 36.27 µs
    Ops per sec: 27,568
    Output bytes: 8,728
  String Heavy:
    Avg us: 32.10 µs
    Ops per sec: 31,151
    Output bytes: 14,151
  Large 10KB:
    Avg ms: 0.022 ms
    Actual bytes: 7,447
    Throughput mb sec: 325.40 MB/s
  Large 100KB:
    Avg ms: 0.215 ms
    Actual bytes: 74,753
    Throughput mb sec: 332.35 MB/s
  Large 1MB:
    Avg ms: 2.187 ms
    Actual bytes: 765,406
    Throughput mb sec: 333.82 MB/s

JSON Decode:
------------------------------------------------------------
  JSON Encode: Small Array:
    Avg us: 1.02 µs
    Ops per sec: 977,639
    Output bytes: 36
  JSON Encode: Medium Array:
    Avg us: 55.97 µs
    Ops per sec: 17,868
    Output bytes: 3,801
  JSON Encode: Nested Array:
    Avg us: 36.27 µs
    Ops per sec: 27,568
    Output bytes: 8,728
  JSON Encode: String Heavy:
    Avg us: 32.10 µs
    Ops per sec: 31,151
    Output bytes: 14,151
  Large JSON Encode: 10KB:
    Avg ms: 0.022 ms
    Actual bytes: 7,447
    Throughput mb sec: 325.40 MB/s
  Large JSON Encode: 100KB:
    Avg ms: 0.215 ms
    Actual bytes: 74,753
    Throughput mb sec: 332.35 MB/s
  Large JSON Encode: 1MB:
    Avg ms: 2.187 ms
    Actual bytes: 765,406
    Throughput mb sec: 333.82 MB/s

Serialize:
------------------------------------------------------------
  stdClass:
    Avg us: 0.79 µs
    Ops per sec: 1,266,509
    Output bytes: 54
  Array:
    Avg us: 0.82 µs
    Ops per sec: 1,223,884
    Output bytes: 84
  Complex Object:
    Avg us: 1.27 µs
    Ops per sec: 789,938
    Output bytes: 272
  Cache: Round-trip:
    Avg us: 54.06 µs
    Ops per sec: 18,499
    Payload bytes: 38,164

Unserialize:
------------------------------------------------------------
  Small:
    Avg us: 0.84 µs
    Ops per sec: 1,183,765
    Input bytes: 41
  Medium:
    Avg us: 14.59 µs
    Ops per sec: 68,550
    Input bytes: 3,298
  Object:
    Avg us: 1.81 µs
    Ops per sec: 553,137
    Input bytes: 272

var_export:
------------------------------------------------------------
  (Config):
    Avg us: 3.51 µs
    Ops per sec: 284,715
    Output bytes: 292

Object:
------------------------------------------------------------
  to Array (Cast):
    Avg ns: 415 ns
    Ops per sec: 2,409,702
  to Array (get_object_vars):
    Avg ns: 696 ns
    Ops per sec: 1,437,600
  Array to (Cast):
    Avg ns: 381 ns
    Ops per sec: 2,622,710

Cache:
------------------------------------------------------------
  Include (Cached Config):
    Avg us: 13.41 µs
    Ops per sec: 74,570
  JSON Round-trip:
    Avg us: 194.33 µs
    Ops per sec: 5,146
    Payload bytes: 34,278

Config:
------------------------------------------------------------
  PHP Include:
    Avg us: 13.33 µs
    Ops per sec: 75,035
  JSON File:
    Avg us: 9.53 µs
    Ops per sec: 104,928

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 20.00 MB

```

---

## config

**Status:** completed  
**Duration:** 0.33 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Configuration Parser Benchmark
========================================

Benchmarking .env Parsing... Done
Benchmarking Config Load... Done
Benchmarking Config Merge... Done
Benchmarking Dot Notation... Done
Benchmarking Env Overrides... Done
Benchmarking Config Caching... Done
Benchmarking Large Config... Done
Analyzing Boot Time... Done

========================================
  Results
========================================

.env Parse (Line-by-line):
  Avg us: 61.69 µs
  Ops per sec: 16,210
  Lines: 24

.env Parse (Regex):
  Avg us: 16.34 µs
  Ops per sec: 61,211

Config Load (Single File):
  Avg us: 11.73 µs
  Ops per sec: 85,232

Config Load (All Files):
  Avg us: 39.51 µs
  Ops per sec: 25,308
  Files: 3

Config Merge (array_merge):
  Avg ns: 692 ns
  Ops per sec: 1,444,098

Config Merge (Deep):
  Avg ns: 910 ns
  Ops per sec: 1,098,321

Config Merge (Spread):
  Avg ns: 476 ns
  Ops per sec: 2,098,777

Access: Direct Array:
  Avg ns: 434 ns
  Ops per sec: 2,303,516

Access: Dot Notation:
  Avg ns: 2227 ns
  Ops per sec: 448,984

Access: Flattened Cache:
  Avg ns: 387 ns
  Ops per sec: 2,581,498

Env: getenv():
  Avg ns: 682 ns
  Ops per sec: 1,465,382

Env: $_ENV:
  Avg ns: 370 ns
  Ops per sec: 2,702,520

Env: Combined Lookup:
  Avg ns: 381 ns
  Ops per sec: 2,624,162

Config Cache: Write:
  Avg us: 133.66 µs
  File size: 1279

Config Cache: Read:
  Avg us: 21.91 µs
  Ops per sec: 45,647

Large Config (100 sections):
  Avg us: 630.52 µs
  File size kb: 46.79 KB

Full Config Boot:
  Avg us: 917.87 µs
  Meets goal: NO (> 150µs)
  Rating: Poor

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 20.00 MB
  Goal: < 150 µs for config boot

```

---

## error

**Status:** completed  
**Duration:** 0.23 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Error Handling & Recovery Benchmark
========================================

Benchmarking Exception Creation... Done
Benchmarking Exception Throw/Catch... Done
Benchmarking Exception Chains... Done
Benchmarking Stack Trace... Done
Benchmarking Error Handler Overhead... Done
Benchmarking Logging Under Load... Done
Benchmarking Recovery Patterns... Done

========================================
  Results
========================================

Exception Creation (Standard):
  Avg ns: 1323 ns
  Ops per sec: 755,664

Exception Creation (Runtime):
  Avg ns: 1559 ns
  Ops per sec: 641,482

Exception Creation (With Previous):
  Avg ns: 2400 ns
  Ops per sec: 416,731

Throw/Catch (Simple):
  Avg us: 18.48 µs
  Ops per sec: 54,122

Throw/Catch (Function):
  Avg us: 23.82 µs
  Ops per sec: 41,989

Throw/Catch (10 Deep):
  Avg us: 55.22 µs
  Ops per sec: 18,109

Exception Chain (1 deep):
  Avg us: 1.33 µs
  Ops per sec: 751,160

Exception Chain (3 deep):
  Avg us: 3.73 µs
  Ops per sec: 267,862

Exception Chain (5 deep):
  Avg us: 5.99 µs
  Ops per sec: 166,919

Exception Chain (10 deep):
  Avg us: 11.80 µs
  Ops per sec: 84,712

Chain Traversal (10 deep):
  Avg us: 6.67 µs
  Ops per sec: 149,903

getTrace():
  Avg ns: 604 ns
  Ops per sec: 1,656,005

getTraceAsString():
  Avg ns: 967 ns
  Ops per sec: 1,033,871

debug_backtrace():
  Avg us: 1.00 µs
  Ops per sec: 1,003,856

debug_backtrace (limited):
  Avg ns: 996 ns
  Ops per sec: 1,003,654

Error (No Handler):
  Avg us: 0.77 µs
  Ops per sec: 1,291,686

Error (Simple Handler):
  Avg us: 1.29 µs
  Ops per sec: 772,258

Error (Logging Handler):
  Avg us: 1.86 µs
  Ops per sec: 538,947

Log Write (Append):
  Avg us: 11.81 µs
  Ops per sec: 84,695

Log Write (Buffered):
  Avg us: 1.85 µs
  Ops per sec: 540,795

error_log():
  Avg us: 9.71 µs
  Ops per sec: 102,985

Try-Catch-Finally:
  Avg us: 11.54 µs
  Ops per sec: 86,657

Multiple Catch Blocks:
  Avg us: 24.06 µs
  Ops per sec: 41,568

Retry Pattern (3 attempts):
  Avg us: 25.20 µs
  Ops per sec: 39,678

========================================
  Recommendations
========================================
  - Use limited debug_backtrace() in production
  - Buffer log writes when possible
  - Keep exception chains shallow
  - Use simple error handlers

  PHP Version: 8.4.15
  Peak Memory: 20.00 MB

```

---

## io

**Status:** completed  
**Duration:** 1.28 seconds  
**Memory:** 2.00 MB  

### Detailed Results

```
========================================
  I/O Stress Benchmark
========================================

Benchmarking Log Write Under Load... Done
Benchmarking Concurrent File Writes... Done
Benchmarking Cache Write Patterns... Done
Benchmarking File Rotation... Done
Benchmarking Disk Latency... Done
Benchmarking Buffered vs Unbuffered... Done
Benchmarking Lock Contention... Done

========================================
  Results
========================================

Log Write (100 bytes):
  Avg us: 8.80 µs
  Min us: 8.28 µs
  Max us: 49.82 µs
  P99 us: 13.81 µs
  Ops per sec: 113,635

Log Write (1000 bytes):
  Avg us: 10.09 µs
  Min us: 8.73 µs
  Max us: 43.12 µs
  P99 us: 15.23 µs
  Ops per sec: 99,072

Log Write (5000 bytes):
  Avg us: 14.12 µs
  Min us: 12.91 µs
  Max us: 49.11 µs
  P99 us: 19.89 µs
  Ops per sec: 70,810

Concurrent Write (10 files):
  Avg us: 566.25 µs
  Per file us: 56.62 µs
  Ops per sec: 1,766

Cache Write (JSON):
  Avg us: 70.64 µs
  Ops per sec: 14,157

Cache Read (JSON):
  Avg us: 10.43 µs
  Ops per sec: 95,894

Cache Write (Atomic):
  Avg us: 109.23 µs
  Ops per sec: 9,155

File Rotation:
  Avg us: 52.09 µs
  Ops per sec: 19,198

Write Latency (1KB):
  Avg us: 76.21 µs
  Throughput mb sec: 12.81 MB/s

Read Latency (1KB):
  Avg us: 6.30 µs
  Throughput mb sec: 155.06 MB/s

Write Latency (10KB):
  Avg us: 125.84 µs
  Throughput mb sec: 77.60 MB/s

Read Latency (10KB):
  Avg us: 11.63 µs
  Throughput mb sec: 839.49 MB/s

Write Latency (100KB):
  Avg us: 359.82 µs
  Throughput mb sec: 271.40 MB/s

Read Latency (100KB):
  Avg us: 16.84 µs
  Throughput mb sec: 5800.45 MB/s

Write Latency (1MB):
  Avg us: 1423.49 µs
  Throughput mb sec: 702.50 MB/s

Read Latency (1MB):
  Avg us: 123.32 µs
  Throughput mb sec: 8109.12 MB/s

Unbuffered (1000 writes):
  Total ms: 6.60 ms
  Per write us: 6.60 µs

Buffered (1 write):
  Total ms: 0.20 ms
  Speedup: 33.8x

Stream Buffered:
  Total ms: 1.16 ms
  Speedup vs unbuffered: 5.7x

Write (No Lock):
  Avg us: 71.89 µs
  Ops per sec: 13,911

Write (flock):
  Avg us: 80.00 µs
  Ops per sec: 12,499

Write (Separate Lock):
  Avg us: 68.14 µs
  Ops per sec: 14,676

========================================
  Recommendations
========================================
  - Use buffered writes for high-frequency logging
  - Atomic writes (temp + rename) for cache safety
  - Stream buffers for sequential writes
  - Separate lock files reduce contention

  PHP Version: 8.4.15
  Peak Memory: 24.00 MB

```

---

## longrunning

**Status:** completed  
**Duration:** 0.23 seconds  
**Memory:** 2.00 MB  

### Detailed Results

```
========================================
  Long-Running Benchmark
========================================
  Iterations: 10,000

Benchmarking Memory Growth... . Done
Benchmarking Garbage Collection... Done
Benchmarking Object Churn... Done
Benchmarking Static Leaks... Done
Benchmarking Container Reuse... Done
Analyzing Memory Pattern... Done

========================================
  Results
========================================

Memory Snapshots:
------------------------------------------------------------
Iteration        Memory (MB)    Real (MB)    Peak (MB)
------------------------------------------------------------
0                      22.00        22.00        22.00
1,000                  22.00         9.60        24.00
2,500                  22.00         9.60        24.00
5,000                  22.00         9.60        24.00
7,500                  22.00         9.60        24.00
10,000                 22.00         9.60        24.00

Memory Growth:
  Initial mb: 22
  Final mb: 22
  Growth mb: 0
  Growth per 1k iterations kb: 0
  Peak mb: 24
  Total time sec: 0.07 s
  Iterations per sec: 138,998

GC Enabled:
  Iterations: 10000
  Time ms: 8.99 ms
  Memory growth mb: 0
  Gc runs: 10
  Final collected: 1998
  Overhead per iteration ns: 899.25

GC Disabled:
  Iterations: 10000
  Time ms: 7.23 ms
  Memory growth mb: 2
  Speedup: 1.24

Object Churn:
  Iterations: 10000
  Time ms: 38.86 ms
  Objects per sec: 257,357
  Memory during mb: 0
  Memory after gc mb: 0

Static Leak (No Cleanup):
  Iterations: 10000
  Memory leaked mb: 0
  Cache entries: 10000
  Log entries: 10000
  Bytes per iteration: 0

Static Leak (With Cleanup):
  Iterations: 10000
  Memory used mb: 0
  Memory saved mb: 0
  Reduction percent: 0

Container Reuse:
  Iterations: 10000
  Time ms: 62.36 ms
  Resolutions per sec: 320,726
  Memory growth mb: 0
  Memory per iteration bytes: 0

Memory Analysis:
  Snapshots: 6
  Avg growth rate kb per 1k: 0
  Pattern: Stable (Excellent)
  Leak detected: NO

========================================
  Long-Running Recommendations
========================================
  - Implement periodic GC in worker loops
  - Bound static caches with LRU eviction
  - Monitor memory between requests
  - Use weak references where appropriate
  - Reset application state between workers

  PHP Version: 8.4.15
  Final Memory: 24.00 MB
  Peak Memory: 24.00 MB

```

---

## security

**Status:** completed  
**Duration:** 0.17 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Security & Serialization Safety
========================================

Benchmarking HMAC Operations... Done
Benchmarking Cookie Signing... Done
Benchmarking Tamper Detection... Done
Benchmarking Encryption... Done
Benchmarking Invalid Payload Recovery... Done
Benchmarking Session Serialization... Done
Benchmarking Cache Corruption... Done
Benchmarking Expiration Checks... Done

========================================
  Results
========================================

HMAC (sha256):
  Avg us: 6.27 µs
  Ops per sec: 159,458

HMAC (sha384):
  Avg us: 5.71 µs
  Ops per sec: 175,003

HMAC (sha512):
  Avg us: 5.74 µs
  Ops per sec: 174,331

HMAC Verify (sha256):
  Avg us: 5.60 µs
  Ops per sec: 178,617

Cookie Sign:
  Avg us: 3.70 µs
  Ops per sec: 270,389
  Cookie size: 249

Cookie Verify:
  Avg us: 4.67 µs
  Ops per sec: 214,065

Tamper Check (Valid):
  Avg us: 3.48 µs
  Valid rate: 100

Tamper Check (Tampered):
  Avg us: 3.73 µs
  Detection rate: 100

Tamper Check (Corrupted Sig):
  Avg us: 2.98 µs
  Detection rate: 100

AES-256-CBC Encrypt:
  Avg us: 3.17 µs
  Ops per sec: 315,249

AES-256-CBC Decrypt:
  Avg us: 3.84 µs
  Ops per sec: 260,490

Payload: valid_json:
  Avg us: 1.20 µs
  Success rate: 100
  Recovery rate: 0

Payload: invalid_json:
  Avg us: 17.30 µs
  Success rate: 0
  Recovery rate: 100

Payload: truncated:
  Avg us: 17.09 µs
  Success rate: 0
  Recovery rate: 100

Payload: empty:
  Avg us: 17.40 µs
  Success rate: 0
  Recovery rate: 100

Payload: null_bytes:
  Avg us: 16.34 µs
  Success rate: 0
  Recovery rate: 100

Payload: binary:
  Avg us: 18.03 µs
  Success rate: 0
  Recovery rate: 100

Session: JSON:
  Avg us: 3.73 µs
  Ops per sec: 268,155
  Size: 224

Session: serialize (safe):
  Avg us: 2.77 µs
  Ops per sec: 361,552
  Size: 305

Cache: valid:
  Avg us: 1.50 µs
  Success rate: 100
  Fallback rate: 0

Cache: truncated:
  Avg us: 1.13 µs
  Success rate: 0
  Fallback rate: 100

Cache: garbage:
  Avg us: 1.02 µs
  Success rate: 0
  Fallback rate: 100

Cache: partial_corruption:
  Avg us: 1.17 µs
  Success rate: 0
  Fallback rate: 100

Expiry: valid:
  Avg ns: 679 ns
  Valid rate: 100

Expiry: expired:
  Avg ns: 638 ns
  Valid rate: 0

Expiry: no_expiry:
  Avg ns: 590 ns
  Valid rate: 100

Expiry: DateTime:
  Avg us: 1.05 µs
  Ops per sec: 953,839

========================================
  Security Recommendations
========================================
  - Always use constant-time comparison (hash_equals)
  - Use JSON for session data (safer than serialize)
  - Implement graceful fallback for corrupted data
  - Use HMAC-SHA256 minimum for signatures
  - Validate expiration before using cached data

  PHP Version: 8.4.15
  Peak Memory: 24.00 MB

```

---

## framework

**Status:** completed  
**Duration:** 0.02 seconds  
**Memory:** 0.00 MB  

### Detailed Results

```
========================================
  Infinri Framework Benchmark Suite
========================================

Benchmarking Bootstrap... Done
Benchmarking Container... Done
Benchmarking Router... Skipped (not registered)
Benchmarking Cache... Done
Benchmarking Events... Done
Benchmarking ModuleLoader... Done

========================================
  Results
========================================

Bootstrap:
  Average: 0.404 ms
  Min: 0.377 ms, Max: 0.494 ms
  Iterations: 10

Container (Singleton):
  Average: 1.61 µs
  Throughput: 622,121 ops/sec
  Iterations: 1000

Container (Factory):
  Average: 4.23 µs
  Throughput: 236,347 ops/sec
  Iterations: 1000

Router:
  Skipped
Cache (Write):
  Average: 1.35 µs
  Throughput: 742,235 ops/sec
  Iterations: 1000

Cache (Read):
  Average: 1.73 µs
  Throughput: 577,160 ops/sec
  Iterations: 1000

Events (10 listeners):
  Average: 1.51 µs
  Throughput: 663,632 ops/sec
  Iterations: 1000

ModuleLoader (Registry):
  Average: 46.00 µs
  Iterations: 100

========================================
  Summary
========================================
  PHP Version: 8.4.15
  Peak Memory: 24.00 MB
  OPcache: Disabled

```

---

# Summary

| Metric | Value |
|--------|-------|
| **Total Duration** | 11.25s |
| **Peak Memory** | 24.00 MB |
| **Suites Run** | 13 |
| **Completed** | 13 |
| **Failed** | 0 |
| **Skipped** | 0 |

## Results Overview

| Suite | Status | Duration | Memory |
|-------|--------|----------|--------|
| **autoloader** | ✅ completed | 715ms | 6.00 MB |
| **module** | ✅ completed | 210ms | 0.00 MB |
| **middleware** | ✅ completed | 446ms | 0.00 MB |
| **routing** | ✅ completed | 494ms | 2.00 MB |
| **database** | ✅ completed | 4.37s | 0.00 MB |
| **reflection** | ✅ completed | 55ms | 0.00 MB |
| **serialization** | ✅ completed | 2.69s | 4.00 MB |
| **config** | ✅ completed | 334ms | 0.00 MB |
| **error** | ✅ completed | 232ms | 0.00 MB |
| **io** | ✅ completed | 1.28s | 2.00 MB |
| **longrunning** | ✅ completed | 233ms | 2.00 MB |
| **security** | ✅ completed | 167ms | 0.00 MB |
| **framework** | ✅ completed | 22ms | 0.00 MB |

---

_Report generated by Infinri Benchmark Suite_
