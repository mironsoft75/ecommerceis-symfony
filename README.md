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

### Seed ( Dummy Data )
```
php bin/console doctrine:fixtures:load
```

### Migrate Fresh & Seed
Veritabanını dummy data ile birlikte tekrar oluşturmak için
```
php bin/console doctrine:migrations:migrate --seed
```

## About
Postman API ve Example Request için:\
https://documenter.getpostman.com/view/22844490/VUqmvJvC