# NCMD App Server

API server cho ứng dụng xem quảng cáo tích điểm và đổi lượt quay thưởng.

## 📋 Mô tả

Ứng dụng cho phép người dùng xem quảng cáo để nhận điểm thưởng, sau đó đổi điểm thành lượt quay thưởng. Được xây dựng bằng Laravel framework.

## ✨ Tính năng chính

- 🔐 Xác thực người dùng (Laravel Sanctum)
- 📺 Quản lý quảng cáo
- 👀 Theo dõi lượt xem quảng cáo
- 💰 Hệ thống điểm thưởng
- 🎰 Đổi điểm thành lượt quay thưởng
- 📊 API RESTful hoàn chỉnh

## 🚀 Cài đặt

### Yêu cầu
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Node.js & npm

### Các bước cài đặt

1. **Clone repository**
   ```bash
   git clone https://github.com/AnLeeDai/ncmd-app-server.git
   cd ncmd-app-server
   ```

2. **Cài đặt dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Cấu hình môi trường**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Cập nhật thông tin database trong `.env`

4. **Chạy setup script**
   ```bash
   composer run setup
   ```

   Hoặc chạy thủ công:
   ```bash
   php artisan migrate
   php artisan db:seed
   npm run build
   ```

5. **Khởi chạy server**
   ```bash
   composer run dev
   ```

   Hoặc chạy riêng:
   ```bash
   php artisan serve
   ```

## 🌐 Deploy trên Render

Dự án này được cấu hình để deploy trên Render với Docker, tối ưu cho cấu hình 512MB RAM và 0.1 CPU.

### Bước deploy

1. **Push code lên GitHub**
   Đảm bảo tất cả files (bao gồm Dockerfile, nginx.conf) đã được commit và push.

2. **Tạo service trên Render**
   - Truy cập [Render Dashboard](https://dashboard.render.com)
   - Tạo Web Service mới
   - Kết nối với repository GitHub
   - Chọn branch (ví dụ: `main` hoặc `feat/setup-deploy-production`)

3. **Cấu hình service**
   - **Runtime**: Docker
   - **Build Command**: (để trống, Dockerfile sẽ tự build)
   - **Start Command**: (để trống, Dockerfile có CMD)
   - **Environment Variables**: Thêm các biến môi trường cần thiết
     - `APP_ENV=production`
     - `APP_KEY` (tạo bằng `php artisan key:generate --show`)
     - `DB_CONNECTION` (ví dụ: `pgsql` cho PostgreSQL)
     - `DB_HOST` (dùng internal database của Render)
     - `DB_PORT`
     - `DB_DATABASE`
     - `DB_USERNAME`
     - `DB_PASSWORD`
     - Các biến khác như `MAIL_*`, `CACHE_DRIVER=file`, `SESSION_DRIVER=file`

4. **Database**
   - Tạo PostgreSQL database trên Render
   - Cập nhật environment variables với thông tin DB

5. **Deploy**
   - Render sẽ tự động build và deploy
   - Theo dõi logs để đảm bảo không có lỗi

### Tối ưu cho low resources

- Nginx: 1 worker process, 512 connections
- PHP-FPM: Default config (có thể điều chỉnh nếu cần)
- Gzip compression enabled
- Static files cached 1 year
- Health check endpoint: `/health`

## 📚 API Endpoints

### Public Routes
- `GET /api/public/videos` - Lấy danh sách quảng cáo
- `POST /api/public/auth/register` - Đăng ký
- `POST /api/public/auth/login` - Đăng nhập

### Private Routes (cần JWT token)
- `POST /api/private/videos/{adId}/start-view` - Bắt đầu xem quảng cáo
- `POST /api/private/videos/{adId}/complete-view` - Hoàn thành xem quảng cáo
- `POST /api/private/videos/exchange-points` - Đổi điểm thành lượt quay
- `POST /api/private/auth/logout` - Đăng xuất

### Admin Routes (chỉ admin)
- `GET /api/admin/users` - Danh sách người dùng
- `GET /api/admin/{id}/users` - Chi tiết người dùng
- `PATCH /api/admin/{id}/users/toggle-active` - Bật/tắt trạng thái active

## 🛠 Công nghệ sử dụng

- **Backend**: Laravel 12
- **Database**: MySQL/PostgreSQL với Eloquent ORM
- **Authentication**: Laravel Sanctum
- **Testing**: Pest
- **Frontend Assets**: Vite + TailwindCSS

## 🧪 Chạy tests

```bash
composer run test
```

Hoặc:
```bash
php artisan test
```

## 📁 Cấu trúc Database

- `users` - Thông tin người dùng
- `ads` - Quảng cáo
- `ad_views` - Lượt xem quảng cáo
- `user_points` - Lịch sử điểm
- `spin_turns` - Lịch sử lượt quay

## 🤝 Đóng góp

1. Fork project
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 📞 Liên hệ

AnLeeDai - [GitHub](https://github.com/AnLeeDai)

Project Link: [https://github.com/AnLeeDai/ncmd-app-server](https://github.com/AnLeeDai/ncmd-app-server)
