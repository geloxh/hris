# Analytics module (not yet built)

Read-only aggregation endpoints, e.g. headcount by department, turnover rate,
average leave utilization, attendance punctuality trend. Query directly with
App\Core\Database (GROUP BY / aggregate SQL) rather than through Model::all()/where(),
since these are reporting queries, not entity CRUD. Cache expensive aggregates
(e.g. in a report_cache table or Redis later) if dashboards get slow.
