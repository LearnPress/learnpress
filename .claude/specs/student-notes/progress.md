# Progress — student-notes

**Status:** 🟡 Spec ready
**Started:** 2026-10-05
**Last updated:** 2026-10-05

## Done
- Spec + DB design agreed

## In progress

## Next
- Step 1: Create table

## Decisions made
- Custom table `learnpress_notes` (not usermeta / comments)
- Phase 1: lesson only; schema keeps `item_type` + `anchor.scope` for quiz later
- Create: enrolled (or finished) students only; no guests / preview
- Edit/delete: owner only
- View: owner, admin, instructor (author/co-instructor) of the note's course — both frontend and backend
- Note content: plain text, max 5000 chars
- Anchor: quote (exact/prefix/suffix) + position; orphan fallback

## Blockers / Notes
