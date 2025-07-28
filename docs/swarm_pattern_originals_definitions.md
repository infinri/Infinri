# 📘 Swarm Framework: Original Pattern Definitions & Examples

This document provides **thorough, extensive, and implementation-ready definitions** of all **invented, reimagined, or heavily extended patterns** used in the Swarm Pattern™ Modular Monolith architecture.

These definitions are written so that **AI models, new developers, and future maintainers** can understand and implement these concepts without ambiguity.

---

## 🧬 1. Swarm Unit Pattern™

### ✅ Definition:

A **SwarmUnit** is an autonomous logic component that observes shared state (the Semantic Mesh) and takes action if certain conditions are met. It replaces controllers, services, and listeners with reactive, rule-driven units.

### 📦 Registration:

SwarmUnits are discovered at runtime using a Component Discovery system that scans designated module paths (e.g., `/SwarmUnits`) or uses tagged service containers. This allows developers to plug in new logic without modifying the core.

### 🧩 Key Interface: that observes shared state (the Semantic Mesh) and takes action if certain conditions are met. It replaces controllers, services, and listeners with reactive, rule-driven units.

### 🧩 Key Interface:

```php
interface SwarmUnitInterface {
  public function triggerCondition(array $mesh): bool;
  public function act(SemanticMesh $mesh): void;
  public function reflect(): array; // Optional: returns metadata, tags, version
}
```

### 💡 Example:

```php
class DeductInventoryOnOrderPaid implements SwarmUnitInterface {
  public function triggerCondition(array $mesh): bool {
    return isset($mesh['order.status']) && $mesh['order.status'] === 'paid';
  }

  public function act(SemanticMesh $mesh): void {
    $sku = $mesh['order.sku'] ?? 'sku-000';
    $qty = $mesh['order.qty'] ?? 1;
    $mesh->set("inventory.$sku", ($mesh["inventory.$sku"] ?? 0) - $qty);
  }
}
```

### ✅ Purpose:

- Central logic unit
- Fully testable in isolation
- Removes need for controller/services/event glue

---

## 🧠 2. Semantic Mesh Pattern™

### ✅ Definition:

The **Semantic Mesh** is a shared, observable, mutable state store that holds all runtime information relevant to the system. It acts like a live context map across all modules.

### 💡 Properties:

- Key-value structured (`string => mixed`)
- Can be scoped per tenant, per user, or globally
- Can use in-memory, Redis, or eventual CRDT storage
- Mesh updates are **atomic** and tracked

### 🧨 Conflict Resolution:

If multiple units attempt to write to the same mesh key, the Mutation Guard pattern applies via one of:

- Compare-and-set guards
- Versioned writes with revision checks
- Explicit priority rules defined in unit metadata (`string => mixed`)
- Can be scoped per tenant, per user, or globally
- Can use in-memory, Redis, or eventual CRDT storage

### 🧩 Example:

```php
$mesh = new SemanticMesh();
$mesh->set('cart.total', 42.50);
$mesh->set('user.role', 'admin');
$value = $mesh->get('cart.total'); // 42.50
```

### ✅ Purpose:

- Removes need for direct method calls
- Becomes a blackboard where logic units operate

---

## 🔁 3. Reactor Loop Pattern

### ✅ Definition:

A **Reactor Loop** is a time- or event-driven execution engine that evaluates all registered SwarmUnits against the current mesh and triggers those whose `triggerCondition()` returns true.

### 💡 Example:

```php
foreach ($units as $unit) {
  if ($unit->triggerCondition($mesh->all())) {
    $unit->act($mesh);
  }
}
```

### ✅ Purpose:

- Powers all logic
- Supports cron, CLI, HTTP request, or daemon-driven execution

---

## 🧠 4. Stigmergic Tracing Pattern

### ✅ Definition:

Inspired by ant pheromone trails, **Stigmergic Tracing** logs the causal path and action metadata of each SwarmUnit execution to form an emergent behavior trace.

### 💡 Example Log Entry:

```json
{
  "unit": "DeductInventoryOnOrderPaid",
  "trigger": "order.status === 'paid'",
  "timestamp": "2025-07-27T17:34:12Z",
  "meshBefore": {...},
  "meshAfter": {...}
}
```

### ✅ Purpose:

- Debugging without step-through tracing
- System observability
- Replay + visualization

### 🛠 Recommended Tools:

- Emit structured JSON logs to `swarm-trace.log`
- Forward trace data to Prometheus, Elasticsearch, or GraphQL endpoints
- Optional: use a visual dashboard to replay traces step-by-step (admin UI plugin)
- System observability
- Replay + visualization

---

## 🧱 5. Swarm Module Packaging Pattern™

### ✅ Definition:

A **Swarm Module** is a fully self-contained feature package that includes:

