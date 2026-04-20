# Đánh giá mã nguồn: `listen_subscription_webhook`

**Ngày:** 02/04/2026  
**File:** `inc/rest-api/v1/frontend/class-lp-rest-gateway-webhook-controller.php`  
**Route:** `POST /wp-json/lp/v1/gateways/{gateway}/subscription-webhook`

---

## Tổng quan

Hàm `listen_subscription_webhook` (dòng 56–106) đóng vai trò là cổng vào duy nhất cho tất cả webhook subscription từ các payment gateway. Luồng xử lý:

1. Lấy và làm sạch `gateway` từ URL
2. Kiểm tra bảo vệ: kích thước payload + rate limit (`guard_webhook_request`)
3. Xác minh gateway tồn tại, đang bật, và hỗ trợ subscription
4. Chuyển tiếp request tới `$gateway->listen_webhook_subscription($request)`
5. Trả về response với status code từ kết quả

---

## Các vấn đề tìm thấy

### 1. [Trung bình] `status_code` không được kiểm tra phạm vi HTTP hợp lệ

**Vị trí:** Dòng 100–103

```php
$status_code = absint( $result['status_code'] );
```

`absint()` chỉ đảm bảo giá trị không âm, không đảm bảo là HTTP status code hợp lệ (100–599). Nếu một gateway implementation trả về `['status_code' => 0]` hoặc `['status_code' => 9999]`, `WP_REST_Response` sẽ nhận status code không hợp lệ.

**Ví dụ trigger lỗi:**
```php
// Gateway trả về:
return [ 'status_code' => 0, 'status' => 'ok' ];
// → HTTP response với status 0 — không hợp lệ theo HTTP spec
```

**Khuyến nghị:** Thêm kiểm tra phạm vi sau `absint()`:
```php
if ( $status_code < 100 || $status_code > 599 ) {
    $status_code = 200;
}
```

---

### 2. [Trung bình] `build_error_response()` để lộ mã lỗi nội bộ ra ngoài

**Vị trí:** Dòng 240–261

Hàm xây dựng response lỗi thực hiện đúng việc tách biệt:
- `$private_message` → chỉ ghi vào `error_log()`
- `$public_message` → trả về cho caller (generic)

Tuy nhiên, `$error_code` vẫn được đưa vào response body công khai:

```php
return new WP_REST_Response(
    array(
        'status'  => 'error',
        'code'    => $error_code,      // ← lộ tên nội bộ
        'message' => $public_message,
    ),
    $status
);
```

Điều này lộ các tên nội bộ như `lp_subscription_manager_missing` hoặc `lp_subscription_webhook_rate_limited` cho bên ngoài — với lỗi 5xx đặc biệt, điều này tiết lộ trạng thái infrastructure.

**Khuyến nghị:** Loại bỏ `'code'` khỏi response đối với lỗi 5xx, hoặc map sang mã lỗi công khai trước khi trả về.

---

### 3. [Thấp] Kiểm tra `empty($gateway_id)` là dead code

**Vị trí:** Dòng 58–66

```php
$gateway_id = sanitize_key( (string) $request->get_param( 'gateway' ) );
if ( empty( $gateway_id ) ) {
    return new WP_REST_Response( /* 400 */ );
}
```

Route regex `(?P<gateway>[a-zA-Z0-9_-]+)` bắt buộc ít nhất 1 ký tự và chỉ cho phép các ký tự mà `sanitize_key()` giữ nguyên (chữ-số, `-`, `_`). Vì vậy `$gateway_id` **không thể rỗng** khi callback này được gọi — đoạn 400 không bao giờ được thực thi.

**Khuyến nghị:** Xóa hoặc thêm comment giải thích nếu muốn giữ như defensive check.

---

### 4. [Thấp] Rate limit counter có race condition

**Vị trí:** Dòng 196–208 trong `check_rate_limit()`

