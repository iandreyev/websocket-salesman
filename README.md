# SimpleAsemblyWebsoket

You can take the finished project here:

    git clone https://gitlab.com/DmitriyProgrammer/simpleasemblywebsoket.git

How to create a project from scratch:

1 step: make directory.

    mkdir simpleasemblywebsoket


2 step: change directory.

    cd simpleasemblywebsoket

3 step: add library Workerman in project.

    composer require workerman/workerman

4 step: make file "index.php" in simpleasemblywebsoket directory.

    NUL> index.php

5 step: code example below for "index.php", we've got from official documentation https://github.com/walkor/workerman,
add this code in your file "index.php".

    <?php

    use Workerman\Worker;

    require_once __DIR__ . '/vendor/autoload.php';

    // Create a Websocket server
    // 127.0.0.1 - IP adress, 61523 - port, for WebSoket, you can use your option
    $ws_worker = new Worker('websocket://127.0.0.1:61523');

    // 4 processes
    $ws_worker->count = 4;

    // Emitted when new connection come
    $ws_worker->onConnect = function ($connection) {
        echo "New connection\n";
    };

    // Emitted when data received
    $ws_worker->onMessage = function ($connection, $data) {
        // Send hello $data
        $connection->send('Hello ' . $data);
    };

    // Emitted when connection closed
    $ws_worker->onClose = function ($connection) {
        echo "Connection closed\n";
    };

    // Run worker
    Worker::runAll();

You created new websocket server!

6 step: run your server. You can use this command for settings of Websoket server.

    php index.php start
    php index.php start -d
    php index.php connections
    php index.php stop
    php index.php restart
    php index.php reload

7 step: make file ".gitignore".

    NUL> .gitignore

8 step: add folder "vendor" in ".gitignore" file.

    /vendor

9 step: make empty file "index.html", it will be your frontend.

    NUL> index.html

10 step: add html code to "index.html".

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <title>Пример работы WebSoket</title>
        </head>
        <body>
            <!-- here will be your JavaScript code -->
        </body>
    </html>

11 step: add JavaScript code in file "index.html".

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <title>Пример работы WebSoket</title>
        </head>
        <body>
            <script>
                let ws = new WebSocket('ws://127.0.0.1:61523');

                ws.addEventListener('message', (event) => {
                    console.info('Frontend got message: ' + event.data); // get from server
                })

                const func = () => {
                    ws.send('Hello Martians!'); //send on server
                };
                setTimeout(func, 2 * 1000);
            </script>
        </body>
    </html>

12 step: rewrite/modernize file "index.php".

    <?php

    use Workerman\Worker;

    require_once __DIR__ . '/vendor/autoload.php';

    // Create a Websocket server
    $ws_worker = new Worker('websocket://127.0.0.1:61523');

    // 4 processes
    $ws_worker->count = 4;

    // Emitted when new connection come
    $ws_worker->onConnect = function ($connection) {
        $connection->send('This message was sent from Backend(index.php), when server was started.');
        echo "New connection\n";
    };

    // Emitted when data received
    $ws_worker->onMessage = function ($connection, $data) {
        // if, server got message from frontend, server send message to Frontend $data
        $connection->send($data);
    };

    // Emitted when connection closed
    $ws_worker->onClose = function ($connection) {
        echo "Connection closed\n";
    };

    // Run worker
    Worker::runAll();

13 step: run server.

    php index.php start

14 step: run frontend.

    index.html

Final description:

    In the browser console you will see messages:

        - Frontend got message: This message was sent from Backend(index.php), when server was started.

        - Frontend got message: Hello Martians!

    First message got, when server was started.
    
    Second message got, after 2 seconds of start.