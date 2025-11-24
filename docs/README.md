# Infinri Platform Architecture Documentation

**Based on SEI (Software Engineering Institute) Best Practices**

This directory contains formal architectural documentation following industry-proven frameworks.

---

## 📚 Documentation Structure

### 1. Architectural Views (SEI Standard)

**[Module View](01-module-view.md)** - Static structure
- Core subsystems
- Module SDK & interfaces
- Dependencies and relationships
- What each module does

**[Component & Connector View](02-component-connector-view.md)** - Runtime behavior
- HTTP request flow (Router → Controller → Module → Response)
- Inter-module communication
- Process interactions
- Data flows

**[Allocation View](03-allocation-view.md)** - Deployment & infrastructure
- Droplet deployment topology
- Caddy → RoadRunner → Cache → Database
- File system mapping
- Resource allocation

### 2. Quality Attributes

**[Quality Attribute Scenarios](04-quality-scenarios.md)**
- Performance targets
- Modifiability requirements
- Security constraints
- Deployability goals
- Testability requirements

### 3. Architecture Evaluation

**[ATAM Evaluation](05-atam-evaluation.md)** - Lightweight risk assessment
- Scenario evaluation
- Trade-off analysis
- Sensitivity points
- Risk mitigation

### 4. Architecture Patterns

**[Pattern Documentation](06-architecture-patterns.md)**
- Layers Pattern (Core → Interfaces → Modules)
- Microkernel Pattern (Plugin architecture)
- Pattern trade-offs
- Pattern combinations

### 5. Design Decisions

**[ADRs (Architecture Decision Records)](07-design-decisions.md)**
- Key architectural decisions
- Rationale and context
- Consequences and trade-offs
- Alternatives considered
- **NEW:** ADR-008 PostgreSQL as exclusive database

### 6. Observability & Operations

**[Observability Requirements](08-observability-requirements.md)** - MANDATORY
- Request metrics (Prometheus)
- Structured logging (JSON + correlation ID)
- Health dashboard (`/health` endpoint)
- Error tracking
- Phase-specific requirements

### 7. Migration Strategy

**[First Migration Target: Contact Module](09-first-migration-contact.md)**
- Golden example for all migrations
- Step-by-step migration guide
- Before/after structure
- Testing strategy
- Success criteria

---

## 🎯 Quick Reference

### For Developers
1. Start with [Module View](01-module-view.md) - understand what exists
2. Read [Component & Connector View](02-component-connector-view.md) - understand runtime
3. Check [Quality Scenarios](04-quality-scenarios.md) - understand constraints

### For DevOps
1. Read [Allocation View](03-allocation-view.md) - deployment topology
2. Review [Quality Scenarios](04-quality-scenarios.md) - performance targets
3. Check [ATAM Evaluation](05-atam-evaluation.md) - known risks

### For Architects
1. Review all views
2. Validate against [Quality Scenarios](04-quality-scenarios.md)
3. Update [ATAM Evaluation](05-atam-evaluation.md) as architecture evolves
4. Document new decisions in [ADRs](07-design-decisions.md)

---

## 📖 Reading Order

**First-time readers:**
1. Module View (what's in the system)
2. Component & Connector View (how it works at runtime)
3. Quality Scenarios (what we're optimizing for)
4. Architecture Patterns (why we chose this design)

**Before implementing:**
1. Module View (understand boundaries)
2. Quality Scenarios (understand constraints)
3. **Observability Requirements (MANDATORY for Phase 1-2)**
4. Design Decisions (understand rationale)
5. First Migration Target (if migrating modules)

**Before deploying:**
1. Allocation View (deployment topology)
2. Quality Scenarios (performance targets)
3. ATAM Evaluation (known risks)

---

## 🔄 Keeping Documentation Current

**When to update:**
- ✅ New module added → Update Module View
- ✅ Request flow changes → Update C&C View
- ✅ Deployment changes → Update Allocation View
- ✅ New requirements → Add Quality Scenario
- ✅ Major decision → Create ADR
- ✅ Risk identified → Update ATAM

**Review schedule:**
- After each phase completion
- Before major releases
- When architecture pain points emerge
- Every 3 months minimum

---

## 📐 Architecture Principles

### 1. Dependency Inversion (SOLID)
```
Core Interfaces  ← Modules implement
     ↑
   Modules (SEO, Router, Cache)
```
- ✅ Modules depend on core
- ❌ Core NEVER depends on modules
- ✅ Modules are optional
- ✅ Core is stable

### 2. Layers + Microkernel Pattern
```
Layer 3: Application (Controllers, Commands)
Layer 2: Services (Modules as plugins)
Layer 1: Core (Interfaces, Container)
Layer 0: Foundation (PHP, RoadRunner)
```

### 3. Quality Attributes Priority
1. **Modifiability** - Add modules in <2 days
2. **Performance** - 50k requests/day per client
3. **Security** - Block unauthorized in <1ms
4. **Deployability** - Deploy client in <30s
5. **Testability** - 85%+ coverage

---

## 🎓 References

- **SEI Software Architecture in Practice** (3rd Ed.)
- **ATAM: Method for Architecture Evaluation**
- **Design Patterns: Elements of Reusable Software** (GoF)
- **SOLID Principles** (Uncle Bob Martin)
- **RoadRunner Documentation** (performance patterns)

---

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Next Review:** After Phase 1 completion
