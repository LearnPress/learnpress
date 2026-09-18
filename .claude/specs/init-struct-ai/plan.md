# Plan — init-struct-ai

## Steps
- [x] Step 1: Tạo `CLAUDE.md` ở root project
- [x] Step 2: Tạo `.claude/settings.json` và `.claude/settings.local.json`
- [x] Step 3: Tạo `.claude/rules/` với `architecture.md`, `testing.md`, `security.md`
- [x] Step 4: Tạo `.claude/skills/code-review/` với `SKILL.md`, `scripts/`, `references/`, `assets/`
- [x] Step 5: Tạo `.claude/skills/test-writer/` với `SKILL.md`, `scripts/`, `references/`, `assets/`
- [x] Step 6: Tạo `.claude/skills/security-audit/` với `SKILL.md`, `scripts/`, `references/`, `assets/`
- [x] Step 7: Tạo `.claude/agents/` với `code-reviewer.md`, `test-writer.md`, `security-reviewer.md`
- [x] Step 8: Tạo `.mcp.json` ở root
- [x] Step 9: Tạo `scripts/` ở root (nếu chưa có)
- [x] Step 10: Tạo `flow-chart/` ở root với `README.md`

## Files to create
| File | Purpose |
|------|---------|
| `CLAUDE.md` | Hướng dẫn dự án chính cho AI |
| `.claude/settings.json` | Cấu hình Claude project |
| `.claude/settings.local.json` | Cấu hình Claude local (gitignored) |
| `.claude/rules/architecture.md` | Quy tắc kiến trúc |
| `.claude/rules/testing.md` | Quy tắc testing |
| `.claude/rules/security.md` | Quy tắc bảo mật |
| `.claude/skills/code-review/SKILL.md` | Kỹ năng code review |
| `.claude/skills/test-writer/SKILL.md` | Kỹ năng viết test |
| `.claude/skills/security-audit/SKILL.md` | Kỹ năng audit bảo mật |
| `.claude/agents/code-reviewer.md` | Agent code reviewer |
| `.claude/agents/test-writer.md` | Agent test writer |
| `.claude/agents/security-reviewer.md` | Agent security reviewer |
| `.mcp.json` | Cấu hình MCP servers |
| `scripts/README.md` | Hướng dẫn scripts |
| `flow-chart/README.md` | Hướng dẫn flow-chart/diagram |

## Files to modify
| File | Change |
|------|--------|
| `.gitignore` | Thêm `settings.local.json` nếu cần |

## Open questions
- Không
