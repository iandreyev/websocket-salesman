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

```shell
iptables -I INPUT 10 -p tcp --dport 8089 -j ACCEPT
iptables -I INPUT 10 -p tcp --dport 8099 -j ACCEPT
```

Все настройки iptables, которые вы указали с помощью этих команд сохранятся только до перезагрузки.
- В Ubuntu выполните:
```shell
sudo /sbin/iptables-save
```
- А в RedHat или CentOS:
```shell
sudo /sbin/service iptables save
```


## Стартовый скрипт

Прилагаемый в комплекте файл `init/wsserver.service` необходимо скопировать в каталог `/etc/systemd/system`

```shell
cd /srv/ws
cp init/wsserver.service /etc/systemd/system/
```

И изменить в нем:
- WorkingDirectory на свою (использована **/srv/ws**)
- указать исполняемый php и абсолютные пути до скрипта в параметрах ExecStart, ExecStop, ExecReload (использован **/usr/bin/php**) - рекомендуется указать абсолютный путь

```shell
# путь установки php
which php
# или
locate -b '\php'
```

Перезапустить службу

```shell
systemctl enable --now wsserver
systemctl daemon-reload
```

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

Ссылки по теме демонизации скриптов PHP

- [Running a PHP Script as Systemd Service in Linux](https://tecadmin.net/running-a-php-script-as-systemd-service-in-linux/)
- [Running a PHP script or Worker as a Systemd Service](https://dev.to/iam_krishnan/running-a-php-script-or-worker-as-a-systemd-service-pf7?ysclid=ly1v6zcsfe510858305)
- [Running PHP Script as a System Service in Ubuntu](https://maslosoft.com/blog/2019/07/10/running-php-script-as-a-system-service-in-ubuntu/)