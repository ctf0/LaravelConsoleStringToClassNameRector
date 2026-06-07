<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

use function dirname;
use function in_array;

final class ExportArtisanListCommand extends Command
{
    const string PATH = 'rector/artisan-command-map.php';

    private array $excludeCommandsList = [];

    protected $signature = 'artisan:list:generate';

    protected $description = 'generate artisan commands class mapper';

    public function handle(): int
    {
        $absolutePath = storage_path(self::PATH);

        $consoleList = collect(Artisan::all())
            ->mapWithKeys(fn (object $command, string $name): array => [$name => $command::class])
            ->reject(fn ($value, $key): bool => in_array($key, $this->excludeCommandsList, true))
            ->unique()
            ->sortKeys()
            ->all();

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, "<?php\n\nreturn " . var_export($consoleList, true) . ';');

        return self::SUCCESS;
    }
}