- SwarmUnits
- Config
- Migrations
- Views / API transforms
- Optional: Mesh seeds or factories

### 📁 Example Structure:

```
/modules
  /Shop
    /SwarmUnits
      PlaceOrder.php
      ApplyCoupon.php
    config.php
    views/
    migrations/
```

### ✅ Purpose:

- Feature-based packaging
- No coupling between modules
- Easy to plug/unplug

---

## 🧭 6. State Snapshot Pattern

### ✅ Definition:

Captures the full or partial mesh state *before and/or after* a unit's execution. Used for rollback, debugging, audit.

### 💡 Usage:

```php
$snapshotBefore = $mesh->snapshot();
$unit->act($mesh);
$snapshotAfter = $mesh->snapshot();
```

### ✅ Purpose:

- Debug diffs
- Temporal rollback
- Auditing flows

---

## 🛡️ 7. Access Guard Pattern

### ✅ Definition:

A **guard** enforces contextual permissions inside SwarmUnits based on mesh state (e.g., user role, plan, status).

### 💡 Example:

```php
if ($mesh['user.role'] !== 'admin') return;
```

Or use injectable Guard class:

```php
if (!$this->guard->allows('update-pricing', $mesh)) return;
```

### ✅ Purpose:

- Replace route middleware
- Context-aware logic gating

---

## ✅ 8. Validation Mesh Pattern

### ✅ Definition:

A reusable validation mechanism to ensure mesh values meet certain requirements before unit logic runs.

### 💡 Example:

```php
if (!$this->validator->has(['order.total', 'user.id'])) return;
```

Or via `triggerCondition()` abstraction.

### ✅ Purpose:

- Clean separation of validation vs logic
- Enforces predictable mesh state

---

## ⏳ 9. Temporal Trigger Pattern

### ✅ Definition:

Allows units to activate only if a time condition has passed.

### ⏱ Scheduling:

SwarmUnits can define time-sensitive triggers by:

- Embedding time-check logic in their `triggerCondition()` method
- Using a built-in **Scheduler Service** or `swarm:tick` CLI command that evaluates mesh timestamps
- Watching keys like `cart.updatedAt`, `user.lastLogin`, `inventory.restockAt`

### 💡 Example:

```php
return (time() - $mesh['cart.updatedAt']) > 600; // 10 mins
```

Or via scheduled job runner with swarm ticks.

### ✅ Purpose:

- Support for abandonment flows
- Queue-like time behavior without queues

---

## ⚔️ 10. Mutation Guard Pattern

### ✅ Definition:

A safeguard that prevents two or more units from writing conflicting mesh values concurrently or repeatedly.

### 💡 Strategies:

- Revision IDs: attach a version number to each mesh key and ensure a unit only writes if the current version matches

```php
if ($mesh->getVersion('cart.total') === $expectedVersion) {
  $mesh->set('cart.total', $newValue);
}
```

- Lock keys: mark a mesh path as "in use" by a unit for short-lived critical sections
- Conditional writes (compare-and-set): write only if current value matches expected

```php
$mesh->compareAndSet('cart.total', 42.00, 45.00);
```

- Lock keys
- Conditional writes (e.g., compare-and-set)

### ✅ Purpose:

- Prevent race conditions
- Protect against loops or conflicting actions

---

## 📦 11. Snapshot Isolation Pattern

### ✅ Definition:

SwarmUnits run using a **frozen view** of the mesh so they aren’t affected by mid-loop writes.

### 💡 Use Case:

If Unit A changes `cart.total`, Unit B shouldn’t see that change mid-loop unless re-evaluated.

### ✅ Purpose:

- Data consistency
- Predictable behavior during unit evaluation

---

## 🧪 12. Telemetry Aggregator Pattern

### ✅ Definition:

A centralized component that aggregates:

- Unit execution time
- Trigger frequency
- Error/failure rates
- Mesh mutation volumes

### 💡 Output:

- Logs
- Metrics dashboards
- Alerts

### ✅ Purpose:

- Monitoring Swarm health
- Feedback loops
- Optimization heuristics

---

## 🔁 13. Retry Queue Pattern

### ✅ Definition:

Failed SwarmUnits can enqueue themselves for delayed retry execution without re-triggering immediately.

### 💡 Example:

```php
$this->retryQueue->add($unit, delay: 300); // Retry in 5 mins
```

### ✅ Purpose:

- Prevent system overload
- Defer unstable dependencies
- Increase reliability

---

## ✅ Summary

These invented or extended patterns form the **non-traditional backbone** of your framework. They enable:

- Fully emergent behavior
- Traceable, debuggable logic
- Reactive architecture with no static control flow
- Seamless extensibility via modules

By defining them clearly here, you future-proof your documentation, AI compatibility, and onboarding ecosystem.

