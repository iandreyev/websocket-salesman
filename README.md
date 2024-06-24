# О разработке
За основу взят пример [SimpleAsemblyWebsoket](https://gitlab.com/DmitriyProgrammer/simpleasemblywebsoket), основанный на [Workerman](https://github.com/walkor/workerman).

Дополнительные материалы:
- Статья на [Хабре](https://habr.com/ru/articles/331462/)
- Примеры того же автора для [Workerman](https://github.com/morozovsk/workerman-examples)

## Задача
- Разработать инструмент для поднятия websocket-сервера и работы с ним

## Возможности
- Обмен сообщениями между frontend и backend

## Требования

- PHP 7.2+
- Модули PHP
    - posix
    - socket

### Настройка Firewall

- iptables -I INPUT 10 -p tcp --dport 8089 -j ACCEPT
- iptables -I INPUT 10 -p tcp --dport 8099 -j ACCEPT


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