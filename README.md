Gearman Bundle for Symfony2
=====
Пакет для простой организации очередей заданий
 
## Возможности

- Запуск одного воркера
- Запуск сразу всех воркеров
- Мониторинг воркеров

## Установка
Для работы тербуется [Gearman](http://gearman.org/) и расширение для PHP [PECL gearman](http://pecl.php.net/package/gearman)

Установка через [Composer](https://getcomposer.org/):

``` json
{
    "require": {
        "cimus/gearman-bundle": "dev-master"
    }
}
```
### Конфигурация

Конфигурация по-умолчанию. Вы можете изменить её в  `app/config.yml`

```yaml
cimus_gearman:
    servers: 
        localhost:
            host: 127.0.0.1
            port: 4730
```

### Регистрация пакета в ядре

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Cimus\GearmanBundle\CimusGearmanBundle(),
        // ...
    );
}
```

### Использование