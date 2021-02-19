# Cервис хранения изображений и их версий


## Зависимости

* PHP 7+
* Apache 2.4
* MySQL 5.7
* библиотека GD
* библиотека Exif


## Скачивание

```
git clone https://github.com/oZoon/hyper.git
```


## Настройка Apache

```
перед запуском сервиса указать корневую директорию hyper,
используются стандартные настройки (80 порт)
```


## Настройка MySQL

```
используются стандартные настройки (3306 порт)
инструкции для создания пользователя/БД/необходимых таблиц см. в файле mysql.txt
```


## Использование сервиса

```
инструкции для использования сервиса см. в файле description.txt
```


---

## ChangeLog

### version 1.0.0

> сделано:
- регистрация нового пользователя (если разрешено)
- двухшаговая авторизация и идентификация
- список изображений (если разрешено)
- постоянная ссылка на изображение
- трансформация изображения в соответствии с запросом (размеры, формат)
- метаданные изображения

> не сделано (по запросу):
- logout пользователя
- сохранение промежуточных версий изображения на сервере
- удаление промежуточных версий изображения на сервере

---
