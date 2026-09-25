# Hướng Dẫn Quản Lý & Convert Font Icon LearnPress

Tài liệu hướng dẫn quy trình thêm mới / cập nhật icon SVG vào bộ font `lp-icon` và build trang xem trước.

---

## 🛠 1. Quy Trình Convert SVG Sang Font Icon (IcoMoon)

### **Bước 1: Mở IcoMoon Project Manager**
- Truy cập: [https://icomoon.io/app/#/projects](https://icomoon.io/app/#/projects)

### **Bước 2: Import bộ Icon hiện tại của LearnPress**
1. Nhấn nút **Import Project**.
2. Chọn file cấu hình: `assets/src/scss/LearnPressIcon.json`.
3. Sau khi upload xong, nhấn nút **Load** để nạp toàn bộ icon hiện có vào workspace.

### **Bước 3: Thêm Icon SVG mới**
1. Chuyển sang giao diện quản lý icon (bấm **Selection** hoặc icon menu).
2. Nhấn nút **Import Icons** (hoặc kéo thả trực tiếp file `.svg` cần thêm vào set icon).
3. Đảm bảo icon mới đã được tích chọn (highlight màu vàng/cam).

### **Bước 4: Xuất bộ Font**
1. Nhấn tab **Generate Font** ở thanh dưới cùng.
2. Kiểm tra tên icon và mã Unicode hex tương ứng (ví dụ: `\e933`).
3. Nhấn nút **Download** để tải gói `.zip` về máy.

### **Bước 5: Convert Font sang định dạng `.woff2`**
1. Giải nén file `.zip` vừa tải về, lấy file font `.woff` hoặc `.ttf`.
2. Truy cập công cụ: [https://fontsource.org/tools/converter](https://fontsource.org/tools/converter) (hoặc [CloudConvert](https://cloudconvert.com/woff-to-woff2)).
3. Upload file `.woff` / `.ttf` và convert sang định dạng **`.woff2`**.
4. Copy file `lp-icon.woff2` và `lp-icon.woff2` vào thư mục:
   ```
   assets/src/css/vendor/fonts/lp-icon/
   ```

### **Bước 6: Khai báo Class mới vào SCSS/CSS**
Mở file `assets/src/scss/_lp-icon-font.scss` và thêm class cho icon mới: (có thể check và copy từ file style.css ở thư mục tải về lúc trước)
```scss
.lp-icon-ten-icon:before {
    content: "\e9xx"; /* Mã unicode tương ứng từ IcoMoon */
}
```
Sau đó chạy lệnh build CSS bằng lệnh `gulp run WatchCSS`.

### **Bước 7: Backup lại file `LearnPressIcon.json` (Bắt buộc)**
> **Quan trọng:** Để các lần sau có thể tiếp tục thêm icon mà không bị mất các icon cũ:
1. Quay lại trang: [https://icomoon.io/app/#/projects](https://icomoon.io/app/#/projects)
2. Nhấn nút **Download** tại project LearnPress vừa chỉnh sửa.
3. Đổi tên file tải về thành `LearnPressIcon.json` và ghi đè vào:
   ```
   assets/src/scss/LearnPressIcon.json
   ```

---

## 🚀 2. Lệnh Build Lại Trang HTML Xem Trước (`index.html`)

Sau khi cập nhật file SCSS/CSS, chạy script để cập nhật giao diện xem trước:

### **Cách 1: Chạy từ thư mục gốc LearnPress (`Plugins/learnpress`)**
```bash
python3 assets/src/view-icons/build_viewicon.py
```

### **Cách 2: Chạy trực tiếp trong thư mục `view-icons`**
```bash
cd assets/src/view-icons
python3 build_viewicon.py
```

---

## 🌐 3. Xem Trước Icon

Mở trực tiếp file `index.html` trên trình duyệt:
```bash
open assets/src/view-icons/index.html
```
