# init-struct-ai

> Khởi tạo cấu trúc thư mục Claude Code cho dự án LearnPress theo phiên bản tháng 9/2026

## Goal
Tạo cấu trúc thư mục và file theo chuẩn Claude Code project structure (9/2026) cho dự án LearnPress.
Bao gồm: CLAUDE.md, rules, skills, agents, hooks, MCP config, và thêm folder flow-chart cho logic/diagram.

## Requirements
- [x] Tạo file `CLAUDE.md` ở root project — hướng dẫn dự án liên tục
- [x] Tạo `.claude/settings.json` và `.claude/settings.local.json`
- [x] Tạo `.claude/rules/` — `architecture.md`, `testing.md`, `security.md`
- [x] Tạo `.claude/skills/` — `code-review/`, `test-writer/`, `security-audit/` (mỗi folder có `SKILL.md`, `scripts/`, `references/`, `assets/`)
- [x] Tạo `.claude/agents/` — `code-reviewer.md`, `test-writer.md`, `security-reviewer.md`
- [x] Tạo `.mcp.json` ở root
- [x] Tạo `scripts/` ở root (nếu chưa có)
- [x] Tạo `flow-chart/` ở root — chứa logic diagram, flowchart

## Acceptance Criteria
- [x] Tất cả folder và file được tạo đúng cấu trúc theo hình ảnh tham khảo
- [x] Các file .md có nội dung template phù hợp với dự án LearnPress (WordPress plugin, PHP)
- [x] Folder `flow-chart/` tồn tại với README hướng dẫn

## Scope
- Root: `CLAUDE.md`, `.mcp.json`, `scripts/`, `flow-chart/`
- `.claude/`: `settings.json`, `settings.local.json`, `rules/`, `skills/`, `agents/`

## Out of scope
- Không thay đổi code source hiện tại
- Không cài đặt thêm dependencies
- Không thay đổi `.claude/specs/` đã có

## References
- Hình ảnh: Cấu trúc dự án Claude Code — Phiên bản tháng 9/2026 (Kishore Pandey - @brijpandeyji)
