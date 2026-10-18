<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Nightly backups: database dump + uploaded files, never the code or .env.
 * (The database part needs mysqldump/pg_dump on the server, so only files are run here.)
 */
class BackupTest extends TestCase
{
    public function test_backup_archives_uploads_without_secrets(): void
    {
        Storage::fake('backups');
        $upload = storage_path('app/public/backup-test/plov.jpg');
        File::ensureDirectoryExists(dirname($upload));
        File::put($upload, 'image');

        try {
            $this->artisan('backup:run --only-files --disable-notifications')->assertSuccessful();

            $archives = Storage::disk('backups')->allFiles();
            $this->assertCount(1, $archives);

            $zip = new ZipArchive;
            $zip->open(Storage::disk('backups')->path($archives[0]));
            $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));
            $zip->close();

            $this->assertTrue($names->contains(fn ($n) => str_ends_with($n, 'backup-test/plov.jpg')));
            $this->assertFalse($names->contains(fn ($n) => str_ends_with($n, '.env') || str_contains($n, 'vendor/')));
        } finally {
            File::deleteDirectory(dirname($upload));
        }
    }

    public function test_app_boots_with_empty_backup_settings_in_env(): void
    {
        // .env.example ships them empty; spatie validates the mail address while
        // booting, so "" instead of a fallback used to take the whole app down
        $result = Process::path(base_path())
            ->env(['BACKUP_NOTIFICATION_EMAIL' => '', 'BACKUP_DISKS' => '', 'BACKUP_ARCHIVE_PASSWORD' => ''])
            ->run([PHP_BINARY, 'artisan', 'config:show', 'backup.notifications.mail.to']);

        $this->assertTrue($result->successful(), $result->errorOutput().$result->output());
        $this->assertStringNotContainsString('not a valid email', $result->output());
    }

    public function test_backups_are_scheduled_nightly(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => $e->command !== null)
            ->mapWithKeys(fn ($e) => [(string) str($e->command)->after('artisan')->replace(["'", '"'], '')->trim() => $e]);

        $this->assertSame('0 2 * * *', $events['backup:run']->expression);
        $this->assertSame('Asia/Ashgabat', $events['backup:run']->timezone);
        $this->assertArrayHasKey('backup:clean', $events);
        $this->assertArrayHasKey('backup:monitor', $events);
    }
}
