{{header}}

<p>Order placed by <strong>{{order_user_name}}</strong> has a new refund request.</p>
<p><strong>Order ID:</strong> {{order_id}}</p>
<p><strong>Order key:</strong> {{order_key}}</p>
<p><strong>Requester ID:</strong> {{refund_requested_by}}</p>
<p><strong>Requester email:</strong> {{refund_requested_email}}</p>
<p><strong>Requested at:</strong> {{refund_requested_at}}</p>
<p><strong>Reason:</strong> {{refund_reason}}</p>
<p><a href="{{admin_order_edit_url}}">Review this refund request</a></p>

{{order_items_table}}

{{footer}}