```php
$rate_data = get_transient( $rate_key );
// ... kiểm tra count ...
$rate_data['count'] = $count + 1;
set_transient( $rate_key, $rate_data, $ttl );
```

`get_transient` + `set_transient` không phải thao tác nguyên tử. Dưới tải đồng thời cao, hai request có thể cùng đọc `count=59`, cùng tăng lên `60`, và cùng vượt qua giới hạn.

**Khuyến nghị:** Sử dụng `wp_cache_incr()` nếu có object cache backend (Redis/Memcached) — đây là thao tác nguyên tử. Vấn đề này cũng tồn tại trong `LP_Subscription_Event_Store::acquire_lock()`.

---

## Ví dụ test với tham số

### Test 1 — Gateway không tồn tại (404)
```bash
curl -X POST 'http://your-site.local/wp-json/lp/v1/gateways/unknown-gw/subscription-webhook' \
  -H 'Content-Type: application/json' \
  -d '{"event_type":"BILLING.SUBSCRIPTION.ACTIVATED"}'
# Kỳ vọng: 404 {"status":"error","message":"Gateway not found."}
```

### Test 2 — PayPal webhook (chữ ký không hợp lệ, test routing)
```bash
curl -X POST 'http://your-site.local/wp-json/lp/v1/gateways/paypal/subscription-webhook' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": "WH-TEST-001",
    "event_type": "BILLING.SUBSCRIPTION.ACTIVATED",
    "resource": {
      "id": "I-TEST123456",
      "plan_id": "P-TEST123",
      "status": "ACTIVE",
      "subscriber": {
        "email_address": "test@example.com",
        "payer_id": "PAYER123"
      }
    }
  }'
# Kỳ vọng: 4xx lỗi xác minh chữ ký từ verify_subscription_webhook()
```

### Test 3 — Payload quá lớn (413)
```bash
curl -X POST 'http://your-site.local/wp-json/lp/v1/gateways/paypal/subscription-webhook' \
  -H 'Content-Type: application/json' \
  --data-binary "$(python3 -c "import sys; sys.stdout.write('A' * 300000)")"
# Kỳ vọng: 413 {"status":"error","code":"lp_subscription_webhook_payload_too_large","message":"Webhook payload too large."}
```

### Test 4 — Rate limit (429, sau request thứ 61 trong 60 giây)
```bash
for i in $(seq 1 61); do
  curl -s -o /dev/null -w "Request $i: %{http_code}\n" \
    -X POST 'http://your-site.local/wp-json/lp/v1/gateways/paypal/subscription-webhook' \
    -H 'Content-Type: application/json' \
    -d '{}'
done
# Kỳ vọng: request 61 trả về 429
```

### Test 5 — Edge case: gateway bị tắt (404 với thông báo chung)
```bash
# Bật gateway paypal rồi tắt đi trong admin, sau đó:
curl -X POST 'http://your-site.local/wp-json/lp/v1/gateways/paypal/subscription-webhook' \
  -H 'Content-Type: application/json' \
  -d '{}'
# Kỳ vọng: 404 {"status":"error","message":"Gateway not found."}
# Lưu ý: cùng thông báo với gateway không tồn tại — cố ý để ẩn thông tin
```

---

## Tổng hợp

| Mức độ | Vấn đề | Dòng |
|--------|--------|------|
| Trung bình | `status_code` không giới hạn trong phạm vi 100–599 | 100–103 |
| Trung bình | Mã lỗi nội bộ lộ ra ngoài qua `build_error_response()` | 258–261 |
| Thấp | Kiểm tra empty gateway là dead code (route regex đã chặn) | 58–66 |
| Thấp | Rate limit counter không nguyên tử | 196–208 |

Nhìn chung, `listen_subscription_webhook` được thiết kế tốt — phân tách bảo vệ/nghiệp vụ rõ ràng, xử lý lỗi nhất quán qua `WP_Error`, và thông báo lỗi công khai được generic hóa đúng cách. Hai vấn đề trung bình nên được sửa trước khi merge.