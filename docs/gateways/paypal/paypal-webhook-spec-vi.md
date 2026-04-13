# Tài liệu Xử lý Webhook Đăng ký PayPal

> **Phiên bản tài liệu:** 1.0
> **Ngày:** 07/04/2026
> **Tham khảo:** https://developer.paypal.com/docs/subscriptions/reference/webhooks/

---

## Tổng quan

Tài liệu này định nghĩa đầy đủ logic xử lý callback webhook từ PayPal cho hệ thống đăng ký trong LearnPress. Tất cả các sự kiện đều tuân thủ tài liệu chính thức của PayPal và quy trình vòng đời đăng ký của LearnPress.

---

## 1. Điểm cuối Webhook

| Thông tin | Giá trị |
|---|---|
| **URL Endpoint** | `/wp-json/lp/v1/gateways/paypal/subscription-webhook` |
| **Phương thức** | `POST` |
| **Xác thực** | Xác minh chữ ký webhook của PayPal |
| **Trạng thái phản hồi** | Luôn trả về `200 OK` cho các yêu cầu hợp lệ |

✅ **Quan trọng:** PayPal sẽ thử gửi lại webhook **theo cấp số nhân** trong vòng 3 ngày nếu nhận được phản hồi không phải 2xx. Luôn trả về 200 ngay cả đối với sự kiện không được hỗ trợ hoặc đã xử lý rồi.

---

## 2. Quy trình xử lý

Tất cả sự kiện webhook đều đi qua đúng trình tự sau:

```
1. 🔐 Xác minh chữ ký
    ├─ Trích xuất tất cả header của PayPal
    ├─ Gọi API xác minh của PayPal
    └─ LỖI → Trả về 403, không xử lý tiếp

2. 🧾 Kiểm tra dữ liệu gửi lên
    ├─ Phân tích payload JSON
    ├─ Kiểm tra cấu trúc sự kiện
    └─ LỖI → Trả về 400

3. 🆔 Kiểm tra sự kiện trùng lặp
    ├─ Kiểm tra xem ID sự kiện đã được xử lý chưa
    └─ ĐÃ XỬ LÝ → Trả về 200 OK

4. 📦 Phân phối sự kiện
    ├─ Tìm handler tương ứng với loại sự kiện
    ├─ Thực thi logic xử lý
    └─ KHÔNG CÓ HANDLER → Trả về 200 OK

5. ✅ Đánh dấu đã xử lý
    └─ Lưu ID sự kiện vào database

6. 📨 Trả về phản hồi
    └─ Luôn là 200 OK
```

---

## 3. Bảng ánh xạ sự kiện

Đây là bảng ánh xạ chính thức các loại sự kiện PayPal với hành động tương ứng trong LearnPress:

| Loại sự kiện PayPal | Hành động LearnPress | Trạng thái Đơn hàng | Trạng thái Đăng ký | Ghi chú |
|---|---|---|---|---|
| **`BILLING.SUBSCRIPTION.CREATED`** | Lưu ID đăng ký PayPal | `pending` (chờ xử lý) | `pending` | Sự kiện đầu tiên nhận được khi người dùng đăng ký |
| **`BILLING.SUBSCRIPTION.ACTIVATED`** | Kích hoạt đăng ký, cấp quyền truy cập khóa học | `completed` (hoàn thành) | `active` (đang hoạt động) | Thanh toán đã được xác nhận thành công |
| **`BILLING.SUBSCRIPTION.CANCELLED`** | Lên lịch hủy vào cuối kỳ hiện tại | `completed` | `active` | ❗ **KHÔNG HỦY NGAY LẬP TỨC** - người dùng đã thanh toán cho kỳ hiện tại |
| **`BILLING.SUBSCRIPTION.SUSPENDED`** | Tạm dừng đăng ký, thu hồi quyền truy cập | `on-hold` (tạm giữ) | `on-hold` | PayPal đã tạm dừng sau nhiều lần thanh toán thất bại |
| **`BILLING.SUBSCRIPTION.EXPIRED`** | Hết hạn đăng ký vĩnh viễn | `completed` | `expired` (đã hết hạn) | Đăng ký đã đến ngày kết thúc |
| **`BILLING.SUBSCRIPTION.PAYMENT.FAILED`** | Ghi lỗi, gửi thông báo cho người dùng | `failed` (thất bại) | `active` | PayPal sẽ thử gửi lại 3 lần trước khi tạm dừng |
| **`PAYMENT.SALE.COMPLETED`** | Xử lý thanh toán gia hạn, gia hạn thời gian đăng ký | `completed` | `active` | Thanh toán định kỳ nhận được thành công |
| **`PAYMENT.SALE.REFUNDED`** | Hoàn tiền đơn hàng, điều chỉnh quyền truy cập | `refunded` (đã hoàn tiền) | *tùy trường hợp* | Hoàn toàn bộ = thu hồi quyền truy cập ngay lập tức |
| **`PAYMENT.SALE.REVERSED`** | Tranh chấp / Chargeback | `refunded` | `suspended` (đã tạm dừng) | ❗ **THU HỒI QUYỀN TRUY CẬP NGAY LẬP TỨC** |

