# LaravelConsoleStringToClassNameRector

A Rector rule to auto-convert laravel console command call from a string to a class, the benefit of this is

1. better IDE support as now we can directly jump to the command
2. quickly find out the usage of the command instead of reaching out to the terminal
3. no need to search the codebase for the command to read how its being structured

the rule will convert something like

```php
Schedule::command('backup:run')->daily();
// to
Schedule::command(\Spatie\Backup\Commands\BackupCommand::class)->daily();
```

## Usage

> ExportArtisanListCommand

- will create the artisan commands map and save it to `storage/rector/artisan-command-map.php`
    - you can change the saved file path from the command if you want

- the command need to run at least once & everytime you add new commands.

    ```bash
    php artisan artisan:list:generate 
    ```

<br/>

> ConsoleStringToClassNameRector

- add the rule to your `rector.php` rules list
    - if you changed the path in the command, make sure to also update it here

```php
use App\Rector\Rules\ConsoleStringToClassNameRector;

return RectorConfig::configure()
    ->withPaths([
        // ...
    ])
    ->withConfiguredRule(ConsoleStringToClassNameRector::class, [
        'path' => __DIR__ . '/storage/rector/artisan-command-map.php',
    ]);
```

- next run rector

```bash
./vendor/bin/rector process --dry-run --clear-cache
```

## Bonus

for cases where you have to use the string version, we can use `getName`, ex.

```php
$this->call('backup:run');
// to
$this->call(app(BackupCommand::class)->getName());
```
