Resume work on an in-progress feature. Use this at the start of every session.

## Input
$ARGUMENTS = "<feature-slug>"

## What to do

### 1. Load context (read in this order)
- `.claude/specs/<feature-slug>/progress.md` — current state, what's done, what's next
- `.claude/specs/<feature-slug>/plan.md` — full plan, step list
- `.claude/specs/<feature-slug>/spec.md` — goals and acceptance criteria
- `CLAUDE.md` — architecture reference

### 2. Summarize state to the user
Print a brief status block:
```
Feature: <feature-slug>
Status:  <status from progress.md>
Done:    <count> steps
Next:    <first item from "Next" section>
```

### 3. Ask what to do
- If **Next** section is clear → offer to start the next step immediately
- If **Blockers** section is non-empty → surface the blockers first and ask how to resolve
- If **Status** is ✅ Done → confirm all acceptance criteria are met, offer to clean up spec

### 4. During the session
After completing each step:
- Mark it `✅` in `plan.md`
- Move it to **Done** in `progress.md`
- Update **Next** with the following step
- Update **Last updated** date in `progress.md`
- Record any decisions made in **Decisions made**

### 5. When ending a session (user says "stop", "save", "pause", or ends conversation)
Update `progress.md`:
- Move completed items to **Done**
- Set **In progress** to what was partially done (if anything)
- Set **Next** to the immediate next action
- Add any **Decisions made** or **Blockers / Notes**
- Update **Last updated** date

### Status values
- 🟡 In progress
- 🔴 Blocked
- ✅ Done