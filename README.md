Here's an updated version of the README.md guide with additional information about the `setDebug()` method:

---

# TelegramPhp Database Backup Library

The `TelegramPhp` class facilitates database backup operations by exporting tables and data into a SQL file and sending it to a Telegram chat. This library is useful for automating database backups and sending them to specified Telegram chats for storage and monitoring.

## Installation

You can install this library via Composer. Run the following command in your terminal:

```bash
composer require expertskb/telegram_php
```

## Usage

### Step 1: Initialize the Backup Class

First, you need to initialize the `Backup` class by providing the necessary parameters:

```php
use Expertskb\TelegramPhp\Backup;

// Initialize the Backup class
$backup = new Backup($host, $username, $database, $password, $bot_token, $chat_id);
```

- `$host`: The hostname of the database server.
- `$username`: The username used to connect to the database.
- `$database`: The name of the database to be backed up.
- `$password`: The password used to connect to the database.
- `$bot_token`: The Telegram bot token used to send the backup file.
- `$chat_id`: The ID of the Telegram chat or an array of chat IDs where you want to send the backup.

### Step 2: Run the Backup Process

To start the backup process, simply call the `run()` method of the `Backup` class:

```php
$backup->run();
```

This will export the database tables and data into a SQL file and send it to the specified Telegram chat(s).

### Optional: Enable Debug Mode

You can enable debug mode to receive detailed logs by calling the `setDebug()` method:

```php
$backup->setDebug(true);
```

### Example

```php
use Expertskb\TelegramPhp\Backup;

// Initialize the Backup class
$backup = new Backup('localhost', 'username', 'my_database', 'password', 'your_bot_token', ['ps_your_id1', 'ps_your_id2']);

// Enable debug mode
$backup->setDebug(true);

// Run the backup process
$backup->run();
```

## Requirements

- PHP 5.6 or higher
- MySQLi extension enabled
- cURL extension enabled

## License

This library is open-source and released under the MIT License. See the [LICENSE](LICENSE) file for details.

---

Feel free to customize this README according to your preferences and add any additional information or usage examples as needed.
