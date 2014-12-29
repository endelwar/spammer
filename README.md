# Spammer

Spammer is a CLI application that sends randomly generated email to a SMTP server.

## Usage

Usage:

```
 spammer [-s|--server[="..."]] [-p|--port[="..."]] [-c|--count[="..."]] [-l|--locale[="..."]]
```

Options:

```
 --server (-s)         SMTP Server ip to send email to (default: "127.0.0.1")
 --port (-p)           SMTP Server port to send email to (default: "25")
 --count (-c)          Number of email to send (default: 10)
 --locale (-l)         Locale to use (default: "en_US")
```

## License

Copyright (c) 2014 Manuel Dalla Lana (endelwar@aregar.it)

This app is licensed under the MIT license. See `LICENSE.md`.
