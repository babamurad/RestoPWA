# Roadmap: Checkout Map & Address Experience (Professional Plan)

## 1) Current state (what already exists)
- Checkout has map-based pin placement and drag/click coordinate capture.
- Address selector supports:
  - marker drag and map click;
  - reverse transition between map/refinement states;
  - manual details (entrance/floor/apartment/landmark/comment).
- Coordinates are stored and synced into checkout flow.

## 2) Main product gap
**Observed issue:** user places only a point, but does not receive confidence that address is valid, deliverable, and complete enough for courier execution.

## 3) Product goals (next stage)
1. **Address confidence:** convert raw point into a validated delivery address.
2. **Delivery certainty:** show serviceability, ETA range, and fee before order confirmation.
3. **Operational quality:** reduce failed deliveries, courier call-backs, and support load.
4. **Conversion:** improve checkout completion rate.

## 4) Execution plan by streams

### Stream A — Address intelligence (P0)
1. Reverse geocoding after pin drop/drag.
2. Structured parsing into street/house/building fields.
3. Confidence score (high/medium/low) based on geocoder precision.
4. Inline prompts when precision is low ("уточните дом", "добавьте ориентир").
5. Save geocoding diagnostics for observability.

**Deliverables:**
- confirmed address preview card;
- confidence badge;
- fallback to manual refinement when confidence < threshold.

### Stream B — Deliverability & pricing on map (P0)
1. Real-time zone check on coordinate change.
2. Clear in/out-of-zone state.
3. Dynamic delivery fee and SLA from distance/zone rules.
4. Block submission outside delivery polygon with recovery path (pickup / nearest restaurant / support).

**Deliverables:**
- zone status badge in checkout;
- pre-submit hard validation for deliverability;
- user-facing rejection reasons.

### Stream C — UX improvements for map interaction (P1)
1. Replace one-point interaction with "center pin" flow (map moves, pin fixed).
2. Add "My location" button with permission handling and fallback.
3. Add search box with suggestions and recent addresses.
4. Show route hints / microcopy for private sector / apartment blocks.
5. Add map skeleton, retry states, and deterministic error messaging.

**Deliverables:**
- improved map controls;
- loading and failure states with retry telemetry.

### Stream D — Checkout data quality (P1)
1. Make phone validation stricter (format + country-aware normalization).
2. Introduce required fields matrix by area type (apartment vs private house).
3. Detect suspicious coordinates (0,0 / outside city bounds / old stale coordinates).
4. Save `address_source` (map_pin, search, manual_edit, geolocate).

**Deliverables:**
- validation schema;
- normalized address payload in order metadata.

### Stream E — Courier & operations feedback loop (P1)
1. Add "courier couldn't find address" feedback reason taxonomy.
2. Feed failed-drop reasons back to address scoring model/rules.
3. Add internal QA panel for problematic addresses.

**Deliverables:**
- operations dashboard widgets;
- weekly quality review process.

### Stream F — Experimentation & analytics (P0/P1)
1. Instrument funnel events:
   - map_opened;
   - pin_moved;
   - reverse_geocode_success/failed;
   - zone_check_pass/failed;
   - checkout_submit_success/failed.
2. Build baseline metrics:
   - checkout completion;
   - address edit rate;
   - delivery failure rate;
   - support tickets per 100 orders.
3. Run A/B test: current flow vs confidence-assisted flow.

**Deliverables:**
- analytics dashboard;
- experiment readout template.

## 5) Technical plan (8-week suggestion)

### Phase 1 (Week 1–2): Foundations
- Implement event telemetry for map & address actions.
- Add reverse geocode endpoint integration and caching.
- Persist confidence score and geocode metadata.

### Phase 2 (Week 3–4): Deliverability core
- Add server-side zone check endpoint for coordinates.
- Integrate fee/SLA preview.
- Add hard validation before submit.

### Phase 3 (Week 5–6): UX hardening
- Add geolocate, search suggestions, and recent addresses.
- Improve empty/loading/error states.
- Improve validation UX and recovery flows.

### Phase 4 (Week 7–8): Optimization
- Launch A/B test.
- Analyze metrics and iterate thresholds.
- Finalize playbook for operations.

## 6) Suggested API/contracts
- `POST /api/geo/reverse` => `{ lat, lon } -> structured_address + confidence`
- `POST /api/geo/zone-check` => `{ lat, lon, vendor_id } -> in_zone, fee, eta, reason`
- `GET /api/geo/suggest?q=` => suggestions
- Order payload extension:
  - `address_source`
  - `geo_confidence`
  - `zone_check_snapshot`

## 7) Risks and mitigations
1. **Geocoder instability / latency**
   - Mitigation: timeout + fallback UI + cache.
2. **Polygon edge-case false negatives**
   - Mitigation: border tolerance buffer + manual override policy.
3. **Permission denial for geolocation**
   - Mitigation: non-blocking fallback to search/manual.
4. **Over-validation harms conversion**
   - Mitigation: gradual enforcement via A/B flags.

## 8) Definition of Done (DoD)
- User sees validated address (not only point).
- User sees deliverability state and fee before submit.
- Out-of-zone orders cannot be placed silently.
- Address-related failed deliveries reduced by agreed target (e.g., -25%).
- Full map funnel analytics available in dashboard.

## 9) Prioritized backlog (first 10 tickets)
1. Reverse geocode service + API contract.
2. Confidence scoring rules and UI badge.
3. Zone check endpoint and checkout integration.
4. Submit guardrail for out-of-zone coordinates.
5. Geolocation CTA with permission flow.
6. Address suggestions + recent addresses storage.
7. Validation schema for address completeness.
8. Analytics events and dashboard panels.
9. Retry/fallback UX for map/geocoder failures.
10. A/B flagging and experiment configuration.
