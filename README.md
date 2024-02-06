# О разработке
За основу взят пример [SimpleAsemblyWebsoket](https://gitlab.com/DmitriyProgrammer/simpleasemblywebsoket), основанный на [Workerman](https://github.com/walkor/workerman).

## Задача
- Разработать инструмент для поднятия websocket-сервера и работы с ним

## Возможности
- Обмен сообщениями между frontend и backend

## Структура



## Стартовый скрипт

Прилагаемый в комплекте файл `init/wsserver.service` необходимо скопировать в каталог `/etc/systemd/system`

```shell
cd /tmp/wsserver
cp init/wsserver.service /etc/systemd/system/
systemctl enable --now wsserver
```

и изменить в нем WorkingDirectory

Дальнейшее управление службой производится следующими командами:
```shell
systemctl status wsserver
systemctl stop wsserver
systemctl start wsserver
```

Посмотреть логи
```shell
journalctl -u wsserver
```