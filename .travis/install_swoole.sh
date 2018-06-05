#!/bin/bash
pecl install swoole << EOF
`#enable debug/trace log support? [no] :`
`#enable sockets supports? [no] :`y
`#enable openssl support? [no] :`
`#enable http2 support? [no] :`
`#enable async-redis support? [no] :`
`#enable mysqlnd support? [no] :`
`#enable postgresql coroutine client support? [no] :`
EOF
