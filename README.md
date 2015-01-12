# BackBee standard

This is "BackBee CMS Standard Edition" distribution. You can use it as your project skeleton.

## Installation

### Composer installation

Before all, you will need a dns.

In Debian-like environements, you only need to update your ``/etc/hosts`` file:

```bash
127.0.0.1   mydomain.dev
```

Then execute this composer project creation command:

```bash
$~ composer create-project "backbee/backbee-standard" /path/to/your/folder v0.11.1
```

Then lauch the PHP builtin web server inside the ``public`` directory:

```bash
$~ cd /path/to/your/folder/public
$~ php -S mydomain.dev:8000
```

In your web browser, access [http://mydomain.dev:8000/install.php](http://mydomain.dev:8000/install/php) and follow
 the installation instructions.

### Disclaimer

This is a very early version of packaged BackBee CMS and this package should not be used in production.
