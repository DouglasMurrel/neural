Установка системы

1. Клонировать систему из репозитория `https://github.com/DouglasMurrel/localhost.git`
2. Выполнить `php bin/console lexik:jwt:generate-keypair`- таким образом будет сгенерирована пара файлов сертификата
3. Скопировать `.env` в `.env.local` и в нем настроить параметры базы данных и почты.
4. Выполнить `composer install` и настроить веб-сервер, чтобы его корень соответствовал директории `public` проекта (для простоты настройки в проекте присутствует файл .htaccess для Apache)
5. Создать базу данных, затем создать в ней таблицы с помощью миграции: `php bin/console doctrine:migrations:migrate`
6. Создать рейсы, на которые будут бронироваться билеты. Это делается консольной командой `php bin/console flight:fill <id>`, где `<id>` - id рейса. Если рейс уже существует, будет выдана ошибка.
При этом в таблицу будут вставлены строки, соответствующие каждому месту. Я решил, что такая модель наиболее выгодна с точки зрения производительности, поскольку при выполнении определенных команд придется искать первое незанятое место в рейсе, и это наиболее удобно делать, если информация обо всех местах уже загружена в БД.
7. Зарегистровать пользователей системы. Это делается отправкой на адрес `https://servername/register` POST-запроса с телом
```
{"email":"<email>","password":"<password>"}
```
Например:
```
curl -X POST -H "Content-Type: application/json" http://localhost/register -d "{\"email\":\"test@test.ru",\"password\":\"yourpassword\"}"
```
Здесь `email` - реальный адрес пользователя, а `password` - пароль, который будет использоваться для выполнения команд.

В дальнейшем, когда пойдет речь об отправке запроса к api, я буду писать только часть url, не включающую имя сервера. То есть, например, если написан url `/api/booking`, то имеется в виду, что запрос должен быть отправлен на `https://servername/api/booking`.

Для выполнения запросов нужно получить токен. Это делается путем отправки POST-запроса на адрес `/api/login_check`. В запросе обязательно должен быть заголовок `Content-Type: application/json`, а тело запроса должно быть таким же, как при регистрации, с заменой названия поля `email` на `username`: `{"username":"<email>","password":"<password>"}`.

Например:
```
curl -X POST -H "Content-Type: application/json" http://localhost/api/login_check -d "{\"username\":\"test@test.ru",\"password\":\"yourpassword\"}"
```
Вы должны получить результат в таком виде:
```
{"token":"<token>"}
```
где `<token>` - это как раз нужный вам токен. Срок его жизни равен 1 часу, затем нужно снова аутентифицироваться.

Для применения методов API вы должны отправить запрос на определенный адрес. У каждого запроса должны быть заголовки
```
Content-Type: application/json
Authorization: Bearer <token>
```
где `<token>` - полученный вами токен.

Например:
```
curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MjAzMTcxNTYsImV4cCI6MTYyMDMyMDc1Niwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoibXVycmVsQHlhbmRleC5ydSJ9.ZR3QsYH57sK8NxRn-UEOuLhyHT0o8d42W1wNFakW85vURaAC9n-XRTgl8Skm7bSRIuO-AiJSCLTpwHOlGoMqTLbES8DN-43s2bn8hkG_2iqFUZ8MTJO_HAGz47BJBRVoaHpeXhCuF648lmWg1nMgiNEDo9lzUT_SJJ-xVfSu9qQcApeYr1cqLITs1m8Sg7LrSk-qKWc5YQigAZgky4an-zPKC7v0R8C36l4eI22ZhNvsVjQs9cDh5IHP5phqCKK7jaHh60vkWMZWaE2j4XdBtaoZTpNVTvAicxIkjLxyiYrTBT2nmFDP1y6sPXIDkmzK1TWE_RdE17KqqgeH3UcTyA" http://localhost/api/booking/1
```

Методы отличаются только адресом, на который отправляются (за исключением пункта 7 - см. ниже).

1. `/api/booking/{flightId}`

Бронирует первое незанятое место на рейс flightId.
В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер заказа в системе.

2. `/api/booking/{flightId}/{seatId}`

Бронирует место seatId на рейс flightId.
В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер заказа в системе.

3. `/api/cancel_booking/{id}`

Отменяет указанное бронирование. Обратите внимание, что `id` - номер заказа в системе, а не номер места на рейсе!

В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер отмененного заказа в системе.   

4. `/api/buy_ticket/{flightId}`

Покупает билет на первое незанятое место на рейс flightId.
В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер заказа в системе.

5. `/api/buy_ticket/{flightId}/{seatId}`

Покупает билет на seatId на рейс flightId.
В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер заказа в системе.

6. `/api/cancel_ticket/{id}`

Возвращает указанный билет. Обратите внимание, что `id` - номер заказа в системе, а не номер места на рейсе!

В случае успеха возвращает объект `{"status":200,"success":id}`, где `id` - номер отмененного заказа в системе.

Если при вызове любого метода API произошла ошибка, возвращается объект `{"status":<code>,"errors":<message>}`

7. /event
Это адрес для callback-ов. Ему не требуется авторизация в вышеописанном виде.
   
Вместо этого он принимает в теле запроса json-объект
```
{"data":{"flight_id":1,"triggered_at":1585012345,"event":"flight_ticket_sales_completed","secret_key":"a1b2c3d4e5f6a1b2c3d4e5f6"}}
```
Здесь значение `secret_key` должно совпадать с заранее прописанной константой.

Значение `event` может быть `flight_ticket_sales_completed` (окончание продажи) или `flight_canceled` (отмена рейса)  