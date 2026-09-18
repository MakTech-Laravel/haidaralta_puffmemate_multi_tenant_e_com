---
paths:
    - 'resources/js/components/{platform,admin}/*-form.tsx'
---

# Platformadmin

## No Precognition validate in sync effects

Never call useForm validate() inside a useEffect that syncs derived fields (e.g. name→slug). That re-renders and re-wraps Inertia Precognition endpoints until Maximum call stack size exceeded. Sync the derived value only when it changed; live-validate uniqueness on blur only.
