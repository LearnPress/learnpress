# BILLING.SUBSCRIPTION.EXPIRED - Giải thích chi tiết

---

## 1. Khi nào thì nhận được sự kiện `BILLING.SUBSCRIPTION.EXPIRED`?

✅ **Sự kiện này CHỈ bắn về khi gói đăng ký của bạn có Điểm Kết Thúc (Fixed Term).**

Trong thuộc tính `billing_cycles` khi tạo Plan trên PayPal, có một trường quan trọng là `total_cycles`:

| Giá trị `total_cycles` | Loại gói | Hành vi | Nhận được `EXPIRED`? |
|---|---|---|---|
| `0` | **Gói vô hạn (Infinite)** | Gói sẽ chạy mãi mãi cho đến khi khách hàng hủy hoặc thanh toán lỗi | ❌ **KHÔNG BAO GIỜ** |
| `> 0` (ví dụ `12`) | **Gói có kỳ hạn cố định** | Sau khi khách hàng đã thanh toán đủ số kỳ, gói sẽ tự động kết thúc | ✅ PayPal sẽ gửi sự kiện này |

---

## 2. Trường hợp: "Hết 1 tháng là phải gia hạn"

Nếu bạn đang xây dựng mô hình:
> Khách hàng thanh toán từng tháng một, hệ thống tự động trừ tiền hàng tháng (Auto-renew)

✅ **Cấu hình đúng:**
> Bạn **PHẢI** để `total_cycles: 0`

### Kết quả với cấu hình đúng:
| Sự kiện | Khi nào nhận được |
|---|---|
| `PAYMENT.SALE.COMPLETED` | Mỗi tháng khi trừ tiền thành công |
| `BILLING.SUBSCRIPTION.CANCELLED` | Khi khách hàng chủ động nhấn Hủy gói |
| `BILLING.SUBSCRIPTION.SUSPENDED` | Khi thanh toán thất bại sau 3 lần thử |
| `BILLING.SUBSCRIPTION.EXPIRED` | ❌ **Bạn sẽ KHÔNG BAO GIỜ nhận được sự kiện này** |

---

## 3. Tại sao không dùng `EXPIRED` cho gia hạn hàng tháng?

> 💡 Theo triết lý thiết kế của PayPal:
>
> **`Expired` nghĩa là gói dịch vụ đã hoàn tất vòng đời của nó hoàn toàn.**
>
> Ví dụ đúng dùng `EXPIRED`:
> - Gói trả góp 12 tháng đã trả xong hết
> - Gói khóa học 6 tháng đã kết thúc
> - Gói thử nghiệm 14 ngày đã hết hạn

Còn mô hình thuê bao phần mềm (SaaS), thành viên hàng tháng:
✅ Luôn là gói vô hạn `total_cycles = 0`
✅ Chỉ dừng khi một trong hai bên chủ động ngắt

---

## ❌ Lỗi phổ biến nhất

Rất nhiều nhà phát triển sai lầm khi cấu hình `total_cycles: 1` cho gói 1 tháng và mong đợi nhận `EXPIRED` mỗi cuối tháng để gia hạn.

👉 **Đây là cách sai hoàn toàn theo thiết kế của PayPal.**

PayPal sẽ không tự động gia hạn gói có `total_cycles > 0`. Khi đến hạn, gói sẽ kết thúc vĩnh viễn và bạn không thể gia hạn nó nữa.