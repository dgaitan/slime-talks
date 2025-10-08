# Slime Talks Messaging API

A comprehensive, production-ready messaging API built with Laravel v12, designed for multi-tenant applications. This API provides secure, scalable messaging capabilities with complete client isolation, authentication, and full pagination support.

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/your-org/slime-talks.git
cd slime-talks

# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Run tests
php artisan test
```

## 📚 Documentation

- **[API Documentation](./API_DOCUMENTATION.md)** - Complete API reference with examples
- **[Integration Guide](./INTEGRATION_GUIDE.md)** - SDK examples and integration patterns
- **[OpenAPI Specification](./swagger.yaml)** - Swagger/OpenAPI 3.0 specification
- **[User Stories](./USER_STORIES.md)** - Complete feature documentation

## ✨ Features

### 🔐 **Multi-tenant Architecture**
- Complete client isolation
- Secure authentication with Bearer tokens + public key validation
- Domain validation with Origin header checking

### 💬 **Messaging System**
- **General Channels**: Direct messages between customers
- **Custom Channels**: Topic-specific channels with custom names
- **Message Types**: Text, image, and file support
- **Metadata Support**: Custom message data and attributes

### 📊 **Advanced Pagination**
- Cursor-based pagination for optimal performance
- Configurable page sizes (1-100 items)
- Consistent pagination across all list endpoints

### 🧪 **Production Ready**
- **107 tests** with **414 assertions**
- Comprehensive error handling and logging
- Stripe-inspired API design for consistency
- Full test coverage with Pest testing framework

## 🏗️ Architecture

### **API Endpoints**

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/client/{uuid}` | GET | Get client information |
| `/customers` | POST/GET | Create/List customers |
| `/customers/{uuid}` | GET | Get customer details |
| `/channels` | POST/GET | Create/List channels |
| `/channels/{uuid}` | GET | Get channel details |
| `/channels/customer/{uuid}` | GET | Get customer channels |
| `/messages` | POST | Send message |
| `/messages/channel/{uuid}` | GET | Get channel messages |
| `/messages/customer/{uuid}` | GET | Get customer messages |

### **Authentication**

All endpoints require three headers:
- `Authorization: Bearer {token}`
- `X-Public-Key: {public_key}`
- `Origin: {domain}`

### **Response Format**

```json
{
  "object": "customer|channel|message|list",
  "id": "unique_identifier",
  // ... object-specific fields
  "created": 1640995200,
  "livemode": false
}
```

## 🛠️ Development

### **Test-Driven Development**

This project follows strict TDD principles:

1. **Write tests first** - All features start with failing tests
2. **Implement minimal code** - Just enough to make tests pass
3. **Refactor and improve** - Enhance code while keeping tests green

### **Running Tests**

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter="MessageTest"

# Run with coverage
php artisan test --coverage
```

### **Test Coverage**

- ✅ **Client Management**: 7 tests
- ✅ **Customer Management**: 14 tests  
- ✅ **Channel Management**: 44 tests
- ✅ **Message Management**: 42 tests
- **Total**: 107 tests with 414 assertions

## 📦 Installation

### **Requirements**

- PHP 8.1+
- Laravel 12
- MySQL/PostgreSQL
- Composer

### **Setup**

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Create test client (optional)
php artisan slime-chat:start-client
```

### **Environment Variables**

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=slime_talks
DB_USERNAME=root
DB_PASSWORD=

# Application
APP_NAME="Slime Talks"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## 🚀 Deployment

### **Production Checklist**

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure database connection
- [ ] Set up SSL certificates
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up monitoring and logging
- [ ] Configure backup strategy

### **Docker Deployment**

```dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www
```

## 📈 Performance

### **Optimizations**

- **Database Indexing**: Optimized queries with proper indexes
- **Pagination**: Cursor-based pagination for large datasets
- **Caching**: Ready for Redis/Memcached integration
- **Connection Pooling**: Efficient database connections

### **Scalability**

- **Multi-tenant**: Complete client isolation
- **Horizontal Scaling**: Stateless API design
- **Load Balancing**: Ready for multiple server deployment
- **Database Sharding**: Prepared for data partitioning

## 🔒 Security

### **Authentication**

- **Bearer Tokens**: Secure API authentication
- **Public Key Validation**: Additional security layer
- **Domain Validation**: Origin header checking
- **Rate Limiting**: Ready for implementation

### **Data Protection**

- **Client Isolation**: Complete data separation
- **Input Validation**: Comprehensive request validation
- **SQL Injection Protection**: Eloquent ORM protection
- **XSS Prevention**: Output sanitization

## 📊 Monitoring

### **Logging**

- **Error Logging**: Comprehensive error tracking
- **Warning Logging**: Business logic failures
- **Performance Logging**: Request timing and metrics
- **Audit Logging**: User action tracking

### **Health Checks**

```bash
# Check API health
curl -X GET https://api.slime-talks.com/health

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

## 🤝 Contributing

### **Development Workflow**

1. Fork the repository
2. Create a feature branch
3. Write tests first (TDD)
4. Implement the feature
5. Ensure all tests pass
6. Submit a pull request

### **Code Standards**

- **PSR-12**: PHP coding standards
- **Laravel Conventions**: Framework best practices
- **PHPDoc**: Comprehensive documentation
- **Type Hints**: Strict typing throughout

### **Testing Requirements**

- All new features must include tests
- Maintain 100% test coverage
- Follow TDD principles
- Include integration tests

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### **Documentation**

- [API Documentation](./API_DOCUMENTATION.md)
- [Integration Guide](./INTEGRATION_GUIDE.md)
- [User Stories](./USER_STORIES.md)
- [OpenAPI Specification](./swagger.yaml)

### **Getting Help**

- **Issues**: Create a GitHub issue
- **Discussions**: Use GitHub Discussions
- **Email**: support@slime-talks.com

### **Community**

- **GitHub**: [github.com/your-org/slime-talks](https://github.com/your-org/slime-talks)
- **Discord**: [discord.gg/slime-talks](https://discord.gg/slime-talks)
- **Twitter**: [@slime_talks](https://twitter.com/slime_talks)

---

**Built with ❤️ using Laravel v12 and Test-Driven Development**

*Ready for production use with comprehensive documentation, full test coverage, and enterprise-grade security.*