---

## 4. Quy tắc chuyển trạng thái

### ✅ Luồng bình thường thành công
```
CREATED → ACTIVATED → [PAYMENT.COMPLETED] (lặp lại hàng tháng) → [CANCELLED / EXPIRED]
```

### ❌ Luồng thanh toán thất bại
```
PAYMENT.FAILED → (Thử lại 1) → (Thử lại 2) → (Thử lại 3) → SUSPENDED
```

### ⚠️ Luồng tranh chấp
```
PAYMENT.REVERSED → SUSPENDED (ngay lập tức)
```

---

## 5. Quy tắc Idempotency

Tất cả các handler **PHẢI** là idempotent:
1. Luôn kiểm tra trạng thái hiện tại trước khi thay đổi
2. Không bao giờ xử lý cùng một ID sự kiện hai lần
3. Xử lý một sự kiện nhiều lần cho kết quả hoàn toàn giống nhau
4. Không có tác dụng phụ khi nhận sự kiện trùng lặp

ID sự kiện đã xử lý sẽ được lưu trong **90 ngày**.

---

## 6. Xử lý lỗi

| Trường hợp | Mã phản hồi | Hành động |
|---|---|---|
| Chữ ký không hợp lệ | `403 Forbidden` | Ghi log lỗi, không xử lý |
| Dữ liệu JSON không hợp lệ | `400 Bad Request` | Ghi log lỗi |
| Thiếu header bắt buộc | `400 Bad Request` | Ghi log lỗi |
| Cổng thanh toán không được bật | `404 Not Found` | Ghi log lỗi |
| Sự kiện trùng lặp | `200 OK` | Bỏ qua |
| Loại sự kiện không được hỗ trợ | `200 OK` | Ghi log để tham khảo |
| Lỗi khi thực thi handler | `200 OK` | Ghi log đầy đủ lỗi, PayPal sẽ thử gửi lại |

---

## 7. Yêu cầu Ghi log

Tất cả sự kiện webhook **PHẢI** ghi lại:
- ID sự kiện
- Loại sự kiện
- ID đăng ký PayPal
- Kết quả xử lý (thành công / thất bại)
- Thông báo lỗi đầy đủ khi có lỗi

Log được lưu tại: `wp-content/uploads/learnpress/logs/paypal-webhooks.log`

---

## 8. Các trường hợp kiểm tra

Các test case bắt buộc khi triển khai:

| Trường hợp kiểm tra | Kết quả mong đợi |
|---|---|
| Chữ ký hợp lệ | Sự kiện được xử lý thành công |
| Chữ ký không hợp lệ | Phản hồi 403, không có thay đổi |
| ID sự kiện trùng lặp | Phản hồi 200, không có thay đổi |
| Loại sự kiện không được hỗ trợ | Phản hồi 200, được ghi log |
| Không tìm thấy đơn hàng | Phản hồi 200, được ghi log |
| Sự kiện đến sai thứ tự | Trạng thái vẫn đúng |

---

## Tài liệu tham khảo

- [Tài liệu chính thức Webhook Đăng ký PayPal](https://developer.paypal.com/docs/subscriptions/reference/webhooks/)
- [Xác minh Webhook PayPal](https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature)
- [Vòng đời Đăng ký LearnPress](internal://docs/subscriptions/lifecycle.md)