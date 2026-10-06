# Flow Chart — LearnPress

Chứa các flowchart, logic diagram, và sequence diagram cho dự án LearnPress.

## Cấu trúc
```
flow-chart/
├── README.md
├── logic/          # Business logic flowcharts
├── diagram/        # Architecture & sequence diagrams
└── *.md            # Mermaid-based diagrams
```

## Định dạng
- Sử dụng **Mermaid** syntax trong file `.md` để vẽ diagram
- Hoặc export hình ảnh `.png`/`.svg` từ draw.io, Excalidraw

## Ví dụ Mermaid
```mermaid
flowchart TD
    A[User enroll course] --> B{Is logged in?}
    B -->|Yes| C[Check payment]
    B -->|No| D[Redirect to login]
    C --> E[Create user item]
```

## Folders
- **logic/** — Flowchart xử lý nghiệp vụ (enroll, quiz, order, ...)
- **diagram/** — Kiến trúc hệ thống, class diagram, sequence diagram
