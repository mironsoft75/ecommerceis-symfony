## Installation

### Docker Compose Install & Run
```
docker compose up -d
```

### Database Create
Veritabanı otomatik oluşmaz ise manuel oluşturabilirsiniz:
```
CREATE DATABASE ecommerce
```

### Migration
```
php bin/console doctrine:migrations:migrate
```

### Seed & Fresh / Fixtures ( Dummy Data )
```
php bin/console doctrine:fixtures:load
```

## About
Postman API ve Example Request için:\
https://documenter.getpostman.com/view/22844490/VUqmvJvC