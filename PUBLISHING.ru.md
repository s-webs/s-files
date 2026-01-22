# Инструкция по публикации пакета в Packagist

> **Примечание:** Для английской документации см. [PUBLISHING.md](PUBLISHING.md)

## Подготовка

1. Убедитесь, что у вас есть аккаунт на [Packagist.org](https://packagist.org)
2. Создайте репозиторий на GitHub/GitLab/Bitbucket с названием `s-files`

## Шаги публикации

### 1. Инициализация Git репозитория

```bash
cd packages/s-webs/s-files
git init
git add .
git commit -m "Initial commit"
```

### 2. Создание репозитория на GitHub

1. Перейдите на GitHub и создайте новый репозиторий `s-files`
2. Добавьте remote:

```bash
git remote add origin https://github.com/s-systems/s-files.git
# или
git remote add origin https://github.com/s-webs/s-files.git
```

3. Отправьте код:

```bash
git push -u origin main
```

### 3. Создание тега версии

```bash
git tag -a v1.0.0 -m "First release"
git push origin v1.0.0
```

### 4. Регистрация в Packagist

1. Перейдите на [Packagist.org](https://packagist.org)
2. Войдите в аккаунт
3. Нажмите "Submit" в верхнем меню
4. Введите URL вашего репозитория: `https://github.com/s-systems/s-files`
5. Нажмите "Check" и затем "Submit"

### 5. Настройка автоматического обновления (опционально)

1. В настройках репозитория на GitHub добавьте Webhook:
   - URL: `https://packagist.org/api/github?username=s-systems`
   - Content type: `application/json`
   - Secret: ваш Packagist API токен

2. Или используйте GitHub Service:
   - Перейдите в Settings → Integrations → Packagist
   - Добавьте ваш Packagist username и API token

## Обновление версии

При каждом обновлении:

```bash
# Обновите версию в composer.json
# Создайте новый тег
git tag -a v1.0.1 -m "Bug fixes"
git push origin v1.0.1
```

Packagist автоматически обновит пакет (если настроен webhook).

## Проверка установки

После публикации проверьте установку:

```bash
composer require s-webs/s-files
```

## Важные замечания

- Убедитесь, что `composer.json` содержит правильную информацию
- Версия должна соответствовать [Semantic Versioning](https://semver.org/)
- README.md должен содержать актуальную информацию
- Убедитесь, что все зависимости указаны правильно
