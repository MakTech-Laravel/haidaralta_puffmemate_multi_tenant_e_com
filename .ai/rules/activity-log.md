---
paths:
    - 'app/Services/ActivityLog/**'
---

# Activity Log

## Site-wide activity log with scoped viewers

Write via AuditsChanges trait + audit() helper + auth subscriber; never log passwords/tokens/2FA. Properties always include portal, branch_id, user_type. Platform: view-activity-logs (all). Branch: view-branch-activity-logs (that branch). Driver/customer: own causer actions only. Controllers use ActivityLogService, not Spatie facades directly.
