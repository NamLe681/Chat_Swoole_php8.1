@component('mail::message')
# 👋 Xin chào {{ $user->name }}

Cảm ơn bạn đã đăng ký tài khoản!

Vui lòng xác nhận địa chỉ email của bạn bằng cách nhấn vào nút bên dưới 👇

@component('mail::button', ['url' => $verificationUrl])
Xác nhận Email
@endcomponent

Liên kết này sẽ hết hạn sau 60 phút.  
Nếu bạn không tạo tài khoản, hãy bỏ qua email này.

Trân trọng,  
**{{ config('app.name') }}**
@endcomponent
