# API Routes — Modul Authentication

| Method | URI             | Middleware               | Controller               |
| ------ | --------------- | ------------------------ | ------------------------ |
| POST   | /api/register   | throttle:6,1             | AuthController@register  |
| POST   | /api/login      | throttle:5,1             | AuthController@login     |
| POST   | /api/verify-otp | throttle:5,1             | AuthController@verifyOtp |
| POST   | /api/logout     | auth:sanctum             | AuthController@logout    |
| GET    | /api/me         | auth:sanctum             | AuthController@me        |
| GET    | /api/admin/ping | auth:sanctum, role:admin | AdminPingController      |
