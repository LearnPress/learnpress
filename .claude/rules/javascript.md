---
paths:
  - "assets/src/**/*.js"
  - "tests/Frontend/**"
---

# JavaScript Rules — LearnPress

- Write files in `assets/src/js/` with vanilla JavaScript (no jQuery/React for new code)
- Reuse functions from `assets/src/js/utils.js` when an equivalent utility exists instead of duplicating them
- Import Toastify notifications from `assets/src/js/lpToastify.js`
- Use the pre-enqueued `window.lpAJAXG` for LearnPress AJAX; do not import `assets/src/js/loadAJAX.js`
- Frontend tests: `tests/Frontend/*.test.cjs`, run with `node --test <file>`
